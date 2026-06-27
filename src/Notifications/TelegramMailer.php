<?php

namespace Nodeloc\Telegram\Notifications;

use Exception;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Nodeloc\Telegram\Repository\TelegramUserRepository;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramMailer
{
    protected Factory $view;
    protected Api $telegramclient;
    protected TelegramUserRepository $telegramUsers;

    /**
     * @throws TelegramSDKException
     */
    public function __construct(
        SettingsRepositoryInterface $settings,
        Factory $view,
        TelegramUserRepository $telegramUsers
    ) {
        $this->view = $view;
        $this->telegramUsers = $telegramUsers;

        $token = (string) $settings->get('nodeloc-telegram.botToken');

        if ($token === '') {
            throw new Exception('No bot token configured for Telegram');
        }

        $this->telegramclient = new Api($token);
    }

    public function send(BlueprintInterface $blueprint, array $users): void
    {
        if (!$blueprint instanceof MailableInterface) {
            return;
        }

        $view = $this->pickBestView($blueprint->getEmailView());

        foreach ($users as $user) {
            $text = $this->view->make($view, compact('blueprint', 'user'))->render();
            $telegramId = $this->telegramUsers->findUserTelegramId($user);

            if (!$telegramId) {
                $this->markError($user, 'missing');
                continue;
            }

            try {
                $this->telegramclient->sendMessage([
                    'chat_id' => $telegramId,
                    'text' => $text,
                ]);

                if ($this->telegramUsers->getTelegramError($user)) {
                    $this->telegramUsers->setTelegramError($user, null);
                }
            } catch (ClientException $exception) {
                $this->handleFailedSend($user, $exception);
            } catch (TelegramSDKException $exception) {
                $this->markError($user, 'unauthorized');
            }
        }
    }

    protected function handleFailedSend(User $user, ClientException $exception): void
    {
        $response = $exception->getResponse();

        if ($response && $response->getStatusCode() !== 403) {
            throw $exception;
        }

        $error = 'unauthorized';

        if ($response) {
            $json = json_decode($response->getBody()->getContents(), true);

            if ($json && str_contains(Arr::get($json, 'description', ''), 'blocked by the user')) {
                $error = 'blocked';
            }
        }

        $this->markError($user, $error);
    }

    protected function markError(User $user, string $error): void
    {
        $this->telegramUsers->setTelegramError($user, $error);
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
