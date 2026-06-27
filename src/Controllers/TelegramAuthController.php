<?php

namespace Nodeloc\Telegram\Controllers;

use Exception;
use Flarum\Forum\Auth\Registration;
use Flarum\Forum\Auth\ResponseFactory;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\Translator;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Stream;
use Nodeloc\Telegram\Repository\TelegramUserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class TelegramAuthController implements RequestHandlerInterface
{
    protected ResponseFactory $authResponse;
    protected SettingsRepositoryInterface $settings;
    protected UrlGenerator $url;
    protected Translator $translator;
    protected TelegramUserRepository $telegramUsers;

    public function __construct(
        ResponseFactory $authResponse,
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
        Translator $translator,
        TelegramUserRepository $telegramUsers
    ) {
        $this->authResponse = $authResponse;
        $this->settings = $settings;
        $this->url = $url;
        $this->translator = $translator;
        $this->telegramUsers = $telegramUsers;
    }

    public function handle(Request $request): ResponseInterface
    {
        try {
            $auth = $this->checkTelegramAuthorization($request->getQueryParams());
            $identifier = (string) $auth['id'];
            $user = $request->getAttribute('actor');

            if ($user && $user->id) {
                $existing = $this->telegramUsers->findLoginProvider($identifier);

                if ($existing && (int) $existing->user_id !== (int) $user->id) {
                    return $this->processContinue(false, 'nodeloc-telegram.forum.auth.linked_to_another_user');
                }

                $this->telegramUsers->linkUser($user, $identifier);

                return $this->processContinue(true);
            }

            $suggestions = array_filter([
                'username' => $auth['username'] ?? null,
                'avatar_url' => $auth['photo_url'] ?? null,
            ]);

            $response = $this->authResponse->make(
                TelegramUserRepository::PROVIDER,
                $identifier,
                function (Registration $registration) use ($suggestions): void {
                    foreach ($suggestions as $key => $value) {
                        $registration->provide($key, $value);
                    }

                    $registration->setPayload($suggestions);
                }
            );

            return $this->makeTelegramAuthResponse($response);
        } catch (Exception $e) {
            return $this->processContinue(false, null, $e->getMessage());
        }
    }

    public function processContinue(bool $isSuccess, ?string $messageKey = null, ?string $fallback = null): HtmlResponse
    {
        $redirect = $this->url->to('forum')->base().'/settings';
        $href = htmlentities($redirect, ENT_QUOTES, 'UTF-8');
        $continue = htmlentities((string) $this->translator->trans('nodeloc-telegram.forum.auth.continue'), ENT_QUOTES, 'UTF-8');

        if ($isSuccess) {
            $message = (string) $this->translator->trans('nodeloc-telegram.forum.auth.linked');
        } elseif ($messageKey) {
            $message = (string) $this->translator->trans($messageKey);
        } else {
            $message = $fallback ?: (string) $this->translator->trans('nodeloc-telegram.forum.auth.failed');
        }

        $info = htmlentities($message, ENT_QUOTES, 'UTF-8');

        return new HtmlResponse(
            "<style>body{text-align:center;padding:20px;padding-top:40vh}p{font-family:sans-serif;font-size:2em;color:#aaa}a{color:#333}</style><p>$info</p><p><a href=\"$href\">$continue</a></p>"
        );
    }

    protected function makeTelegramAuthResponse(ResponseInterface $response): ResponseInterface
    {
        $html = (string) $response->getBody();
        $payload = '{}';

        if (preg_match('/authenticationComplete\((.*)\);<\/script>/s', $html, $matches)) {
            $payload = $matches[1];
        }

        $baseUrl = json_encode($this->url->to('forum')->base(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $safeHtml = <<<HTML
<script>
(function () {
    var payload = $payload;

    if (window.opener && window.opener.app && typeof window.opener.app.authenticationComplete === 'function') {
        window.opener.app.authenticationComplete(payload);
        window.close();
        return;
    }

    try {
        window.sessionStorage.setItem('nodelocTelegramAuthPayload', JSON.stringify(payload));
    } catch (e) {}

    window.location.replace($baseUrl);
}());
</script>
HTML;

        $body = new Stream('php://temp', 'wb+');
        $body->write($safeHtml);
        $body->rewind();

        return $response->withBody($body);
    }

    /**
     * @param array<string, mixed> $authData
     * @return array<string, mixed>
     */
    protected function checkTelegramAuthorization(array $authData): array
    {
        $token = (string) $this->settings->get('nodeloc-telegram.botToken');

        if ($token === '') {
            throw new Exception('No bot token configured for Telegram');
        }

        foreach (['id', 'auth_date', 'hash'] as $requiredKey) {
            if (!isset($authData[$requiredKey]) || $authData[$requiredKey] === '') {
                throw new Exception('Missing Telegram authorization data');
            }
        }

        $checkHash = (string) $authData['hash'];
        unset($authData['hash']);

        $dataCheckArr = [];

        foreach ($authData as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $dataCheckArr[] = $key.'='.$value;
        }

        sort($dataCheckArr);
        $dataCheckString = implode("\n", $dataCheckArr);
        $secretKey = hash('sha256', $token, true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($hash, $checkHash)) {
            throw new Exception('Data is not from Telegram');
        }

        if ((time() - (int) $authData['auth_date']) > 86400) {
            throw new Exception('Telegram authorization data is outdated');
        }

        return $authData;
    }
}
