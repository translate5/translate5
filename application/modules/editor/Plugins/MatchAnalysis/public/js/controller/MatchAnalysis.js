
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
 * @class Editor.plugins.MatchAnalysis.controller.MatchAnalysis
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.MatchAnalysis.controller.MatchAnalysis', {
    extend: 'Ext.app.Controller',
    
    requires: [
        'Editor.plugins.MatchAnalysis.view.AnalysisPanel',
        'Editor.plugins.MatchAnalysis.view.LanguageResources',
        'Editor.plugins.MatchAnalysis.view.FuzzyBoundaryConfig',
        'Editor.plugins.MatchAnalysis.view.AnalysisWindow',
        'Editor.plugins.MatchAnalysis.store.admin.pricing.PresetStore'
    ],
    
    models: ['Editor.plugins.MatchAnalysis.model.MatchAnalysis'],
    stores:['Editor.plugins.MatchAnalysis.store.MatchAnalysis'],
    
    refs:[{
        ref: 'adminTaskTaskManagement',
        selector: 'adminTaskTaskManagement'
    },{
        ref: 'preferencesTabpanal',
        selector: 'adminTaskTaskManagement > tabpanel'
    },{
        ref:'matchAnalysisPanel',
        selector: 'matchAnalysisPanel'
    },{
        ref: 'analysisRerunMsg',
        selector: '#analysisNeedRerun'
    },{
        ref:'languageResourceTaskAssocPanel',
        selector:'languageResourceTaskAssocPanel'
    },{
        ref: 'taskAssocGrid',
        selector: '#languageResourcesTaskAssocGrid'
    },{
      ref:'projectPanel',
      selector:'#projectPanel'
    },{
        ref:'projectTaskGrid',
        selector:'#projectTaskGrid'
    },{
        ref: 'taskAddWindow',
        selector: '#adminTaskAddWindow'
    }],
    strings:{
        taskGridIconTooltip:'#UT#Match-Analyse',
        finishTask:'#UT#Beenden',
        analysis:'#UT#Analyse Starten',
        analysisHint:'#UT#Umfasst eine QS und Terminologie Prüfung',
        startTermCheck:'#UT#Terminologie Prüfung starten',
        preTranslation:'#UT#Analyse &amp; Vorübersetzungen starten',
        preTranslationTooltip:'#UT#Die Vorübersetzung löst auch eine neue Analyse aus',
        startOperationMsg:'#UT#Match-Analyse und Vorübersetzungen werden ausgeführt.',
        finishAnalysisMsg:'#UT#Die Match-Analyse und die Vorübersetzung sind abgeschlossen.',
        internalFuzzy:'#UT#Zähle interne Fuzzy',
        pretranslateMatchRate:'#UT#TM Vorübersetzungs Match-Rate',
        pretranslateMatchRateTooltip:'#UT#Vorübersetzung mit TM-Match, die größer oder gleich dem ausgewählten Wert ist',
        pretranslateTmAndTerm:'#UT#Vorübersetzen (TM &amp; Terme)',
        pretranslateTmAndTermTooltip:'#UT#Treffer aus der Terminologie werden bevorzugt vorübersetzt.',
        pretranslateMt:'#UT#Vorübersetzen (MT)',
        pretranslateMtTooltip:'#UT#Wenn diese Option aktiviert ist, werden alle Treffer, die nicht aus dem TM vorübersetzt sind, von der MT vorübersetzt.',
        analysisLoadingMsg:'#UT#Analyse läuft'
    },
    listen:{
        component:{
            '#adminTaskTaskManagement #taskManagementTabPanel':{
                render:'onTaskTaskManagementPanelRender'
            },
            '#adminTaskAddWindow': {
                beforerender:'onAdminTaskWindowBeforeRender'
            },
            '#languageResourceTaskAssocPanel':{
                render:'onLanguageResourcesPanelRender',
                activate:'onLanguageResourcesPanelActivate',
            },
            '#languageResourcesWizardPanel':{
                startMatchAnalysis: 'onStartOperation'
            },
            'taskActionMenu': {
                itemsinitialized: 'onTaskActionColumnItemsInitialized',
                show: 'onTaskActionMenuShow'
            }
        },
        controller:{
            '#admin.TaskOverview':{
                taskCreated:'onTaskCreated',
                wizardCardImportDefaults:'onWizardCardImportDefaults'
            },
            '#LanguageResourcesTaskassoc':{
                taskAssocSavingFinished:'onTaskAssocSavingFinished'
            }
        },
        store:{
            '#languageResourcesTaskAssoc':{
                load:'onLanguageResourcesTaskAssocStoreLoad'
            },
            '#projectTasks': {
                load: 'onProjectTaskLoad'
            }
        }
    },

    init : function() {
        var me=this;
        Ext.StoreManager.get('admin.task.Config').on({
            load:{
                fn:'onTaskConfigStoreLoad',
                scope:me
            }
        });
    },

    onLaunch: function(){

        // Create preset store
        Ext.create('Editor.plugins.MatchAnalysis.store.admin.pricing.PresetStore');
    },

    /***
     * Queue the analysis when the import with defaults button is clicked
     * @param task
     */
    onWizardCardImportDefaults: function (task) {
        this.startOperation(task.get('id'), 'pretranslation', true);
    },

    /**
     * Task action column items initialized event handler.
     */
    onTaskActionColumnItemsInitialized: function(items) {
        var me=this;
        items.push({
            text: me.strings.taskGridIconTooltip,
            itemId:'analysisActionItem',
            glyph: 'f200@FontAwesome5FreeSolid',
            action: 'editorAnalysisTask',
            hidden:true,
            bind:{
                hidden:'{!isNotErrorImportPendingCustom}'
            },
            scope:me,
            disabled: true,
            handler:me.onMatchAnalysisMenuClick,
            sortIndex:8
        });
    },

    /***
     *
     * @param menu
     */
    onTaskActionMenuShow: function (menu){
        var configStore = Ext.create('Editor.store.admin.CustomerConfig'),
            vm = menu.getViewModel(),
            task = vm && vm.get('task');

        if(!task){
            return;
        }

        configStore.loadByCustomerId(task.get('customerId'),function (){
            var config = configStore.getConfig('plugins.MatchAnalysis.enableAnalysisActionMenu');
            if (menu.rendered) {
                menu.down('#analysisActionItem').setDisabled(!config);
            }
        });
    },

    /**
     * Inserts the language resource card into the task import wizard
     */
    onAdminTaskWindowBeforeRender: function(window){
        var me=this;
        window.insertCard({
            xtype:'languageResourcesWizardPanel',
            //index where the card should appear in the group
            groupIndex:2,
            listeners:{
                activate:{
                    fn:me.onLanguageResourcesWizardPanelActivate,
                    scope:me
                }
            }
        },'postimport');
    },

    /***
     * On task management tabpanel render
     */
    onTaskTaskManagementPanelRender: function(panel){
        //add the matchanalysis panel in the tabpanel
        panel.insert(2,{
           xtype:'matchAnalysisPanel'
        });
    },

    onLanguageResourcesPanelRender: function(panel){
        var me=this,
            storeData=[];

        //init the pretranslate matchrate options (from 0-103)
        for(var i=0;i<=103;i++){
            storeData.push({
                id:i,
                value:i+"%"
            });
        }
        
        panel.addDocked([{
            xtype : 'toolbar',
            dock : 'bottom',
            ui: 'footer',
            border: '1 0 0 0',
            items: [{
                xtype:'button',
                text: me.strings.analysis,
                itemId:'btnAnalysis',
                width:150,
                dock:'bottom',
                glyph: 'f200@FontAwesome5FreeSolid',
                bind:{
                    disabled:'{!enableDockedToolbar || !hasLanguageResourcesAssoc}',
                    hidden:'{isAnalysisButtonHidden}'
                },
                listeners:{
                    click:{
                        fn: me.matchAnalysisButtonHandler,
                        scope:me
                    }
                }
            },{
                xtype: 'tbseparator'
            },{
                xtype: 'container',
                html: me.strings.analysisHint,
                bind: {
                    hidden:'{isAnalysisButtonHidden}'
                }
            },{
                xtype: 'container',
                html: me.strings.analysisNeedRerun,
                itemId: 'analysisNeedRerun',
                hidden: true,
                style: {
                    color: 'red'
                }
            }]
        },{
            xtype : 'container',
            dock : 'bottom',
            bind:{
                disabled:'{!enableDockedToolbar || !hasLanguageResourcesAssoc}'
            },
            layout: {
                type: 'column',
                //align: 'left'
            },
            padding: 5,
            defaults: {
                padding: 5
            },
            items : [{
                xtype:'checkbox',
                value: 1,
                boxLabel: me.strings.internalFuzzy,
                itemId: 'cbInternalFuzzy',
            },{
                xtype:'checkbox',
                bind:{
                    disabled:'{!hasTmOrCollection}'
                },
                value: 1,
                cls: 'lableInfoIcon',
                boxLabel:me.strings.pretranslateTmAndTerm,
                autoEl: {
                    tag: 'div',
                    'data-qtip': me.strings.pretranslateTmAndTermTooltip
                },
                itemId:'pretranslateTmAndTerm',
            },{
                xtype:'combobox',
                // stretch: false,
                // align: 'left',
                itemId:'cbMinMatchrate',
                fieldLabel: me.strings.pretranslateMatchRate,
                labelWidth: 120,
                tooltip:me.strings.pretranslateMatchRateTooltip,
                store: Ext.create('Ext.data.Store', {
                    fields: ['id', 'value'],
                    data : storeData
                }),
                value:Editor.app.getTaskConfig('plugins.MatchAnalysis.pretranslateMatchRate'),
                displayField: 'value',
                valueField: 'id',
                queryMode: 'local'
            },{
                xtype:'checkbox',
                bind:{
                    disabled:'{!hasMt}'
                },
                value: 1,
                cls:'lableInfoIcon',
                boxLabel:me.strings.pretranslateMt,
                autoEl: {
                    tag: 'div',
                    'data-qtip': me.strings.pretranslateMtTooltip
                },
                itemId:'pretranslateMt'
            }]
        }]);
    },

    onLanguageResourcesPanelActivate: function (){
        // update the field defaults from config after the panel is visually visible
        this.updateDefaultFields();
    },

    /***
     * Event handler after a task was successfully created
     */
    onTaskCreated: function(task){
        this.loadTaskAssoc(task);
    },
    
    onLanguageResourcesWizardPanelActivate: function(panel){
        if(!panel.task){
            return;
        }
        var me=this;
        me.loadTaskAssoc(panel.task);

        // update the field defaults from config after the panel is visually visible
        me.updateDefaultFields();
    },
    
    onMatchAnalysisMenuClick: function(item){
        var task = item.lookupViewModel(true).get('task'),
            win = Ext.create('Editor.plugins.MatchAnalysis.view.AnalysisWindow');

        win.setTask(task);
        win.show();
    },
    
    /***
     * Load match resources task assoc store
     */
    loadTaskAssoc: function(task){
        var taskAssoc=Editor.app.getController('Editor.controller.LanguageResourcesTaskassoc');
        //load the task assoc store
        taskAssoc.handleLoadPreferences(taskAssoc,task);
    },
    
    /***
     * On language resource task assoc store load event handler
     */
    onLanguageResourcesTaskAssocStoreLoad: function(store){
        this.updateTaskAssoc(store);
    },

    /***
     * On task config store load event handler
     */
    onTaskConfigStoreLoad: function (){
        this.updateDefaultFields();
    },

    onStartOperation: function(taskId, operation){
        this.startOperation(taskId, operation, false);
    },
    
    matchAnalysisButtonHandler: function(){
        var me = this,
            taskManagement = me.getAdminTaskTaskManagement(),
            tmAndTermChecked = me.isCheckboxChecked('pretranslateTmAndTerm'),
            mTChecked = me.isCheckboxChecked('pretranslateMt'),
            assocGridChanges = me.getTaskAssocGrid().getStore().getModifiedRecords(),
            assocController = Editor.app.getController('Editor.controller.LanguageResourcesTaskassoc'),
            task = taskManagement.getCurrentTask(),
            operation = (mTChecked || tmAndTermChecked) ? "pretranslation" : "analysis";

        // Check how much assoc-records have changes but not yet saved
        me.unsavedAssocQueueSize = assocGridChanges.length;

        // Hide rerun msg
        me.getAnalysisRerunMsg().hide();

        // If nothing unsaved - start operation
        if (me.unsavedAssocQueueSize === 0) {

            // Remove loading mask
            taskManagement.setLoading(false);

            // Start operation
            me.startOperation(task.get('id'), operation, false);

        // Else cut and save the first from unsaved and do that until no unsaved left
        } else {
            assocController.saveRecord(assocGridChanges.shift(), true);
        }
    },

    /***
     * Start the match analysis or pretranslation for the taskId.
     * Operation can contains:
     *    'analysis' -> runs only match analysis
     *    'pretranslation' -> runs match analysis with pretranslation
     *
     * @param {int} taskId
     * @param {string} operation
     * @param {bool} importDefaults run analysis with defaults
     */
    startOperation: function(taskId, operation, importDefaults){
        var params = {
            isTaskImport: this.getComponentByItemId('adminTaskAddWindow') ? 1 : 0,
            batchQuery: this.isCheckboxChecked('batchQuery')
        };
        // when importing with defaults, those fields will be set on the backend
        if(!importDefaults){
            params.internalFuzzy = this.isCheckboxChecked('cbInternalFuzzy');
            params.pretranslateTmAndTerm = this.isCheckboxChecked('pretranslateTmAndTerm');
            params.pretranslateMt = this.isCheckboxChecked('pretranslateMt');
            params.pretranslateMatchrate = this.getComponentByItemId('cbMinMatchrate').getValue();
        }
        Editor.util.TaskActions.operation(operation, taskId, params);
    },
    
    /***
     * Language resource to task assoc after save event handler
     */
    onTaskAssocSavingFinished: function(record, store){
        this.updateTaskAssoc(store);
        if (this.unsavedAssocQueueSize) {
            this.matchAnalysisButtonHandler();
        }
    },

    /***
     * Updates task assoc panel fields and view model fields
     */
    updateTaskAssoc: function(assocStore){
        var me = this,
            panels = Ext.ComponentQuery.query('languageResourceTaskAssocPanel'),
            store = assocStore ? assocStore : (me.getTaskAssocGrid() ? me.getTaskAssocGrid() : null); // TODO FIXME: How can a Panel act as a store ??
        if(!panels || panels.length < 1 || !store){
            return;
        }
        for(var i=0; i < panels.length; i++){
            var pnl = panels[i],
                vm = pnl.getViewModel(),
                items = (store.getData().getSource() || store.getData()).getRange();
               //set the view model items variable
            vm && vm.set('items', items);
        }
    },

    /***
     * Update analysis default checkbox values from the task config
     * @param panel
     */
    updateDefaultFields: function(){
        var me=this,
            cbInternalFuzzy = me.getComponentByItemId('cbInternalFuzzy'),
            pretranslateMt = me.getComponentByItemId('pretranslateMt'),
            pretranslateTmAndTerm = me.getComponentByItemId('pretranslateTmAndTerm'),
            cbMinMatchrate = me.getComponentByItemId('cbMinMatchrate');

        cbInternalFuzzy && cbInternalFuzzy.setValue(Editor.app.getTaskConfig('plugins.MatchAnalysis.internalFuzzyDefault'));
        pretranslateMt && pretranslateMt.setValue(Editor.app.getTaskConfig('plugins.MatchAnalysis.pretranslateMtDefault'));
        pretranslateTmAndTerm && pretranslateTmAndTerm.setValue(Editor.app.getTaskConfig('plugins.MatchAnalysis.pretranslateTmAndTermDefault'));
        cbMinMatchrate && cbMinMatchrate.setValue(Editor.app.getTaskConfig('plugins.MatchAnalysis.pretranslateMatchRate'));
    },
    
    /***
     * Get component by itemId
     */
    getComponentByItemId: function(itemId){
        var context = Ext.ComponentQuery.query('adminTaskAddWindow')[0], // for import use the import window as context
            cmp=null;

        // for non import call, use the default language resources reference
        if(context === undefined){
            context = Ext.ComponentQuery.query('languageResourceTaskAssocPanel')[0];
        }
        if(context === undefined){
            return null;
        }
        cmp=context.query('#'+itemId);
        if(cmp.length<1){
            return null;
        }
        return cmp[0];
    },

    /***
     * Check if the checbox component is checked
     */
    isCheckboxChecked: function(itemId){
        var component=this.getComponentByItemId(itemId);
        if(!component || component.isDisabled()){
            return 0;
        }
        return component.checked ? 1 : 0;
    },

    /**
     * Disable analysis tab if project has no tasks for some reason
     *
     * @param store
     * @param records
     * @param successful
     */
    onProjectTaskLoad: function(store, records, successful) {
        if (successful === false) {
            return;
        }
        if (!records.length) {
            this.getMatchAnalysisPanel().setDisabled();
            this.getMatchAnalysisPanel().down('grid').getViewModel().set('hasAnalysisData', false);
        }
    }
});