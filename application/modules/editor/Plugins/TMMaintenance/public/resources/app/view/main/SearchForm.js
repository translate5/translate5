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
                            label: 'TM',
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
                    label: 'Source',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                    },
                },
                TMMaintenance.view.fields.ModeCombo.create('sourceMode'),
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'target',
                    label: 'Target',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
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
                    label: 'Author',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                    },
                },
                TMMaintenance.view.fields.ModeCombo.create('authorMode'),
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'additionalInfo',
                    label: 'Additional info',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                    },
                },
                TMMaintenance.view.fields.ModeCombo.create('additionalInfoMode'),
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'document',
                    label: 'Document',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
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
                    label: 'Creation date after',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                    },
                },
                {
                    xtype: 'datepickerfield',
                    required: false,
                    name: 'creationDateTo',
                    label: 'Creation date before',
                    flex: 1,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                    },
                },
                {
                    xtype: 'button',
                    name: 'search',
                    text: 'Search',
                    flex: 1,
                    handler: 'onSearchPress',
                    formBind: true,
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{!selectedTm}',
                    },
                },
            ]
        },
    ],
})
