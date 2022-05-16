Ext.define('TMMaintenance.model.Segment', {
    extend: 'TMMaintenance.model.Base',

    fields: [
        'SourceText',
        'TargetText',
    ],

    proxy: {
        type: 'ajax',
        api: {
            create: '/editor/plugins_tmmaintenance_api/segment/create',
            update: '/editor/plugins_tmmaintenance_api/segment/update',
            delete: '/editor/plugins_tmmaintenance_api/segment/delete',
        },
        reader: {
            type: 'json',
        },
        writer: {
            type : 'json',
            rootProperty : 'data',
        },
    }
});
