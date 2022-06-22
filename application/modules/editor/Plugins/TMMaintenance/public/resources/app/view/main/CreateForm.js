Ext.define('TMMaintenance.view.main.CreateForm', {
    extend: 'Ext.form.Panel',
    xtype: 'createform',
    controller: 'createform',
    layout: 'vbox',
    defaultType: 'textfield',
    userCls: '',

    items: [
        {
            required: true,
            label: 'Source text',
            name: 'rawSource',
        },
        {
            required: true,
            label: 'Target text',
            name: 'rawTarget',
        },
    ],

    buttons: [
        {
            text: 'Save',
            handler: 'onSavePress'
        },
        {
            text: 'Cancel',
            handler: 'onCancelPress'
        },
    ]
})
