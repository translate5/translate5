Ext.define('TMMaintenance.model.Tm', {
    extend: 'TMMaintenance.model.Base',
    fields: [
        {name: 'id', type: 'int'},
        {name: 'name', type: 'string'},
        {name: 'sourceLanguage', type: 'string'},
        {name: 'targetLanguage', type: 'string'},
    ],
});