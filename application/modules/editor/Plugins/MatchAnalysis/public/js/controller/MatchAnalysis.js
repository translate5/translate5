
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
    }],
        
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
        termtaggerSegment:'#UT#Terminologie prüfen und markieren',
        analysisLoadingMsg:'#UT#Analyse läuft'
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
                render:'onLanguageResourcesPanelRender'
            },
            '#languageResourcesWizardPanel':{
            	startMatchAnalysis:'onStartMatchAnalysis'
            },
            'taskActionColumn': {
                itemsinitialized: 'onTaskActionColumnItemsInitialized'
            }
        },
        controller:{
        	'#admin.TaskOverview':{
        		taskCreated:'onTaskCreated',
                taskUnhandledAction: 'onTaskActionColumnNoHandler',
                taskStateCheckPullCleaned:'onTaskStateCheckPullCleaned'
            },
            '#LanguageResourcesTaskassoc':{
                taskAssocSavingFinished:'onTaskAssocSavingFinished'
            }
        },
        store:{
            '#Editor.store.LanguageResources.TaskAssocStore':{
                load:'onLanguageResourcesTaskAssocStoreLoad'
            }
        }
    },

    /**
     * Task action column items initialized event handler.
     */
    onTaskActionColumnItemsInitialized: function(items) {
        items.push({
            tooltip:this.strings.taskGridIconTooltip,
            iconCls: 'ico-task-analysis',
            isAllowedFor: 'editorAnalysisTask'   ,
            sortIndex:8,   
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
            groupIndex:1,
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
        var me=this,
            prefWindow=panel.up('window');
        
        //add the matchanalysis panel in the tabpanel
        panel.insert(2,{
           xtype:'matchAnalysisPanel',
           task:prefWindow.actualTask
        });
    },
    
    onLanguageResourcesPanelRender:function(panel){
    	var me=this,
    		win=panel.up('window'),
    		task=win.actualTask,
    		storeData=[], buttons = [];
    	
    	//init the pretranslate matchrate options (from 0-103)
    	for(var i=0;i<=103;i++){
    		storeData.push({
    			id:i,
    			value:i+"%"
    		});
    	}
    	
    	if(task && !task.isImporting()){
        	buttons = [{
                xtype:'button',
                text:this.strings.analysis,
                itemId:'btnAnalysis',
                width:150,
                dock:'bottom',
                //TODO icon
                listeners:{
                    click:{
                        fn:this.matchAnalysisButtonHandler,
                        scope:this
                    }
                }
            }];
    	}
    	
    	//the task exist->add buttons in the task assoc panel
    	panel.addDocked([{
            xtype : 'toolbar',
            dock : 'bottom',
            ui: 'footer',
            layout: {
                type: 'hbox',
                pack: 'start'
            },
            items: buttons
        },{
            xtype : 'toolbar',
            dock : 'bottom',
            ui: 'footer',
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
                        cls:'pretranslateCheckboxIcon',
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
                        queryMode: 'local',
                    }
                ]
            },{
                xtype:'checkbox',
                bind:{
                    disabled:'{!hasMt}'
                },
                value: 1,
                cls:'pretranslateCheckboxIcon',
                boxLabel:me.strings.pretranslateMt,
                autoEl: {
                    tag: 'div',
                    'data-qtip': me.strings.pretranslateMtTooltip
                },
                itemId:'pretranslateMt',
            },{
                xtype:'checkbox',
                bind:{
                    disabled:'{!hasTermcollection}'
                },
                value: 1,
                boxLabel:me.strings.termtaggerSegment,
                autoEl: {
                    tag: 'div',
                    'data-qtip': me.strings.termtaggerSegment
                },
                itemId:'termtaggerSegment',
            }]
        }]);
    },
    
    /***
     * When action column click with no click handler is found
     */
    onTaskActionColumnNoHandler:function(action, column, task){
        if(action != 'handleTaskAnalysis' || this.getAdminTaskPreferencesWindow()){
            return;
        }
        var me=this,
            taskPref = me.application.getController('admin.TaskPreferences');
        
        //display the task preferences window with focus on matchanalysis panel
        taskPref.handleTaskPreferences(task,'matchAnalysisPanel');
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
        this.updateTaskAssocPanelViewModel(store);
    },

    onStartMatchAnalysis:function(taskId,operation){
    	this.startAnalysis(taskId,operation);
    },
    
    matchAnalysisButtonHandler:function(button){
    	var me=this,
            win=button.up('window'),
            tmAndTermChecked=me.getComponentByItemId('pretranslateTmAndTerm').checked,
            mTChecked=me.getComponentByItemId('pretranslateMt').checked,
			task=win.actualTask,
			operation=(mTChecked || tmAndTermChecked) ? "pretranslation" : "analysis";
    	
    	me.startAnalysis(task.get('id'),operation);
    },
    
    /***
     * Start the match analysis or pretranslation for the taskId.
     * Operation can contains: 
     *    'analysis' -> runs only match analysis
     *    'pretranslation' -> runs match analysis with pretranslation
     */
    startAnalysis:function(taskId,operation){
    	//'editor/:entity/:id/operation/:operation',
        var me=this;
        
        me.addLoadingMask();
        Ext.Ajax.request({
            url: Editor.data.restpath+'task/'+taskId+'/'+operation+'/operation',
            method: "PUT",
            params: {
            	internalFuzzy: me.isChecboxChecked('cbInternalFuzzy'),
                pretranslateMatchrate: me.getComponentByItemId('cbMinMatchrate').getValue(),
                pretranslateTmAndTerm: me.isChecboxChecked('pretranslateTmAndTerm'),
                pretranslateMt: me.isChecboxChecked('pretranslateMt'),
                termtaggerSegment: me.isChecboxChecked('termtaggerSegment'),
                isTaskImport:me.getComponentByItemId('adminTaskAddWindow') ? 1 : 0
            },
            scope: this,
            timeout:600000,
            success: function(response){
                me.checkTaskState(taskId);
            }, 
            failure: function(response){
                me.removeLoadingMask();
            	Editor.app.getController('ServerException').handleException(response);
            }
        })
    },

    /***
     * Language resource to task assoc after save event handler
     */
    onTaskAssocSavingFinished:function(record,store){
        var me=this;
        me.updateTaskAssocPanelViewModel(store);
    },

    /***
     * Update the language resources task assoc panel view model
     */
    updateTaskAssocPanelViewModel:function(assocStore){
        var me=this,
            pnl=me.getLanguageResourceTaskAssocPanel(),
            store=assocStore ? assocStore : (me.getTaskAssocGrid() ? me.getTaskAssocGrid() : null);
        if(!pnl || !store){
            return;
        }
        //set the view model items variable
        pnl.getViewModel().set('items',(store.getData().getSource() || store.getData()).getRange());
    },
    
    /***
     * Get component by itemId
     */
    getComponentByItemId:function(itemId){
    	var cmp=Ext.ComponentQuery.query('#'+itemId);
    	if(cmp.length<1){
    		return null;
    	}
    	return cmp[0];
    },

    /***
     * Check if the checbox component is checked
     */
    isChecboxChecked:function(itemId){
        var component=this.getComponentByItemId(itemId);
        if(!component || component.isDisabled()){
            return 0;
        }
        return component.checked ? 1 : 0;
    },

    /***
     * Add tast state check conditional function, so the task reload condition depends also from the matchanalysis state
     */
    checkTaskState:function(taskId){
        //the task needs to be updated, so the last state is fetched from the db
        Ext.StoreManager.get('admin.Tasks').getById(taskId).load({
            success:function(){
                var controller=Editor.app.getController('Editor.controller.admin.TaskOverview');
                //add match analysis state checker function to the task state checker loop
                controller.addTaskStateCheckPull(function(rec){
                    return rec.get('state')=='matchanalysis';
                });
                controller.startCheckImportStates();
            }
        });
    },

    /***
     * Tash state check cleand event handler. This event is fired from Task overview controller.
     */
    onTaskStateCheckPullCleaned:function(){
        this.removeLoadingMask(true);
    },

    /***
     * Add loadin mask in match analysis panel and in the task assoc panel
     */
    addLoadingMask:function(){
        var me=this,
            assocPanel=me.getComponentByItemId('languageResourceTaskAssocPanel');
            matchAnalysisPanel=me.getComponentByItemId('matchAnalysisPanel');
        
        if(assocPanel && assocPanel.getEl()){
            assocPanel.getEl().mask(me.strings.analysisLoadingMsg);
        }

        if(matchAnalysisPanel && matchAnalysisPanel.getEl()){
            matchAnalysisPanel.getEl().mask(me.strings.analysisLoadingMsg);
        }
    },

    /***
     * Remove loading mask from task assoc panel and match analysis panel.
     * If the reloadStore is set, the analysis panel will be reloaded
     */
    removeLoadingMask:function(reloadStore){
        var me=this,
            assocPanel=me.getComponentByItemId('languageResourceTaskAssocPanel'),
            matchAnalysisPanel=me.getComponentByItemId('matchAnalysisPanel'),
            matchAnalysisGrid=me.getComponentByItemId('matchAnalysisGrid');
        
        if(!assocPanel){
            return;
        }
        assocPanel.unmask();;

        if(!matchAnalysisPanel){
            return;
        }
        if(reloadStore){
            matchAnalysisGrid.getStore().reload();
        }
        matchAnalysisPanel.getEl().unmask(me.strings.analysisLoadingMsg);
    }

});