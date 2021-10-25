Ext.define('Erp.model.purchaseOrder.Comment', {
    extend: 'Erp.model.CommentBase',
    foreignField: 'purchaseOrderId',

    constructor: function() {
        var me = this;
        me.processCommentBase(me);
        me.callParent(arguments);
    },

    processCommentBase: function(config) {
        config.proxy.writer.foreignField = config.foreignField;
        config.proxy.reader.foreignField = config.foreignField;
    }
}, function(cls){
    cls.proxyConfig = Ext.clone(cls.proxyConfig);
    cls.proxyConfig.url = 'erp/purchaseordercomment';
});