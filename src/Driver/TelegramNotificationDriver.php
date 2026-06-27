<?php

namespace Nodeloc\Telegram\Driver;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\NotificationDriverInterface;
use Flarum\Notification\MailableInterface;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Nodeloc\Telegram\Job\SendTelegramNotificationJob;
use Nodeloc\Telegram\Repository\TelegramUserRepository;
use ReflectionClass;

class TelegramNotificationDriver implements NotificationDriverInterface
{
    protected Queue $queue;
    protected TelegramUserRepository $telegramUsers;

    public function __construct(Queue $queue, TelegramUserRepository $telegramUsers)
    {
        $this->queue = $queue;
        $this->telegramUsers = $telegramUsers;
    }

    public function send(BlueprintInterface $blueprint, array $users): void
    {
        if (!$blueprint instanceof MailableInterface) {
            return;
        }

        $recipients = array_values(array_filter($users, function (User $user) use ($blueprint): bool {
            return $this->shouldSendTelegramToUser($blueprint, $user);
        }));

        if ($recipients !== []) {
            $this->queue->push(new SendTelegramNotificationJob($blueprint, $recipients));
        }
    }

    protected function shouldSendTelegramToUser(BlueprintInterface $blueprint, User $user): bool
    {
        if (!$user->getPreference(User::getNotificationPreferenceKey($blueprint::getType(), 'telegram'))) {
            return false;
        }

        return $this->telegramUsers->hasTelegramLogin($user);
    }

    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void
    {
        if ((new ReflectionClass($blueprintClass))->implementsInterface(MailableInterface::class)) {
            User::registerPreference(
                User::getNotificationPreferenceKey($blueprintClass::getType(), 'telegram'),
                'boolval',
                in_array('telegram', $driversEnabledByDefault, true)
            );
        }
    }
}
