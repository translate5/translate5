Ext.define('TMMaintenance.view.main.Main', {
    extend: 'Ext.Container',
    xtype: 'app-main',

    requires: [
        'TMMaintenance.view.main.Toolbar',
        'TMMaintenance.view.main.SearchForm',
        'TMMaintenance.view.main.List',
        'TMMaintenance.view.main.SelectTmDialog',
        'TMMaintenance.view.main.DeleteBatchDialog',
        'TMMaintenance.view.main.ErrorDialog',
        'Ext.Dialog',
    ],

    controller: 'main',
    viewModel: 'main',

    items: [
        {
            id: 'containerHeader',
            xtype: 'maintoolbar',
            docked: 'top',
            userCls: 'mb-20 toolbar',
            shadow: true
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
                    userCls: 'result-list ml-20 mr-20',
                    shadow: true,
                },
            ],
        },
        {
            xtype: 'selectTmDialog',
        },
        {
            xtype: 'deleteBatchDialog',
        },
        {
            xtype: 'errorDialog',
        },
    ],
});
