Ext.define('TMMaintenance.view.fields.ModeComboSourceTarget', {
    singleton: true,

    create: function(name, label) {
        return {
            xtype: 'combobox',
            required: false,
            name: name,
            disabled: '{!selectedTm}',
            labelWidth: 100,
            bind: {
                disabled: '{!selectedTm}',
                label: '{l10n.searchForm.'+label+'}',
                hidden: '{!l10n.selectTm.tm}',
                store: '{l10n.modeComboSourceTarget}',
            },
            store: [],
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
            value: 'contains'
        }
    },
});