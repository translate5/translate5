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
    scrollable: true,

    defaults: {
        shadow: true
    },

    items: [
        {
            id: 'containerHeader',
            xtype: 'maintoolbar',
            docked: 'top',
            userCls: 'main-toolbar',
            shadow: true
        },
        {
            id: 'searchform',
            xtype: 'searchform',
            userCls: 'search-form',
        },
        {
            id: 'resultPanel',
            xtype: 'panel',
            userCls: 'result-panel',
            items: [
                {
                    id: 'mainlist',
                    xtype: 'mainlist',
                    userCls: 'panel big-40',
                    // TODO move this to styles
                    responsiveConfig: {
                        'width >= 1000': {
                            height: 'calc(100% - 20px)'
                        },
                        'width < 1000': {
                            height: 300
                        }
                    }
                },
                {
                    xtype: 'button',
                    text: 'Create',
                    handler: 'onCreatePressed',
                    // TODO disable if TM is not selected
                },
                {
                    id: 'editform',
                    xtype: 'editform',
                    userCls: 'panel big-40 hidden',
                },
            ]
        },
    ]
});
