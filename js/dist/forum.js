(() => {
    'use strict';

    const compat = flarum.core.compat || {};
    const app = unwrap(compat.app);
    const extendModule = compat.extend || {};
    const extend = extendModule.extend || extendModule;
    const User = unwrap(compat['models/User']);
    const Model = unwrap(compat.Model);
    const LogInModal = unwrap(compat['components/LogInModal']);
    const NotificationGrid = unwrap(compat['components/NotificationGrid']);
    const SettingsPage = unwrap(compat['components/SettingsPage']);

    if (!app || !extend || !User || !Model || !LogInModal) {
        return;
    }

    app.initializers.add('nodeloc-telegram', () => {
        User.prototype.canReceiveTelegramNotifications = Model.attribute('canReceiveTelegramNotifications');
        User.prototype.nodelocTelegramError = Model.attribute('nodelocTelegramError');

        extend(LogInModal.prototype, 'fields', (items) => {
            if (!app.forum.attribute('nodeloc-telegram.botUsername')) {
                return;
            }

            items.add(
                'nodeloc-telegram',
                m('.Form-group.NodelocTelegramLoginFormGroup', telegramLoginWidget(
                    'nodeloc-telegram.forum.log_in_with_telegram_button'
                )),
                -20
            );
        });

        if (NotificationGrid) {
            extend(NotificationGrid.prototype, 'notificationMethods', (items) => {
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
        }

        if (SettingsPage) {
            extend(SettingsPage.prototype, 'accountItems', (items) => {
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

            extend(SettingsPage.prototype, 'notificationsItems', (items) => {
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
    });

    function telegramLoginWidget(labelKey) {
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

    function unwrap(module) {
        return module && (module.default || module);
    }
})();
