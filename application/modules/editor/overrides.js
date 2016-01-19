Ext.define('Ext.ux.fixed.BufferedStore', {
    override: 'Ext.data.BufferedStore',
    contains: function(record) {
        return this.indexOf(record) > -1;
    }
});
