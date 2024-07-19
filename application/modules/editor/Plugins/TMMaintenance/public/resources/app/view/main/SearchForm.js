Ext.define('TMMaintenance.view.main.SearchForm', {
    extend: 'Ext.form.Panel',
    xtype: 'searchform',
    controller: 'searchform',

    requires: [
        'Ext.layout.HBox',
        'TMMaintenance.view.main.SelectTm',
        'TMMaintenance.view.fields.ModeCombo',
        'TMMaintenance.view.fields.ModeComboSourceTarget',
    ],
    padding: '15 15 0 15',
    autoSize: true,
    keyMap: {
        ENTER: 'onSearchPress'
    },
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
            margin: '0 0 15 0',
            responsiveConfig: {
                'width >= 500': {
                    layout: 'hbox'
                },
                'width < 500': {
                    layout: 'vbox'
                }
            },
            items: [
                {
                    xtype: 'panel',
                    responsiveConfig: {
                        'width >= 500': {
                            width: 'calc(50% - 15px)',
                            margin: '0 15 0 0'
                        },
                        'width < 500': {
                            width: '100%'
                        }
                    },
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
                            flex: 1,
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
                            iconCls: 'x-fa fa-folder-open',
                            handler: 'onSelectTmPress',
                        },
                    ],
                },
                {
                    xtype: 'panel',
                    layout: 'hbox',
                    responsiveConfig: {
                        'width >= 500': {
                            width: '30%',
                            margin: '0 15 0 0'
                        },
                        'width < 500': {
                            width: '100%'
                        }
                    },
                    defaults: {
                        width: '50%'
                    },
                    items: [
                        {
                            xtype: 'combobox',
                            required: false,
                            name: 'sourceLanguage',
                            displayField: 'label',
                            valueField: 'rfc5646',
                            style: 'margin-right: 5px',
                            store: [],
                            flex: 1,
                            disabled: '{!selectedTm}',
                            bind: {
                                disabled: '{!selectedTm}',
                                hidden: '{!l10n.searchForm.target}',
                                store: '{languages}',
                                label: '{l10n.searchForm.sourceLanguage}'
                            },
                        },
                        {
                            xtype: 'combobox',
                            required: false,
                            name: 'targetLanguage',
                            label: 'Target language',
                            displayField: 'label',
                            valueField: 'rfc5646',
                            store: [],
                            flex: 1,
                            disabled: '{!selectedTm}',
                            bind: {
                                disabled: '{!selectedTm}',
                                hidden: '{!l10n.searchForm.target}',
                                store: '{languages}',
                                label: '{l10n.searchForm.targetLanguage}'
                            },
                        },
                    ]
                },
                {
                    xtype: 'panel',
                    layout: 'hbox',
                    responsiveConfig: {
                        'width >= 500': {
                            width: '20%',
                            margin: '0 15 0 0'
                        },
                        'width < 500': {
                            width: '100%'
                        }
                    },
                    defaults: {
                        width: '50%'
                    },
                    items: [
                        {
                            xtype: 'button',
                            name: 'search',
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
                            handler: 'onDeleteBatchPress',
                            formBind: true,
                            disabled: true,
                            bind: {
                                disabled: '{!hasRecords}',
                                text: '{l10n.searchForm.deleteAll}',
                                hidden: '{!l10n.searchForm.deleteAll}',
                                tooltip: '{l10n.searchForm.deleteAllTooltip}',
                            },
                        }
                    ]
                }
            ],
        },
        {
            xtype: 'panel',
            layout: 'hbox',
            flex: 1,
            defaults: {
                margin: '0 15 15 0'
            },
            items: [
                {
                    xtype: 'container',
                    layout: 'hbox',
                    width: 'calc(40% - 15px)',
                    items: [
                        TMMaintenance.view.fields.ModeComboSourceTarget.create('sourceMode', 'source'),
                        {
                            xtype: 'textfield',
                            required: false,
                            name: 'source',
                            flex: 1,
                            disabled: '{!selectedTm}',
                            bind: {
                                disabled: '{!selectedTm}',
                                hidden: '{!l10n.searchForm.source}',
                            },
                        },
                    ]
                },
                {
                    xtype: 'container',
                    layout: 'hbox',
                    width: 'calc(40% - 15px)',
                    items: [
                        TMMaintenance.view.fields.ModeCombo.create('authorMode', 'author'),
                        {
                            xtype: 'textfield',
                            required: false,
                            name: 'author',
                            flex: 1,
                            disabled: '{!selectedTm}',
                            bind: {
                                disabled: '{!selectedTm}',
                                hidden: '{!l10n.searchForm.author}',
                            },
                        },
                    ]
                },
                {
                    xtype: 'datepickerfield',
                    required: false,
                    name: 'creationDateTo',
                    labelWidth: '50%',
                    width: '20%',
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        label: '{l10n.searchForm.creationDateTo}',
                        hidden: '{!l10n.searchForm.creationDateTo}',
                    },
                },
            ],
        },
        {
            xtype: 'panel',
            layout: 'hbox',
            width: '100%',
            defaults: {
                margin: '0 15 15 0'
            },
            items: [
                {
                    xtype: 'container',
                    layout: 'hbox',
                    width: 'calc(40% - 15px)',
                    items: [
                        TMMaintenance.view.fields.ModeComboSourceTarget.create('targetMode', 'target'),
                        {
                            xtype: 'textfield',
                            required: false,
                            name: 'target',
                            flex: 1,
                            disabled: '{!selectedTm}',
                            bind: {
                                disabled: '{!selectedTm}',
                                hidden: '{!l10n.searchForm.target}',
                            },
                        },
                    ]
                },
                {
                    xtype: 'container',
                    layout: 'hbox',
                    width: 'calc(40% - 15px)',
                    items: [
                        TMMaintenance.view.fields.ModeCombo.create('documentMode', 'document'),
                        {
                            xtype: 'textfield',
                            required: false,
                            name: 'document',
                            flex: 1,
                            disabled: '{!selectedTm}',
                            bind: {
                                disabled: '{!selectedTm}',
                                hidden: '{!l10n.searchForm.document}',
                            },
                        },
                    ]
                },
                {
                    xtype: 'datepickerfield',
                    required: false,
                    name: 'creationDateFrom',
                    labelWidth: '50%',
                    width: '20%',
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                        label: '{l10n.searchForm.creationDateFrom}',
                        hidden: '{!l10n.searchForm.creationDateFrom}',
                    },
                },
            ],
        },
    ],
})
