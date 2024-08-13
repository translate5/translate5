Ext.define('TMMaintenance.view.main.MainModel', {
    extend: 'Ext.app.ViewModel',

    alias: 'viewmodel.main',

    data: {
        name: 'TMMaintenance',
        selectedTm: null,
        lastOffset: null,
        hasRecords: false,
        hasMoreRecords: false,
        l10n: {},
        languages: {},
        tms: {},
        totalAmount: false,
        loadingRecordNumber: null
    },
    formulas: {
        listTitle: function(get) {
            var total = get('totalAmount'),
                chunk = get('loadingRecordNumber'),
                title = total === false ? get('l10n.list.title') : get('l10n.list.totalAmount');

            if (total === null) {
                title += get('l10n.list.calculating');
            } else if (total !== false) {
                title += total;
                if (chunk) {
                    title += ', ' + get('l10n.list.loadingSegmentNumber') + chunk + ' ...';
                }
            }
            return  title;
        }
    }
});
