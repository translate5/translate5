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
 * Main Controller of the task custom fields feature
 *
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskCustomField', {
    extend: 'Ext.app.Controller',

    requires: [
        'Editor.view.admin.task.CustomField.Grid',
        'Editor.store.admin.TaskCustomField',
        //'Editor.model.admin.Customer',
    ],
    listen: {
        component: {
            '#preferencesOverviewPanel': {
                added: 'addToSettingsPanel'
            },
            /*'#displayTabPanel': { // customerPanel > tabPanel
                added: 'addToCustomerPanel',
            },*/
            '#taskMainCard': {
                render: {
                    fn: 'addCustomFieldsToTaskMainCard',
                    priority: 900 // we want after customersCombo has been added
                }
            },
            '#taskMainCard combobox#customerId': {
                change: {
                    fn: function(customerCombo, customerId){
                        customerId = (!customerId) ? null : customerId; // may be ''
                        var selection = customerCombo.getSelection();
                        if(selection){
                            /*var store = Ext.getStore('taskCustomFieldStore').createImportWizardCustomFieldsMetaData(customerId),
                                pgrid = Ext.getCmp('taskMainCardCustomFieldPropertyGrid');
                            pgrid.setStore(store);
                            pgrid.enable();*/
                        }
                    }
                }
            }
        }
    },

    /** @property {Editor.view.admin.taskCustomField.Grid} panel reference to our main view */
    panel: null,

    addToSettingsPanel: function(panel){
        if(Editor.app.authenticatedUser.isAllowed('taskCustomField')){
            Ext.create('Editor.store.admin.TaskCustomField');
            this.panel = panel.insert(2, {
                xtype: 'taskCustomFieldGrid',
                routePrefix: 'preferences/',
                store: {
                    type: 'chained',
                    source: 'taskCustomFieldStore',
                    storeId: 'taskCustomFieldGlobal'/*,
                    filters: [function(item){
                        return !item.data.customerId;
                    }]*/
                },
            });
        }
    },
    /*addToCustomerPanel: function(tabPanel) {
        if(Editor.app.authenticatedUser.isAllowed('taskCustomField')){
            // create filtered store from taskCustomFieldStore & apply it to the grid's view-model
            var vm = tabPanel.up('[viewModel]').getViewModel();
            var vmStores = vm.storeInfo || {};
            vmStores.customersTaskCustomFieldStore = {
                source: 'taskCustomFieldStore',
                storeId: 'customersTaskCustomFieldStore',
                filters: [{
                    id: 'clientFilter',
                    property: 'customerId',
                    value: '{list.selection}',
                    filterFn: function(rec){
                        return !rec.get('customerId') || (this._value && this._value.id === rec.get('customerId'));
                    },
                }],
                sorters: [{
                    property: 'customerId',
                    direction: 'ASC'
                }, {
                    property: 'position',
                    direction: 'ASC'
                }]
            };
            vm.setStores(vmStores);
            // add the custom fields grid to the tabPanel and bind it to the customer
            tabPanel.insert(2, {
                xtype: 'taskCustomFieldGrid',
                routePrefix: 'client/:clientId/',
                bind: {
                    customer: '{list.selection}', // list is reference name of customerGrid
                    store: '{customersTaskCustomFieldStore}'
                },
                isCustomerGrid: true,
            });
            tabPanel.setActiveTab(0);
        }
    },*/
    addCustomFieldsToTaskMainCard: function(taskMainCard) {
        /*taskMainCard.down('#taskMainCardContainer').add({
            id: 'taskMainCardCustomFieldPropertyGrid',
            disabled: true,
            xtype: 'propertygrid?',
        });*/
    }
});
