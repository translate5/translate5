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
            name: 'SourceText',
        },
        {
            required: true,
            name: 'TargetText',
        },
    ],

    buttons: [
        {
            text: 'Save',
            handler: 'onSave'
        }
    ]
})
