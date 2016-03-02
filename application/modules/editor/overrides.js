Ext.define('Ext.ux.fixed.BufferedStore', {
    override: 'Ext.data.BufferedStore',
    contains: function(record) {
        return this.indexOf(record) > -1;
    }
});


/**
 * Fix for EXT6UPD-33
 * needed for ext-6.0.0
 * should be solved natively with ext-6.0.1
 */
Ext.define('Ext.ux.fixed.PageMap', {
    override: 'Ext.data.PageMap',
    getByInternalId: function(internalId) {
        var index = this.indexMap[internalId];
        if (index != null) {
            return this.getAt(index);
        }
    }
});

/**
 * Fix for EXT6UPD-46
 * needed for ext-6.0.0
 * should be solved natively with ext-6.0.1
 */
Ext.define('Ext.ux.fixed.ListFilter', {
    override: 'Ext.grid.filters.filter.List',
    getGridStoreListeners: function() {
        if(this.autoStore) {
            return this.callParent(arguments);
        }
        return {};
    }
});



