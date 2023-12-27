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


    // register listeners: load store for the grid after the grid is rendered
    listen: {
        component: {
            '#taskCustomFieldGrid': {
                afterrender: 'onTaskCustomFieldGridAfterRender'
            }
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
            label: '{"en":"","de":""}',
            tooltip: '{"en":"","de":""}',
            type: 'textfield',
            mode: 'optional',
        });
        store.add(newCustomField);
        grid.setSelection(newCustomField);
    },

    onTaskCustomFieldGridAfterRender: function(grid){
        grid.getStore().load();
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

    /*routesToSet: {
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
        /** @link Editor.controller.admin.Customer TODO FIXME: support routing in Customer Controller * /
        for(const [route, action] of Object.entries(this.routesToSet)){
            routes[view.routePrefix + itemId + '/' + route] = action;
        }
        this.setRoutes(routes);
        this.callParent(arguments);
    },*/
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
    /*onPresetRoute: async function(/* presetId * /){
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
    }*/
});