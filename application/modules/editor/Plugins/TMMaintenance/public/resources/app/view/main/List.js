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
            dataIndex: 'source',
            minWidth: 200,
            cell: {
                encodeHtml: false,
            },
        },
        {
            text: 'Target text',
            dataIndex: 'target',
            minWidth: 200,
            cell: {
                encodeHtml: false,
            },
        },
        {
            cell: {
                tools: {
                    delete: {
                        iconCls: 'x-fa fa-trash-alt',
                        handler: 'onDeletePressed',
                    },
                },
            },
        },
    ],

    listeners: {
        select: 'onItemSelected'
    }
});
