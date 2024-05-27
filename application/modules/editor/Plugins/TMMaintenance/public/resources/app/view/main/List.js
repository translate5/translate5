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

    bind: {
        title: '{l10n.list.title}',
    },

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
            dataIndex: 'source',
            minWidth: 300,
            editable: true,
            sortable: false,
            cell: {
                encodeHtml: false,
            },
            bind: {
                text: '{l10n.list.columns.sourceText}',
                hidden: '{!l10n.list.columns.sourceText}',
            },
            editor: {
                xtype: 't5editor',
                dataIndex: 'source',
                editingDataIndex: 'source',
            },
            renderer: 'sourceTargetRenderer',
        },
        {
            dataIndex: 'target',
            minWidth: 300,
            editable: true,
            sortable: false,
            cell: {
                encodeHtml: false,
                height: 100,
            },
            bind: {
                text: '{l10n.list.columns.targetText}',
                hidden: '{!l10n.list.columns.targetText}',
            },
            editor: {
                xtype: 't5editor',
                dataIndex: 'target',
                editingDataIndex: 'target',
            },
            renderer: 'sourceTargetRenderer',
        },
        {
            bind: {
                text: '{l10n.list.columns.sourceText}',
                hidden: '{!l10n.list.columns.sourceText}',
            },
            cell: {
                userCls: 'editor-tools',
                tools: {
                    edit: {
                        iconCls: 'x-fa fa-pen',
                        handler: 'onEditPress',
                        bind: {
                            hidden: '{record.isEditing}',
                            tooltip: '{l10n.list.edit}',
                        },
                    },
                    delete: {
                        iconCls: 'x-fa fa-trash-alt',
                        handler: 'onDeletePress',
                        bind: {
                            hidden: '{record.isEditing}',
                            tooltip: '{l10n.list.delete}',
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
            tpl: '{metaData.author}',
            xtype: 'templatecolumn',
            bind: {
                text: '{l10n.list.columns.author}',
                hidden: '{!l10n.list.columns.author}',
            },
        },
        {
            tpl: '{metaData.timestamp}',
            xtype: 'templatecolumn',
            bind: {
                text: '{l10n.list.columns.creationDate}',
                hidden: '{!l10n.list.columns.creationDate}',
            },
        },
        {
            tpl: '{metaData.documentName}',
            xtype: 'templatecolumn',
            bind: {
                text: '{l10n.list.columns.document}',
                hidden: '{!l10n.list.columns.document}',
            },
        },
        {
            tpl: '{metaData.additionalInfo}',
            xtype: 'templatecolumn',
            bind: {
                text: '{l10n.list.columns.additionalInfo}',
                hidden: '{!l10n.list.columns.additionalInfo}',
            },
        },
    ],

    titleBar: {
        shadow: false,
        items: [
            {
                xtype: 'button',
                align: 'right',
                handler: 'onCreatePress',
                disabled: '{disabled}',
                bind: {
                    disabled: '{!selectedTm}',
                    text: '{l10n.list.create}',
                    hidden: '{!l10n.list.create}',
                },
            },
        ],
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
