Ext.define('TMMaintenance.view.main.SearchFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.searchform',

    listen: {
        global: {
            onApplicationLoad: 'onLoad'
        }
    },

    onLoad: function () {
        let me = this;
        // Setup default ajax headers
        Ext.Ajax.setDefaultHeaders({
            'Accept': 'application/json'
        });

        Ext.Ajax.request({
            url: '/editor/plugins_tmmaintenance_api/tm/list',
            async: false,
            method: 'GET',
            success: function (xhr) {
                let data = Ext.JSON.decode(xhr.responseText, true);

                if (!(data)) {
                    // TODO show an error
                    return;
                }

                me.getViewModel().setData(data);
            }
        })
    },

    /**
     * @param {ComboBox} comboBox
     * @param {String} tm
     */
    onTMChange: function (comboBox, tm) {
        console.log(tm);
    },

    onEnterCriteria: function () {

    },

    onSearch: function () {
        let values = this.getView().getValues();

        let store = Ext.getCmp('mainlist').store;

        store.load({
            params: {
                tm: values.tm,
                searchCriteria: values.searchCriteria,
            }
        });
    },
})
