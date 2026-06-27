<?php

namespace Nodeloc\Telegram;

use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\User\User;
use Nodeloc\Telegram\Controllers\TelegramAuthController;
use Nodeloc\Telegram\Driver\TelegramNotificationDriver;
use Nodeloc\Telegram\Repository\TelegramUserRepository;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),
    new Extend\Locales(__DIR__.'/resources/locale'),
    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canReceiveTelegramNotifications')
                ->get(fn (User $user) => resolve(TelegramUserRepository::class)->hasTelegramLogin($user)),
            Schema\Str::make('nodelocTelegramError')
                ->nullable()
                ->get(fn (User $user) => $user->getAttribute('flagrow_telegram_error')),
        ]),
    (new Extend\Notification())
        ->driver('telegram', TelegramNotificationDriver::class),
    (new Extend\Settings())
        ->serializeToForum('nodeloc-telegram.botUsername', 'nodeloc-telegram.botUsername')
        ->serializeToForum('nodeloc-telegram.enableNotifications', 'nodeloc-telegram.enableNotifications', 'boolval'),
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),
    (new Extend\Routes('forum'))
        ->get('/auth/telegram', 'nodeloc.telegram.auth', TelegramAuthController::class),
];
