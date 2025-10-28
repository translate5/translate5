Ext.define('Editor.view.segments.filters.IsRepeated', {
    extend: 'Ext.grid.filters.filter.List',
    alias: 'grid.filter.isrepeatedlist',
    type: 'list',

    itemDefaults: {
        listeners: {
            checkchange: item => item.parentMenu.parentMenu.ownerCmp.filter.syncIncludeFirstState()
        }
    },

    createMenuItems: function (store) {
        var me = this,
            menu = me.menu,
            len = store.getCount(),
            contains = Ext.Array.contains,
            listeners, itemDefaults, record, gid, idValue, idField, labelValue, labelField, i, item, processed;

        // B/c we're listening to datachanged event, we need to make sure there's a menu.
        if (len && menu) {
            itemDefaults = me.getItemDefaults();
            menu.suspendLayouts();
            menu.removeAll(true);
            gid = me.single ? Ext.id() : null;
            idField = me.idField;
            labelField = me.labelField;

            processed = [];

            for (i = 0; i < len; i++) {
                record = store.getAt(i);
                idValue = record.get(idField);
                labelValue = record.get(labelField);

                // Only allow unique values.
                if (labelValue == null || contains(processed, idValue)) {
                    continue;
                }

                processed.push(labelValue);

                // Note that the menu items will be set checked in filter#activate() if the value of the menu
                // item is in the cfg.value array.
                item = menu.add(Ext.apply({
                    text: labelValue,
                    group: gid,
                    itemId: 'item' + idValue,
                    value: idValue,
                    checkHandler: me.onCheckChange,
                    userCls: record.get('userCls'),                                           // +
                    scope: me
                }, itemDefaults));
            }

            menu.resumeLayouts(true);

            me.syncIncludeFirstState(!!~this.filter.getValue().indexOf(4));                   // +
        }
    },

    syncCheckedState: function() {
        var me = this,
            menu = me.menu,
            value = me.filter.getValue(),
            i, len, checkItem;

        // If menu is not yet instantiated - do nothing
        if (!menu) {
            return;
        }

        // Foreach menucheckitem
        for (i = 0, len = menu.items.length; i < len; i++) {

            // Get item
            checkItem = menu.items.getAt(i);

            // Set checked based on whether it's value in the list of filter's values
            checkItem.setChecked(Ext.Array.indexOf(value, checkItem.value) > -1, true);
        }
    },

    syncIncludeFirstState: function(checked) {
        var me = this,
            menu = me.menu;

        // If menu is not yet instantiated - do nothing
        if (!menu) {
            return;
        }

        // Prepare shortcuts
        var none = menu.down('[value=0]'),
            source = menu.down('[value=1]'),
            target = menu.down('[value=2]'),
            includeFirst = menu.down('[value=4]');

        // Toggle/checked logic for includeFirst-item
        if (source.checked && target.checked) {
            includeFirst.setDisabled(true);
            includeFirst.setChecked(true);
            none.setDisabled(true);
            none.setChecked(false);
        } else if (none.checked) {
            includeFirst.setDisabled(true);
            includeFirst.setChecked(false);
        } else if (source.checked || target.checked) {
            includeFirst.setDisabled(false);
            none.setDisabled(false);
        } else {
            includeFirst.setDisabled(!checked);
            includeFirst.setChecked(false);
            none.setDisabled(false);
        }
    },
});
