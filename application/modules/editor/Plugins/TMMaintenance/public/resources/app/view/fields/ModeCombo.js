Ext.define('TMMaintenance.view.fields.ModeCombo', {
    singleton: true,

    create: function(name) {
        return {
            xtype: 'combobox',
            required: false,
            name: name,
            label: 'Mode',
            disabled: '{!selectedTm}',
            maxWidth: 100,
            bind: {
                disabled: '{!selectedTm}',
            },
            store: [
                {name: 'Contains', value: 'contains'},
                {name: 'Concordance', value: 'concordance'},
                {name: 'Exact', value: 'exact'}
            ],
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
            value: 'contains'
        }
    },
});