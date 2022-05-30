Ext.define('TMMaintenance.view.main.List', {
    extend: 'Ext.grid.Grid',
    xtype: 'mainlist',

    requires: [
        'TMMaintenance.store.Segment',
        'Ext.grid.plugin.CellEditing',
        'Ext.grid.plugin.Editable',
        'Ext.grid.rowedit.Plugin'
    ],

    title: 'Segments',

    controller: 'main',
    store: {
        type: 'segment',
    },

    plugins: {
        // cellediting: true,
        rowedit: {
            // selectOnEdit: true
            autoConfirm: false,
            autoCancel: false,
        }
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
            editable: true,
            cell: {
                encodeHtml: false,
            },
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
