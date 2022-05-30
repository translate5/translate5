Ext.define('TMMaintenance.store.Segment', {
    extend: 'Ext.data.Store',

    alias: 'store.segment',

    model: 'TMMaintenance.model.Segment',

    proxy: {
        type: 'ajax',
        url: '/editor/plugins_tmmaintenance_api/segments',
        reader: {
            type: 'json',
            rootProperty: 'items'
        },
    },
    // TODO Lower value was added for testing, increase to 20 after development is done
    pageSize: 5,
});
