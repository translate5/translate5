Ext.define('TMMaintenance.view.main.MainModel', {
    extend: 'Ext.app.ViewModel',

    alias: 'viewmodel.main',

    data: {
        name: 'TMMaintenance',
        selectedTm: null,
        lastOffset: null,
        hasMoreRecords: false,
        l10n: {},
    },
});
