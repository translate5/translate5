/**
 * This file contains several fixes for ExtJS 5
 */

//Fixing missing contains method in buffered Store, see http://www.sencha.com/forum/showthread.php?289038-Is-editing-records-in-buffered-store-supported
Ext.define('Ext.ux.fixed.BufferedStore', {
    override: 'Ext.data.BufferedStore',
    contains: function(record) {
        return this.indexOf(record) > -1;
    }
});

//Set a default date filter format:
Ext.define('Ext.ux.fixed.DateFilter', {
    override: 'Ext.grid.filters.filter.Date',
    getDateFormat: function() {
        return 'Y-m-d H:i:s';
    }
});

//Enable setting filter values in list filters
//siehe auch: http://stackoverflow.com/questions/25975456/how-to-use-gridfilters-plugin-and-programmatically-clear-set-the-filters
Ext.define('Ext.ux.fixed.ListFilter', {
    override: 'Ext.grid.filters.filter.List',
    setValue: function(values) {
        var me = this,
            len, i, items, item;

        if(!values) {
            me.callParent();
            return;
        }

        if(!me.menu){
            me.createMenu();
            me.setStoreFilter();
        }

        len = values.length;
        items = me.menu.items;
        for (i = 0, len = items.length; i < len; i++) {
            item = items.getAt(i);
            item.setChecked(Ext.Array.contains(values, item.value), true);
        }
        me.callParent();
        me.setActive(false);
        me.setActive(true);
        return;
    }
});

//Empty value will be ignored now.
//(If empty value exist, then this will be treated as a valid record, and allowBlank=false will be ignored)
Ext.define('Erp.ux.form.MultiSelect', {
    override: 'Ext.ux.form.MultiSelect',
    setupValue: function(value) {
        var delimiter = this.delimiter,
            valueField = this.valueField,
            i = 0,
            out,
            len,
            item;
            
        if (Ext.isDefined(value)) {
            if (delimiter && Ext.isString(value)) {
                value = value.split(delimiter);
            } else if (!Ext.isArray(value)) {
                value = [value];
            }
        
            for (len = value.length; i < len; ++i) {
                item = value[i];
                if (item && item.isModel) {
                    value[i] = item.get(valueField);
                }
            }
            out = Ext.Array.unique(value);
        } else {
            out = [];
        }
        if(out.length>0 && out[0]==""){
            out = [];
        }
        return out;
    }
});