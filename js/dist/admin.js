(function () {
    'use strict';

    var core = window.flarum && window.flarum.core ? window.flarum.core : {};
    var app = resolve('admin/app', core.app);

    app = app && (app.default || app);

    if (!app) {
        return;
    }

    app.initializers.add('nodeloc-telegram', function () {
        app.extensionData
            .for('nodeloc-telegram')
            .registerSetting(
                {
                    setting: 'nodeloc-telegram.botUsername',
                    type: 'text',
                    label: app.translator.trans('nodeloc-telegram.admin.settings.field.botUsername'),
                },
                15
            )
            .registerSetting(
                {
                    setting: 'nodeloc-telegram.botToken',
                    type: 'text',
                    label: app.translator.trans('nodeloc-telegram.admin.settings.field.botToken'),
                },
                15
            )
            .registerSetting({
                setting: 'nodeloc-telegram.enableNotifications',
                type: 'boolean',
                label: app.translator.trans('nodeloc-telegram.admin.settings.field.enableNotifications'),
            });
    });

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
