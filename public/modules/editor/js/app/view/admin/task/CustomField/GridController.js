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

Ext.define('Editor.view.admin.task.CustomField.GridController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.taskCustomFieldGridController',
    listen: {
        component: {
            '#': {
                afterrender: 'onAfterRender',
                selectionchange: 'onSelectionChange'
            },
            'form': {
                boxready: 'formBoxReady'
            },
            'form combobox#type': {
                change: 'onTypeChange'
            }
        },
        store: {
            '#taskCustomFieldStore': {
                load: 'onLoad'
            }
        }
    },

    /**
     * Routes
     */
    routesToSet: {
        ':recordId': 'onRecordRoute',
    },

    /**
     * Auto-select first row in the grid and refresh global custom fields array
     *
     * @param store
     */
    onLoad: function(store) {
        if (store.getCount()) {

            // Check whether record id is gived in hash
            var match = Ext.util.History.getToken().match(/(?!\/)[0-9]+$/);

            // Auto-select row in the grid by id from hash, if given, or just first one
            if (match) {
                this.onRecordRoute(match[0]);
            } else {
                this.getViewModel().set('customField', store.first());
            }

            // Refresh Editor.data.editor.task.customFields array
            store.refreshGlobalCustomFields();
        }
    },

    /**
     *
     * @param value
     * @returns {*|string}
     */
    idRenderer: value => Ext.isNumeric(value) ? value : '*',

    /**
     * Renderer for localized values. Value for current locale is returned if exists or raw value otherwise
     *
     * @param value
     * @returns {*}
     */
    l10nRenderer: value => Editor.data.locale in (Ext.JSON.decode(value, true) || {})
        ? (Ext.JSON.decode(value, true) || {})[Editor.data.locale]
        : value,

    /**
     * Return human-friendly title for a value of type-field
     *
     * @param value
     * @param meta
     * @param record
     * @returns {*|string}
     */
    typeRenderer: function(value, meta, record) {
        if (value === 'combobox') {
            meta.tdAttr = 'data-qtip="' + Ext.String.htmlEncode(record.get('comboboxData')) + '"';
            meta.tdStyle = 'text-decoration: underline dotted; text-underline-offset: 4px;';
        }

        return this._enumRenderer(value, 'type');
    },

    /**
     * Return human-friendly title for a value of mode-field
     *
     * @param value
     * @returns {string}
     */
    modeRenderer: function(value) {
        return this._enumRenderer(value, 'mode');
    },

    /**
     * Convert comma-separated value of placesToShow-field into human-friendly comma-separated title
     *
     * @param value
     * @returns {string}
     */
    placesToShowRenderer: function(value) {
        return this._enumRenderer(value, 'placesToShow');
    },

    /**
     * Helper function to comma-separated value given by values-arg
     * for the field given by prop-arg - into a human-friendly comma-separated title
     *
     * @param values
     * @param prop
     * @returns {string}
     * @private
     */
    _enumRenderer: function(values, prop) {
        if (!values) return values;
        var render = [];
        values.split(',').forEach(
            value => render.push(
                Ext.Array.toValueMap(
                    Editor.data.l10n.taskCustomField.meta[prop].data, 'value'
                )[value]['name']
            )
        );
        return render.join(', ');
    },

    /**
     * Handler for 'Create new custom field' button
     */
    createCustomField: function(){
        var grid = this.getView(),
                store = grid.getStore(),
                newCustomField = Ext.create('Editor.model.admin.task.CustomField');
        newCustomField.set({
            id: null,
            label: '{"en":"","de":""}',
            tooltip: '{"en":"","de":""}',
            type: 'textfield',
            mode: 'optional',
        });
        store.add(newCustomField);
        grid.setSelection(newCustomField);
    },

    /**
     * Load store on grid render
     *
     * @param grid
     */
    onAfterRender: grid => grid.getStore().load(),

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
    /*isDeleteDisabled: function(view, rowIndex, colIndex, item, record) {
        return view.grid.isCustomerGrid && !record.get('customerId');
    },*/

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
    /*isEditDisabled: function(view, rowIndex, colIndex, item, record) {
        return view.ownerGrid.isCustomerGrid && !record.get('customerId');
    },*/

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
                    JSON.stringify(data, ['id', 'label', 'tooltip', 'type', 'regex', 'mode', 'placesToShow'])
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
     * Prepare and set routes
     *
     * @param view
     */
    beforeInit: function(view){
        var itemId = view.getItemId(), routes = {};

        // Prepare routes
        for (const [route, action] of Object.entries(this.routesToSet)) {
            routes[view.routePrefix + itemId + '/' + route] = action;
        }

        // Set routes
        this.setRoutes(routes);

        // Call parent
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

    /**
     * The argument depends on the routePrefix of the view
     */
    onRecordRoute: async function(recordId) {
        var grid = this.getView(), store = grid.getStore();

        // Close all windows
        Editor.util.Util.closeWindows();

        // Wait for store load?
        await Editor.util.Util.awaitStore(store);

        // Get
        var selected = grid.getSelectionModel().getSelectionStart(), toSelect = grid.getStore().getById(recordId);

        // If there is no record found by recordId in the store
        if (!toSelect) {

            // Get recordId to redirect to, if possible
            if (selected) {
                recordId = selected.id;
            } else if (store.getCount()) {
                recordId = store.first().getId();
            } else {
                recordId = '';
            }

            // Do redirect
            this.redirectTo(
                Editor.util.Util.trimLastSlash(Ext.util.History.getToken()) + (recordId ? '/' + recordId : '')
            );

        // Else move selection to the desired record
        } else if (toSelect !== selected) {
            grid.setSelection(toSelect);
        }
    },

    /**
     * Apply handlers to fire when json-fields are clicked,
     * as there is neither triggers supported by ExtJS 6.2 nor click-event directly supported for fields
     *
     * @param form
     */
    formBoxReady: function(form){
        var label = form.down('#label'),
            tooltip = form.down('#tooltip'),
            comboboxData = form.down('#comboboxData');

        // Apply handlers
        label.inputEl.on('click', el => this.onJsonFieldClick(label, 40));
        tooltip.inputEl.on('click', el => this.onJsonFieldClick(tooltip, 119));
        //comboboxData.inputEl.on('click', el => this.jsonFieldClick(comboboxData));
    },

    /**
     * Make sure SimpleMap-popup is shown when field is clicked so that json-value can be edited via the popup
     *
     * @param field
     */
    onJsonFieldClick: function(field, valueMaxLength) {
        Ext.ClassManager.get('Editor.view.admin.config.type.SimpleMap').getJsonFieldEditor({
            jsonField: field,
            hideTbar: true,
            readonlyIndex: true,
            valueMaxLength: valueMaxLength
        });
    },

    /**
     * Save changes to new or existing custom field
     */
    onSave:function(){
        var view = this.getView(),
            record = view.getViewModel().get('customField'),
            selector = '#' + view.down('tableview').id + '-record-' + record.internalId;

        // Put a mask on the whole view
        view.mask(Ext.LoadMask.prototype.msg);

        // Start saving request
        record.save({
            callback: () => {
                view.unmask();

                // Refresh Editor.data.editor.task.customFields array
                view.getStore().refreshGlobalCustomFields();

                // Do redirect
                this.redirectTo(
                    Editor.util.Util.trimLastSlash(Ext.util.History.getToken()) + (record.get('id') ? '/' + record.get('id') : '')
                );

                // Put yellow background as it's dissapears somewhy if new record was saved
                view.el.down(selector).addCls('x-grid-item-selected');
            }
        });
    },

    /**
     * Cancel changes pending for to new or existing custom field
     */
    onCancel:function(){
        this.getViewModel().get('customField').reject();
    },

    /**
     * Delete custom field
     */
    onDelete:function(){
        var view = this.getView(), record = view.getViewModel().get('customField');
        view.mask(Ext.LoadMask.prototype.msg);
        record.erase({
            callback: () => {
                view.unmask();
                if (!record.phantom) {
                    view.getStore().refreshGlobalCustomFields()
                }
                view.getViewModel().set('customField', null);
            }
        });
    },

    /**
     * Make sure Readonly-option in Mode-combobox won't be selectable
     *
     * @param selModel
     * @param selected
     */
    onSelectionChange: function(selModel, selected) {

        // If nothing selected - skip
        if (!selected.length) return;

        // Adjust options available in Mode-combobox based on value in Type-combobox
        this.adjustModeChoices();
    },

    /**
     * Adjust options available in Mode-combobox based on value in Type-combobox
     */
    adjustModeChoices: function() {

        // Get options store for mode-combobox
        var modeCombo = this.getView().down('form #mode'),
            modeStore = modeCombo.getStore(),
            customField = this.getViewModel().get('customField');

        // Clear filter, if any
        modeStore.clearFilter();

        // If nothing selected in custom fields grid - return
        if (!customField || !customField.id) return;

        // If currently selected customField is readonly
        if (customField.get('mode') !== 'readonly') {

            // If currently selected customField is checkbox
            if (customField.get('type') === 'checkbox') {

                // Make sure only Optional is available for choice in Mode-field
                modeStore.filterBy(mode => mode.get('value') === 'optional');

                // Set optional as the only possible value in this case
                customField.set('mode', 'optional');

            // Otherwise both Optional and Required should be available there
            } else {
                modeStore.filterBy(mode => mode.get('value') !== 'readonly');

                // Set optional as the only possible value in this case
                if (customField.getModified('mode')) {
                    customField.set('mode', customField.getModified('mode'));
                }
            }
        }
    },

    /**
     * Adjust choices available in Mode-combobox based on current value of Type-combobox
     *
     * @param combo
     * @param value
     */
    onTypeChange: function(combo, value) {
        this.adjustModeChoices();
    },

    /**
     * Handler for Add button in combobox data grid
     */
    onComboboxOptionAdd: function () {
        var win = this.getView(),
            grid = win.down('grid#comboboxDataGrid'),
            rec;

        rec = grid.store.insert(0, {
            index: '',
            value: ''
        })[0];
        //we set the values after creation, so that the record looks dirty
        rec.set('index', 'option' + grid.store.getCount());
        rec.set('value', {en: "", de: ""});
    },

    /**
     * Handler for Remove button in combobox data grid
     */
    onComboboxOptionRemove: function () {
        var win = this.getView(),
            grid = win.down('grid#comboboxDataGrid'),
            selMod = grid.getSelectionModel();

        if (grid.findPlugin('rowediting'))
            grid.findPlugin('rowediting').cancelEdit();

        grid.store.remove(selMod.getSelection());

        if (grid.store.getCount() > 0) {
            selMod.select(0);
        }
    }
});