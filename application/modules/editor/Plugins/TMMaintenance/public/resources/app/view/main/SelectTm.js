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
    bind: {
        title: '{l10n.selectTm.title}',
    },
    minHeight: 400,
    layout: 'fit',

    columns: [
        {
            dataIndex: 'name',
            bind: {
                text: '{l10n.selectTm.name}',
                hidden: '{!l10n.selectTm.name}',
            },
            minWidth: 200,
            filter: {
                type: 'string'
            },
        },
        {
            dataIndex: 'sourceLanguage',
            bind: {
                text: '{l10n.selectTm.sourceLanguage}',
                hidden: '{!l10n.selectTm.sourceLanguage}',
            },
            width: 100,
            filter: {
                type: 'string'
            },
        },
        {
            dataIndex: 'targetLanguage',
            bind: {
                text: '{l10n.selectTm.targetLanguage}',
                hidden: '{!l10n.selectTm.targetLanguage}',
            },
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
                    bind: {
                        text: '{l10n.selectTm.select}',
                        hidden: '{!l10n.selectTm.select}',
                    },
                    handler: 'onTmSelect',
                }
            }
        },
    ],
    emptyText: 'No records available',
});