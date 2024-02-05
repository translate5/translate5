
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
 * @class Editor.view.LanguageResources.TmWindowViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.LanguageResources.TmWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.tmwindowviewcontroller',
    messages: {
        notConfigured: '#UT#Die Sprachressource "{0}" muss erst konfiguriert werden. Weitere Infos: {1}',
    },
    listen: {
        component: {
            'languagecombo': {
                change:'onLanguageComboChange'
            }
        }
    },
    
    /**
     * On add new tm window render handler
     */
    onTmWindowRender: function () {
        var me = this,
            view = me.getView();

        view.getViewModel().set('uploadLabel', view.strings.file);
    },

    labelTooltipInstance:null,

    /**
     * Resource combo handler.
     * Info: the resource value can be also null!!
     */
    onResourceChange: function(field,resource){
        var me = this,
            view = me.getView(),
            serviceName = field.getSelection() && field.getSelection().get('serviceName'),
            resourceType = field.getSelection() && field.getSelection().get('resourceType'),
            engineBased = field.getSelection() && field.getSelection().get('engineBased'),
            helppage = field.getSelection() && field.getSelection().get('helppage'),
            vm = view.getViewModel(),
            engineCombo = view.down('#engine');

        if (!me.isValidService(serviceName, helppage)) {
            return false;
        }
        
        vm.set('serviceName', serviceName);
        vm.set('resourceType', resourceType);
        vm.set('engineBased', engineBased);

        // upload field has different tooltip and label based on the selected resource
        me.updateUploadFieldConfig();

        let record = Ext.StoreManager.get('Editor.store.LanguageResources.Resources').getById(resource),
            sourceField = view.down('combo[name="sourceLang"]'),
            targetField = view.down('combo[name="targetLang"]');

        sourceField.suspendEvents();
        sourceField.clearValue(null);
        sourceField.resumeEvents();

        targetField.suspendEvents();
        targetField.clearValue(null);
        targetField.resumeEvents();

        if (record && me.isEngineBasedResource(record)) {

            engineCombo.suspendEvents();
            engineCombo.clearValue(null);
            engineCombo.getStore().clearFilter();
            engineCombo.getStore().setProxy({
                type: 'ajax',
                url: Editor.data.restpath + 'languageresourceresource/' + record.get('serviceName') + '/engines',
                reader : {
                    rootProperty: 'rows',
                    type : 'json'
                }
            });
            engineCombo.getStore().load();
            engineCombo.resumeEvents();
        }

        // set the source and target languages for the selected resource
        let sourceData = record ? record.get('sourceLanguages') : [],
            targetData = record ? record.get('targetLanguages') : [];
            
        sourceField.getStore().loadRawData(sourceData);
        targetField.getStore().loadRawData(targetData);
    },
    
    /**
     * Check if the selected service is valid to be used. If not, the user gets a message shown.
     * @returns boolean
     */
    isValidService: function (serviceName, helppage) {
        var me = this;
        // The resource combo now also includes unconfigured services.
        // Other than resources, the objects for these items only have a name, serviceName and helppage.
        // After "Cancel" (= helppage is null), no message is needed.
        if (helppage !== undefined && helppage !== null) {
            Editor.MessageBox.addError(Ext.String.format(me.messages.notConfigured, serviceName, helppage));
            return false;
        }
        return true;
    },

    /**
     * Engine combo change handler
     */
    onEngineComboChange: function(combo, newVal, oldVal, eOpts){
        var me = this,
            view = me.getView(),
            selection = combo.getSelection(),
            source = selection && selection.get('source'),
            target = selection && selection.get('target'),
            sourceField = view.down('combo[name="sourceLang"]'),
            targetField = view.down('combo[name="targetLang"]'),
            langStore = sourceField.getStore();

        //reset the store on empty value
        if(!newVal){
            combo.getStore().clearFilter();
        }

        //clean the languages selection on each engine change (Not if the cource/target are defined as wildcards ("*")

        if(source !== '*') {

            // first, suspend the events (the change event)
            sourceField.suspendEvents();
            sourceField.clearValue(null);

            //get the selection source rfc value, and set the source language combo with it
            if (source) {
                var sr = langStore.findRecord('rfc5646', source, 0, false, false, true);
                if (sr) {
                    sourceField.setSelection(sr);
                }
            }
            // after, resume events
            sourceField.resumeEvents();
        }

        if(target !== '*') {

            // first suspend the events (the change event)
            targetField.suspendEvents();
            targetField.clearValue(null);
            //get the selection target rfc value, and set the target language combo with it
            if (target) {
                var tr = langStore.findRecord('rfc5646', target, 0, false, false, true);
                if (tr) {
                    targetField.setSelection(tr);
                }
            }
            // after, resume events
            targetField.resumeEvents();
        }
    },

    /**
     * Source and target language change handler
     */
    onLanguageComboChange: function(){
        this.filterEngines();
    },

    onCustomersTagFieldChange:function(field,newValue){
        var me=this;
        // filter out all selected customers in useAsDefault which are not available in the current selection
        me.removeNotAvailableCustomers(me.getView().down('#useAsDefault'),newValue);
        // filter out all selected customers in pivotAsDefault which are not available in the current slection
        me.removeNotAvailableCustomers(me.getView().down('#pivotAsDefault'),newValue);
    },

    onCustomersReadTagFieldChange:function(field,newValue){
        var me=this;
        // filter out all selected customers in writeAsDefault which are not available in the current selection
        me.removeNotAvailableCustomers(me.getView().down('#writeAsDefault'),newValue);
    },

    /***
     * Remove the selected options in child which are not available in the source
     * @param child
     * @param source
     */
    removeNotAvailableCustomers:function(child,source){
        child.setValue(Ext.Array.intersect(child.getValue(),source));
    },

    /**
     * Filter the engines store, when source or target language is selected
     */
    filterEngines:function(){
        var me = this,
            view = me.getView(),
            engineComboField = view.down('combo[name="engines"]:visible(true)'),
            engineComboStore,
            selectedEngine,
            sourceLang,
            targetLang,
            filterData = [];
        
        if (engineComboField === null) {
            return;
        }
        selectedEngine = engineComboField.getSelection();
        engineComboStore = engineComboField.getStore();
        sourceLang = view.down('combo[name="sourceLang"]').getSelection();
        targetLang = view.down('combo[name="targetLang"]').getSelection();

        //clean the engine filters
        engineComboStore.clearFilter();

        //preselect when only one result is filtered
        engineComboStore.on({
            filterchange:function (sdlstore){
                if(sdlstore.getData().length === 1){
                    engineComboField.suspendEvents();
                    engineComboField.setSelection(sdlstore.getAt(0));
                    engineComboField.resumeEvents();
                }
            }
        });

        engineComboField.suspendEvents();
        //clean the selected value in the sdl combo if it does not comply with the currently selected languages
        if(!(selectedEngine && (!sourceLang || selectedEngine.get('source') === '*') && (!targetLang || selectedEngine.get('target') === '*'))){
            engineComboField.clearValue(null);
        }

        //if source is selected, create source filter
        if(sourceLang){
            filterData.push({
                property: 'source',
                value: sourceLang.get('rfc5646')
            });
            filterData.push({
                property: 'source',
                value: '*'
            });
        }

        //if target is selected, create target filter
        if(targetLang){
            filterData.push({
                property: 'target',
                value: targetLang.get('rfc5646')
            });
            filterData.push({
                property: 'target',
                value: '*'
            });
        }

        //apply the filters to the sdl language store
        if(filterData.length > 0){
            engineComboStore.addFilter(filterData, true);
        }

        engineComboField.resumeEvents();
    },

    /**
     * Is the current selected resource uses engines conception
     */
    isEngineBasedResource: function (resourceType) {
        return resourceType.get('engineBased');
    },

    /***
     * Is the current selected resource of termcollection type
     */
    isTermcollectionResource:function(){
        var vm=this.getView().getViewModel();
        return vm.get('serviceName')==Editor.model.LanguageResources.Resource.TERMCOLLECTION_SERVICE_NAME;
    },

    /***
     * Set custom config for the upload field based on the selected resource.
     */
    updateUploadFieldConfig:function (){
        var me=this,
            view=me.getView(),
            uploadField=view.down('filefield[name="tmUpload"]'),
            isTc = me.isTermcollectionResource();

        // create tooltip instance to show fileupload field tooltips.
        if(me.labelTooltipInstance === null){
            me.labelTooltipInstance = Ext.create('Ext.tip.ToolTip', {
                target: uploadField.labelEl
            });
        }
        me.labelTooltipInstance.setHtml(isTc ? view.strings.collectionUploadTooltip : view.strings.file);

        uploadField.regexText = isTc ? view.strings.importTbxType : view.strings.importTmxType;
        uploadField.regex = isTc ? view.tbxRegex : view.tmxRegex;
        view.getViewModel().set('uploadLabel',isTc ? view.strings.collection : view.strings.file);

        isTc ? uploadField.labelEl.addCls('lableInfoIcon') : uploadField.labelEl.removeCls('lableInfoIcon');
    },

    onResourceBeforeSelect: function(combo, record) {
        if (!record.data.creatable) {
            return false;
        }
    },
});
