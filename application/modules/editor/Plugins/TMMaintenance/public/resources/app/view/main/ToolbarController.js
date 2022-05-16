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

        this.loadData();
    },

    onLogoutButtonClick: function () {
        location = '/login/logout';
    },

    // TODO add chosen locale
    loadData: function () {
        let me = this;
        // Setup default ajax headers
        Ext.Ajax.setDefaultHeaders({
            'Accept': 'application/json'
        });

        Ext.Ajax.request({
            url: '/editor/plugins_tmmaintenance_api/locale/list',
            async: false,
            method: 'GET',
            success: function (xhr) {
                let data = Ext.JSON.decode(xhr.responseText, true);

                if (!(data)) {
                    // TODO show an error
                    return;
                }

                me.getViewModel().setData(data);

                // me.down('[reference=locale]').skipChangeHandler = true;
                // me.down('[reference=locale]').setValue(data.locale + '');
                // delete me.down('[reference=locale]').skipChangeHandler;
            }
        })
    },
})
