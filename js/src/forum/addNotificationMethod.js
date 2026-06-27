import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import NotificationGrid from 'flarum/forum/components/NotificationGrid';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import m from 'mithril';

import { telegramLoginWidget } from './addLoginButton';

export default function () {
    extend(NotificationGrid.prototype, 'notificationMethods', function (items) {
        if (!telegramNotificationsEnabled()) {
            return;
        }

        const user = app.session.user;

        if (!user?.canReceiveTelegramNotifications()) {
            return;
        }

        items.add('telegram', {
            name: 'telegram',
            icon: 'fab fa-telegram-plane',
            label: app.translator.trans('nodeloc-telegram.forum.settings.notify_by_telegram_heading'),
        });
    });

    extend(SettingsPage.prototype, 'accountItems', function (items) {
        if (!telegramNotificationsEnabled()) {
            return;
        }

        const user = app.session.user;

        if (!user || user.canReceiveTelegramNotifications()) {
            return;
        }

        items.add('nodeloc-telegram', telegramLoginWidget('/settings'));
    });

    extend(SettingsPage.prototype, 'notificationsItems', function (items) {
        if (!telegramNotificationsEnabled()) {
            return;
        }

        const user = app.session.user;
        const botUsername = app.forum.attribute('nodeloc-telegram.botUsername');

        if (!user?.nodelocTelegramError() || !botUsername) {
            return;
        }

        items.add('nodelocTelegramError', {
            view() {
                return m('.Alert', m('p', app.translator.trans('nodeloc-telegram.forum.settings.unblock_telegram_bot', {
                    a: m('a', { href: `https://t.me/${botUsername}` }),
                    username: `@${botUsername}`,
                })));
            },
        });
    });
}

function telegramNotificationsEnabled() {
    return !!app.forum.attribute('nodeloc-telegram.enableNotifications')
        && !!app.session
        && !!app.session.user
        && !!app.forum.attribute('nodeloc-telegram.botUsername');
}
