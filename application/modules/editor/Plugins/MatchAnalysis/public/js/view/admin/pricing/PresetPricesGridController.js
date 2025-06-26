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

/**
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.MatchAnalysis.view.admin.pricing.PresetPricesGridController', {
    extend: 'Ext.app.ViewController',
    requires: [
        'Editor.view.LanguagesCombo'
    ],
    alias: 'controller.pricingpresetpricesGridController',
    listen: {
        store: {
            'pricingpresetpricesStore': {
                beforeload: function() {
                    this.lookup('gridview').suspendEvent('refresh'); // prevent repaint until records are processed
                },
                load: function(store, records, success, operation){
                    this.dynamicCols(operation.getResultSet().getMetadata());
                    this.lookup('gridview').resumeEvent('refresh'); // enable repaint
                    this.lookup('gridview').refresh();
                }
            }
        }
    },
    control: {
        '#': { // # references the view
            close: 'handleClose',
            beforeedit: 'suspendRangeChange',
            canceledit: 'resumeRangeChange',
            edit: 'onPricesEdit',
            headerclick: 'onHeaderClick'
        },
        'textfield#search': {
            change: 'handleSearch',
        }
    },

    /**
     * Shared config for languages combobox
     */
    languageIdsCombo: {
        xtype: 'tagfield',
        allowBlank: false,
        collapseOnSelect: true,
        anyMatch: true,
        autoSelect: true,
        store: {
            type: 'languagestore'
        },
        displayField: 'label',
        minWidth: 300,
        margin: 0,
        forceSelection: true,
        queryMode: 'local',
        typeAhead: false,
        valueField: 'id',
        listeners: {
            change: () => Ext.Msg.center()
        }
    },

    /**
     * Listener for change-event of a search-textfield
     *
     * @param field
     * @param value
     */
    handleSearch: function(field, value) {

        // Apply/clear filter
        value ? this.setSearchFilter(value) : this.getView().getStore().clearFilter();

        // Toggle field's clear-trigger icon
        field.getTrigger('clear').setVisible(value);
    },

    /**
     * Apply search-textfield's value to be a store's filter
     *
     * @param {string} value
     */
    setSearchFilter: function(value) {
        var me = this,
            store = me.getView().getStore(),
            searchRE = new RegExp(Editor.util.Util.escapeRegex(value), 'i');

        // Clear filter
        store.clearFilter();

        // Add new one
        store.addFilter({
            id: 'search',
            filterFn: function({data}) {

                // Delete props that won't be searched among
                delete data.id; delete data.presetId;

                // Stringify row data
                var text = JSON.stringify([
                    Object.values(data),
                    me.languageIdRenderer(data.sourceLanguageId),
                    me.languageIdRenderer(data.targetLanguageId)
                ]);

                // Search in stringified
                return searchRE.exec(text);
            }
        });
    },

    /**
     * Handler for a click-event on edit-icon, to activate row editor for that record
     *
     * @param view
     * @param rowIndex
     * @param colIndex
     * @param item
     * @param e
     * @param record
     */
    editPrice: function(view, rowIndex, colIndex, item, e, record) {

        // Get rowediting plugin instance
        var rowEditor = view.grid.findPlugin('rowediting');

        // Apply selection to this record
        view.select(rowIndex);

        // Start editing
        rowEditor.startEdit(record);
    },

    /**
     * Show clone price prompt
     */
    clonePrice: function(view, rowIdx, colIdx, actionCfg, evt, rec) {

        // Show prompt
        Ext.Msg.prompt(Editor.data.l10n.MatchAnalysis.pricing.prices.cloneFor, '', btn => this.doClonePrice(btn, rec, view));

        // Add tagfields
        Ext.Msg.customFields([
            {...this.languageIdsCombo, itemId: 'sourceLanguageIds', bind: {emptyText: '{l10n.MatchAnalysis.pricing.prices.sourceLangs}'}, margin: '0 0 10 0'},
            {...this.languageIdsCombo, itemId: 'targetLanguageIds', bind: {emptyText: '{l10n.MatchAnalysis.pricing.prices.targetLangs}'}}
        ]);
    },

    /**
     * Do clone price
     *
     * @param btn
     * @param rec
     */
    doClonePrice: function(btn, rec) {

        // Get custom fields values
        var params = Ext.Msg.customFields();

        // If prompt was cancelled - return
        if (btn === 'cancel') {
            return;
        }

        // Append priceId-param
        params.priceId = rec.get('id');

        // Make clone-request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpresetprices/clone',
            params: params,
            success: xhr => {

                // Get clone data
                var append = Ext.decode(xhr.responseText).append;

                // Add into the store
                this.getView().getStore().add(append);
            },
            failure: xhr => {

                // If we reached this line, it means validation went wrong, so:
                // 1. reopen clone prices prompt
                // 2. apply last submitted values to the prompt's custom fields
                // 3. mark prompt's combobox as invalid and specify the reason
                this.clonePrice(null, null, null, null, null, rec);
                Ext.Msg.down('#sourceLanguageIds').setValue(params.sourceLanguageIds);
                Ext.Msg.down('#targetLanguageIds').setValue(params.targetLanguageIds);
                Ext.Msg.down('#sourceLanguageIds').markInvalid(Ext.decode(xhr.responseText)?.msg);
            }
        })
    },

    /**
     * Delete preset prices-record after server-side confirmation
     *
     * @param view
     * @param rowIdx
     * @param colIdx
     * @param actionCfg
     * @param evt
     * @param rec
     */
    deletePrice: function(view, rowIdx, colIdx, actionCfg, evt, rec) {
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpresetprices',
            method: 'DELETE',
            params: {
                pricesId : rec.get('id')
            },
            success: xhr => rec.store.remove(rec)
        });
    },

    /**
     * Handles closing the pricing panel
     */
    handleClose: function() {
        window.location.hash = window.location.hash.replace(/\/prices.*$/,'');
    },

    /**
     * Render language label instead of id
     *
     * @param value
     * @returns {*}
     */
    languageIdRenderer: function(value, meta) {
        var title = this.getView().languages.getById(value).get('label');
        meta.tdAttr = 'data-qtip="' + title + '"';
        return title;
    },

    /**
     * Add fields to prices-model and columns to prices-grid
     *
     * @param cols
     */
    dynamicCols: function(cols) {
        var hc = this.getView().down('headercontainer'), width, from, till,
            colsNow = [], colsWas = hc.query('gridcolumn[dataIndex^=range]'),
             idsNow,  idsWas = Ext.Array.pluck(colsWas, 'dataIndex').join(),
            fieldsNow = [], tm = Ext.create('Ext.util.TextMetrics'), text = '';

        cols.forEach(range => {
            from = parseInt(range.from);
            till = parseInt(range.till);
            text = this.rangeText(from, till);
            width = tm.getWidth(text) + (range.from === range.till ? 35 : 25)

            // Prepare colsNow-array
            colsNow.push({
                xtype: 'numbercolumn',
                sortable: false,
                align: 'end',
                format: '0,000.0000',
                text: text,
                dataIndex: 'range' + range.id,
                width: width,
                minWidth: width,
                menuDisabled: true,
                range: {
                    rangeId: range.id,
                    from: from,
                    till: till
                },
                editor: {
                    xtype: 'numberfield',
                    decimalPrecision: 4,
                    minValue: 0,
                    step: 0.0001,
                    hideTrigger: true,
                    valueToRaw: value => Ext.util.Format.number(Ext.valueFrom(value, ''), '0.0000')
                }
            });

            // Prepare fieldsNow
            fieldsNow.push({
                type: 'number',
                name: 'range' + range.id
            });
        });

        // Prepare idsNow
        idsNow = Ext.Array.pluck(colsNow, 'dataIndex').join();

        // If bunch of cols changed
        if (idsNow !== idsWas) {

            // Destroy old bunch of cols
            colsWas.forEach(col => col.destroy());

            // Add fields
            Editor.plugins.MatchAnalysis.model.admin.pricing.PresetPricesModel.replaceFields(fieldsNow, idsWas);

            // Add new bunch
            hc.insert(4, colsNow);
        }
    },

    /**
     * This method is a handler for edit-event, fired by rowediting-plugin on prices-grid
     *
     * @param plugin
     * @param context
     */
    onPricesEdit: function(plugin, context) {
        // Enable create/delete buttons back when editing completed
        this.resumeRangeChange();
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpresetprices',
            method: 'PUT',
            params: {pricesId : context.record.get('id'), ...context.record.getChanges()},
            success: xhr => context.record.commit()
        });
    },

    /**
     * Show range prompt
     *
     * @param src Source component
     */
    openRangePrompt: function(src) {

        // Prompt title
        var me = this, ranges = Ext.Array.pluck(this.getView().query('numbercolumn[range]'), 'range'), overlap,
            title = src.xtype === 'numbercolumn'
                ? Editor.data.l10n.MatchAnalysis.pricing.range.edit
                : Editor.data.l10n.MatchAnalysis.pricing.range.create;

        // Show prompt
        Ext.Msg.prompt(title, '', (btn, text) => this.handleRangePromptAnswer(btn, text, src)).down('textfield').hide();
        Ext.Msg.toFront().focus();

        // Add tagfield
        if (!Ext.Msg.down('#range')) {
            Ext.Msg.promptContainer.add({
                xtype: 'container',
                itemId: 'range',
                layout: 'hbox',
                defaults: {
                    xtype: 'numberfield',
                    minValue: 0,
                    maxValue: 104,
                    width: 110,
                    allowBlank: false,
                    listeners: {
                        change: field => {

                            // Get value in format 'xx-yy'
                            var value = Ext.Array.pluck(Ext.Msg.query('numberfield'), 'value').join('-');

                            // Apply to hidden textfield
                            Ext.Msg.down('textfield').setValue(value);

                            // Check validity
                            Ext.Msg.isValid();
                        }
                    }
                },
                items: [{
                    itemId: 'from',
                    value: src.range?.from,
                    bind: {
                        emptyText: '{l10n.MatchAnalysis.pricing.range.from}'
                    },
                    margin: '0 10 0 0',
                    validator: function (value) {
                        var end = this.ownerCt.down('#till');
                        if (end.getValue() !== null && end.getValue() < value) {
                            return Editor.data.l10n.MatchAnalysis.pricing.range.less;
                        }
                        if (overlap = me.overlap(ranges, value, src.range?.rangeId)) {
                            return overlap;
                        }

                        return true;
                    }
                }, {
                    itemId: 'till',
                    value: src.range?.till,
                    bind: {
                        emptyText: '{l10n.MatchAnalysis.pricing.range.till}'
                    },
                    validator: function (value) {
                        var begin = this.ownerCt.down('#from');
                        if (begin.getValue() !== null && begin.getValue() > value) {
                            return Editor.data.l10n.MatchAnalysis.pricing.range.greater;
                        }
                        if (overlap = me.overlap(ranges, value, src.range?.rangeId)) {
                            return overlap;
                        }
                        return true;
                    }
                }],
                keyMap: {
                    ENTER: () => Ext.Msg.isValid() && Ext.Msg.msgButtons.ok.click()
                },
                listeners: {
                    afterrender: ct => ct.down('numberfield').fireEvent('change')
                }
            })
        }
    },

    /**
     * Show delete ranges prompt
     */
    deleteRange: function() {
        var me = this, hc = me.getView().down('headercontainer'), data = [];

        // Prepare rangeIds-combobox store data
        hc.query('gridcolumn[dataIndex^=range]').forEach(col => data.push({
            id: col.dataIndex.replace(/^range/, ''),
            label: col.text
        }));

        // Show prompt
        Ext.Msg.prompt(Editor.data.l10n.MatchAnalysis.pricing.range.delete, '',
            (btn, text) => this.doDeleteRange(btn, text)
        ).down('textfield').hide();
        Ext.Msg.toFront();

        // Add tagfield
        if (!Ext.Msg.down('#rangeIds')) {
            Ext.Msg.promptContainer.add({
                xtype: 'tagfield',
                collapseOnSelect: true,
                allowBlank: false,
                anyMatch: true,
                autoSelect: true,
                store: {
                    type: 'json',
                    data: data
                },
                displayField: 'label',
                minWidth: 300,
                margin: 0,
                bind: {
                    emptyText: '{l10n.MatchAnalysis.pricing.range.selectToDelete}',
                },
                forceSelection: true,
                itemId: 'rangeIds',
                queryMode: 'local',
                typeAhead: false,
                valueField: 'id',
                listeners: {
                    change: (combo, values) => combo.up().down('textfield').setValue(values.join(',')) && Ext.Msg.center()
                }
            });
        }

    },

    /**
     * Do delete range
     *
     * @param btn
     * @param rangeIds
     */
    doDeleteRange: function(btn, rangeIds) {

        // Reset Ext.Msg singleton to initial state
        Ext.Msg.down('textfield').show().up().down('#rangeIds').destroy();

        // If prompt was cancelled - return
        if (btn === 'cancel') {
            return;
        }

        // Make create-request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpresetrange',
            method: 'DELETE',
            params: {
                presetId: this.getView().getPreset().get('id'),
                rangeIds: rangeIds
            },
            success: xhr => {

                // Get deleted record data
                var deleted = Ext.decode(xhr.responseText).deleted;

                // Add into the store
                deleted.forEach(rangeId => {

                    // Remove column from grid
                    this.getView().down('[dataIndex=range' + rangeId + ']').destroy();

                    // Remove field from model
                    Editor.plugins.MatchAnalysis.model.admin.pricing.PresetPricesModel.removeFields(['range' + rangeId]);
                });
            },
            failure: xhr => {

                // If we reached this line, it means validation went wrong, so:
                // 1. reopen delete ranges prompt
                // 2. apply last submitted rangeIds to the prompt's combobox
                // 3. mark prompt's combobox as invalid and specify the reason
                this.deleteRange();
                Ext.Msg.down('#rangeIds').setValue(rangeIds);
                Ext.Msg.down('#rangeIds').markInvalid(Ext.decode(xhr.responseText)?.msg);
            }
        });
    },

    /**
     * Show create price prompt
     */
    createPrice: function() {

        // Show prompt
        Ext.Msg.prompt(Editor.data.l10n.MatchAnalysis.pricing.prices.createFor, '', btn => this.doCreatePrice(btn));

        // Add tagfield
        Ext.Msg.customFields([
            {...this.languageIdsCombo, itemId: 'sourceLanguageIds', bind: {emptyText: '{l10n.MatchAnalysis.pricing.prices.sourceLangs}'}, margin: '0 0 10 0'},
            {...this.languageIdsCombo, itemId: 'targetLanguageIds', bind: {emptyText: '{l10n.MatchAnalysis.pricing.prices.targetLangs}'}}
        ]);
    },

    /**
     * Do create price
     *
     * @param btn
     */
    doCreatePrice: function(btn) {

        // Get custom fields values
        var params = Ext.Msg.customFields();

        // If prompt was cancelled - return
        if (btn === 'cancel') {
            return;
        }

        // Append presetId-param
        params.presetId = this.getView().getPreset().get('id'),

        // Make create-request
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpresetprices',
            method: 'POST',
            params: params,
            success: xhr => {

                // Get created record data
                var append = Ext.decode(xhr.responseText).append;

                // Add into the store
                this.getView().getStore().add(append);
            },
            failure: xhr => {

                // If we reached this line, it means validation went wrong, so:
                // 1. reopen create prices prompt
                // 2. apply last submitted values to the prompt's custom fields
                // 3. mark prompt's combobox as invalid and specify the reason
                this.createPrice();
                Ext.Msg.down('#sourceLanguageIds').setValue(params.sourceLanguageIds);
                Ext.Msg.down('#targetLanguageIds').setValue(params.targetLanguageIds);
                Ext.Msg.down('#sourceLanguageIds').markInvalid(Ext.decode(xhr.responseText)?.msg);
            }
        });
    },

    /**
     * Create/edit range
     *
     * @param {String} btn Which button was pressed in prompt 'ok' or 'cancel'
     * @param {String} range String value in format 'x-y', where x and y are range rate range boundaries
     * @param {Ext.button.Button|Ext.grid.column.Column} src Component from where range prompt was initially triggered
     */
    handleRangePromptAnswer: function(btn, range, src) {

        // Reset Ext.Msg singleton to initial state
        Ext.Msg.down('textfield').show().up().down('#range').destroy();

        // If prompt was cancelled - return
        if (btn === 'cancel') {
            return;
        }

        // Prepare xhr options shared for both POST and PUT requests
        var xhrSharedOptions = {
            url: Editor.data.restpath + 'plugins_matchanalysis_pricingpresetrange',
            params: {
                from: range.split('-').shift(),
                till: range.split('-').pop()
            },

            // If we reached this line, it means validation went wrong, so:
            failure: xhr => {

                // 1. reopen prompt
                // 2. apply last submitted languageId to the prompt's combobox
                // 3. mark prompt's combobox as invalid and specify the reason
                this.openRangePrompt(src);
                Ext.Msg.down('#from').setValue(range.split('-').shift());
                Ext.Msg.down('#till').setValue(range.split('-').pop());
                Ext.Msg.down('#from').markInvalid(Ext.decode(xhr.responseText)?.msg);
            }
        }

        // Prepare options specific to what we're doing
        var xhrCustomOptions = src.xtype === 'numbercolumn'
            ? {
                method: 'PUT' ,
                params: {rangeId: src.range.rangeId},
                success: xhr => {

                    // Update range-prop
                    src.range.from = xhrSharedOptions.params.from;
                    src.range.till = xhrSharedOptions.params.till;

                    // Update column header text
                    src.setText(this.rangeText(src.range.from, src.range.till));
                }
            }
            :
            {
                method: 'POST',
                params: {presetId: this.getView().getPreset().get('id')},
                success: xhr => this.getView().getStore().reload(),
            }

        // Merge options
        var options = Ext.merge(xhrSharedOptions, xhrCustomOptions);

        // Make request
        Ext.Ajax.request(options);
    },

    /**
     * Get text for matchrate range column heading
     *
     * @param from
     * @param till
     * @returns {string}
     */
    rangeText: function(from, till) {
        return till + '%' + rif(from !== till, ' - ' + from + '%');
    },

    /**
     * Check whether `value` belongs to any of existing `ranges`, except the one having `rangeId`
     *
     * @param ranges
     * @param value
     * @param rangeId
     * @returns {string|boolean}
     */
    overlap: function(ranges, value, rangeId) {

        // If value is an empty string - skip
        if (value === '') {
            return false;
        }

        // Foreach other range - check overlap
        for (var range of ranges) {
            if (range.rangeId !== rangeId) {
                if (value >= range.from && value <= range.till) {
                    return Editor.data.l10n.MatchAnalysis.pricing.range.overlaps + ' ' + this.rangeText(range.from, range.till);
                }
            }
        }
    },

    /**
     * Handler for headerclick-event on grid
     *
     * @param hc
     * @param col
     */
    onHeaderClick: function(hc, col) {
        if (col.range) this.openRangePrompt(col);
    },

    /**
     * Disable create/delete buttons when editing starts
     */
    suspendRangeChange: function() {
        this.getView().query('#rangeCreate, #rangeDelete').forEach(button => button.disable());
    },

    /**
     * Enable create/delete buttons back when editing cancelled
     */
    resumeRangeChange: function() {
        this.getView().query('#rangeCreate, #rangeDelete').forEach(button => button.enable());
    }
});