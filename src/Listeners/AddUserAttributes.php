<?php

namespace Nodeloc\Telegram\Listeners;

use Flarum\Api\Serializer\UserSerializer;
use Flarum\User\User;
use Nodeloc\Telegram\Repository\TelegramUserRepository;

class AddUserAttributes
{
    protected TelegramUserRepository $telegramUsers;

    public function __construct(TelegramUserRepository $telegramUsers)
    {
        $this->telegramUsers = $telegramUsers;
    }

    public function __invoke(UserSerializer $serializer, User $user): array
    {
        return [
            'canReceiveTelegramNotifications' => $this->telegramUsers->hasTelegramLogin($user),
            'nodelocTelegramError' => $this->telegramUsers->getTelegramError($user),
        ];
    }
}
