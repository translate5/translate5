Ext.define('Erp.plugins.Moravia.model.Type', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'name', type: 'string'},
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest', 
    url: Erp.data.restpath+'plugins_moravia_type',
    reader : {
      rootProperty: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      rootProperty: 'data',
      writeAllFields: false
    }
  }
});