Ext.define('TMMaintenance.view.main.SelectTm', {
    extend: 'Ext.grid.Grid',
    xtype: 'selecttm',
    controller: 'selecttm',
    store: {
        type: 'tm',
    },
    plugins: {
        gridfilters: true,
    },
    title: 'Select TM',
    minHeight: 400,
    layout: 'fit',

    columns: [
        {
            text: 'Name',
            dataIndex: 'name',
            minWidth: 200,
            filter: {
                type: 'string'
            },
        },
        {
            text: 'Source Language',
            dataIndex: 'sourceLanguage',
            width: 100,
            filter: {
                type: 'string'
            },
        },
        {
            text: 'Target Language',
            dataIndex: 'targetLanguage',
            width: 100,
            filter: {
                type: 'string'
            },
        },
        {
            menuDisabled: true,
            cell: {
                xtype: 'widgetcell',
                widget: {
                    xtype: 'button',
                    text: 'Select',
                    handler: 'onTmSelect',
                }
            }
        },
    ],
    emptyText: 'No records available',
});