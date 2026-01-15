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
            },
            '#ServerException': {
                serverExceptionE1500: 'onServerExceptionE1500Handler',
                serverExceptionE1501: 'onServerExceptionE1501Handler'
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
            'checkcolumn[dataIndex/="has(Read|Write|Pivot|Tqe|TqeInstantTranslate)Access"]': {
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
        this.reloadForCustomer(customerId);
    },

    reloadForCustomer: function (customerId){
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
            customerId = view.up('[viewModel]').getViewModel().get('record').getId();

        view.setLoading(true);

        // Reload record to get latest entity version
        record.load({
            callback: function(loadedRecord, operation, success) {
                if (!success) {
                    view.setLoading(false);
                    return;
                }

                var customerIds = Ext.clone(loadedRecord.get('customerIds'));
                var state = me.captureCustomerAssocState(loadedRecord);

                // Prepare new value for customerIds-field
                checked
                    ? customerIds.push(customerId)
                    : customerIds = customerIds.filter(id => parseInt(id) !== parseInt(customerId));

                // If assoc is going to be removed - uncheck other checkboxes
                if (checked === false) {
                    // Also remove the customer from all default-access lists so refreshFlags can't re-check them
                    state.customerUseAsDefaultIds = state.customerUseAsDefaultIds.filter(id => parseInt(id) !== parseInt(customerId));
                    state.customerWriteAsDefaultIds = state.customerWriteAsDefaultIds.filter(id => parseInt(id) !== parseInt(customerId));
                    state.customerPivotAsDefaultIds = state.customerPivotAsDefaultIds.filter(id => parseInt(id) !== parseInt(customerId));
                    state.customerTqeAsDefaultIds = state.customerTqeAsDefaultIds.filter(id => parseInt(id) !== parseInt(customerId));
                    state.customerTqeInstantTranslateAsDefaultIds = state.customerTqeInstantTranslateAsDefaultIds.filter(id => parseInt(id) !== parseInt(customerId));

                    loadedRecord.set({
                        hasReadAccess : false,
                        hasWriteAccess: false,
                        hasPivotAccess: false,
                        hasTqeAccess: false,
                        hasTqeInstantTranslateAccess: false,
                        penaltyGeneral: 0,
                        penaltySublang: Editor.data.segments.matchratemaxvalue
                    });
                }

                // Update value
                state.customerIds = Ext.Array.clone(customerIds);
                me.applyCustomerAssocState(loadedRecord, state);

                // Save record
                loadedRecord.save({
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
                    callback: function(record, operation, success) {
                        view.setLoading(false);

                        if (success) {
                            loadedRecord.refreshFlags(customerId);
                            Editor.MessageBox.addSuccess(
                                Ext.String.format(
                                    Editor.controller.TmOverview.prototype.strings.edited,
                                    loadedRecord.getName()
                                )
                            );
                        }
                    }
                });
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
                hasPivotAccess: 'customerPivotAsDefaultIds',
                hasTqeAccess: 'customerTqeAsDefaultIds',
                hasTqeInstantTranslateAccess: 'customerTqeInstantTranslateAsDefaultIds'
            }[column.dataIndex];

        view.setLoading(true);

        // Reload record to get latest entity version
        record.load({
            callback: function(loadedRecord, operation, success) {
                if (!success) {
                    view.setLoading(false);
                    return;
                }

                // Build full next-state and reapply it so we always send consistent arrays to backend
                // (ExtJS may omit unchanged fields otherwise, which can lead to stale defaults on backend).
                var state = me.captureCustomerAssocState(loadedRecord);

                var addCustomer = function(list) {
                    list = Ext.Array.clone(list || []);
                    if (!list.some(id => parseInt(id) === parseInt(customerId))) {
                        list.push(customerId);
                    }
                    return list;
                };
                var removeCustomer = function(list) {
                    return Ext.Array.clone(list || []).filter(id => parseInt(id) !== parseInt(customerId));
                };

                state[idsField] = checked ? addCustomer(state[idsField]) : removeCustomer(state[idsField]);

                // If access is going to be added
                if (checked) {

                    // But assoc is missing
                    state.customerIds = addCustomer(state.customerIds);
                    loadedRecord.set({ hasClientAssoc: true });

                    // If write access is going to be added, but read-access is missing
                    if (column.dataIndex === 'hasWriteAccess'
                        && !state.customerUseAsDefaultIds.some(id => parseInt(id) === parseInt(customerId))) {
                        state.customerUseAsDefaultIds = addCustomer(state.customerUseAsDefaultIds);
                        loadedRecord.set({ hasReadAccess: true });
                    }

                // Else if access is going to be removed, and it's a read-access
                } else if (column.dataIndex === 'hasReadAccess') {

                    // Remove check for write-access as well
                    loadedRecord.set({
                        hasWriteAccess: false
                    });
                    state.customerWriteAsDefaultIds = removeCustomer(state.customerWriteAsDefaultIds);
                }

                me.applyCustomerAssocState(loadedRecord, state);

                loadedRecord.save({
                    params: {
                        forced: 0
                    },
                    callback: function(record, operation, success) {
                        view.setLoading(false);

                        if (success) {
                            loadedRecord.refreshFlags(customerId);
                            Editor.MessageBox.addSuccess(
                                Ext.String.format(
                                    Editor.controller.TmOverview.prototype.strings.edited,
                                    loadedRecord.getName()
                                )
                            );
                        }
                    }
                });
            }
        });
    },

    /**
     * Make sure current penalty value won't be shown until loaded
     *
     * @param value
     * @param meta
     * @param record
     * @returns {string|*}
     */
    penaltyRenderer: function(value, meta, record) {
        // do not render any value for mt resources
        if(Editor.util.LanguageResources.resourceType.MT === record.get('resourceType')){
            return '';
        }
        return value === undefined ? '<img src="/modules/editor/images/loading-spinner.gif" width="13">' : value;
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
            me.reloadForCustomer(customer.getId());
        }
    },

    onServerExceptionE1500Handler: function (responseText, ecode, response) {
        return this.handleTqeConflict(responseText, ecode, response);
    },

    onServerExceptionE1501Handler: function (responseText, ecode, response) {
        return this.handleTqeConflict(responseText, ecode, response);
    },

    handleTqeConflict: function (responseText, ecode, response) {
        var request = response ? response.request : null,
            record = (request && request.records) ? request.records[0] : null,
            view = this.getView();

        if (!record || !view || !view.isVisible()) {
            return;
        }

        view.setLoading(false);
        this.showTqeConflictDialog(responseText, ecode, response);
        return false;
    },

    captureCustomerAssocState: function(record) {
        return {
            customerIds: Ext.Array.clone(record.get('customerIds') || []),
            customerUseAsDefaultIds: Ext.Array.clone(record.get('customerUseAsDefaultIds') || []),
            customerWriteAsDefaultIds: Ext.Array.clone(record.get('customerWriteAsDefaultIds') || []),
            customerPivotAsDefaultIds: Ext.Array.clone(record.get('customerPivotAsDefaultIds') || []),
            customerTqeAsDefaultIds: Ext.Array.clone(record.get('customerTqeAsDefaultIds') || []),
            customerTqeInstantTranslateAsDefaultIds: Ext.Array.clone(record.get('customerTqeInstantTranslateAsDefaultIds') || [])
        };
    },

    applyCustomerAssocState: function(record, state) {
        Object.keys(state || {}).forEach(function(field) {
            record.set(field, Ext.Array.clone(state[field] || []));
        });
    },

    /**
     * Show TQE/TQE instant-translate conflict dialog
     * @param responseText
     * @param ecode
     * @param response
     * @returns {boolean}
     */
    showTqeConflictDialog: function (responseText, ecode, response) {
        var me = this,
            translated = responseText.errorsTranslated,
            extraData = responseText.extraData ? responseText.extraData : null,
            conflicts = extraData ? extraData.conflicts : [],
            request = response ? response.request : false,
            record = (request && request.records) ? request.records[0] : false;

        if (!record) {
            return;
        }

        // we need to get the last entityVersion from the last failed save request.
        if(responseText.rows){
            record.set('entityVersion', responseText.rows.entityVersion);
        }

        var conflictList = conflicts.map(function (conflict) {
            return '<li>' + Ext.String.htmlEncode(conflict.resourceName) +
                   ' (' + Ext.String.htmlEncode(conflict.serviceName) + ')' +
                   ' - ' + Ext.String.htmlEncode(conflict.languagePairs) + '</li>';
        });

        var title = (ecode === 'E1500')
            ? Editor.data.l10n.languageResources.editLanguageResource.tqeConflictTitle
            : Editor.data.l10n.languageResources.editLanguageResource.tqeInstantTranslateConflictTitle;

        Ext.create('Ext.window.MessageBox').show({
            title: title,
            msg: Ext.String.format('{0} <ul>{1}</ul> {2}',
                translated.errorMessages[0],
                conflictList.join(''),
                translated.errorMessages[1]
            ),
            buttons: Ext.Msg.YESNO,
            fn: function (button) {
                if (button === "yes") {
                    me.saveLanguageResourceWithTqeResolve(record);
                    return true;
                }
                me.reloadForCustomer(record.get('customerId'));
                return false;
            },
            scope: me,
            defaultFocus: 'no',
            icon: Ext.MessageBox.QUESTION
        });

        return false;
    },

    /**
     * Save language resource with TQE/TQA conflict resolution
     * Reloads the record first to avoid 409 Conflict errors due to entity versioning
     * @param record
     */
    saveLanguageResourceWithTqeResolve: function (record) {
        var me = this,
            view = me.getView(),
            customerId = view.up('[viewModel]').getViewModel().get('record').getId();

        if (!record) {
            return;
        }

        view.setLoading(true);

        record.save({
            params: {
                resolveTqeConflicts: 1,
                forced: 0
            },
            callback: function(rec, op, ok) {
                view.setLoading(false);
                if (!ok) {
                    return;
                }

                me.getView().getStore().getSource().reload();

                Editor.MessageBox.addSuccess(
                    Ext.String.format(
                        Editor.controller.TmOverview.prototype.strings.edited,
                        record.getName()
                    )
                );
            }
        });
    }
});
