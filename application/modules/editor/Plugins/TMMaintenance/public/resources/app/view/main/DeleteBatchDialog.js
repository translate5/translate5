Ext.define('TMMaintenance.view.main.DeleteBatchDialog', {
    extend: 'Ext.Dialog',
    xtype: 'deleteBatchDialog',
    itemId: 'deleteBatchDialog',
    bodyPadding: 20,
    centered: true,
    maximizable: false,
    width: 300,
    bind: {
        html: '{l10n.deleteBatch.warningText}',
    },
    buttons: [
        {
            bind: {
                text: '{l10n.deleteBatch.proceed}',
                hidden: '{!l10n.deleteBatch.proceed}',
            },
            handler: () => Ext.getCmp('searchform').getController().onDeleteBatch(),
        },
        {
            bind: {
                text: '{l10n.deleteBatch.close}',
                hidden: '{!l10n.deleteBatch.close}',
            },
            handler: () => {
                Ext.ComponentQuery.query('#deleteBatchDialog')[0].hide();
            },
        },
    ]
});