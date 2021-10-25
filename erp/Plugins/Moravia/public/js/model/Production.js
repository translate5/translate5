/*
 */

Ext.define('Erp.plugins.Moravia.model.Production', {
    extend: 'Erp.model.Project',
    fields: [{
        type:'int',
        name:'productionId'
    },{
        type:'int',
        name:'orderId'
    },{
        type:'string',
        name:'endCustomer'
    },{
        type:'string',
        name:'projectNameEndCustomer'
    },{
        type:'string',
        name:'productionType'
    },{
        type: 'date',
        name:'submissionDate',
        dateReadFormat:'Y-m-d H:i:s',
        dateWriteFormat:'Y-m-d'
    },{
        type:'string',
        name:'pmCustomer'
    },{
        type: 'float',
        name: 'preliminaryWeightedWords',
        allowNull:true
    },{
        type: 'float',
        name: 'weightedWords',
        allowNull:true
    },{
        type: 'float',
        name: 'hours',
        allowNull:true
    },{
        type: 'float',
        name: 'handoffValue',
    },{
        type: 'string',
        name: 'prNumber',
    },{
        type: 'boolean',
        name: 'balanceValueCheck',
        allowNull:true
    },{
        type: 'int',
        name: 'handoffNumber',
    }]
});