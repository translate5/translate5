Ext.define('TMMaintenance.view.main.List', {
    extend: 'Ext.grid.Grid',
    xtype: 'mainlist',

    requires: [
        'TMMaintenance.store.Segment',
        'Ext.grid.plugin.CellEditing',
        'Ext.grid.plugin.Editable',
        'Ext.grid.rowedit.Plugin',
        'Ext.translate5.Editor',
        'TMMaintenance.view.main.CreateForm',
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
        viewModel: true,
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
            renderer: 'sourceTargetRenderer',
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
                dataIndex: 'target',
                editingDataIndex: 'rawTarget',
            },
            renderer: 'sourceTargetRenderer',
        },
        {
            text: 'Actions',
            cell: {
                userCls: 'editor-tools',
                tools: {
                    edit: {
                        iconCls: 'x-fa fa-pen',
                        handler: 'onEditPress',
                        bind: {
                            hidden: '{record.isEditing}',
                        },
                    },
                    delete: {
                        iconCls: 'x-fa fa-trash-alt',
                        handler: 'onDeletePress',
                        bind: {
                            hidden: '{record.isEditing}',
                        },
                    },
                    spinner: {
                        iconCls: 'icon loading',
                        bind: {
                            hidden: '{!record.isSaving}',
                        },
                        tooltip: 'Saving. Please wait a while.',
                    },
                    save: {
                        iconCls: 'icon save',
                        handler: 'saveCurrent',
                        bind: {
                            hidden: '{!record.isEditing}',
                        },
                    },
                    cancel: {
                        iconCls: 'icon cancel',
                        handler: 'cancelEditing',
                        bind: {
                            hidden: '{!record.isEditing}',
                        },
                    },
                    saveGoNext: {
                        iconCls: 'icon save-go-previous',
                        handler: 'saveCurrentGoToPrevious',
                        bind: {
                            hidden: '{!record.isEditing}',
                        },
                    },
                    saveGoPrevious: {
                        iconCls: 'icon save-go-next',
                        handler: 'saveCurrentGoToNext',
                        bind: {
                            hidden: '{!record.isEditing}',
                        },
                    },
                    cancelGoNext: {
                        iconCls: 'icon close-go-previous',
                        handler: 'goToPrevious',
                        bind: {
                            hidden: '{!record.isEditing}',
                        },
                    },
                    cancelGoPrevious: {
                        iconCls: 'icon close-go-next',
                        handler: 'goToNext',
                        bind: {
                            hidden: '{!record.isEditing}',
                        },
                    },
                },
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
    ],

    titleBar: {
        shadow: false,
        items: [
            {
                xtype: 'button',
                align: 'right',
                text: 'Create',
                handler: 'onCreatePress',
                disabled: '{disabled}',
                bind: {
                    disabled: '{!selectedTm}',
                },
            },
        ],
    },

    listeners: {
        onContainerScrollEnd: 'onContainerScrollEnd',
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

    dialog: {
        xtype: 'dialog',
        title: 'Create new',
        closable: false,
        defaultFocus: '#ok',
        maximizable: true,
        bodyPadding: 20,
        maxWidth: 600,
        minWidth: 400,
        items: [
            {
                id: 'createform',
                xtype: 'createform',
            },
        ],
    },
});
