
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
        'Editor.plugins.MatchAnalysis.view.LanguageResources'
    ],
    
    models: ['Editor.plugins.MatchAnalysis.model.MatchAnalysis'],
    stores:['Editor.plugins.MatchAnalysis.store.MatchAnalysis'],
    
    refs:[{
        ref: 'adminTaskPreferencesWindow',
        selector: 'adminTaskPreferencesWindow'
    },{
        ref: 'preferencesTabpanal',
        selector: 'adminTaskPreferencesWindow > tabpanel'
    },{
        ref:'matchAnalysisPanel',
        selector: 'matchAnalysisPanel'
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
    TASK_STATE_ANALYSIS: 'matchanalysis',
    strings:{
        taskGridIconTooltip:'#UT#Match-Analyse',
        finishTask:'#UT#Beenden',
        analysis:'#UT#Analyse Starten',
        preTranslation:'#UT#Analyse &amp; Vorübersetzungen starten',
        preTranslationTooltip:'#UT#Die Vorübersetzung löst auch eine neue Analyse aus',
        startAnalysisMsg:'#UT#Match-Analyse und Vorübersetzungen werden ausgeführt.',
        finishAnalysisMsg:'#UT#Die Match-Analyse und die Vorübersetzung sind abgeschlossen.',
        internalFuzzy:'#UT#Zähle interne Fuzzy',
        pretranslateMatchRate:'#UT#Vorübersetzungs Match-Rate',
        pretranslateMatchRateTooltip:'#UT#Vorübersetzung mit TM-Match, die größer oder gleich dem ausgewählten Wert ist',
        pretranslateTmAndTerm:'#UT#Vorübersetzen (TM &amp; Terme)',
        pretranslateTmAndTermTooltip:'#UT#Treffer aus der Terminologie werden bevorzugt vorübersetzt.',
        pretranslateMt:'#UT#Vorübersetzen (MT)',
        pretranslateMtTooltip:'#UT#Treffer aus dem TM werden bevorzugt vorübersetzt',
        analysisLoadingMsg:'#UT#Analyse läuft'
    },
    
    listeners:{
        beforeStartAnalysis:'onBeforeStartAnalysis'  
    },
    listen:{
        component:{
            '#adminTaskPreferencesWindow tabpanel':{
                render:'onTaskPreferencesWindowPanelRender'
            },
            '#adminTaskAddWindow': {
                beforerender:'onAdminTaskWindowBeforeRender'
            },
            '#languageResourceTaskAssocPanel':{
                render:'onLanguageResourcesPanelRender',
                activate:'onLanguageResourcesPanelActivate',
            },
            '#languageResourcesWizardPanel':{
                startMatchAnalysis:'onStartMatchAnalysis'
            },
            'taskActionMenu': {
                itemsinitialized: 'onTaskActionColumnItemsInitialized'
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

    /***
     * Queue the analysis when the import with defaults button is clicked
     * @param task
     */
    onWizardCardImportDefaults: function (task) {
        this.startAnalysis(task.get('id'),'pretranslation',true);
    },

    /***
     * Before analysis event handler
     */
    onBeforeStartAnalysis:function(taskId,operation){
        var me = this,
            setAnalysisRecordState=function(store,taskId){
                var record = store ? store.getById(taskId) : null;
                if(!record){
                    return;
                }
                record.set('state','matchanalysis');
            };
        //before the analysis is started, set the task state to 'matchanalysis'
        //the matchanalysis and languageresourcesassoc panel loading masks are binded 
        //to the task status. Changing the status to analysis will automaticly apply the loading masks for those panels
        setAnalysisRecordState(Ext.StoreManager.get('admin.Tasks'),taskId);
        setAnalysisRecordState(me.getProjectTaskGrid().getStore(),taskId);
    },
    
    /**
     * Task action column items initialized event handler.
     */
    onTaskActionColumnItemsInitialized: function(items) {
        var me=this;
        items.push({
            text:this.strings.taskGridIconTooltip,
            glyph: 'f200@FontAwesome5FreeSolid',
            action: 'editorAnalysisTask',
            hidden:true,
            bind:{
                hidden:'{!isNotErrorImportPendingCustom}'
            },
            scope:me,
            handler:me.onMatchAnalysisMenuClick,
            sortIndex:8
        });
    },
    /**
     * Inserts the language resource card into the task import wizard
     */
    onAdminTaskWindowBeforeRender:function(window,eOpts){
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
     * On task preferences window tabpanel render
     */
    onTaskPreferencesWindowPanelRender:function(panel){
        //add the matchanalysis panel in the tabpanel
        panel.insert(2,{
           xtype:'matchAnalysisPanel'
        });
    },

    onLanguageResourcesPanelRender:function(panel){
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
            layout: {
                type: 'hbox',
                pack: 'start'
            },
            items: [{
                xtype:'button',
                text:this.strings.analysis,
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
                        fn:this.matchAnalysisButtonHandler,
                        scope:this
                    }
                }
            }]
        },{
            xtype : 'toolbar',
            dock : 'bottom',
            ui: 'footer',
            bind:{
                disabled:'{!enableDockedToolbar || !hasLanguageResourcesAssoc}'
            },
            layout: {
                type: 'vbox',
                align: 'left'
            },
            items : [{
                xtype:'checkbox',
                value: 1,
                boxLabel:this.strings.internalFuzzy,
                itemId:'cbInternalFuzzy',
                dock:'bottom'
            },{
                dock:'bottom',
                xtype:'container',
                layout: {
                    type: 'hbox',
                    align: 'left'
                },
                items:[
                    {
                        xtype:'checkbox',
                        bind:{
                            disabled:'{!hasTmOrCollection}'
                        },
                        value: 1,
                        cls:'lableInfoIcon',
                        boxLabel:me.strings.pretranslateTmAndTerm,
                        autoEl: {
                            tag: 'div',
                            'data-qtip': me.strings.pretranslateTmAndTermTooltip
                        },
                        itemId:'pretranslateTmAndTerm',
                        padding: '0 20 0 0'
                    },{
                        xtype:'combobox',
                        stretch: false,
                        align: 'left',
                        itemId:'cbMinMatchrate',
                        fieldLabel: me.strings.pretranslateMatchRate,
                        tooltip:me.strings.pretranslateMatchRateTooltip,
                        store: Ext.create('Ext.data.Store', {
                            fields: ['id', 'value'],
                            data : storeData
                        }),
                        value:100,
                        displayField: 'value',
                        valueField: 'id',
                        queryMode: 'local'
                    }
                ]
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

    onLanguageResourcesPanelActivate:function (){
        // update the field defaults from config after the panel is visually visible
        this.updateDefaultFields();
    },

    /***
     * Event handler after a task was successfully created
     */
    onTaskCreated:function(task){
        this.loadTaskAssoc(task);
    },
    
    onLanguageResourcesWizardPanelActivate:function(panel){
        if(!panel.task){
            return;
        }
        var me=this;
        me.loadTaskAssoc(panel.task);

        // update the field defaults from config after the panel is visually visible
        me.updateDefaultFields();
    },
    
    onMatchAnalysisMenuClick:function(item){
        var me=this,
            task=item.lookupViewModel(true).get('task');
        me.getProjectPanel().getController().redirectFocus(task,true);
        me.getAdminTaskPreferencesWindow().down('tabpanel').setActiveTab('matchAnalysisPanel');
    },
    
    /***
     * Load match resources task assoc store
     */
    loadTaskAssoc:function(task){
        var taskAssoc=Editor.app.getController('Editor.controller.LanguageResourcesTaskassoc');
        //load the task assoc store
        taskAssoc.handleLoadPreferences(taskAssoc,task);
    },
    
    /***
     * On language resource task assoc store load event handler
     */
    onLanguageResourcesTaskAssocStoreLoad:function(store){
        var me=this;
        me.updateTaskAssoc(store);
    },

    /***
     * On task config store load event handler
     */
    onTaskConfigStoreLoad: function (){
        this.updateDefaultFields();
    },

    onStartMatchAnalysis:function(taskId,operation){
        this.startAnalysis(taskId,operation);
    },
    
    matchAnalysisButtonHandler:function(button){
        var me=this,
            win=button.up('#adminTaskPreferencesWindow'),
            tmAndTermChecked=me.isCheckboxChecked('pretranslateTmAndTerm'),
            mTChecked=me.isCheckboxChecked('pretranslateMt'),
            task=win.getCurrentTask(),
            operation=(mTChecked || tmAndTermChecked) ? "pretranslation" : "analysis";
        
        me.startAnalysis(task.get('id'),operation);
    },
    
    /***
     * Start the match analysis or pretranslation for the taskId.
     * Operation can contains:
     *    'analysis' -> runs only match analysis
     *    'pretranslation' -> runs match analysis with pretranslation
     *
     * @param taskId
     * @param operation
     * @param importDefaults : run analysis with defaults
     */
    startAnalysis:function(taskId,operation,importDefaults){
        //'editor/:entity/:id/operation/:operation',
        var me = this,
            removeDefaults = importDefaults===undefined ? false : importDefaults,
            params = {
                internalFuzzy: me.isCheckboxChecked('cbInternalFuzzy'),
                pretranslateTmAndTerm: me.isCheckboxChecked('pretranslateTmAndTerm'),
                pretranslateMt: me.isCheckboxChecked('pretranslateMt'),
                pretranslateMatchrate: me.getComponentByItemId('cbMinMatchrate').getValue(),
                isTaskImport:me.getComponentByItemId('adminTaskAddWindow') ? 1 : 0,
                batchQuery:me.isCheckboxChecked('batchQuery')
            };

        me.fireEvent('beforeStartAnalysis',taskId,operation);

        // when importing with defaults, those fields will be set on the backend
        if(removeDefaults){
            delete params.internalFuzzy;
            delete params.pretranslateTmAndTerm;
            delete params.pretranslateMt;
        }

        Ext.Ajax.request({
            url: Editor.data.restpath+'task/'+taskId+'/'+operation+'/operation',
            method: "PUT",
            params:params,
            scope: this,
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },
    
    /***
     * Language resource to task assoc after save event handler
     */
    onTaskAssocSavingFinished:function(record,store){
        this.updateTaskAssoc(store);
    },

    /***
     * Updates task assoc panel fields and view model fields
     */
    updateTaskAssoc:function(assocStore){
        var me=this,
            panels=Ext.ComponentQuery.query('languageResourceTaskAssocPanel'),
            store=assocStore ? assocStore : (me.getTaskAssocGrid() ? me.getTaskAssocGrid() : null);
        if(!panels || panels.length<1 || !store){
            return;
        }
        for(var i=0;i<panels.length;i++){
            var pnl=panels[i],
                vm=pnl.getViewModel();
            //set the view model items variable
            vm && vm.set('items',(store.getData().getSource() || store.getData()).getRange());
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
            pretranslateTmAndTerm = me.getComponentByItemId('pretranslateTmAndTerm');

        cbInternalFuzzy && cbInternalFuzzy.setValue(Editor.app.getTaskConfig('plugins.MatchAnalysis.internalFuzzyDefault'));
        pretranslateMt && pretranslateMt.setValue(Editor.app.getTaskConfig('plugins.MatchAnalysis.pretranslateMtDefault'));
        pretranslateTmAndTerm && pretranslateTmAndTerm.setValue(Editor.app.getTaskConfig('plugins.MatchAnalysis.pretranslateTmAndTermDefault'));
    },
    
    /***
     * Get component by itemId
     */
    getComponentByItemId:function(itemId){
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
    isCheckboxChecked:function(itemId){
        var component=this.getComponentByItemId(itemId);
        if(!component || component.isDisabled()){
            return 0;
        }
        return component.checked ? 1 : 0;
    }

});