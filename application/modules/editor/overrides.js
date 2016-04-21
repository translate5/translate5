/**
 * Fixing missing contains method for bufferedstores
 * needed for ext-6.0.0
 * recheck on update
 */
Ext.define('Ext.overrides.fixed.BufferedStore', {
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
Ext.define('Ext.overrides.fixed.PageMap', {
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
Ext.define('Ext.overrides.fixed.ListFilter', {
    override: 'Ext.grid.filters.filter.List',
    getGridStoreListeners: function() {
        if(this.autoStore) {
            return this.callParent(arguments);
        }
        return {};
    }
});

/**
* @property {RegExp}
* @private
* Regular expression used for validating identifiers.
* !!!WARNING!!! This  and next override is made to allow ids starting with a digit. This is due to the bulk of legacy data
*/
Ext.validIdRe = /^[a-z0-9_][a-z0-9\-_]*$/i;
Ext.define('Ext.overrides.dom.Element', {
    override: 'Ext.dom.Element',
    
    constructor: function(dom) {
        this.validIdRe = Ext.validIdRe;
        this.callParent(arguments);
    }
});

/**
 * fixing for this bug: https://www.sencha.com/forum/showthread.php?288898-W-targetCls-is-missing.-This-may-mean-that-getTargetEl()-is-being-overridden-but-no/page3
 * needed for ext-6.0.0
 * recheck on update
 */
Ext.define('Ext.overrides.layout.container.Container', {
  override: 'Ext.layout.container.Container',

  notifyOwner: function() {
    this.owner.afterLayout(this);
  }
});

/**
 * enables the ability to set a optional menuOffset in menus
 * needed for ext-6.0.0
 * this override must be revalidated on extjs update
 */
Ext.override(Ext.menu.Item, {
    deferExpandMenu: function() {
        var me = this;

        if (!me.menu.rendered || !me.menu.isVisible()) {
            me.parentMenu.activeChild = me.menu;
            me.menu.parentItem = me;
            me.menu.parentMenu = me.menu.ownerCt = me.parentMenu;
            me.menu.showBy(me, me.menuAlign, me.menuOffset);
        }
    }
});


/**
 * Fixing EXT6UPD-131 (fixed natively in ext-6.0.1, must be removed then!)
 */
Ext.override(Ext.grid.filters.filter.TriFilter, {
    deactivate: function () {
        var me = this,
            filters = me.filter,
            f, filter, value;

        if (!me.countActiveFilters() || me.preventFilterRemoval) {
            return;
        }

        me.preventFilterRemoval = true;

        for (f in filters) {
            filter = filters[f];

            value = filter.getValue();
            if (value || value === 0) {
                me.removeStoreFilter(filter);
            }
        }

        me.preventFilterRemoval = false;
    }
});