import { extend } from 'flarum/common/extend';
import LogInModal from 'flarum/forum/components/LogInModal';
import app from 'flarum/forum/app';
import m from 'mithril';

export default function () {
    extend(LogInModal.prototype, 'fields', function (items) {
        if (!app.forum.attribute('nodeloc-telegram.botUsername')) {
            return;
        }

        items.add(
            'nodeloc-telegram',
            m('.Form-group.NodelocTelegramLoginFormGroup', telegramLoginWidget(
                '/',
                'nodeloc-telegram.forum.log_in_with_telegram_button'
            )),
            -20
        );
    });
}

export function telegramLoginWidget(returnTo, labelKey) {
    return m(
        '.NodelocTelegramLoginButton.Button.Button--primary.Button--block',
        {
            role: 'button',
            'aria-label': app.translator.trans(labelKey),
        },
        [
            m('span', app.translator.trans(labelKey)),
            m('script', {
                async: true,
                src: 'https://telegram.org/js/telegram-widget.js?22',
                'data-telegram-login': app.forum.attribute('nodeloc-telegram.botUsername'),
                'data-size': 'large',
                'data-radius': '10',
                'data-auth-url': `${app.forum.attribute('baseUrl')}/auth/telegram?returnTo=${encodeURIComponent(returnTo)}`,
                'data-request-access': 'write',
            }),
        ]
    );
}
