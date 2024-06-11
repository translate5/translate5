Ext.define('TMMaintenance.mixin.ErrorMessage', {
    showServerError: function (error) {
        let serverError;

        if (error.hasOwnProperty('errorMessage')) {
            serverError = error.errorMessage;
        } else {
            serverError = error?.response?.responseJson?.errorMessage;
        }

        const l10n = this.getViewModel().data.l10n;

        const dialog = Ext.ComponentQuery.query('#errorDialog')[0];
        dialog.setHtml(
            l10n.error.couldNotProcessRequest +
            (serverError ? ('<br>' + l10n.error.responseFromServer + serverError) : '')
        );
        dialog.show();
    }
});