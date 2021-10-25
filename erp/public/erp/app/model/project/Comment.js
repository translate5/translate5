Ext.define('Erp.model.project.Comment', {
    extend: 'Erp.model.CommentBase',
    foreignField: 'orderId',

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
    cls.proxyConfig.url = 'erp/ordercomment';
});