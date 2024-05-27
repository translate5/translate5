Ext.define('TMMaintenance.view.main.SelectTmDialog', {
    extend: 'Ext.Dialog',
    xtype: 'selectTmDialog',
    itemId: 'selectTmDialog',
    maximizable: true,
    bodyPadding: 20,
    centered: true,
    items: [
        {
            xtype: 'selecttm',
            itemId: 'selecttm',
        },
    ],
    buttons: [
        {
            bind: {
                text: '{l10n.selectTm.close}',
                hidden: '{!l10n.selectTm.close}',
            },
            handler: () => {
                Ext.ComponentQuery.query('#selectTmDialog')[0].hide();
            },
        },
    ]
});