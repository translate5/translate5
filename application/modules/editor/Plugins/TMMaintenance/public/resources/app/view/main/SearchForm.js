Ext.define('TMMaintenance.view.main.SearchForm', {
    extend: 'Ext.form.Panel',
    xtype: 'searchform',
    controller: 'searchform',
    layout: 'vbox',
    userCls: '',
    items: [
        {
            xtype: 'combobox',
            fieldLabel: 'Choose TM',
            name: 'tm',
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
            bind: {
                store: '{tms}'
            },
            store: [],
            value: '',
            listeners: {
                change: 'onTMChange'
            },
        },
        {
            xtype: 'panel',
            layout: 'hbox',
            items: [
                {
                    xtype: 'textfield',
                    required: true,
                    name: 'searchCriteria',
                    flex: 1,
                },
                {
                    xtype: 'button',
                    iconCls: 'x-fa fa-search',
                    handler: 'onSearch',
                    formBind: true,
                    // disabled: '{!selectedTm}'
                }
            ]
        },
    ],
    // masked: {
    //     xtype: 'loadmask',
    //     indicator: false
    // },
    // bind: {
    //     masked: {
    //         message: '{l10n.noCollections}',
    //         userCls: 'nothing-for-you {filterWindow.collections.length ? "x-hidden" : ""}'
    //     }
    // }
})
