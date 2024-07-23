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

    store: {
        type: 'segment',
    },

    plugins: {
        cellediting: {
            triggerEvent: 'tap'
        },
    },

    itemConfig: {
        viewModel: true,
    },
    variableHeights: true,
    loadingText: '',
    columns: [
        {
            xtype: 'rownumberer',
            width: 40
        },
        {
            dataIndex: 'id',
            xtype: 'templatecolumn',
            text: 'ID',
            hidden: true,
            groupable: false,
        },
        {
            tpl: '{metaData.internalKey}',
            xtype: 'templatecolumn',
            text: 'Internal key',
            hidden: true,
            groupable: false,
        },
        {
            dataIndex: 'source',
            minWidth: 300,
            flex: 1,
            editable: true,
            sortable: false,
            cell: {
                encodeHtml: false,
            },
            bind: {
                text: '{l10n.list.columns.sourceText}',
            },
            editor: {
                xtype: 't5editor',
                dataIndex: 'source',
                editingDataIndex: 'source',
            },
            renderer: 'sourceTargetRenderer',
            groupable: false,
        },
        {
            dataIndex: 'target',
            minWidth: 300,
            flex: 1,
            editable: true,
            sortable: false,
            cell: {
                encodeHtml: false,
                height: 100,
            },
            bind: {
                text: '{l10n.list.columns.targetText}',
            },
            editor: {
                xtype: 't5editor',
                dataIndex: 'target',
                editingDataIndex: 'target',
            },
            renderer: 'sourceTargetRenderer',
            groupable: false,
        },
        {
            bind: {
                text: '{l10n.list.columns.actions}',
            },
            cell: {
                userCls: 'editor-tools',
                tools: {
                    delete: {
                        iconCls: 'x-fa fa-trash-alt',
                        handler: 'onDeletePress',
                        bind: {
                            hidden: '{record.isEditing}',
                            tooltip: '{l10n.list.delete}',
                        },
                    },
                    editSource: {
                        iconCls: 'x-fa fi-pencil',
                        handler: 'onEditSource',
                        bind: {
                            hidden: '{record.isEditing}',
                            tooltip: '{l10n.list.editSource}',
                        }
                    },
                    editTarget: {
                        iconCls: 'x-fa fa-edit',
                        handler: 'onEditTarget',
                        bind: {
                            hidden: '{record.isEditing}',
                            tooltip: '{l10n.list.editTarget}',
                        }
                    },
                    spinner: {
                        iconCls: 'icon loading',
                        bind: {
                            hidden: '{!record.isSaving}',
                        },
                        tooltip: 'Saving. Please wait a while.',
                    },
                    save: {
                        iconCls: 'x-fa fa-check',
                        handler: 'saveCurrent',
                        bind: {
                            hidden: '{!record.isEditing}',
                            tooltip: '{l10n.list.save}',
                        },
                    },
                    cancel: {
                        iconCls: 'x-fa fa-times',
                        handler: 'cancelEditing',
                        bind: {
                            hidden: '{!record.isEditing}',
                            tooltip: '{l10n.list.cancel}',
                        },
                    },
                    saveGoNext: {
                        iconCls: 'x-fa fa-arrow-up',
                        handler: 'saveCurrentGoToPrevious',
                        bind: {
                            hidden: '{!record.isEditing}',
                            tooltip: '{l10n.list.saveGoToPrevious}',
                        },
                    },
                    saveGoPrevious: {
                        iconCls: 'x-fa fa-arrow-down',
                        handler: 'saveCurrentGoToNext',
                        bind: {
                            hidden: '{!record.isEditing}',
                            tooltip: '{l10n.list.saveGoToNext}',
                        },
                    },
                    cancelGoNext: {
                        iconCls: 'icon close-go-previous',
                        style: 'filter: grayscale(100%);',
                        handler: 'goToPrevious',
                        bind: {
                            hidden: '{!record.isEditing}',
                            tooltip: '{l10n.list.closeGoToPrevious}',
                        },
                    },
                    cancelGoPrevious: {
                        iconCls: 'icon close-go-next',
                        style: 'filter: grayscale(100%);',
                        handler: 'goToNext',
                        bind: {
                            hidden: '{!record.isEditing}',
                            tooltip: '{l10n.list.closeGoToNext}',
                        },
                    },
                },
            },
        },
        {
            tpl: '{metaData.sourceLang}',
            xtype: 'templatecolumn',
            flex: 1,
            bind: {
                text: '{l10n.list.columns.sourceLanguage}',
            },
            groupable: false,
        },
        {
            tpl: '{metaData.targetLang}',
            xtype: 'templatecolumn',
            flex: 1,
            bind: {
                text: '{l10n.list.columns.targetLanguage}',
            },
            groupable: false,
        },
        {
            tpl: '{metaData.author}',
            xtype: 'templatecolumn',
            flex: 1,
            bind: {
                text: '{l10n.list.columns.author}',
            },
            groupable: false,
        },
        {
            tpl: '{metaData.timestamp}',
            xtype: 'templatecolumn',
            width: 180,
            bind: {
                text: '{l10n.list.columns.creationDate}',
            },
            groupable: false,
        },
        {
            tpl: '{metaData.documentName}',
            xtype: 'templatecolumn',
            flex: 1,
            hidden: true,
            bind: {
                text: '{l10n.list.columns.document}',
            },
            groupable: false,
        },
        {
            tpl: '{metaData.additionalInfo}',
            xtype: 'templatecolumn',
            flex: 1,
            hidden: true,
            bind: {
                text: '{l10n.list.columns.additionalInfo}',
            },
            groupable: false,
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
                    const controller = Ext.ComponentQuery.query('app-main')[0].getController();
                    controller.onContainerScrollEnd(arguments);
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
