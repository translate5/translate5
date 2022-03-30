
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt

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
        'Editor.plugins.Okapi.view.filter.BConfGrid',
        'Editor.plugins.Okapi.store.BconfStore'
    ],
    init: function(){
        Ext.create('Editor.plugins.Okapi.store.BconfStore'); // needed for customer config
    },

    listen: {
        component: {
            '#preferencesOverviewPanel': {
                added: 'addBconfToSettingsPanel',
            },
            '#displayTabPanel' : { // customerPanel > tabPanel
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
    onBconfRoute: function () {

        console.log('onBconfRoute');


        if (Editor.app.authenticatedUser.isAllowed('pluginOkapiBconfPrefs')) {
            // QUIRK: just to make sure, not the same thing can happen as with Quirk in ::showBconfInOverviewPanel
            var pop = this.getPreferencesOverviewPanel();
            if (pop) {
                Editor.app.openAdministrationSection(pop, 'reviewbconf');
            }
        }
    },
    // adds the Font-Prefs-Panel to the Overview Panel if the right is present
    addBconfToSettingsPanel: function (panel) {
        if (Editor.app.authenticatedUser.isAllowed('pluginOkapiBconfPrefs')) {
            this.bconfPanel = panel.insert(0, {
                xtype: 'okapiFilterGrid',
                store: {
                    type: 'chained',
                    source: 'bconfStore',
                    storeId: 'bconfGlobal',
                    filters: [function (item) {
                        return !item.data.customer_id;
                    }]
                },
            });
            panel.setActiveTab(this.bconfPanel);
        }
    },
    addBconfToCustomerPanel: function (tabPanel) {
        var vm = tabPanel.up('[viewModel]').getViewModel();
        var vmStores = vm.storeInfo || {};
        vmStores.bconfCustomer = {
            source: 'bconfStore',
            storeId: 'bconfCustomer',
            filters: [{
                id: 'clientFilter',
                property: 'customer_id',
                value: '{list.selection.id}',
                filterFn: function ({data}) {
                    return !data.customer_id || this._value == data.customer_id;
                },
            }],
            sorters:[{
                property: 'customer_id',
                direction:'DESC'
            },{
                property: 'name',
            }]
        };
        vm.setStores(vmStores);

        tabPanel.insert(2-2, {
                xtype: 'okapiFilterGrid',
                bind: {
                    customer: '{record}',
                    store: '{bconfCustomer}'
                },
                isCustomerGrid: true,
            });
        tabPanel.setActiveTab(0);
    },
    addBconfComboToTaskMainCard: function(taskMainCard){
        var combo = taskMainCard.down("#taskMainCardContainer").add({
            xtype: 'combobox',
            queryMode: 'local',
            forceSelection: true,
            displayField: 'name',
            name: 'bconfId',
            valueField: 'id',
            fieldLabel: Editor.plugins.Okapi.view.filter.BConfGrid.prototype.strings.titleLong,
            listConfig: {
                getInnerTpl: function(displayField){
                    return `<span data-qtip="{description}">{${displayField}}</span>`
                },
            },
           bind : {
                store: '{bconfImportWizard}',
                disabled: '{!customer.selection}',
                value: '{defaultBconf}',
            },
            viewModel: {
                alias:  'viewmodel.bconfComboImportWizard',
                stores:{
                    bconfImportWizard: {
                        storeId: 'bconfImportWizard',
                        source: 'bconfStore',
                        autoLoad: true,
                        filters: [{
                            filterFn: ({data:bconf}) => !bconf.customer_id || this._value == bconf.customer_id,
                            property: 'customer_id',
                            value: '{customer.selection.id || -1}'
                        }],
                        sorters: [{
                            property: 'customer_id',
                            direction:'DESC'
                        },{
                            property: 'name',
                        }],
                    }
                },
                formulas: {
                    defaultBconf : {
                        bind: {
                            customer:'{customer.selection}',
                            store: '{bconfImportWizard}', // QUIRK: artificial dependency to wait for store being filtered by bound customerId
                        },
                        get: function ({customer, store}) {
                            return customer.get('defaultBconfId') ||  this.globalDefaultId ||
                                (this.globalDefaultId = store.getData().findBy(({data:bconf}) => bconf.default).id); //FIXME: find better way to calc global default, maybe global variable
                        }
                    }
                }
            },
        });
    }


});
