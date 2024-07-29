Ext.define('TMMaintenance.view.main.ToolbarController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.toolbar',

    /**
     * @param {ComboBox} comboBox
     * @param {String} newLocale
     */
    onLocaleChange: function (comboBox, newLocale) {
        Ext.ComponentQuery.query('app-main')[0].getController().loadData(newLocale);
    },

    onLogoutButtonClick: function () {
        location = '/login/logout';
    },
})
