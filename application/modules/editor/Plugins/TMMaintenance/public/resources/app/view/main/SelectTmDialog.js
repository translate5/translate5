Ext.define('TMMaintenance.view.main.SelectTmDialog', {
    extend: 'Ext.Dialog',
    xtype: 'selectTmDialog',
    itemId: 'selectTmDialog',
    title: 'Select TM',
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
            text: 'Close',
            handler: () => Ext.ComponentQuery.query('#selectTmDialog')[0].hide(),
        },
    ]
});