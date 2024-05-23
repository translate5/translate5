/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 		     http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

Ext.define('Editor.plugins.MatchAnalysis.view.admin.pricing.PresetGridController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.Editor.plugins.MatchAnalysis.view.admin.pricing.PresetGridController',

    /**
     * Delete preset after server-side confirmation
     *
     * @param view
     * @param rowIdx
     * @param colIdx
     * @param actionCfg
     * @param evt
     * @param rec
     */
    deletePreset: function(view, rowIdx, colIdx, actionCfg, evt, rec) {
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpreset',
            method: 'DELETE',
            params: {
                presetId : rec.get('id')
            },
            success: xhr => rec.store.remove(rec)
        });
    },

    /**
     * Prompt name for the preset clone
     *
     * @param view
     * @param rowIdx
     * @param colIdx
     * @param actionCfg
     * @param evt
     * @param rec
     */
    clonePreset: function (view, rowIdx, colIdx, actionCfg, evt, rec) {
        Ext.Msg.prompt(Editor.data.l10n.MatchAnalysis.pricing.preset.clone, '',
            (btn, text) => this.doClonePreset(btn, text, rec, view),
            this, false, rec.get('name'));
    },

    /**
     * Do clone
     *
     * @param btn
     * @param text
     * @param rec
     * @param view
     */
    doClonePreset: function(btn, text, rec, view) {

        // If prompt was cancelled - return
        if (btn === 'cancel') {
            return;
        }

        // Make clone-request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpreset/clone',
            params: {
                presetId: rec.get('id'),
                name: text,
                customerId: view.ownerGrid.getCustomer()?.get('id')
            },
            success: function (response) {

                // Get clone data
                var clone = Ext.decode(response.responseText).clone;

                // Add into the store
                rec.store.add(clone);

                // Start edit clone description
                this.startEditDescription(clone.id);
            },
            failure: function(response) {

                // If we reached this line, it means validation went wrong, so:
                // 1. reopen clone name prompt
                // 2. apply last submitted clone name to the prompt's textfield
                // 3. mark prompt's textfield as invalid and specify the reason
                this.clonePreset(view, null, null, null, null, rec);
                Ext.Msg.down('textfield').setValue(text);
                Ext.Msg.down('textfield').markInvalid(Ext.decode(response.responseText)?.msg);
            },
            scope: this,
        });
    },

    /**
     * Method used for getting the disability of 'delete' action-icon
     *
     * @param view
     * @param rowIndex
     * @param colIndex
     * @param item
     * @param record
     * @returns {*|boolean}
     */
    isDeleteDisabled: function(view, rowIndex, colIndex, item, record) {
        return record.get('isDefault')
            || record.get('name') === Editor.data.plugins.MatchAnalysis.pricing.systemDefaultPresetName
            || view.grid.isCustomerGrid && !record.get('customerId');
    },

    /**
     * Method used for getting the disability of 'edit' action-icon
     *
     * @param view
     * @param rowIndex
     * @param colIndex
     * @param item
     * @param record
     * @returns {boolean}
     */
    isEditDisabled: function(view, rowIndex, colIndex, item, record) {
        return record.get('name') === Editor.data.plugins.MatchAnalysis.pricing.systemDefaultPresetName
            || view.ownerGrid.isCustomerGrid && !record.get('customerId');
    },

    /**
     * Collect params consisting of [modified props => their values] pairs
     * and rend request to apply those changes server-side
     *
     * @param plugin
     * @param context
     */
    onPresetEdit: function(plugin, context) {

        // Prepare params
        var params = {}, prop;
        for (prop in context.record.modified) {
            params[prop] = context.record.get(prop);
        }
        params.presetId = context.record.get('id');

        // Make request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpreset',
            method: 'PUT',
            params: params,
            success: () => context.record.commit()
        });
    },

    /**
     * Open celleditor in description-column for record with given id
     *
     * @param id
     */
    startEditDescription: function(id) {
        var grid = this.getView(),
            rec = grid.getStore().getById(id),
            descCol = grid.getColumnManager().getHeaderByDataIndex('description');

        // Trigger description editing
        grid.editingPlugin.activateCell(grid.getView().getPosition(rec, descCol), /* skipBeforeCheck */ true, /* doFocus*/ true);
    },

    /**
     * Forbid systemDefault editing, show Name prompt
     *
     * @param cellEditPlugin
     * @param cellContext
     * @returns {boolean}
     */
    onBeforeEdit: function(cellEditPlugin, cellContext) {
        var grid = this.getView(), rec = cellContext.record, {name, customerId} = rec.getData();

        // Do selection. todo: do we really need that?
        grid.view.select(rec);

        // Prevent change system default and global presets in customer view
        if (name === Editor.data.plugins.MatchAnalysis.pricing.systemDefaultPresetName
            || grid.isCustomerGrid && !customerId) {
            return false;
        }

        // If we're going to edit name
        if (cellContext.field === 'name') {

            // Prompt new name, apply it server-side, and reflect that in grid row
            this.promptPresetName(grid.view, name, rec).then(newName => {
                rec.set('name', newName);
                rec.commit();
            }).catch(Ext.emptyFn);

            // Prevent cell-editing
            return false;
        }
    },

    /**
     * Prompt preset name
     *
     * @param view
     * @param name
     * @param rec
     * @returns {Promise<*|Promise<unknown>|Promise|Promise>}
     */
    promptPresetName: async function(view, name = '', rec = null){
        return new Promise((resolve, reject) => Ext.Msg.prompt(Editor.data.l10n.MatchAnalysis.pricing.preset.rename, '',
            (btn, text) => this.doRenamePreset(btn, text, rec, view, resolve, reject),
        this, false, name || rec.get('name')));
    },

    /**
     * Do rename on server-side and reflect in grid on success, or re-prompt on failure
     *
     * @param btn
     * @param text
     * @param rec
     * @param view
     */
    doRenamePreset: function(btn, text, rec, view, resolve, reject) {

        // If prompt was cancelled - return and reject promise
        if (btn === 'cancel') {
            return reject();
        }

        // Make clone-request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpreset',
            method: 'PUT',
            params: {
                presetId: rec.get('id'),
                name: text
            },
            success: () => resolve(text),
            failure: xhr => {

                // Reject promise
                reject();

                // If we reached this line, it means validation went wrong, so:
                // 1. reopen rename prompt
                // 2. apply last submitted name to the prompt's textfield
                this.promptPresetName(view, text, rec).then(name => {
                    rec.set('name', name);
                    rec.commit();
                }).catch(Ext.emptyFn);

                // 3. mark prompt's textfield as invalid and specify the reason
                Ext.Msg.down('textfield').markInvalid(Ext.decode(xhr.responseText)?.msg);
            }
        })
    },

    /**
     * Filter grid by keyword
     *
     * @param field
     * @param keyword
     */
    filterByKeyword: function(field, keyword) {
        var store = this.getView().getStore(), trimmed = keyword.trim(), rex;

        // If keyword is non-empty
        if (trimmed) {

            // Prepare regex
            rex = new RegExp(Editor.util.Util.escapeRegex(trimmed), 'i');

            // Add filter
            store.addFilter({
                id: 'search',
                filterFn: ({data}) => rex.exec(
                    JSON.stringify(data, ['id', 'name', 'unitType', 'description'])
                )
            })

        // Else remove filter
        } else {
            store.removeFilter('search');
        }

        // Toggle clear-trigger on keyword-field
        field.getTrigger('clear').setVisible(trimmed);
    },

    /**
     * Prompt name for the preset creation
     */
    createPreset: function (btn, evt, name) {
        Ext.Msg.prompt(Editor.data.l10n.MatchAnalysis.pricing.preset.create, '',
            (btn, text) => this.doCreatePreset(btn, text),
            this, false, name);
    },

    /**
     * Do create preset
     *
     * @param answer
     * @param name
     */
    doCreatePreset: function(answer, name) {
        var grid = this.getView();

        // If prompt was cancelled - return
        if (answer === 'cancel') {
            return;
        }

        // Make clone-request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpreset',
            method: 'POST',
            params: {
                name: name,
                customerId: grid.getCustomer()?.get('id')
            },
            success: xhr => {

                // Get created preset data
                var created = Ext.decode(xhr.responseText).created;

                // Add into the store
                grid.getStore().add(created);

                // Start edit description of a newly created preset
                this.startEditDescription(created.id);
            },
            failure: xhr => {

                // If we reached this line, it means validation went wrong, so:
                // 1. reopen clone name prompt and apply last submitted name to the prompt's textfield
                // 2. mark prompt's textfield as invalid and specify the reason
                this.createPreset(null, null, name);
                Ext.Msg.down('textfield').markInvalid(Ext.decode(xhr.responseText)?.msg);
            }
        });
    },

    routesToSet: {
        ':presetId': 'onPresetRoute',
        ':presetId/prices': async function(presetId){
            presetId = parseInt(presetId, 10);
            var grid = this.getView();
            var sel = grid.selection;
            if(sel?.id !== presetId){
                Editor.util.Util.parentRoute();
                sel = await Editor.util.Util.awaitSelection(grid, presetId);
            }
            if(sel?.id === presetId){
                var col = grid.getColumnManager().getHeaderById('presetPrices');
                var cell = grid.view.getCell(grid.store.getById(presetId), col);
                cell?.focus().down('.x-action-col-0')?.dom.click(); // triggers showPricesGrid
            }
        }
    },
    beforeInit: function(view){
        var itemId = view.getItemId(),
            routes = {};
        /** @link Editor.controller.admin.Customer TODO FIXME: support routing in Customer Controller */
        for(const [route, action] of Object.entries(this.routesToSet)){
            routes[view.routePrefix + itemId + '/' + route] = action;
        }
        this.setRoutes(routes);
        this.callParent(arguments);
    },
    /**
     * Event listeners
     */
    control: {
        '#': {
            'selectionchange': function(selModel, selected) {
                if (selected.length) {
                    this.redirectTo(selected[0]);
                }
            }
        }
    },
    /** The argument depends on the routePrefix of the view */
    onPresetRoute: async function(/* presetId */){
        var grid = this.getView(),
            presetIdArgIndex = (grid.routePrefix.match(/\/:/g) || []).length,
            presetId = arguments[presetIdArgIndex];
        Editor.util.Util.closeWindows();
        await Editor.util.Util.awaitStore(grid.getStore());
        var selected = grid.getSelectionModel().getSelectionStart(),
            toSelect = grid.getStore().getById(presetId);
        if(!toSelect){
            var correctRoute = Editor.util.Util.trimLastSlash(Ext.util.History.getToken()) + (selected ? '/' + selected.id : '');
            this.redirectTo(correctRoute);
        } else if(toSelect !== selected){
            grid.setSelection(toSelect);
        }
    },

    showPricesGrid: function(view, rowIdx, colIdx, actionCfg, evt, preset) {
        view.select(rowIdx);
        if(!window.location.hash.endsWith('/prices')){
            window.location.hash += '/prices';
        }
        var pricesGrid = Ext.getCmp('presetPricesGrid');
        if(pricesGrid){
            if(pricesGrid.preset.get('id') === preset.get('id')){
                return;
            } else {
                pricesGrid.close();
                pricesGrid.destroy();
            }
        }
        pricesGrid = Ext.create('Editor.plugins.MatchAnalysis.view.admin.pricing.PresetPricesGrid', {
            preset: preset,
            constrain: true,
            languages: this.languages || (this.languages = Ext.create(Editor.store.admin.Languages)),
            modal: true,
            //renderTo: this.getView().up('viewport'),
            floating: true,
            closable: true,
            height: window.innerHeight - 50,
            width: window.innerWidth - 50,
            maximizable: true,
            //height: '95%',
            //width: '95%',
            resizable: true
        });
        pricesGrid.show();
    },

    /**
     * Handler when a global preset default checkbox is changed
     *
     * @param {Object} col
     * @param {Integer} recordIndex
     * @param {Boolean} checked: the status of the checkbox
     * @param {Editor.model.admin.pricing.PresetModel} record: the record whose row was clicked
     * @returns {boolean}
     */
    onBeforeGlobalCheckChange: function(col, recordIndex, checked, record) {
        var wasDefault;

        // Firings of this event without record what in theory must not happen.
        // Also not-checked events must be dismissed
        if (!record || !checked) {
            return false;
        }

        // Make request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpreset/setdefault',
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            params: {
                presetId: record.get('id')
            },
            success: xhr => {

                // Get response json
                var json = Ext.JSON.decode(xhr.responseText, true);

                // Apply isDefault-flag
                record.set('isDefault', true, {commit: true});

                // Crucial: the default-id is a global that must be updated!
                Editor.data.plugins.MatchAnalysis.pricing.systemDefaultPresetId = record.get('id');

                // If some other preset was system default - clear isDefault flag from there
                if (json.wasDefault) {
                    if (wasDefault = record.store.getById(json.wasDefault)) {
                        wasDefault.set('isDefault', false, {commit: true});
                    }
                }
            }
        });

        // Return false as isDefault-flag is applied inside success-callback above
        return false;
    },

    /**
     * Handler when a customer-specific preset default checkbox is changed
     * There is always a row to be highlighted and one to be unhighlighted
     * The exception is, when system default is (de)selected as customer default - then don't refresh old
     * @param {Object} col
     * @param {Integer} recordIndex
     * @param {Boolean} checked: the status of the checkbox
     * @param {Editor.model.admin.pricing.PresetModel} record: the record whose row was clicked
     * @returns {boolean}
     */
    onBeforeCustomerCheckChange: function(col, recordIndex, checked, record){
        // at times extJs fires this event without record what in theory must not happen
        if(!record){
            return;
        }
        var gridView = col.getView(),
            customer = gridView.grid.getCustomer(),
            customerId = customer.id,
            oldPresetId = customer.get('defaultPricingPresetId'),
            newChecked = (oldPresetId !== record.id), // find-params: ... startIndex, anyMatch, caseSensitive, exactMatch
            newPresetId = newChecked ? record.id : null;
        gridView.select(record);
        Ext.Ajax.request({
            url: Editor.data.restpath + 'customermeta',
            method: 'PUT',
            params: {
                id: customerId,
                data: Ext.encode({
                    defaultPricingPresetId: newPresetId
                })
            },
            success: function() {
                var storeId, store, sCustomer;
                // unfortunately there are two customer stores, which both act as source for the TaskImport customer selector, so we have to update them both
                for(storeId of ['customersStore', 'userCustomers']){
                    store = Ext.getStore(storeId);
                    sCustomer = (store) ? store.getById(customerId) : null;
                    if(sCustomer){
                        sCustomer.set('defaultPricingPresetId', newPresetId, { commit: true, silent: true });
                    }
                }
                // refresh the grid for the changed record
                gridView.refreshNode(record);
                if(oldPresetId !== null){
                    // if there was a old record, refresh the grid for the old record
                    var preset = gridView.getStore().getById(oldPresetId);
                    if(preset){
                        gridView.refreshNode(preset);
                    }
                }
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
        return false; // checked state handled manually via view.refresh
    },

    setEditableCellHint: function(view, record, metaData) {
        if (this.isEditDisabled(view, null, null, null, record)) {
            return;
        }
        var hint = view.up('[viewModel]').getViewModel().get('l10n.editableCellHint');
        metaData.tdAttr = 'data-qtip="' + hint + '"';
    },

    editableCellRenderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
        this.setEditableCellHint(view, record, metaData);

        return Ext.String.htmlEncode(value);
    },

    editablePriceAdjustmentCellRenderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
        this.setEditableCellHint(view, record, metaData);
        return Ext.util.Format.number(Ext.valueFrom(value, ''), '0.00');
    },
    editableUnitTypeCellRenderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
        this.setEditableCellHint(view, record, metaData);
        return Editor.data.l10n.MatchAnalysis.pricing.preset.unitType[value];
    }
});