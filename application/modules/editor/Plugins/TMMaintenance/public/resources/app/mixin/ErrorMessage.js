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

        if (error.hasOwnProperty('errorMessage')) {
            errorMessage = error.errorMessage;
        } else {
            errorMessage = error?.response?.responseJson?.errorMessage;
            errorCode = error?.response?.responseJson?.errorCode;
        }

        const l10n = this.getViewModel().data.l10n;
        errorMessage = (errorMessage ? (l10n.error.responseFromServer + errorMessage) : '');

        if (!this.isInfo(errorCode)) {
            errorMessage = l10n.error.couldNotProcessRequest + '<br>' + errorMessage;
        }

        return errorMessage;
    },

    getDialogTitle: function (error) {
        const l10n = this.getViewModel().data.l10n;
        let errorCode = error?.response?.responseJson?.errorCode;

        return this.isInfo(errorCode) ? l10n.error.infoTitle : l10n.error.title;
    },

    isInfo: function (errorCode) {
        return errorCode === 'E1377';
    }
});