(function () {
    'use strict';

    var core = window.flarum && window.flarum.core ? window.flarum.core : {};
    var app = resolve('forum/app', core.app);
    var extendModule = resolve('common/extend', core.extend || {});
    var extend = extendModule.extend || (extendModule.default && extendModule.default.extend) || extendModule;
    var User = resolve('common/models/User', resolve('models/User'));
    var Model = resolve('common/Model', resolve('Model'));
    var LogInModal = resolve('forum/components/LogInModal', resolve('components/LogInModal'));
    var NotificationGrid = resolve('forum/components/NotificationGrid', resolve('components/NotificationGrid'));
    var SettingsPage = resolve('forum/components/SettingsPage', resolve('components/SettingsPage'));
    var m = window.m || core.m;

    app = app && (app.default || app);
    User = User && (User.default || User);
    Model = Model && (Model.default || Model);
    LogInModal = LogInModal && (LogInModal.default || LogInModal);
    NotificationGrid = NotificationGrid && (NotificationGrid.default || NotificationGrid);
    SettingsPage = SettingsPage && (SettingsPage.default || SettingsPage);

    if (!app || !extend || !User || !Model || !LogInModal || !m) {
        return;
    }

    app.initializers.add('nodeloc-telegram', function () {
        User.prototype.canReceiveTelegramNotifications = Model.attribute('canReceiveTelegramNotifications');
        User.prototype.nodelocTelegramError = Model.attribute('nodelocTelegramError');

        showTelegramMessage();

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

        if (NotificationGrid) {
            extend(NotificationGrid.prototype, 'notificationMethods', function (items) {
                if (!telegramNotificationsEnabled()) {
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
            extend(SettingsPage.prototype, 'accountItems', function (items) {
                if (!telegramNotificationsEnabled()) {
                    return;
                }

                var user = app.session.user;

                if (!user || user.canReceiveTelegramNotifications()) {
                    return;
                }

                items.add('nodeloc-telegram', telegramLoginWidget('/settings', 'nodeloc-telegram.forum.link_telegram_button'));
            });

            extend(SettingsPage.prototype, 'notificationsItems', function (items) {
                if (!telegramNotificationsEnabled()) {
                    return;
                }

                var user = app.session.user;
                var botUsername = app.forum.attribute('nodeloc-telegram.botUsername');

                if (!user || !user.nodelocTelegramError() || !botUsername) {
                    return;
                }

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

    function telegramLoginWidget(returnTo, labelKey) {
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
                    'data-auth-url': app.forum.attribute('baseUrl') + '/auth/telegram?returnTo=' + encodeURIComponent(returnTo),
                    'data-request-access': 'write',
                }),
            ]
        );
    }

    function showTelegramMessage() {
        var message = new URLSearchParams(window.location.search).get('telegramMessage');

        if (!message || !app.alerts || typeof app.alerts.show !== 'function') {
            return;
        }

        app.alerts.show({ type: 'success' }, message);

        var url = new URL(window.location.href);
        url.searchParams.delete('telegramMessage');
        window.history.replaceState({}, document.title, url.toString());
    }

    function telegramNotificationsEnabled() {
        return !!app.forum.attribute('nodeloc-telegram.enableNotifications')
            && !!app.session
            && !!app.session.user
            && !!app.forum.attribute('nodeloc-telegram.botUsername');
    }

    function resolve(name, fallback) {
        if (core[name]) {
            return core[name];
        }

        var current = core;
        var parts = name.split('/');

        for (var i = 0; i < parts.length; i++) {
            current = current && current[parts[i]];
        }

        return current || fallback;
    }
}());
