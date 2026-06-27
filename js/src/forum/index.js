import app from 'flarum/forum/app';
import User from 'flarum/common/models/User';
import Model from 'flarum/common/Model';

import addLoginButton from './addLoginButton';
import addNotificationMethod from './addNotificationMethod';

app.initializers.add('nodeloc-telegram', () => {
    User.prototype.canReceiveTelegramNotifications = Model.attribute('canReceiveTelegramNotifications');
    User.prototype.nodelocTelegramError = Model.attribute('nodelocTelegramError');

    consumeTelegramAuthPayload();
    addLoginButton();
    addNotificationMethod();
});

function consumeTelegramAuthPayload() {
    if (!window.sessionStorage || typeof app.authenticationComplete !== 'function') {
        return;
    }

    const payload = window.sessionStorage.getItem('nodelocTelegramAuthPayload');

    if (!payload) {
        return;
    }

    window.sessionStorage.removeItem('nodelocTelegramAuthPayload');
    app.authenticationComplete(JSON.parse(payload));
}
