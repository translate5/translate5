Ext.define('TMMaintenance.store.Segment', {
    extend: 'Ext.data.Store',

    alias: 'store.segment',

    model: 'TMMaintenance.model.Segment',

    proxy: {
        type: 'ajax',
        url: '/editor/plugins_tmmaintenance_api',
        reader: {
            type: 'json',
            rootProperty: 'items',
        },
    },
});
