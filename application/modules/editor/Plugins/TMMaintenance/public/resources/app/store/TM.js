Ext.define('TMMaintenance.store.TM', {
    extend: 'Ext.data.Store',
    alias: 'store.tm',
    storeId: 'tm',
    model: 'TMMaintenance.model.Tm',
    proxy: {
        type: 'ajax',
        url: '/editor/plugins_tmmaintenance_api/tm/list',
        reader: {
            type: 'json',
            rootProperty: 'items',
        },
    },
});