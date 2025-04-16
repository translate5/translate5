Ext.define('TMMaintenance.mixin.ErrorMessage', {
    showServerError: function (error) {
        this.showGeneralError(this.getErrorMessage(error), this.getDialogTitle(error));
    },

    showGeneralError: function (error, title = null) {
        const dialog = this.getErrorDialog();

        if (title) {
            dialog.setTitle(title);
        }

        dialog.setHtml(error);
        dialog.show();
    },

    getErrorDialog: function () {
        return Ext.ComponentQuery.query('#errorDialog')[0];
    },

    getErrorMessage: function (error) {
        let errorMessage;
        let errorCode = null;

        if (
            error?.response?.responseJson?.errorsTranslated &&
            error?.response?.responseJson?.errorsTranslated.length > 0
        ) {
            errorMessage = error?.response?.responseJson?.errorsTranslated.join('<br>');
        } else if (error.hasOwnProperty('errorMessage')) {
            errorMessage = error.errorMessage;
        } else {
            errorMessage = error?.response?.responseJson?.errorMessage;
        }

        const l10n = this.getViewModel().data.l10n;

        if (!this.isInfo(error)) {
            errorMessage = (errorMessage ? (l10n.error.responseFromServer + ': ' + errorMessage) : '');
            errorMessage = l10n.error.couldNotProcessRequest + '<br>' + errorMessage;
        }

        const info = Editor.userLogin;

        errorMessage += '<br>' +
            '<p style="font-size: 10px;color: #808080;font-style: italic;user-select: text;">'
            + info + ' ' + Ext.Date.format(new Date(), 'Y-m-d H:i:sO') + '</p>'

        return errorMessage;
    },

    getDialogTitle: function (error) {
        const l10n = this.getViewModel().data.l10n;

        return this.isInfo(error) ? l10n.error.messageFromServer : l10n.error.title;
    },

    isInfo: function (error) {
        return error.hasOwnProperty('status') && error.status === 422;
    }
});