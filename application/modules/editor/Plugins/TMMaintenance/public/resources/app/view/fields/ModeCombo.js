Ext.define('TMMaintenance.view.fields.ModeCombo', {
    singleton: true,

    create: function(name) {
        return {
            xtype: 'combobox',
            required: false,
            name: name,
            disabled: '{!selectedTm}',
            maxWidth: 100,
            bind: {
                disabled: '{!selectedTm}',
                label: '{l10n.searchForm.tm}',
                hidden: '{!l10n.selectTm.tm}',
                store: '{l10n.modeStore}',
            },
            store: [],
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
            value: 'contains'
        }
    },
});