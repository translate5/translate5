Ext.define('TMMaintenance.view.main.MainModel', {
    extend: 'Ext.app.ViewModel',

    alias: 'viewmodel.main',

    data: {
        name: 'TMMaintenance',
        tms: null,
        //TODO move to search form view model
        lastOffset: null,
        hasMoreRecords: false,
    },
});
