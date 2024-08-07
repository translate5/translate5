Ext.define('TMMaintenance.view.main.DeleteBatchDialog', {
    extend: 'Ext.Dialog',
    xtype: 'deleteBatchDialog',
    itemId: 'deleteBatchDialog',
    bodyPadding: 20,
    centered: true,
    maximizable: false,
    width: 300,
    bind: {
        html: '<h3>{l10n.deleteBatch.warningText}</h3>',
    },
    buttons: [
        {
            bind: {
                text: '{l10n.deleteBatch.no}',
                hidden: '{!l10n.deleteBatch.no}',
            },
            handler: () => Ext.getCmp('searchform').getController().onDeleteBatch(),
        },
        {
            bind: {
                text: '{l10n.deleteBatch.yes}',
                hidden: '{!l10n.deleteBatch.yes}',
            },
            handler: () => {
                Ext.ComponentQuery.query('#deleteBatchDialog')[0].hide();
            },
        },
    ]
});