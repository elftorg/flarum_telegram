import LogInModal from 'flarum/forum/components/LogInModal';
import SignUpModal from 'flarum/forum/components/SignUpModal';
import app from 'flarum/forum/app';
import m from 'mithril';

import extendMethod from './extendMethod';

export default function () {
    addTelegramAuthButton(LogInModal, 'nodeloc-telegram.forum.log_in_with_telegram_button');
    addTelegramAuthButton(SignUpModal, 'nodeloc-telegram.forum.sign_up_with_telegram_button', true);
}

function addTelegramAuthButton(ModalClass, labelKey, skipWhenToken = false) {
    extendMethod(ModalClass.prototype, 'fields', function (items) {
        if (!app.forum.attribute('nodeloc-telegram.botUsername')) {
            return;
        }

        if (skipWhenToken && this.attrs && this.attrs.token) {
            return;
        }

        items.add(
            'nodeloc-telegram',
            m('.Form-group.NodelocTelegramLoginFormGroup', telegramLoginWidget(labelKey)),
            -20
        );
    });
}

export function telegramLoginWidget(labelKey) {
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
                'data-auth-url': app.forum.attribute('baseUrl') + '/auth/telegram',
                'data-request-access': 'write',
            }),
        ]
    );
}
