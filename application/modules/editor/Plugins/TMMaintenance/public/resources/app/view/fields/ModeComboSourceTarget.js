Ext.define('TMMaintenance.view.fields.ModeComboSourceTarget', {
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