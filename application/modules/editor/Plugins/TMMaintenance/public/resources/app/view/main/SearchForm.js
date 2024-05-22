Ext.define('TMMaintenance.view.main.SearchForm', {
    extend: 'Ext.form.Panel',
    xtype: 'searchform',
    controller: 'searchform',

    requires: [
        'Ext.layout.HBox',
        'TMMaintenance.store.TM',
        'TMMaintenance.view.main.SelectTm'
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
                                    form.down('#tmInfo').setHtml('Selected TM: ' + (selectedName ? selectedName : 'Not selected'));
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
                            xtype: 'component',
                            itemId: 'tmInfo',
                            html: 'Selected TM: Not selected',
                            style: 'margin-top: 1rem;',
                        },
                        {
                            xtype: 'button',
                            name: 'selectTm',
                            text: 'Select TM',
                            handler: 'onSelectTmPress',
                        },
                    ],
                },
            ],
        },
        {
            xtype: 'panel',
            layout: 'hbox',
            flex: 1,
            defaults: {
                margin: '0 10 0 0'
            },
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
                    width: 100,
                    validators: {
                        type: 'controller',
                        fn: 'validateSearchField',
                    },
                },
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'searchCriteria',
                    label: 'Seaarch text',
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{disabled}',
                    },
                },
                {
                    xtype: 'textfield',
                    required: false,
                    name: 'author',
                    label: 'Author',
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{disabled}',
                    },
                },
                {
                    xtype: 'datepickerfield',
                    required: false,
                    name: 'creationDateFrom',
                    label: 'Creation date after',
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{disabled}',
                    },
                },
                {
                    xtype: 'datepickerfield',
                    required: false,
                    name: 'creationDateTo',
                    label: 'Creation date before',
                    disabled: '{!selectedTm}',
                    bind: {
                        disabled: '{disabled}',
                    },
                },
                {
                    xtype: 'button',
                    name: 'search',
                    // iconCls: 'x-fa fa-search',
                    text: 'Search',
                    handler: 'onSearchPress',
                    formBind: true,
                    disabled: '{!selectedTm}',
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
