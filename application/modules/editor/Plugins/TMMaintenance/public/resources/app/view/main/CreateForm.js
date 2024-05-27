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
            name: 'source',
            bind: {
                label: '{l10n.createForm.sourceText}',
                hidden: '{!l10n.createForm.sourceText}',
            },
        },
        {
            required: true,
            name: 'target',
            bind: {
                label: '{l10n.createForm.targetText}',
                hidden: '{!l10n.createForm.targetText}',
            },
        },
    ],

    buttons: [
        {
            handler: 'onSavePress',
            bind: {
                text: '{l10n.createForm.save}',
                hidden: '{!l10n.createForm.save}',
            },
        },
        {
            handler: 'onCancelPress',
            bind: {
                text: '{l10n.createForm.cancel}',
                hidden: '{!l10n.createForm.cancel}',
            },
        },
    ]
})
