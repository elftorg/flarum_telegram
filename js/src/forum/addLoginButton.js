import { extend } from 'flarum/common/extend';
import LogInButtons from 'flarum/forum/components/LogInButtons';
import app from 'flarum/forum/app';
import m from 'mithril';

export default function () {
    extend(LogInButtons.prototype, 'items', function (items) {
        const botUsername = app.forum.attribute('nodeloc-telegram.botUsername');

        if (!botUsername) {
            return;
        }

        items.add('nodeloc-telegram', telegramLoginWidget('/'));
    });
}

export function telegramLoginWidget(returnTo) {
    return m('script', {
        async: true,
        src: 'https://telegram.org/js/telegram-widget.js?22',
        'data-telegram-login': app.forum.attribute('nodeloc-telegram.botUsername'),
        'data-size': 'large',
        'data-radius': '10',
        'data-auth-url': `${app.forum.attribute('baseUrl')}/auth/telegram?returnTo=${encodeURIComponent(returnTo)}`,
        'data-request-access': 'write',
    });
}
