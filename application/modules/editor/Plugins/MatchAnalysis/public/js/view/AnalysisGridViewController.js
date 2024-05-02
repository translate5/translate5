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

Ext.define('Editor.plugins.MatchAnalysis.view.AnalysisGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.matchAnalysisGrid',

    listen: {
        store: {
            '#Editor.plugins.MatchAnalysis.store.MatchAnalysis': {
                load : 'onAnalysisRecordLoad',
                metachange: 'onMetaChange',
                beforeload: 'onAnalysisBeforeLoad'
            }
        }
    },

    control: {
        '#pricingPresetId': {
            change: 'onPricingPresetChange'
        }
    },

    init : function() {
        var me=this;
        Ext.on({
            projectTaskSelectionChange:'onProjectTaskSelectionChange',
            scope:me
        });
    },

    /***
     * Event handler for project task change event.
     * On each task change, analysis store will be reloaded for the new task
     * @param newTask
     */
    onProjectTaskSelectionChange: function (newTask){
        var view = this.getView();
        if(!newTask){
            view?.setTask(newTask);
            return;
        }
        if (view) {

            view.down('#unitType').suspendEvent('change');
            view.down('#unitType').setValue(newTask.get('presetUnitType'));
            view.down('#unitType').resumeEvent('change');
            view.setTask(newTask);
            view.down('#pricingPresetId').getStore().clearFilter();
        }
    },

    /***
     * Export analysis by given type
     * @param type
     */
    exportAction:function (type){
        var me= this,
            params = {},
            task = me.getView().task;

        params.taskGuid = task.get('taskGuid');
        params.type = type.xtype === 'menuitem' ? type.masterComponent.itemId : type.itemId;
        params.unitType = me.getView().down('#unitType').getValue();
        window.open(Editor.data.restpath+'plugins_matchanalysis_matchanalysis/export?'+Ext.urlEncode(params));
    },

    /***
     * On match analysis record is loaded in the store
     */
    onAnalysisRecordLoad:function(store, records, success, operation) {

        // If Tasks-tab was opened by default on t5 app load,
        // and then MatchAnalysis window was opened via 'MatchAnalysis' menu-item
        // of the selected task's menu - this handler 'onAnalysisRecordLoad' is
        // triggered twice, because at this point we have two instances of matchAnalysisGrid-view
        // and each having it's own instance of matchAnalysisGrid-viewcontroller
        // Second ones are the ones instantiated by clicking 'MatchAnalysis' menu-item
        // and it's ok with that, but the first [view + viewcontroller] pair was created
        // on initial t5 app load, despite Projects-tab was not active by default, and this
        // means that task was NOT set up for the first view, as that view is not event exist in DOM
        // So in that case we just skip that
        if (!this.getView().task) {
            return;
        }

        // If load request was not successful for some reason - return
        if (success === false) {
            return;
        }

        var me=this,
        	view=me.getView(),
            vm = view.getViewModel(),
            task = view.task,
        	record=store.getAt(0),
            hasData = !!record,
            customerId = task.get('customerId'),
            pricingPresetId = operation.getResultSet().getMetadata().pricingPresetId,
            currency = operation.getResultSet().getMetadata().currency,
            noPricing = operation.getResultSet().getMetadata().noPricing,
            pricingPresetCombo = view.down('#pricingPresetId');

        vm.set('hasAnalysisData', hasData);

        view.down('#infoPanel').update({
            hasAnalysisData: hasData,
            created: record && record.get('created'),
            internalFuzzy: record && record.get('internalFuzzy'),
            errorCount: record && record.get('errorCount'),
            editFullMatch: task && task.get('edit100PercentMatch'),
            strings: view.strings
        });

        // Update preset store and make certain preset to be selected, if need
        if (!pricingPresetCombo.getStore() || customerId !== vm.get('customerId')) {
            vm.set('customerId', customerId);
            pricingPresetCombo.setStore(
                Ext.getStore('pricingPresetStore').createImportWizardSelectionData(customerId)
            );
        }
        pricingPresetCombo.suspendEvent('change');
        pricingPresetCombo.setValue(pricingPresetId);
        pricingPresetCombo.resumeEvent('change');

        // Get preset
        var preset = pricingPresetCombo.getSelection();
        if (Editor.app.authenticatedUser.isAllowed('pluginMatchAnalysisPricingPreset')) {
            pricingPresetCombo.setDisabled(!preset);
        }

        // Set price adjustment and final amount
        if (preset) {
            vm.set({
                priceAdjustment: preset.get('priceAdjustment'),
                finalAmount: Ext.util.Format.number(
                    preset.get('priceAdjustment') +
                    (store.last()?.get('unitCountTotal') || 0), '0.00'),
                currency: currency,
                noPricing: noPricing
            });
        } else {
            vm.set({
                priceAdjustment: 0,
                finalAmount: '0.00',
                currency: '',
                noPricing: true
            });
        }
    },

    /***
     * Before analysis store is loaded, add additional parameters to the proxy
     * @param store
     */
    onAnalysisBeforeLoad:function( store){
        var me = this,
            view = me.getView(),
            proxy=store.getProxy(),
            unitTypeField = view && view.down('#unitType'),
            unitTypeValue = Editor.data.plugins.MatchAnalysis.calculateBasedOn;

        try {
            // in some cases, when the grid is clicked to fast, the field dom element is not set (it can be because of
            // extjs cache) and getting the value using getValue method produces js error
            if(unitTypeField){
                unitTypeValue = unitTypeField.getValue();
            }
        }catch (e){
            if( !Ext.isEmpty(unitTypeField.value)){
                unitTypeValue = unitTypeField.value;
            }
        }
        var merged = Ext.merge({}, proxy.getExtraParams(), {
                unitType:unitTypeValue
            });
        proxy.setExtraParams(merged);
    },

    /**
     * reconfigure the grid to use the configured match rate fields
     * @param {Editor.plugins.MatchAnalysis.store.MatchAnalysis} store
     * @param {Object} meta
     */
    onMetaChange: function(store, meta) {
        var view = this.getView();

        view && view.reconfigure(store, view.getColumnConfig(meta.fields));
    },

    /***
     * Unit type combo box select - event handler
     */
    onUnitTypeSelect: function (combo, type){
        var me = this,
            view = me.getView(),
            store = view && view.getStore(),
            presetCombo = view.down('#pricingPresetId'),
            presetStore = presetCombo.getStore(),
            presetValueWas = presetCombo.getValue(),
            presetValueNow = 0;

        presetStore.clearFilter();
        presetStore.filterBy(function(record){
            if (record.get('unitType') === type.get('id')) {
                if (!presetValueNow) {
                    presetValueNow = record.get('id');
                }
                return true;
            }
        });

        if (presetValueWas === presetValueNow) {
            store && store.load();
        } else {
            presetCombo.setValue(presetValueNow).setDisabled(!presetValueNow);
            if (!presetValueNow) {
                store && store.load();
            }
        }
    },

    onPricingPresetChange: function (combo, value) {
        var me = this,
            view = me.getView(),
            task = view.task,
            preset = combo.getSelection();

        // If no preset - do nothing
        if (!value) return;

        Ext.Ajax.request({
            url: Editor.data.restpath + 'taskmeta',
            method: 'PUT',
            params: {
                id: task.get('taskGuid'),
                data: Ext.encode({
                    pricingPresetId: value
                })
            },
            success: xhr => {
                if (preset) {
                    task.set('presetId', preset.get('id'));
                    task.set('presetUnitType', preset.get('unitType'));
                }
                view.setTask(task)
            }
        });
    }
});