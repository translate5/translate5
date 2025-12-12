/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.view.LanguageResources.CustomerTmAssocController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.LanguageResources.CustomerTmAssocController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.customerTmAssoc',

    listen: {
        controller: {
            '#LanguageResources': {
                customerchange: 'onCustomerChange'
            }
        },
        store: {
            '#languageResourceStore': {
                load: 'onLanguageResourcesStoreLoad'
            }
        },
        component: {
            'grid': {
                edit: 'onPenaltyEdit'
            },
            'checkcolumn[dataIndex=hasClientAssoc]': {
                checkchange: 'onToggleClientAssoc'
            },
            'checkcolumn[dataIndex/="has(Read|Write|Pivot)Access"]': {
                checkchange: 'onToggleClientAccess'
            }
        }
    },

    /**
     * Renderer for sourceLang and targetLang columns
     *
     * @param val
     * @param md
     * @returns {string}
     */
    langRenderer : function(val, md) {
        var tip = [], rfc = [], i, lang;

        // If no languages - return empty string
        if (!val || val.length < 1) {
            return '';
        }

        // Collect labels and rfcs
        for (i = 0; i < val.length; i++) {
            lang = Ext.StoreMgr.get('admin.Languages').getById(val[i]);
            if (lang) {
                tip.push(lang.get('label'));
                rfc.push(lang.get('rfc5646'));
            }
        }

        // Use joined labels as a tip
        md.tdAttr = 'data-qtip="' + tip.join(',') + '"';

        // Use rfcs as as value
        return rfc.join(',');
    },

    /**
     * Handler for event of selection change in customers grid
     */
    onCustomerChange: function(customerId) {
        var me = this,
            penaltyDefined = {},
            penaltyDefault = {
                penaltyGeneral: 0,
                penaltySublang: Editor.data.segments.matchratemaxvalue
            },
            penaltyCleared = {
                penaltyGeneral: undefined,
                penaltySublang: undefined
            };

        // Clear penalties info and recalc flags
        Ext.defer(() => me.forEachLangRes(rec => {
            rec.set(penaltyCleared);
            rec.commit();
            rec.refreshFlags(customerId);
        }), 100);

        // Load penalties info
        Ext.Ajax.request({
            method: 'GET',
            url: Editor.data.restpath + 'languageresourcecustomerassoc',
            params: {
                customerId : customerId
            },
            success: xhr => {

                // Prepare penalties info from existing assoc
                (Ext.JSON.decode(xhr.responseText, true)?.rows || []).forEach(assoc => penaltyDefined[assoc.languageResourceId] = {
                    penaltyGeneral: parseInt(assoc.penaltyGeneral),
                    penaltySublang: parseInt(assoc.penaltySublang),
                });

                // Apply penalties info from existing assoc or apply default penalties info
                Ext.defer(() => me.forEachLangRes(rec => {
                    rec.set(penaltyDefined[rec.getId()] || penaltyDefault);
                    rec.commit();
                }), 100);
            }
        });
    },

    /**
     * Update hasClientAssoc, has(Read|Write|Pivot)Access checkboxes states
     */
    refreshFlags: function(customerId) {
        this.getView().getStore().each(rec => rec.refreshFlags(customerId), this, {filtered: true});
    },

    /**
     * Handler for checked state change event of 'Usable for client' column
     *
     * @param column
     * @param rowIndex
     * @param checked
     * @param record
     */
    onToggleClientAssoc: function(column, rowIndex, checked, record, e) {
        var me = this,
            view = me.getView(),
            customerId = view.up('[viewModel]').getViewModel().get('record').getId(),
            customerIds = Ext.clone(record.get('customerIds'));

        // Prepare new value for customerIds-field
        checked
            ? customerIds.push(customerId)
            : customerIds = customerIds.filter(id => parseInt(id) !== parseInt(customerId));

        // If assoc is going to be removed - uncheck other checkboxes
        if (checked === false) {
            record.set({
                hasReadAccess : false,
                hasWriteAccess: false,
                hasPivotAccess: false,
                penaltyGeneral: 0,
                penaltySublang: Editor.data.segments.matchratemaxvalue
            });
        }

        // Update value
        record.set('customerIds', customerIds);

        // Save record
        record.save({
            preventDefaultHandler: true,
            params: {
                // Here we do this check to distinguish between cases when onToggleClientAssoc is called as a handler
                // for checkchange-event with Ext.event.Event as 5th argument, and cases when onToggleClientAssoc
                // is called by ourselves with 1 as 5th argument to be used as forced-param for PUT request
                forced: e === true ? 1 : 0
            },
            failure: (rec, op) => {

                // Reject changes for now, because at this point we don't know whether user will press Yes-button in
                // task <-> langres unassign confirmation dialog that will be shown if current langres is assigned to
                // at least a single task.
                record.reject();
                record.refreshFlags(customerId);

                // But if such a confirmation dialog will be surely shown
                // - set up a retry() function to be called if Yes-button is pressed
                if (~['E1447', 'E1473'].indexOf(op.error.response.responseJson?.errorCode)) {
                    op.error.response.retry = forced => {
                        me.onToggleClientAssoc(column, rowIndex, checked, record, forced);
                    }
                }

                // Handle exception
                Editor.app.getController('ServerException').handleException(op.error.response)
            },
            success: () => {
                record.refreshFlags(customerId);
                Editor.MessageBox.addSuccess(
                    Ext.String.format(
                        Editor.controller.TmOverview.prototype.strings.edited,
                        record.getName()
                    )
                );
            }
        });
    },

    /**
     * Handler for checked state change event of 'Read access by default' column
     *
     * @param column
     * @param rowIndex
     * @param checked
     * @param record
     */
    onToggleClientAccess: function(column, rowIndex, checked, record) {
        var me = this,
            view = me.getView(),
            customerId = view.up('[viewModel]').getViewModel().get('record').getId(),
            idsField = {
                hasReadAccess : 'customerUseAsDefaultIds',
                hasWriteAccess: 'customerWriteAsDefaultIds',
                hasPivotAccess: 'customerPivotAsDefaultIds'
            }[column.dataIndex];

        // Update access
        record.set(idsField, checked
            ? record.get(idsField).concat([customerId])
            : record.get(idsField).filter(id => parseInt(id) !== parseInt(customerId)));

        // If access is going to be added
        if (checked) {

            // But assoc is missing
            if (!record.get('customerIds').some(id => parseInt(id) === customerId)) {

                // Append assoc
                record.set({
                    customerIds: record.get('customerIds').concat([customerId]),
                    hasClientAssoc: true
                });
            }

            // If write access is going to be added, but read-access is missing
            if (column.dataIndex === 'hasWriteAccess'
                && !record.get('customerUseAsDefaultIds').some(id => parseInt(id) === customerId)) {

                // Append read-access
                record.set({
                    customerUseAsDefaultIds: record.get('customerUseAsDefaultIds').concat([customerId]),
                    hasReadAccess: true
                });
            }

        // Else if access is going to be removed, and it's a read-access
        } else if (column.dataIndex === 'hasReadAccess') {

            // Remove check for write-access as well
            record.set({
                hasWriteAccess: false
            });
        }

        // Save record
        record.save({
            preventDefaultHandler: true,
            params: {
                forced: 0
            },
            failure: (records, op) => {
                Editor.app.getController('ServerException').handleException(
                    op.error.response
                )
            },
            success: () => {
                record.refreshFlags(customerId);
                Editor.MessageBox.addSuccess(
                    Ext.String.format(
                        Editor.controller.TmOverview.prototype.strings.edited,
                        record.getName()
                    )
                );
            }
        });
    },

    /**
     * Make sure current penalty value won't be shown until loaded
     *
     * @param value
     * @returns {string|*}
     */
    penaltyRenderer: function(value) {
        return value === undefined ?  '<img src="/modules/editor/images/loading-spinner.gif" width="13">' : value;
    },

    /**
     * Handler for edit-event fired when penalty cell editing done
     *
     * @param plugin
     * @param context
     */
    onPenaltyEdit: function(plugin, context) {
        var data = {},
            record = context.record,
            customerId = this.getView().up('[viewModel]').getViewModel().get('record').getId(),
            customerIds = Ext.clone(record.get('customerIds'));

        // Setup a value for a certain kind of penalty (e.g. penaltyGeneral or penaltySublang)
        data[context.field] = context.value;

        // Prepare data for that assoc to be detected updated, or created if not exists
        data.customerId = customerId;
        data.languageResourceId = record.get('id');

        // If no assoc yet exists
        if (record.get('hasClientAssoc') === false) {

            // Append customerId to the list and set assoc flag
            record.set({
                customerIds: customerIds.concat(customerId),
                hasClientAssoc: true
            });
        }

        // Create or update assoc with new value of a certain penalty
        Ext.Ajax.request({
            url: Editor.data.restpath + 'languageresourcecustomerassoc',
            method: 'POST',
            params: data,
            failure: xhr => {
                record.reject();
                Editor.app.getController('ServerException').handleException(xhr)
            },
            success: xhr => record.commit()
        });
    },

    /**
     * Do something for each record in the store
     *
     * @param {Function} fn
     */
    forEachLangRes: function(fn) {
        this.getView().getStore().each(fn, this, {filtered: true});
    },

    /**
     * Refresh selected customer specific props on language resources store
     */
    onLanguageResourcesStoreLoad: function() {
        var me = this,
            customer = this.getView().up('[viewModel]').getViewModel().get('record');

        if (customer) {
            me.onCustomerChange(customer.getId());
        }
    }
});