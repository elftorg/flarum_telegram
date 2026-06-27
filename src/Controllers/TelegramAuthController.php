<?php

namespace Nodeloc\Telegram\Controllers;

use Exception;
use Flarum\Forum\Auth\Registration;
use Flarum\Forum\Auth\ResponseFactory;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\Translator;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Nodeloc\Telegram\Repository\TelegramUserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class TelegramAuthController implements RequestHandlerInterface
{
    public function __construct(
        protected ResponseFactory $authResponse,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
        protected Translator $translator,
        protected TelegramUserRepository $telegramUsers
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $returnTo = $this->returnTo($request);

        try {
            $auth = $this->checkTelegramAuthorization($request->getQueryParams());
            $identifier = (string) $auth['id'];
            $actor = $request->getAttribute('actor');

            if ($actor && $actor->id) {
                $existing = $this->telegramUsers->findLoginProvider($identifier);

                if ($existing && (int) $existing->user_id !== (int) $actor->id) {
                    return $this->redirectWithFlash('linked_to_another_user', '/settings');
                }

                $this->telegramUsers->linkUser($actor, $identifier);

                return $this->redirectWithFlash('linked', '/settings');
            }

            $suggestions = array_filter([
                'username' => $auth['username'] ?? null,
                'avatar_url' => $auth['photo_url'] ?? null,
            ]);

            return $this->authResponse->make(
                TelegramUserRepository::PROVIDER,
                $identifier,
                function (Registration $registration) use ($suggestions): void {
                    foreach ($suggestions as $key => $value) {
                        $registration->provide($key, $value);
                    }

                    $registration->setPayload($suggestions);
                },
                $returnTo
            );
        } catch (Exception $e) {
            return $this->redirectWithFlash('failed', '/');
        }
    }

    protected function returnTo(Request $request): string
    {
        $returnTo = $request->getQueryParams()['returnTo'] ?? '/';

        if (!is_string($returnTo) || !str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//')) {
            return '/';
        }

        return $returnTo;
    }

    protected function redirectWithFlash(string $key, string $path): RedirectResponse
    {
        $message = $this->translator->trans('nodeloc-telegram.forum.auth.'.$key);
        $separator = str_contains($path, '?') ? '&' : '?';

        return new RedirectResponse(
            $this->url->to('forum')->base().$path.$separator.'telegramMessage='.rawurlencode((string) $message)
        );
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
        unset($authData['hash'], $authData['returnTo']);

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
