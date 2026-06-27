<?php

namespace Nodeloc\Telegram\Notifications;

use Exception;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Symfony\Contracts\Translation\TranslatorInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramMailer
{
    protected Mailer $mailer;

    /**
     * @var TranslatorInterface&Translator
     */
    protected $translator;

    protected SettingsRepositoryInterface $settings;
    protected Factory $view;
    protected Api $telegramclient;

    /**
     * @param TranslatorInterface&Translator $translator
     * @throws TelegramSDKException
     */
    public function __construct(
        Mailer $mailer,
        TranslatorInterface $translator,
        SettingsRepositoryInterface $settings,
        Factory $view
    ) {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->settings = $settings;
        $this->view = $view;

        $token = (string) $settings->get('nodeloc-telegram.botToken');

        if ($token === '') {
            throw new Exception('No bot token configured for Telegram');
        }

        $this->telegramclient = new Api($token);
    }

    protected function getTelegramId(User $user): ?string
    {
        $provider = LoginProvider::query()
            ->where('user_id', $user->id)
            ->where('provider', 'telegram')
            ->first();

        return $provider ? (string) $provider->identifier : null;
    }

    public function send(BlueprintInterface $blueprint, array $users): void
    {
        if (!$blueprint instanceof MailableInterface) {
            return;
        }

        $view = $this->pickBestView($blueprint->getEmailView());

        foreach ($users as $user) {
            $text = $this->view->make($view, compact('blueprint', 'user'))->render();
            $telegramId = $user->getAttribute('flagrow_telegram_id') ?: $this->getTelegramId($user);

            if (!$telegramId) {
                $user->flagrow_telegram_error = 'missing';
                $user->save();
                continue;
            }

            if (!$user->getAttribute('flagrow_telegram_id')) {
                $user->flagrow_telegram_id = $telegramId;
                $user->save();
            }

            try {
                $this->telegramclient->sendMessage([
                    'chat_id' => $telegramId,
                    'text' => $text,
                ]);

                if ($user->flagrow_telegram_error) {
                    $user->flagrow_telegram_error = null;
                    $user->save();
                }
            } catch (ClientException $exception) {
                $this->handleFailedSend($user, $exception);
            } catch (TelegramSDKException $exception) {
                $user->flagrow_telegram_error = 'unauthorized';
                $user->save();
            }
        }
    }

    protected function handleFailedSend(User $user, ClientException $exception): void
    {
        $response = $exception->getResponse();

        if ($response && $response->getStatusCode() !== 403) {
            throw $exception;
        }

        $user->flagrow_telegram_error = 'unauthorized';

        if ($response) {
            $json = json_decode($response->getBody()->getContents(), true);

            if ($json && str_contains(Arr::get($json, 'description', ''), 'blocked by the user')) {
                $user->flagrow_telegram_error = 'blocked';
            }
        }

        $user->save();
    }

    /**
     * Read the same way as Illuminate\Mail\Mailer::parseView().
     *
     * @param mixed $view
     * @throws Exception
     */
    protected function pickBestView($view): string
    {
        if (is_string($view)) {
            return $view;
        }

        if (is_array($view)) {
            if (isset($view[0])) {
                return $view[0];
            }

            foreach (['html', 'text', 'raw'] as $key) {
                $candidate = Arr::get($view, $key);

                if ($candidate) {
                    return $candidate;
                }
            }
        }

        throw new Exception('No view found for that mailable');
    }
}
