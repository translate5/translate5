Ext.define('TMMaintenance.view.main.ErrorDialog', {
    extend: 'Ext.Dialog',
    xtype: 'errorDialog',
    itemId: 'errorDialog',
    bodyPadding: 20,
    centered: true,
    maximizable: false,
    width: 300,
    bind: {
        title: '{l10n.error.title}',
        html: '{l10n.error.couldNotProcessRequest}',
    },
    buttons: [
        {
            bind: {
                text: '{l10n.error.close}',
                hidden: '{!l10n.error.close}',
            },
            handler: () => {
                Ext.ComponentQuery.query('#errorDialog')[0].hide();
            },
        },
    ]
});