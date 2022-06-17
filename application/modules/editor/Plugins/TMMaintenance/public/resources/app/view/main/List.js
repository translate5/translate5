Ext.define('TMMaintenance.view.main.List', {
    extend: 'Ext.grid.Grid',
    xtype: 'mainlist',

    requires: [
        'TMMaintenance.store.Segment',
        'Ext.grid.plugin.CellEditing',
        'Ext.grid.plugin.Editable',
        'Ext.grid.rowedit.Plugin',
        'Ext.translate5.Editor',
    ],

    title: 'Segments',

    controller: 'main',
    store: {
        type: 'segment',
    },

    plugins: {
        cellediting: true,
    },

    itemConfig: {
        minHeight: 100,
    },

    columns: [
        {
            text: 'Source text',
            dataIndex: 'source',
            minWidth: 200,
            sortable: false,
            cell: {
                encodeHtml: false,
            },
            editable: false,
            editor: {
                xtype: 'celleditor',
            },
        },
        {
            text: 'Target text',
            dataIndex: 'target',
            minWidth: 300,
            editable: true,
            sortable: false,
            cell: {
                encodeHtml: false,
                height: 100,
            },
            editor: {
                xtype: 't5editor',
                editingDataIndex: 'rawTarget',
            },
        },
        {
            text: 'Author',
            tpl: '{metaData.author}',
            xtype: 'templatecolumn',
        },
        {
            text: 'Creation date',
            tpl: '{metaData.timestamp}',
            xtype: 'templatecolumn',
        },
        {
            text: 'Document name',
            tpl: '{metaData.documentName}',
            xtype: 'templatecolumn',
        },
        {
            text: 'Additional info',
            tpl: '{metaData.additionalInfo}',
            xtype: 'templatecolumn',
        },
        {
            cell: {
                tools: {
                    edit: {
                        iconCls: 'x-fa fa-pen',
                        handler: 'onEditPressed',
                    },
                    delete: {
                        iconCls: 'x-fa fa-trash-alt',
                        handler: 'onDeletePressed',
                    },
                },
            },
        },
    ],

    listeners: {
        select: 'onItemSelected',
        onContainerScrollEnd: 'onContainerScrollEnd',
        edit: 'onRowEdit',
    },

    scrollable: {
        y: true,
        listeners: {
            scrollend: function () {
                let maxPosition = this.getMaxPosition().y;
                let threshold = Math.ceil(maxPosition * 0.1);

                if (this.getPosition().y + threshold >= maxPosition) {
                    this.component.getController().onContainerScrollEnd(arguments);
                }
            },
        },
    },
});
