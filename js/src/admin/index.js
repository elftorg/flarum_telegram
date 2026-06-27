import app from 'flarum/admin/app';

app.initializers.add('nodeloc-telegram', () => {
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
