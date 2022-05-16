Ext.define('TMMaintenance.view.main.List', {
    extend: 'Ext.grid.Grid',
    xtype: 'mainlist',

    requires: [
        'TMMaintenance.store.Segment'
    ],

    title: 'Segments',

    store: {
        type: 'segment'
    },

    columns: [
        {
            text: 'Source text',
            dataIndex: 'SourceText',
        },
        {
            text: 'Target text',
            dataIndex: 'TargetText',
        }
    ],

    listeners: {
        select: 'onItemSelected'
    }
});
