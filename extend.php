<?php

namespace Nodeloc\Telegram;

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Nodeloc\Telegram\Controllers\TelegramAuthController;
use Nodeloc\Telegram\Driver\TelegramNotificationDriver;
use Nodeloc\Telegram\Listeners\AddUserAttributes;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),
    new Extend\Locales(__DIR__.'/resources/locale'),
    (new Extend\ApiSerializer(UserSerializer::class))
        ->attributes(AddUserAttributes::class),
    (new Extend\Notification())
        ->driver('telegram', TelegramNotificationDriver::class),
    (new Extend\Settings())
        ->serializeToForum('nodeloc-telegram.botUsername', 'nodeloc-telegram.botUsername')
        ->serializeToForum('nodeloc-telegram.enableNotifications', 'nodeloc-telegram.enableNotifications', 'boolval'),
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),
    (new Extend\Routes('forum'))
        ->get('/auth/telegram', 'auth.telegram', TelegramAuthController::class),
];
