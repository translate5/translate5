Ext.define('TMMaintenance.view.main.SearchFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.searchform',

    onTMChange: function () {
        let values = this.getView().getValues();
        this.getViewModel().set('selectedTm', values.tm);
        this.getViewModel().set('disabled', null === values.tm || null === values.searchField);
    },

    onSearchFieldChange: function () {
        let values = this.getView().getValues();
        this.getViewModel().set('disabled', null === values.tm || null === values.searchField);
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
