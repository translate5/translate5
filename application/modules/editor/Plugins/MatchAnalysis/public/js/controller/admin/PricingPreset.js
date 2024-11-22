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
 * Main Controller of the pricing presets feature
 * Adds the PresetGrids and PresetCombo
 *
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.MatchAnalysis.controller.admin.PricingPreset', {
    extend: 'Ext.app.Controller',

    requires: [
        'Editor.plugins.MatchAnalysis.view.admin.pricing.PresetGrid',
        'Editor.plugins.MatchAnalysis.store.admin.pricing.PresetStore',
        'Editor.model.admin.Customer',
    ],
    init: function(){
        Editor.model.admin.Customer.addFields([{
            type: 'int',
            name: 'defaultPricingPresetId',
            persist: false,
            allowNull: true
        }]);
    },
    listen: {
        component: {
            '#preferencesOverviewPanel': {
                added: 'addPresetToSettingsPanel',
            },
            '#displayTabPanel': { // customerPanel > tabPanel
                added: 'addPresetToCustomerPanel',
            },
            '#taskMainCard': {
                render: {
                    fn: 'addPresetComboToTaskMainCard',
                    priority: 900 // we want after customersCombo has been added
                }
            },
            '#taskMainCard combobox#customerId': {
                change: {
                    fn: function(customerCombo, customerId){
                        customerId = (!customerId) ? null : customerId; // may be ''
                        var selection = customerCombo.getSelection();
                        if(selection){
                            var store = Ext.getStore('pricingPresetStore').createImportWizardSelectionData(customerId, selection.get('defaultPricingPresetId')),
                                combo = Ext.getCmp('taskImportPricingPresetId');
                            combo.setStore(store);
                            combo.setValue(store.selectedId);
                            combo.enable();
                        }
                    }
                }
            }
        }
    },
    refs: [{
        ref: 'preferencesOverviewPanel',
        selector: '#preferencesOverviewPanel'
    }],
    /** @property {Editor.view.admin.pricing.PresetGrid} presetPanel reference to our main view */
    presetPanel: null,
    // adds the Font-Prefs-Panel to the Overview Panel if the right is present
    addPresetToSettingsPanel: function(panel){
        if(Editor.app.authenticatedUser.isAllowed('pluginMatchAnalysisPricingPreset')){
            this.presetPanel = panel.insert(2, {
                xtype: 'pricingPresetGrid',
                routePrefix: 'preferences/',
                store: {
                    type: 'chained',
                    source: 'pricingPresetStore',
                    storeId: 'pricingPresetGlobal',
                    filters: [function(item){
                        return !item.data.customerId;
                    }]
                },
            });
        }
    },
    addPresetToCustomerPanel: function(tabPanel) {
        if(Editor.app.authenticatedUser.isAllowed('pluginMatchAnalysisPricingPreset')
            || Editor.app.authenticatedUser.isAllowed('pluginMatchAnalysisCustomerPricingPreset')
        ){
            // create filtered store from pricingPresetStore & apply it to the grid's view-model
            var vm = tabPanel.up('[viewModel]').getViewModel();
            var vmStores = vm.storeInfo || {};
            vmStores.customersPricingPresetStore = {
                source: 'pricingPresetStore',
                storeId: 'customersPricingPresetStore',
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
            // add the pricingPreset grid to the tabPanel and bind it to the customer
            tabPanel.insert(2, {
                xtype: 'pricingPresetGrid',
                routePrefix: 'client/:clientId/',
                bind: {
                    customer: '{list.selection}', // list is reference name of customerGrid
                    store: '{customersPricingPresetStore}'
                },
                isCustomerGrid: true,
            });
            tabPanel.setActiveTab(0);
        }
    },
    addPresetComboToTaskMainCard: function(taskMainCard) {
        taskMainCard.down('#taskSecondCardContainer').add({
            xtype: 'combobox',
            queryMode: 'local',
            forceSelection: true,
            displayField: 'name',
            id: 'taskImportPricingPresetId',
            name: 'pricingPresetId',
            valueField: 'id',
            disabled: true,
            value: Editor.data.plugins.MatchAnalysis.pricing.systemDefaultPresetId,
            bind: {
                fieldLabel: '{l10n.MatchAnalysis.pricing.preset.combo}'
            },
            tpl: Ext.create('Ext.XTemplate',
                '<ul class="x-list-plain t5leveledList"><tpl for=".">',
                    '<li role="option" class="{[values.cid == 0 ? "x-boundlist-item t5level1" : "x-boundlist-item"]}" title="{[Ext.String.htmlEncode(values.description)]}">{[Ext.String.htmlEncode(values.name)]}</li>',
                '</tpl></ul>'
            )
        });
    }
});
