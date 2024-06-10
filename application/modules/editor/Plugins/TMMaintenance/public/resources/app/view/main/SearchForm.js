Ext.define('TMMaintenance.view.main.SearchForm', {
    extend: 'Ext.form.Panel',
    xtype: 'searchform',
    controller: 'searchform',

    requires: [
        'Ext.layout.HBox',
        'TMMaintenance.store.TM',
        'TMMaintenance.view.main.SelectTm',
        'TMMaintenance.view.fields.ModeCombo',
    ],

    autoSize: true,

    listeners: {
        change: {
            delegate: 'field',
            fn: function(field, newValue, oldValue) {
                field.up('app-main').getViewModel().set('hasRecords', false);
            }
        }
    },

    items: [
        {
            xtype: 'panel',
            layout: 'hbox',
            flex: 1,
            defaults: {
                margin: '0 20 0 0'
            },
            items: [
                {
                    xtype: 'container',
                    autoSize: true,
                    flex: 1,
                    layout: 'hbox',
                    items: [
                        {
                            xtype: 'numberfield',
                            name: 'tm',
                            hidden: true,
                            listeners: {
                                change: function (field, newValue) {
                                    const form = this.up('searchform');
                                    const selectedName = form.getController().getTmNameById(newValue);
                                    form.down('#tmName').setValue(selectedName ? selectedName : '');
                                },
                            },
                            bind: '{selectedTm}',
                            userCls: 'tm',
                            validators: {
                                type: 'controller',
                                fn: 'validateTmField',
                            },
                        },
                        {
                            xtype: 'textfield',
                            itemId: 'tmName',
                            editable: false,
                            clearable: false,
                            bind: {
                                label: '{l10n.searchForm.tm}',
                                hidden: '{!l10n.searchForm.tm}',
                            },
                            listeners: {
                                click: 'onSelectTmPress',
                            },
                        },
                        {
                            xtype: 'button',
                            name: 'selectTm',
                            iconCls: 'x-fa fa-search',
                            handler: 'onSelectTmPress',
                        },
                    ],
                },
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'source',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        label: '{l10n.searchForm.source}',
                        hidden: '{!l10n.searchForm.source}',
                    },
                },
                TMMaintenance.view.fields.ModeCombo.create('sourceMode'),
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'target',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        label: '{l10n.searchForm.target}',
                        hidden: '{!l10n.searchForm.target}',
                    },
                },
                TMMaintenance.view.fields.ModeCombo.create('targetMode'),
            ],
        },
        {
            xtype: 'panel',
            layout: 'hbox',
            flex: 1,
            defaults: {
                margin: '0 20 0 0'
            },
            items: [
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'author',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        label: '{l10n.searchForm.author}',
                        hidden: '{!l10n.searchForm.author}',
                    },
                },
                TMMaintenance.view.fields.ModeCombo.create('authorMode'),
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'additionalInfo',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        label: '{l10n.searchForm.additionalInfo}',
                        hidden: '{!l10n.searchForm.additionalInfo}',
                    },
                },
                TMMaintenance.view.fields.ModeCombo.create('additionalInfoMode'),
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'document',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        label: '{l10n.searchForm.document}',
                        hidden: '{!l10n.searchForm.document}',
                    },
                },
                TMMaintenance.view.fields.ModeCombo.create('documentMode'),
            ],
        },
        {
            xtype: 'panel',
            layout: 'hbox',
            flex: 1,
            defaults: {
                margin: '0 20 0 0'
            },
            items: [
                // {
                //     xtype: 'textfield',
                //     required: false,
                //     name: 'context',
                //     label: 'Context',
                //     flex: 1,
                //     disabled: '{!selectedTm}',
                //     bind: {
                //         disabled: '{!selectedTm}',
                //     },
                // },
                // TMMaintenance.view.fields.ModeCombo.create('contextMode'),
                {
                    xtype: 'datepickerfield',
                    required: false,
                    name: 'creationDateFrom',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        label: '{l10n.searchForm.creationDateFrom}',
                        hidden: '{!l10n.searchForm.creationDateFrom}',
                    },
                },
                {
                    xtype: 'datepickerfield',
                    required: false,
                    name: 'creationDateTo',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        label: '{l10n.searchForm.creationDateTo}',
                        hidden: '{!l10n.searchForm.creationDateTo}',
                    },
                },
                {
                    xtype: 'button',
                    name: 'search',
                    flex: 1,
                    handler: 'onSearchPress',
                    formBind: true,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        text: '{l10n.searchForm.search}',
                        hidden: '{!l10n.searchForm.search}',
                    },
                },
                {
                    xtype: 'button',
                    name: 'deleteBatch',
                    flex: 1,
                    handler: 'onDeleteBatchPress',
                    formBind: true,
                    disabled: true,
                    bind: {
                        disabled: '{!hasRecords}',
                        text: '{l10n.searchForm.deleteAll}',
                        hidden: '{!l10n.searchForm.deleteAll}',
                        tooltip: '{l10n.searchForm.deleteAllTooltip}',
                    },
                },
            ]
        },
    ],
})
