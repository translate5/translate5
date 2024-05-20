Ext.define('TMMaintenance.view.main.SearchForm', {
    extend: 'Ext.form.Panel',
    xtype: 'searchform',
    controller: 'searchform',

    requires: [
        'Ext.layout.HBox',
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
                            bind: {
                                store: '{tms}',
                            },
                            listeners: {
                                change: 'onTMChange',
                            },
                            userCls: 'tm',
                            validators: {
                                type: 'controller',
                                fn: 'validateTmField',
                            },
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
                                },
                                {
                                    text: 'Target',
                                    value: 'target',
                                },
                            ],
                            listeners: {
                                change: 'onSearchFieldChange',
                            },
                            userCls: 'field',
                            validators: {
                                type: 'controller',
                                fn: 'validateSearchField',
                            },
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
                    required: false,
                    name: 'searchCriteria',
                    label: 'Search criteria',
                    disabled: '{!disabled}',
                    bind: {
                        disabled: '{disabled}',
                    },
                },
                {
                    xtype: 'button',
                    name: 'search',
                    iconCls: 'x-fa fa-search',
                    handler: 'onSearchPress',
                    formBind: true,
                    disabled: '{!disabled}',
                    bind: {
                        disabled: '{disabled}',
                    },
                },
            ],
        },
    ],

    getSearchCriteriaValue: function () {
        return this.getValues().searchCriteria;
    },

    getSearchFieldValue: function () {
        return this.getValues().searchField;
    },
})
