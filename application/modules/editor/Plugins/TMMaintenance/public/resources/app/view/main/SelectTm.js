Ext.define('TMMaintenance.view.main.SelectTm', {
    extend: 'Ext.grid.Grid',
    xtype: 'selecttm',
    controller: 'selecttm',
    store: [],
    plugins: {
        gridfilters: true,
    },
    bind: {
        title: '{l10n.selectTm.title}',
        store: '{tms}',
        emptyText: '{l10n.selectTm.emptyText}',
    },
    minHeight: 400,
    layout: 'fit',
    variableHeights: true,

    columns: [
        {
            dataIndex: 'name',
            bind: {
                text: '{l10n.selectTm.name}',
            },
            minWidth: 200,
            flex: 1,
            filter: {
                type: 'string'
            },
            groupable: false,
        },
        {
            dataIndex: 'sourceLanguage',
            bind: {
                text: '{l10n.selectTm.sourceLanguage}',
            },
            width: 100,
            filter: {
                type: 'string'
            },
            groupable: false,
        },
        {
            dataIndex: 'targetLanguage',
            bind: {
                text: '{l10n.selectTm.targetLanguage}',
            },
            width: 100,
            filter: {
                type: 'string'
            },
            groupable: false,
        },
        {
            dataIndex: 'clients',
            bind: {
                text: '{l10n.selectTm.clients}',
            },
            width: 100,
            filter: {
                type: 'string'
            },
            groupable: false,
        },
        {
            menuDisabled: true,
            cell: {
                xtype: 'widgetcell',
                widget: {
                    xtype: 'button',
                    bind: {
                        text: '{l10n.selectTm.select}',
                    },
                    handler: 'onTmSelect',
                }
            }
        },
    ],
    listeners: {
        childdoubletap: 'onTmDoubleTap'
    }
});