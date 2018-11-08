
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
        preTranslation:'#UT#Analyse & Vorübersetzungen Starten',
        preTranslationTooltip:'#UT#Die Vorübersetzung löst auch eine neue Analyse aus',
        startAnalysisMsg:'#UT#Match-Analyse und Vorübersetzungen werden ausgeführt.',
        internalFuzzy:'#UT#Zähle interne Fuzzy',
        pretranslateMatchRate:'#UT#Vorübersetzungs Match-Rate',
        pretranslateMatchRateTooltip:'#UT#Vorübersetzung mit TM-Match, die größer oder gleich dem ausgewählten Wert ist',
        pretranslateTmAndTerm:'#UT#Vorübersetzen (TM & Terme)',
        pretranslateTmAndTermTooltip:'#UT#Treffer aus der Terminologie werden bevorzugt vorübersetzt.',
        pretranslateMt:'#UT#Vorübersetzen (MT)',
        pretranslateMtTooltip:'#UT#Treffer aus dem TM werden bevorzugt vorübersetzt'
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
            }
        },
        controller:{
        	'#admin.TaskOverview':{
        		taskCreated:'onTaskCreated'
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

    init : function() {
        var me = this,
            toc = me.application.getController('admin.TaskOverview');
        toc.on('taskActionColumnNoHandler', me.onTaskActionColumnNoHandler, me);
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
    		storeData=[];
    	
    	//init the pretranslate matchrate options (from 0-103)
    	for(var i=0;i<=103;i++){
    		storeData.push({
    			id:i,
    			value:i+"%"
    		});
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
            items:[
                this.getAnalysisConfig(task)
            ]
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
                value:true,
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
                        value:true,
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
                cls:'pretranslateCheckboxIcon',
                boxLabel:me.strings.pretranslateMt,
                autoEl: {
                    tag: 'div',
                    'data-qtip': me.strings.pretranslateMtTooltip
                },
                itemId:'pretranslateMt',
            }]
        }]);
    },
    
    /***
     * When action column click with no click handler is found
     */
    onTaskActionColumnNoHandler:function(column,task){
        if(this.getAdminTaskPreferencesWindow()){
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
        var me=this,
	        taskAssoc=Editor.app.getController('Editor.controller.LanguageResourcesTaskassoc');
	    
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
    
    /***
     * analysis checkbox check change handler
     */
    onCbAnalysisChecked:function(field,newValue,oldValue,eOpts){ 
    	var me=this,
    		cbPreTranslation=me.getComponentByItemId('cbPreTranslation'),
    		win=me.getAdminTaskPreferencesWindow();
    	if(!newValue){
    		cbPreTranslation.setValue(newValue);
    	}
    },
    
    /***
     * pre translation checkbox check change handler
     */
    onCbPreTranslationChecked:function(field,newValue,oldValue,eOpts){ 
    	var me=this,
    		cbAnalysis=me.getComponentByItemId('cbAnalysis');
    	if(newValue){
    		cbAnalysis.setValue(newValue);
    	}
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
        			
        me.reloadTaskRecord(taskId);
        
    	Editor.MessageBox.addInfo(me.strings.startAnalysisMsg);
    	Ext.Ajax.request({
            url:Editor.data.restpath+'task/'+taskId+'/'+operation+'/operation',
                method: "PUT",
                params:{
                	internalFuzzy:me.getComponentByItemId('cbInternalFuzzy').checked,
                    pretranslateMatchrate:me.getComponentByItemId('cbMinMatchrate').getValue(),
                    pretranslateTmAndTerm:me.getComponentByItemId('pretranslateTmAndTerm').checked,
                    pretranslateMt:me.getComponentByItemId('pretranslateMt').checked,
                },
                scope: this,
                timeout:600000,
                success: function(response){
                	me.reloadTaskRecord(taskId);
                }, 
                failure: function(response){
                	Editor.app.getController('ServerException').handleException(response);
                	me.reloadTaskRecord(taskId);
                }
        })
    },
    
    /***
     * Reload task record
     */
    reloadTaskRecord:function(taskId){
    	var me=this,
    		taskOverview = me.application.getController('admin.TaskOverview'),
    		taskStore=taskOverview.getAdminTasksStore();
		//TODO reload only one row
    	taskStore.reload();
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
    		return;
    	}
    	return cmp[0];
    },
    
    /***
     * Get the analysis config. It can be checkbox or button depending if the task exist or not.
     */
    getAnalysisConfig:function(task){
    	if(!task || task.isImporting()){
    		return {
    			xtype:'checkbox',
    			boxLabel:this.strings.analysis,
    			itemId:'cbAnalysis',
    			dock:'bottom',
    			listeners:{
    				change:{
    					fn:this.onCbAnalysisChecked,
    					scope:this
    				}
    			}
    		};
    	}
    	return {
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
    	};
    }
});