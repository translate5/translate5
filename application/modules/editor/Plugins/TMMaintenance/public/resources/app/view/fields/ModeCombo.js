Ext.define('TMMaintenance.view.fields.ModeCombo', {
    singleton: true,

    create: function(name, label) {
        return {
            xtype: 'combobox',
            required: false,
            name: name,
            disabled: '{!selectedTm}',
            labelWidth: 120,
            bind: {
                disabled: '{!selectedTm}',
                label: '{l10n.searchForm.'+label+'}',
                hidden: '{!l10n.selectTm.tm}',
                store: '{l10n.modeCombo}',
            },
            store: [],
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
            value: 'contains'
        }
    },
});