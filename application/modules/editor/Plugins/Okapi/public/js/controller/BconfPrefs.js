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
 * Main Controller of the Visual Review
 * Defines the Layout of the review Panel and it's controls, listens to the relevant global events and perocesses them
 *
 * @class BconfPrefs
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
        Editor.model.admin.Customer.addFields([{type: 'int', name: 'defaultBconfId', persist:true}])
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
        }
    },
    refs: [{
        ref: 'preferencesOverviewPanel',
        selector: '#preferencesOverviewPanel'
    }],
    routes: {
        'bconfprefs': 'onBconfRoute'
    },
    // just a reference to our view
    bconfPanel: null,
    // shows the preference panel in the preferences (bconf-section is shown via 'showBconfInOverviewPanel' afterwards)
    onBconfRoute: function(){

        console.log('onBconfRoute');


        if(Editor.app.authenticatedUser.isAllowed('pluginOkapiBconfPrefs')){
            // QUIRK: just to make sure, not the same thing can happen as with Quirk in ::showBconfInOverviewPanel
            var pop = this.getPreferencesOverviewPanel();
            if(pop){
                Editor.app.openAdministrationSection(pop, 'reviewbconf');
            }
        }
    },
    // adds the Font-Prefs-Panel to the Overview Panel if the right is present
    addBconfToSettingsPanel: function(panel){
        if(Editor.app.authenticatedUser.isAllowed('pluginOkapiBconfPrefs')){
            this.bconfPanel = panel.insert(2, {
                xtype: 'okapiBconfGrid',
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
        var vm = tabPanel.up('[viewModel]').getViewModel();
        var vmStores = vm.storeInfo || {};
        vmStores.bconfCustomer = {
            source: 'bconfStore',
            storeId: 'bconfCustomer',
            filters: [{
                id: 'clientFilter',
                property: 'customerId',
                value: '{list.selection.id}',
                filterFn: function({data}){
                    return !data.customerId || this._value == data.customerId;
                },
            }],
            sorters: [{
                property: 'customerId',
                direction: 'DESC'
            }, {
                property: 'name',
            }]
        };
        vm.setStores(vmStores);

        tabPanel.insert(2, {
            xtype: 'okapiBconfGrid',
            bind: {
                customer: '{list.selection}', // list is reference name of customerGrid
                store: '{bconfCustomer}'
            },
            isCustomerGrid: true,
        });
        tabPanel.setActiveTab(0);
    },
    addBconfComboToTaskMainCard: function(taskMainCard){
        taskMainCard.down('#taskMainCardContainer').add({
            xtype: 'combobox',
            queryMode: 'local',
            forceSelection: true,
            displayField: 'name',
            name: 'bconfId',
            valueField: 'id',
            fieldLabel: Editor.plugins.Okapi.view.BconfGrid.prototype.strings.titleLong,
            listConfig: {
                getInnerTpl: function(displayField){
                    return `<span data-qtip="{description}">{${displayField}}</span>`
                },
            },
            bind: {
                store: '{bconfImportWizard}',
                disabled: '{!customer.selection}',
                value: '{defaultBconf}',
            },
            viewModel: {
                alias: 'viewmodel.bconfComboImportWizard',
                stores: {
                    bconfImportWizard: {
                        storeId: 'bconfImportWizard',
                        source: 'bconfStore',
                        autoLoad: true,
                        filters: [{
                            filterFn: function({data: bconf}){return !bconf.customerId || this._value == bconf.customerId},
                            property: 'customerId',
                            value: '{customer.selection.id}'
                        }],
                        sorters: [{
                            property: 'customerId',
                            direction: 'DESC'
                        }, {
                            property: 'name',
                        }],
                    }
                },
                formulas: {
                    defaultBconf: {
                        bind: {
                            customer: '{customer.selection}',
                            store: '{bconfImportWizard}', // QUIRK: artificial dependency to wait for store being filtered by bound customerId
                        },
                        get: function({customer, store}){
                            return customer.get('defaultBconfId') || this.globalDefaultId ||
                                //FIXME: find better way to (pre)calculate global default, maybe global variable
                                (this.globalDefaultId = store.getData().findBy(({data: bconf}) => bconf.isDefault).id);
                        }
                    }
                }
            }
        });
    }


});
