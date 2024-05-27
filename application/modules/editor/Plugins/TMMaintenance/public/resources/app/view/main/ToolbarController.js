Ext.define('TMMaintenance.view.main.ToolbarController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.toolbar',

    listen: {
        global: {
            onApplicationLoad: 'onLoad'
        }
    },

    onLoad: function () {
        this.loadData();
    },

    /**
     * @param {ComboBox} comboBox
     * @param {String} newLocale
     */
    onLocaleChange: function (comboBox, newLocale) {
        console.log(newLocale);

        this.loadData(newLocale);
    },

    onLogoutButtonClick: function () {
        location = '/login/logout';
    },

    // TODO add chosen locale
    loadData: function (newLocale = null) {
        let me = this;
        // Setup default ajax headers
        Ext.Ajax.setDefaultHeaders({
            'Accept': 'application/json',
            'csrfToken': window.csrfToken,
        });

        let url = '/editor/plugins_tmmaintenance_api/locale/list';
        if (newLocale) {
            url += '?locale=' + newLocale
        }

        Ext.Ajax.request({
            url: url,
            async: false,
            method: 'GET',
            success: function (xhr) {
                let data = Ext.JSON.decode(xhr.responseText, true);

                if (!(data)) {
                    // TODO show an error
                    return;
                }

                me.getViewModel().setData(data);

                const localeField = me.getView().down('[reference=locale]');
                localeField.skipChangeHandler = true;
                localeField.setValue(data.locale + '');
                delete localeField.skipChangeHandler;
            }
        })
    },
})
