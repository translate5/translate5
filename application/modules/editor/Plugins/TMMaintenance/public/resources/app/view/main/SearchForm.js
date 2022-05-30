Ext.define('TMMaintenance.view.main.SearchForm', {
    extend: 'Ext.form.Panel',
    xtype: 'searchform',
    controller: 'searchform',

    requires: [
        'Ext.layout.HBox'
    ],

    autoSize: true,

    items: [
        {
            xtype: 'panel',
            layout: 'hbox',
            items: [
                {
                    xtype: 'container',
                    autoSize: true,
                    flex: 1,
                    items: [
                        {
                            xtype: 'combobox',
                            name: 'tm',
                            label: 'Choose TM',
                            displayField: 'name',
                            valueField: 'value',
                            queryMode: 'remote',
                            forceSelection: true,
                            store: {
                                autoload: true,
                                fields: [
                                    'name',
                                    'value',
                                ],
                                proxy: {
                                    type: 'ajax',
                                    url: '/editor/plugins_tmmaintenance_api/tm/list',
                                    reader: {
                                        type: 'json',
                                    }
                                },
                            },
                            listeners: {
                                change: 'onTMChange'
                            },
                            userCls: 'tm',
                        },
                    ],
                },
                {
                    xtype: 'container',
                    autoSize: true,
                    flex: 1,
                    items: [
                        {
                            xtype: 'combobox',
                            name: 'searchField',
                            label: 'Search in',
                            options: [
                                {
                                    text: 'Source',
                                    value: 'source',
                                }, {
                                    text: 'Target',
                                    value: 'target',
                                },
                            ],
                            listeners: {
                                change: 'onSearchFieldChange'
                            },
                            userCls: 'field',
                        },
                    ],
                },
            ],
        },
        {
            xtype: 'panel',
            layout: 'hbox',
            items: [
                {
                    xtype: 'textfield',
                    required: true,
                    name: 'searchCriteria',
                    label: 'Search criteria',
                    disabled: '{!disabled}',
                    bind: {
                        disabled: '{disabled}',
                    },
                },
                {
                    xtype: 'button',
                    iconCls: 'x-fa fa-search',
                    handler: 'onSearch',
                    formBind: true,
                    disabled: '{!disabled}',
                    bind: {
                        disabled: '{disabled}',
                    },
                },
            ],
        },
    ],
})
