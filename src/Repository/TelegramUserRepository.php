<?php

namespace Nodeloc\Telegram\Repository;

use Flarum\User\LoginProvider;
use Flarum\User\User;

class TelegramUserRepository
{
    public const PROVIDER = 'telegram';
    public const ERROR_PREFERENCE = 'nodelocTelegramError';

    public function findLoginProvider(string $identifier): ?LoginProvider
    {
        return LoginProvider::query()
            ->where('provider', self::PROVIDER)
            ->where('identifier', $identifier)
            ->first();
    }

    public function findUserTelegramId(User $user): ?string
    {
        if (!$user->exists || !$user->id) {
            return null;
        }

        $provider = $user->loginProviders()
            ->where('provider', self::PROVIDER)
            ->first();

        return $provider ? (string) $provider->identifier : null;
    }

    public function hasTelegramLogin(User $user): bool
    {
        return $this->findUserTelegramId($user) !== null;
    }

    public function getTelegramError(User $user): ?string
    {
        $error = $user->getPreference(self::ERROR_PREFERENCE);

        if ($error) {
            return (string) $error;
        }

        $legacyError = $user->getAttribute('flagrow_telegram_error');

        return $legacyError ? (string) $legacyError : null;
    }

    public function setTelegramError(User $user, ?string $error): void
    {
        $user->setPreference(self::ERROR_PREFERENCE, $error);
        $user->save();
    }

    public function linkUser(User $user, string $identifier): LoginProvider
    {
        return $user->loginProviders()->firstOrCreate([
            'provider' => self::PROVIDER,
            'identifier' => $identifier,
        ]);
    }
}
