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
 * Main Controller of the Okapi Plugin
 * Adds the BconfGrids and BconfCombo
 *
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.Okapi.controller.BconfPrefs', {
    extend: 'Ext.app.Controller',

    requires: [
        'Editor.plugins.Okapi.view.BconfGrid',
        'Editor.plugins.Okapi.store.BconfStore',
        'Editor.model.admin.Customer'
    ],
    init: function(){
        Editor.model.admin.Customer.addFields([{
            type: 'int',
            name: 'defaultBconfId',
            persist: false,
            allowNull: true
        }]);

    },
    onLaunch: function(){
        Ext.create('Editor.plugins.Okapi.store.BconfStore'); // in onLaunch so customerStore can import default bconf before if needed
    },

    listen: {
        component: {
            '#preferencesOverviewPanel': {
                added: 'addBconfToSettingsPanel',
            },
            '#displayTabPanel': { // customerPanel > tabPanel
                added: 'addBconfToCustomerPanel',
            },
            '#taskMainCard': {
                render: {
                    fn: 'addBconfComboToTaskMainCard',
                    priority: 900 // we want after customersCombo has been added
                }
            },
            '#taskMainCard combobox#customerId': {
                change: {
                    fn: function(customerCombo, customerId){
                        customerId = (!customerId) ? null : customerId; // may be ''
                        var store = Ext.getStore('bconfStore').createImportWizardSelectionData(customerId, customerCombo.getSelection().get('defaultBconfId')),
                            combo = Ext.getCmp('taskImportBconfId');
                        combo.setStore(store);
                        combo.setValue(store.selectedId);
                        combo.enable();
                    }
                }
            }
        }
    },
    refs: [{
        ref: 'preferencesOverviewPanel',
        selector: '#preferencesOverviewPanel'
    }],
    /** @property {Editor.plugins.Okapi.view.BconfGrid} bconfPanel reference to our main view */
    bconfPanel: null,
    // adds the Font-Prefs-Panel to the Overview Panel if the right is present
    addBconfToSettingsPanel: function(panel){
        if(Editor.app.authenticatedUser.isAllowed('pluginOkapiBconfPrefs')){
            this.bconfPanel = panel.insert(2, {
                xtype: 'okapiBconfGrid',
                id: 'okapiBconfGrid',
                routePrefix: 'preferences/',
                store: {
                    type: 'chained',
                    source: 'bconfStore',
                    storeId: 'bconfGlobal',
                    filters: [function(item){
                        return !item.data.customerId;
                    }]
                },
            });
        }
    },
    addBconfToCustomerPanel: function(tabPanel){
        if(Editor.app.authenticatedUser.isAllowed('pluginOkapiBconfPrefs')){
            // create filtered store from bconfStore & apply it to the grid's view-model
            var vm = tabPanel.up('[viewModel]').getViewModel();
            var vmStores = vm.storeInfo || {};
            vmStores.customersBconfStore = {
                source: 'bconfStore',
                storeId: 'customersBconfStore',
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
                    property: 'name',
                    direction: 'ASC'
                }]
            };
            vm.setStores(vmStores);
            // add the bconf grid to the tabPanel and bind it to the customer
            tabPanel.insert(2, {
                xtype: 'okapiBconfGrid',
                id: 'bconfCustomerGrid',
                routePrefix: 'client/:clientId/',
                bind: {
                    customer: '{list.selection}', // list is reference name of customerGrid
                    store: '{customersBconfStore}'
                },
                isCustomerGrid: true,
            });
            tabPanel.setActiveTab(0);
        }
    },
    addBconfComboToTaskMainCard: function(taskMainCard){
        taskMainCard.down('#taskMainCardContainer').add({
            xtype: 'combobox',
            queryMode: 'local',
            forceSelection: true,
            displayField: 'name',
            id: 'taskImportBconfId',
            name: 'bconfId',
            valueField: 'id',
            disabled: true,
            value: Editor.data.plugins.Okapi.systemDefaultBconfId,
            fieldLabel: Editor.plugins.Okapi.view.BconfGrid.prototype.strings.titleLong,
            tpl: Ext.create('Ext.XTemplate',
                '<ul class="x-list-plain t5leveledList"><tpl for=".">',
                '<li role="option" class="{[values.cid == 0 ? "x-boundlist-item t5level1" : "x-boundlist-item"]}" title="{description}">{name}</li>',
                '</tpl></ul>'
            )
        });
    }


});
