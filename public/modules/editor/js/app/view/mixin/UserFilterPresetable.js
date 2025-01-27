Ext.define('Editor.view.mixin.UserFilterPresetable', {

    /**
     * Handlers to be added to view controller
     */
    control: {
        '#userFilterPresetCreateBtn': {
            click: 'onCreateUserFilterPresetClick'
        },
        '#userFilterPresetDeleteBtn': {
            click: 'onDeleteUserFilterPresetClick'
        },
        '#userFilterPresetCombo': {
            change: 'onFilterPresetChange'
        },
        '#resetFilterBtn':{
            click:'onResetFilterButtonClick'
        },
    },

    /**
     * Apply UI items and their handlers to manage presets
     *
     * @param viewController
     * @param view
     */
    init: function(viewController, view){
        view.stateKey = view.is('grid') ? view.xtype : view.down('grid').itemId;

        // Add ui items into view's toolbar
        view.down('toolbar').add([
            {
                xtype: 'combobox',
                queryMode: 'local',
                displayField: 'title',
                itemId: 'userFilterPresetCombo',
                valueField: 'id',
                store: {
                    data: Editor.data.app.user.filterPreset[view.stateKey].store,
                },
                value: Editor.data.app.user.filterPreset[view.stateKey].value,
                fieldLabel: Editor.data.l10n.user.filterPreset.choices.label,
                labelWidth: Ext.create('Ext.util.TextMetrics').getWidth(
                    Editor.data.l10n.user.filterPreset.choices.label
                ) + 5,
                bind: {
                    fieldLabel: '{l10n.user.filterPreset.choices.label}',
                    tooltip: '{l10n.user.filterPreset.choices.tooltip}'
                }
            },
            {
                xtype: 'button',
                itemId: 'userFilterPresetCreateBtn',
                text: Editor.data.l10n.user.filterPreset.create.text,
                tooltip: Editor.data.l10n.user.filterPreset.create.tooltip,
                bind: {
                    text: '{l10n.user.filterPreset.create.text}',
                    tooltip: '{l10n.user.filterPreset.create.tooltip}'
                }
            },
            {
                xtype: 'button',
                itemId: 'userFilterPresetDeleteBtn',
                text: Editor.data.l10n.user.filterPreset.delete,
                disabled: !Editor.data.app.user.filterPreset[view.stateKey].value,
                bind: {
                    text: '{l10n.user.filterPreset.delete}'
                }
            },{
                xtype: 'button',
                glyph: 'f068@FontAwesome5FreeSolid',
                itemId: 'resetFilterBtn',
                bind: {
                    text: '{l10n.projectGrid.strings.resetFilterText}',
                    tooltip: '{l10n.projectGrid.strings.resetFilterText}'
                }
            }
        ]);

        // Add listeners
        viewController.control(this.control);
    },

    /**
     * Prompt preset title prior preset creation
     */
    onCreateUserFilterPresetClick: function (btn, evt, title) {
        Ext.Msg.prompt(
            Editor.data.l10n.user.filterPreset.create.text, // title
            Editor.data.l10n.user.filterPreset.create.window + '<br>&nbsp;', // message
            this.doCreateUserFilterPreset,             // handler
            this,                                      // scope
            false,                                     // multiline
            title                                      // value to pre-fill textfield with
        );
    },

    /**
     * Do create preset
     *
     * @param answer
     * @param title
     */
    doCreateUserFilterPreset: function(answer, title) {
        var combo = this.getView().down('#userFilterPresetCombo');

        // If prompt was cancelled - return
        if (answer === 'cancel') {
            return;
        }

        // Make create-request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'userfilterpreset',
            method: 'POST',
            params: {
                title: title,
                panel: this.getView().stateKey
            },
            success: xhr => {

                // Get created preset data
                var created = Ext.decode(xhr.responseText).created;

                // Add into the choices store
                combo.getStore().add(created);
                if (combo.overflowClone) {
                    combo.overflowClone.getStore().add(created);
                }

                // Make newly created preset to be selected
                combo.suspendEvent('apply');
                combo.setValue(created.id);
                combo.resumeEvent('apply');
            },
            failure: xhr => {

                // If we reached this line, it means validation went wrong, so:
                // 1. reopen preset title prompt and apply last submitted title to the prompt's textfield
                // 2. mark prompt's textfield as invalid and specify the reason
                this.onCreateUserFilterPresetClick(null, null, title);
                Ext.Msg.down('textfield').markInvalid(Ext.decode(xhr.responseText)?.msg);
            }
        });
    },

    /**
     * Apply filters from selected preset
     *
     * @param combo
     * @param nowValue
     * @param wasValue
     */
    onFilterPresetChange: function(combo, nowValue, wasValue) {
        var nowPreset = nowValue && combo.getStore().getById(nowValue),
            wasPreset = wasValue && combo.getStore().getById(wasValue),
            filters = nowPreset && Ext.JSON.decode(nowPreset.get('state')).storeState.filters,
            deleteBtn = this.getView().down('#userFilterPresetDeleteBtn'),
            cleared;

        // If value was an ID of a preset, but now it's not because it is now fully or partially cleared
        if (wasPreset) {

            // Setup wasPreset flag
            combo.wasPreset = true;
        }

        // Setup a flag indicating whether combobox value was fully cleared after some value was really selected
        cleared = combo.wasPreset && !nowValue;

        // If some preset is really selected or value is null - apply filters (even if null-filters)
        // Else do nothing, which might be the case when user started clearing the value in the combobox
        // by pressing BACKSPACE or doing other changes except really selecting some option from the list
        if (nowPreset || cleared) {

            // Apply filters, even if they're null i.e. they will be cleared
            if (!combo.isSuspended('apply')) {
                this.applyFilters(filters);
            }

            // Maintain delete-button state
            deleteBtn.setDisabled(cleared);

            // If previously selected preset was cleared - set wasPreset flag back to false
            if (cleared) {
                combo.wasPreset = false;
            }
        }
    },

    /**
     * Handler for click-event for a button to delete currently selected filter preset
     */
    onDeleteUserFilterPresetClick: function() {
        var presetCombo = this.getView().down('#userFilterPresetCombo'),
            presetId = presetCombo.getValue(),
            presetStore = presetCombo.getStore();

        // Make delete-request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'userfilterpreset/' + presetId,
            method: 'DELETE',
            success: xhr => {

                // Clear preset combo
                presetCombo.setValue(null);

                // Remove preset from store
                presetStore.remove(presetStore.getById(presetId));

                // Remove preset from overflowClone's store, if any
                if (presetCombo.overflowClone) {
                    presetStore = presetCombo.overflowClone.getStore();
                    presetStore.remove(presetStore.getById(presetId));
                }
            }
        });
    },

    /**
     * Apply given filters instead of the current ones, or just deactivate the current ones
     *
     * @param filters[]|null
     */
    applyFilters: function(filters) {
        var view = this.getView(),
            grid = view.is('grid') ? view : view.down('grid'),
            cols = grid.columnManager.getColumns(),
            store = grid.getStore();

        // Prevent store from being reloaded until filters state setup done
        store.getFilters().beginUpdate();

        // Deactivate current filters for all columns, if any
        for (var i = 0; i < cols.length; i++) {

            // Get column filter
            var filter = cols[i].filter;

            // If exists - deactivate
            if (filter && filter.isGridFilter) {
                filter.setActive(false);
            }
        }

        // Remove current filters from store
        store.getFilters().removeAll();

        // Apply filters from preset
        if (filters) {
            grid.activateGridColumnFilter(filters);
        }

        // Reload store with respect to the updated filters state
        store.getFilters().endUpdate();
    },

    /**
     * Reset filter button click handler
     */
    onResetFilterButtonClick: function() {
        var me = this, combo = me.getView().down('#userFilterPresetCombo');

        // If some preset is selected
        if (combo.getValue()) {
            combo.setValue('');
        } else {
            me.applyFilters(null);
        }
    },
});