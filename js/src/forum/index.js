import app from 'flarum/forum/app';
import User from 'flarum/common/models/User';
import Model from 'flarum/common/Model';

import addLoginButton from './addLoginButton';
import addNotificationMethod from './addNotificationMethod';

app.initializers.add('nodeloc-telegram', () => {
    User.prototype.canReceiveTelegramNotifications = Model.attribute('canReceiveTelegramNotifications');
    User.prototype.nodelocTelegramError = Model.attribute('nodelocTelegramError');

    showTelegramMessage();
    addLoginButton();
    addNotificationMethod();
});

function showTelegramMessage() {
    const message = new URLSearchParams(window.location.search).get('telegramMessage');

    if (!message || !app.alerts || typeof app.alerts.show !== 'function') {
        return;
    }

    app.alerts.show({ type: 'success' }, message);

    const url = new URL(window.location.href);
    url.searchParams.delete('telegramMessage');
    window.history.replaceState({}, document.title, url.toString());
}
