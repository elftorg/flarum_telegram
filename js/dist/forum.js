(function () {
    'use strict';

    var extensionExports = {};
    var core = window.flarum && window.flarum.core ? window.flarum.core : {};
    var compat = core.compat || {};
    var app = unwrap(resolve('forum/app', resolve('app')));
    var User = unwrap(resolve('common/models/User', resolve('models/User')));
    var Model = unwrap(resolve('common/Model', resolve('Model')));
    var LogInModal = unwrap(resolve('forum/components/LogInModal', resolve('components/LogInModal')));
    var NotificationGrid = unwrap(resolve('forum/components/NotificationGrid', resolve('components/NotificationGrid')));
    var SettingsPage = unwrap(resolve('forum/components/SettingsPage', resolve('components/SettingsPage')));
    var m = window.m || unwrap(resolve('mithril'));

    exportExtension(extensionExports);

    if (!app || !User || !Model || !LogInModal || !m) {
        return;
    }

    app.initializers.add('nodeloc-telegram', function () {
        User.prototype.canReceiveTelegramNotifications = Model.attribute('canReceiveTelegramNotifications');
        User.prototype.nodelocTelegramError = Model.attribute('nodelocTelegramError');

        consumeTelegramAuthPayload();

        extendMethod(LogInModal.prototype, 'fields', function (items) {
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
            extendMethod(NotificationGrid.prototype, 'notificationMethods', function (items) {
                if (!app.forum.attribute('nodeloc-telegram.enableNotifications')) {
                    return;
                }
                if (!app.session || !app.session.user) {
                    return;
                }

                var user = app.session.user;
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
            extendMethod(SettingsPage.prototype, 'accountItems', function (items) {
                if (!app.forum.attribute('nodeloc-telegram.enableNotifications')) {
                    return;
                }
                if (!app.session || !app.session.user) {
                    return;
                }

                var user = app.session.user;
                if (user && !user.canReceiveTelegramNotifications()) {
                    items.add('nodeloc-telegram', telegramLoginWidget('nodeloc-telegram.forum.link_telegram_button'));
                }
            });

            extendMethod(SettingsPage.prototype, 'notificationsItems', function (items) {
                if (!app.forum.attribute('nodeloc-telegram.enableNotifications')) {
                    return;
                }
                if (!app.session || !app.session.user) {
                    return;
                }

                var user = app.session.user;
                if (!user || !user.nodelocTelegramError()) {
                    return;
                }

                var botUsername = app.forum.attribute('nodeloc-telegram.botUsername');

                items.add('nodelocTelegramError', {
                    view: function () {
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

    function consumeTelegramAuthPayload() {
        if (!window.sessionStorage || typeof app.authenticationComplete !== 'function') {
            return;
        }

        var payload = window.sessionStorage.getItem('nodelocTelegramAuthPayload');

        if (!payload) {
            return;
        }

        window.sessionStorage.removeItem('nodelocTelegramAuthPayload');
        app.authenticationComplete(JSON.parse(payload));
    }

    function resolve(name, fallback) {
        if (compat[name]) {
            return compat[name];
        }

        if (core[name]) {
            return core[name];
        }

        return fallback;
    }

    function unwrap(module) {
        return module && (module.default || module);
    }

    function extendMethod(object, method, callback) {
        var original = object[method];

        object[method] = function () {
            var args = Array.prototype.slice.call(arguments);
            var value = original ? original.apply(this, args) : undefined;

            callback.apply(this, [value].concat(args));

            return value;
        };

        if (original) {
            Object.assign(object[method], original);
        }
    }

    function exportExtension(exports) {
        if (typeof module !== 'undefined') {
            module.exports = exports;
        }

        if (window.flarum && window.flarum.extensions) {
            window.flarum.extensions['nodeloc-telegram'] = exports;
        }
    }
}());
