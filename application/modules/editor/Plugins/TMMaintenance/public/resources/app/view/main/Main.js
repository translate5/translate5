Ext.define('TMMaintenance.view.main.Main', {
    extend: 'Ext.Container',
    xtype: 'app-main',

    requires: [
        'TMMaintenance.view.main.Toolbar',
        'TMMaintenance.view.main.SearchForm',
        'TMMaintenance.view.main.List',
        'TMMaintenance.view.main.EditForm',
    ],

    controller: 'main',
    viewModel: 'main',

    items: [
        {
            id: 'containerHeader',
            xtype: 'maintoolbar',
            docked: 'top',
            userCls: 'mb-20',
        },
        {
            xtype: 'panel',
            userCls: 'w-100',
            shadow: false,
            items: [
                {
                    id: 'searchform',
                    xtype: 'searchform',
                    userCls: 'search-form ml-20 mr-20 mb-20',
                    shadow: true
                },
            ],
        },
        {
            xtype: 'panel',
            userCls: 'w-100 container',
            shadow: false,
            items: [
                {
                    id: 'mainlist',
                    xtype: 'mainlist',
                    userCls: 'result-list column left ml-20',
                    shadow: true,
                },
                {
                    xtype: 'panel',
                    userCls: 'column right mr-20',
                    shadow: true,
                    items:[
                        {
                            xtype: 'button',
                            text: 'Create',
                            handler: 'onCreatePressed',
                            disabled: '{disabled}',
                            bind: {
                                disabled: '{!selectedTm}',
                            },
                        },
                        {
                            id: 'editform',
                            xtype: 'editform',
                            userCls: 'panel',
                        },
                    ]
                },
            ]
        },
    ]
});
