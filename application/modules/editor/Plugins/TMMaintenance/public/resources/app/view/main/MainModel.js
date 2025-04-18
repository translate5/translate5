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
        loadingTotalAmount: false,
        loadingRecordNumber: null,
        loadedQty: 0,
        totalLoadedQty: 0
    },
    formulas: {
        listTitle: function(get) {
            var total = get('totalAmount'),
                chunk = get('loadingRecordNumber'),
                title = '';

            if (get('loadingTotalAmount') || total !== null) {

                title += total === false
                    ? get('l10n.list.title')
                    : get('l10n.list.totalAmount');

                if (total === null) {
                    title += get('l10n.list.calculating');
                } else if (total !== false) {
                    title += total;
                }

                title += ' ';
            }
            if (chunk) {
                title += get('l10n.list.loadingSegmentNumber') + chunk + ' ...';
            }

            if (chunk === false && get('lastOffset') === null) {
                title = get('l10n.list.totalAmount') + total;
            }

            if (title.length === 0) {
                title = Ext.String.format(get('l10n.list.loadedSoFar'), get('totalLoadedQty'));
            }
            return  title;
        }
    }
});
