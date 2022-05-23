Ext.define('TMMaintenance.view.main.SearchFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.searchform',

    /**
     * @param {ComboBox} comboBox
     * @param {String} tm
     */
    onTMChange: function (comboBox, tm) {
        this.getViewModel().set('disabled', null === tm);
    },

    onSearch: function () {
        let values = this.getView().getValues();

        let store = Ext.getCmp('mainlist').store;

        store.load({
            params: {
                tm: values.tm,
                searchField: values.searchField,
                searchCriteria: values.searchCriteria,
            }
        });
    },
})
