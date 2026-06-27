import app from 'flarum/admin/app';

app.initializers.add('nodeloc-telegram', () => {
    app.extensionData
        .for('nodeloc-telegram')
        .registerSetting(
            {
                setting: 'nodeloc-telegram.botUsername',
                type: 'text',
                label: app.translator.trans('nodeloc-telegram.admin.settings.field.botUsername'),
                help: app.translator.trans('nodeloc-telegram.admin.settings.field.botUsername_help'),
            },
            15
        )
        .registerSetting(
            {
                setting: 'nodeloc-telegram.botToken',
                type: 'text',
                label: app.translator.trans('nodeloc-telegram.admin.settings.field.botToken'),
                help: app.translator.trans('nodeloc-telegram.admin.settings.field.botToken_help'),
            },
            15
        )
        .registerSetting({
            setting: 'nodeloc-telegram.enableNotifications',
            type: 'boolean',
            label: app.translator.trans('nodeloc-telegram.admin.settings.field.enableNotifications'),
            help: app.translator.trans('nodeloc-telegram.admin.settings.field.enableNotifications_help'),
        });

});
