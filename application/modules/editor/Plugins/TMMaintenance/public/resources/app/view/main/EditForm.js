Ext.define('TMMaintenance.view.main.EditForm', {
    extend: 'Ext.form.Panel',
    xtype: 'editform',
    controller: 'editform',
    layout: 'vbox',
    defaultType: 'textfield',
    userCls: '',

    items: [
        {
            required: true,
            name: 'rawSource',
        },
        {
            required: true,
            name: 'rawTarget',
        },
    ],

    buttons: [
        {
            text: 'Save',
            handler: 'onSavePressed'
        },
        {
            text: 'Cancel',
            handler: 'onCancelPressed'
        },
    ]
})
