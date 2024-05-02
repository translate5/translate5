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
        },
    },

    save: function(options) {
        // Check if the record is considered new
        // phantom is true if the record has not been saved to the server yet
        if (this.phantom) {
            this.getProxy().setActionMethods({create: 'POST'});
        } else {
            this.getProxy().setActionMethods({update: 'PUT'});
        }

        this.callParent([options]);
    }
});
