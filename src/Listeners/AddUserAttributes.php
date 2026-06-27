<?php

namespace Nodeloc\Telegram\Listeners;

use Flarum\Api\Serializer\UserSerializer;
use Flarum\User\LoginProvider;
use Flarum\User\User;

class AddUserAttributes
{
    public function __invoke(UserSerializer $serializer, User $user): array
    {
        $provider = LoginProvider::query()
            ->where('user_id', $user->id)
            ->where('provider', 'telegram')
            ->first();

        $telegramId = $provider ? $provider->identifier : null;

        return [
            'canReceiveTelegramNotifications' => $telegramId !== null,
            'nodelocTelegramError' => $user->flagrow_telegram_error ?? null,
        ];
    }
}
