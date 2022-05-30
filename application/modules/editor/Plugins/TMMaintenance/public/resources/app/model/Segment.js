Ext.define('TMMaintenance.model.Segment', {
    extend: 'TMMaintenance.model.Base',

    fields: [
        'id',
        'tm',
        'languageResourceType',
        'languageResourceid',
        'matchrate',
        'rawSource',
        'rawTarget',
        'source',
        'state',
        'target',
    ],

    hasOne: {
        model: 'SegmentMetaData',
        name: 'metaData',
        associationKey: 'metaData'
    },

    proxy: {
        type: 'rest',
        url: '/editor/plugins_tmmaintenance_api/segments',
        writer: {
            encode: true,
            rootProperty: 'data',
            type: 'json',
            writeAllFields: true,
        }
    }
});
