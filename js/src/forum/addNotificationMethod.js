import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import NotificationGrid from 'flarum/forum/components/NotificationGrid';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import m from 'mithril';

import { telegramLoginWidget } from './addLoginButton';

export default function () {
    extend(NotificationGrid.prototype, 'notificationMethods', function (items) {
        if (!app.forum.attribute('nodeloc-telegram.enableNotifications')) {
            return;
        }
        if (!app.session || !app.session.user) {
            return;
        }

        const user = app.session.user;
        if (!user || !user.canReceiveTelegramNotifications()) {
            return;
        }

        items.add('telegram', {
            name: 'telegram',
            icon: 'fab fa-telegram-plane',
            label: app.translator.trans('nodeloc-telegram.forum.settings.notify_by_telegram_heading'),
        });
    });

    extend(SettingsPage.prototype, 'accountItems', function (items) {
        if (!app.forum.attribute('nodeloc-telegram.enableNotifications')) {
            return;
        }
        if (!app.session || !app.session.user) {
            return;
        }

        const user = app.session.user;
        if (user && !user.canReceiveTelegramNotifications()) {
            items.add('nodeloc-telegram', telegramLoginWidget('nodeloc-telegram.forum.link_telegram_button'));
        }
    });

    extend(SettingsPage.prototype, 'notificationsItems', function (items) {
        if (!app.forum.attribute('nodeloc-telegram.enableNotifications')) {
            return;
        }
        if (!app.session || !app.session.user) {
            return;
        }

        const user = app.session.user;
        if (!user || !user.nodelocTelegramError()) {
            return;
        }

        const botUsername = app.forum.attribute('nodeloc-telegram.botUsername');

        items.add('nodelocTelegramError', {
            view() {
                return m('.Alert', m('p', app.translator.trans('nodeloc-telegram.forum.settings.unblock_telegram_bot', {
                    a: m('a', { href: 'https://t.me/' + botUsername }),
                    username: '@' + botUsername,
                })));
            },
        });
    });
}
