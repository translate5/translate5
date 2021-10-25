/**
* This tag filter allows you the filter by include/exclude values defined in two different tag fields.

* Example Tag Filter Usage:
*
*     var names = Ext.create('Ext.data.Store', {
*         fields: ['id','show','rating'],
*         data: [
*             {id: 0, name: 'Aleksandar'},
*             {id: 1, name: 'Aleksandar - 2' },
*             {id: 2, name: 'Aleksandar - 3'},
*             {id: 3, name: 'Aleksandar - 4'}
*         ]
*     });
*   
*     Ext.create('Ext.grid.Panel', {
*         renderTo: Ext.getBody(),
*         title: 'Tag filter',
*         height: 250,
*         width: 350,
*         store: names,
*         plugins: 'gridfilters',
*         columns: [{
*             dataIndex: 'id',
*             text: 'ID',
*             width: 50,
*             filter: {
*				type: 'tagfilter',
*				emptyText:'-- Bitte auswählen --',
*				fields: {
*		            in: {
*		            	fieldLabel:'Enthalten',
*		            	store: names
*		            },
*		            notInList: {
*		            	fieldLabel:'Ausnehmen',
*		            	store: names
*		            }
*		        }
*            }
*         },{
*             dataIndex: 'name',
*             text: 'Name',
*             flex: 1                  
*         }]
*     });
*/
Ext.define('Erp.plugins.Moravia.view.TagFilter', {
	extend: 'Ext.grid.filters.filter.TriFilter',
    alias: ['grid.filter.tagfilter'],
 
    uses: ['Ext.form.field.Tag'],
 
    type: 'tagfilter',
 
    config: {
        /**
         * @cfg {Object} [fields]
         * Configures field items individually. These properties override those defined
         * by `{@link #itemDefaults}`.
         *
         * Example usage:
         *
         *      fields: {
         *          // Override itemDefaults for one field:
         *          gt: {
         *              width: 200
         *          }
         *
         *          // "lt" and "eq" fields retain all itemDefaults
         *      },
         */
        fields: {
            in: {
                fieldLabel:'Include',
                //iconCls: Ext.baseCSSPrefix + 'grid-filters-in',
                //margin: '0 0 3px 0'
            },
            notInList: {
                fieldLabel:'Exclude',
                //iconCls: Ext.baseCSSPrefix + 'grid-filters-lt',
                //margin: '0 0 3px 0'
            }
        },

        menuItems: ['in', 'notInList'],
    },

    constructor: function (config) {
        var me = this,
            stateful = false,
            filter = {},
            filterIn, filterNotInList, value, operator;
 
        me.callParent([config]);
 
        value = me.value;
 
        filterIn = me.getStoreFilter('in');
        filterNotInList = me.getStoreFilter('notInList');
 
        if (filterIn || filterNotInList) {
            // This filter was restored from stateful filters on the store so enforce it as active. 
            stateful = me.active = true;
            if (filterIn) {
                me.onStateRestore(filterIn);
            }
            if (filterNotInList) {
                me.onStateRestore(filterNotInList);
            }
        } else {
            // Once we've reached this block, we know that this grid filter doesn't have a stateful filter, so if our 
            // flag to begin saving future filter mutations is set we know that any configured filter must be nulled 
            // out or it will replace our stateful filter. 
            if (me.grid.stateful && me.getGridStore().saveStatefulFilters) {
                value = undefined;
            }
 
            // TODO: What do we mean by value === null ? 
            me.active = me.getActiveState(config, value);
        }
 
        // Note that stateful filters will have already been gotten above. If not, or if all filters aren't stateful, we 
        // need to make sure that there is an actual filter instance created, with or without a value. 
        // 
        // Note use the alpha alias for the operators ('in', 'notInList') so they map in Filters.onFilterRemove(). 
        filter.in = filterIn || me.createFilter({
            operator: 'in',
            value: (!stateful && value && Ext.isDefined(value.in)) ?
                value.in :
                null
        }, 'in');
 
        filter.notInList = filterNotInList || me.createFilter({
            operator: 'notInList',
            value: (!stateful && value && Ext.isDefined(value.notInList)) ?
                value.notInList :
                null
        }, 'notInList');
 
        me.filter = filter;
 
        if (me.active) {
            me.setColumnActive(true);
            if (!stateful) {
                for (operator in value) {
                    me.addStoreFilter(me.filter[operator]);
                }
            }
            // TODO: maybe call this.activate? 
        }
    },

    setValue: function (value) {
        var me = this,
            filters = me.filter,
            add = [],
            remove = [],
            active = false,
            filterCollection = me.getGridStore().getFilters(),
            filter, v, i, rLen, aLen;
 
        if (me.preventFilterRemoval) {
            return;
        }
 
        me.preventFilterRemoval = true;
 
        if ('in' in value) {
            v = value.in;
            if (v || v === 0) {
                add.push(filters.in);
                filters.in.setValue(v);
            } else {
                remove.push(filters.in);
            }
        } 
        if ('notInList' in value) {
            v = value.notInList;
            if (v || v === 0) {
                add.push(filters.notInList);
                filters.notInList.setValue(v);
            } else {
                remove.push(filters.notInList);
            }
        }
 
        // Note that we don't want to update the filter collection unnecessarily, so we must know the 
        // current number of active filters that this TriFilter has +/- the number of filters we're 
        // adding and removing, respectively. This will determine the present active state of the 
        // TriFilter which we can use to not only help determine if the condition below should pass 
        // but (if it does) how the active state should then be updated. 
        rLen = remove.length;
        aLen = add.length;
        active = !!(me.countActiveFilters() + aLen - rLen);
 
        if (rLen || aLen || active !== me.active) {
            // Begin the update now because the update could also be triggered if #setActive is called. 
            // We must wrap all the calls that could change the filter collection. 
            filterCollection.beginUpdate();
 
            if (rLen) {
                for (i = 0; i < rLen; i++) {
                    filter = remove[i];
 
                    me.fields[filter.getOperator()].setValue(null);
                    filter.setValue(null);
                    me.removeStoreFilter(filter);
                }
            }
 
            if (aLen) {
                for (i = 0; i < aLen; i++) {
                    me.addStoreFilter(add[i]);
                }
            }
 
            me.setActive(active);
            filterCollection.endUpdate();
        }
 
        me.preventFilterRemoval = false;
    },

    //<locale> 
    /**
     * @cfg {String} emptyText 
     * The empty text to show for each field.
     */
    emptyText: 'Select value',
    //</locale> 
 
    itemDefaults: {
        xtype: 'tagfield',
        queryMode: 'local',
        displayField: 'name',
        valueField: 'id'
    },
 
    createMenu: function () {
        var me = this,
            listeners = {
                scope: me,
                change: me.onTagFieldValueChange,
            },
            itemDefaults = me.getItemDefaults(),
            menuItems = me.menuItems,
            fields = me.getFields(),
            field, i, len, key, item, cfg;
 
        me.callParent();
 
        me.fields = {};
 
        for (i = 0, len = menuItems.length; i < len; i++) {
            key = menuItems[i];
            if (key !== '-') {
                field = fields[key];
 
                cfg = {
                    labelClsExtra: Ext.baseCSSPrefix + 'grid-filters-icon ' + field.iconCls
                };
 
                if (itemDefaults) {
                    Ext.merge(cfg, itemDefaults);
                }
 
                Ext.merge(cfg, field);
                cfg.emptyText = cfg.emptyText || me.emptyText;
                delete cfg.iconCls;
 
                me.fields[key] = item = me.menu.add(cfg);
 
                item.filter = me.filter[key];
                item.filterKey = key;
                item.on(listeners);
            } else {
                me.menu.add(key);
            }
        }
    },
 
    getValue: function (field) {
        var value = {};
        value[field.filterKey] = field.getValue();
        return value;
    },
 
    /**
     * @private
     * Handler called when the tag field value is selected
     */
    onTagFieldValueChange: function (field,newValue,oldValue,eOpts) {
        var value = {};
 
        value[field.filterKey] = newValue;
 
        this.setValue(value);
    }
});