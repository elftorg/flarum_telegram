<?php

namespace Nodeloc\Telegram\Repository;

use Flarum\User\LoginProvider;
use Flarum\User\User;

class TelegramUserRepository
{
    public const PROVIDER = 'telegram';

    public function findLoginProvider(string $identifier): ?LoginProvider
    {
        return LoginProvider::query()
            ->where('provider', self::PROVIDER)
            ->where('identifier', $identifier)
            ->first();
    }

    public function findUserTelegramId(User $user): ?string
    {
        $provider = $user->loginProviders()
            ->where('provider', self::PROVIDER)
            ->first();

        return $provider ? (string) $provider->identifier : null;
    }

    public function hasTelegramLogin(User $user): bool
    {
        return $this->findUserTelegramId($user) !== null;
    }

    public function linkUser(User $user, string $identifier): LoginProvider
    {
        return $user->loginProviders()->firstOrCreate([
            'provider' => self::PROVIDER,
            'identifier' => $identifier,
        ]);
    }
}
