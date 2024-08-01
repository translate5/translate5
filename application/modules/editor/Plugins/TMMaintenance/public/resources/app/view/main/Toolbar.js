Ext.define('TMMaintenance.view.main.Toolbar', {
    extend: 'Ext.Toolbar',
    xtype: 'maintoolbar',

    controller: 'toolbar',
    height: 70,
    padding: '0 20 0 0',
    requires: [
        'Ext.Button',
        'Ext.Img',
        'Ext.SegmentedButton',
        'TMMaintenance.view.main.ToolbarController',
    ],
    items: [
        {
            xtype: 'image',
            id: 'logo',
            userCls: 'logo',
        },
        '->',
        {
            xtype: 'combobox',
            reference: 'locale',
            queryMode: 'local',
            displayField: 'name',
            valueField: 'locale',
            itemId: 'locale',
            bind: {
                store: '{l10n.localeStore}',
            },
            value: 'en',
            store: [],
            listeners: {
                change: 'onLocaleChange',
            },
        },
        {
            margin: '0 0 0 10',
            xtype: 'button',
            bind: {
                text: '{l10n.logout}',
                hidden: '{!l10n.logout}',
            },
            handler: 'onLogoutButtonClick',
        },
    ],
});
