
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
        'Editor.plugins.MatchAnalysis.view.MatchResources'
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
    }],
        
    strings:{
        taskGridIconTooltip:'#UT#Match-Analyse',
        finishTask:'#UT#Beenden',
        analysis:'#UT#Analyse',
        preTranslation:'#UT#Pre-translate',
        startAnalysisMsg:'#UT#Match-Analyse und Vorübersetzungen werden ausgeführt.',
        internalFuzzy:'#UT#Zähle interne Fuzzy',
        pretranslateMatchRate:'#UT#Vorübersetzungs Match-Rate',
        pretranslateMatchRateTooltip:'#UT#Vorübersetzung mit TM-Match, die größer oder gleich dem ausgewählten Wert ist'
    },
    
    listen:{
        component:{
            '#adminTaskPreferencesWindow tabpanel':{
                render:'onTaskPreferencesWindowPanelRender'
            },
            '#adminTaskAddWindow': {
                beforerender:'onAdminTaskWindowBeforeRender'
            },
            '#matchResourceTaskAssocPanel':{
            	render:'onMatchResourcesPanelRender'
            }
        },
        controller:{
        	'#Editor.plugins.MatchResource.controller.TaskAssoc':{
        		taskAssocSavingFinished:'onTaskAssocSavingFinished'
        	},
        	'#admin.TaskOverview':{
        		taskCreated:'onTaskCreated'
        	}
        }
    
    },
    
    onAdminTaskWindowBeforeRender:function(window,eOpts){
        var me=this;
        window.insertCard({
            xtype:'matchResourcesPanel',
            //index where the card should appear in the group
            groupIndex:1,
            listeners:{
                activate:{
                	fn:me.onMatchResourcesPanelActivate,
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
        panel.add({
           xtype:'matchAnalysisPanel',
           task:prefWindow.actualTask
        });
    },
    
    onMatchResourcesPanelRender:function(panel){
    	var me=this,
    		win=panel.up('window'),
    		task=win.actualTask;

    	//add internal fuzzy checkbox
    	panel.addDocked([{
    		xtype:'combobox',
    		stretch: false,
    		align: 'left',
    		itemId:'cbMinMatchrate',
    		fieldLabel: me.strings.pretranslateMatchRate,
    		tooltip:me.strings.pretranslateMatchRateTooltip,
    		store: Ext.create('Ext.data.Store', {
    			fields: ['id', 'value'],
    			data : [
    				{"id":"10", "value":"10%"},
    				{"id":"20", "value":"20%"},
    				{"id":"30", "value":"30%"},
    				{"id":"40", "value":"40%"},
    				{"id":"50", "value":"50%"},
    				{"id":"60", "value":"60%"},
    				{"id":"70", "value":"70%"},
    				{"id":"80", "value":"80%"},
    				{"id":"90", "value":"90%"},
    				{"id":"100", "value":"100%"},
    				]
    		}),
    		value:100,
    		displayField: 'value',
    		valueField: 'id',
    		queryMode: 'local',
    		dock:'bottom'
    	
    	},{
			xtype:'checkbox',
			boxLabel:this.strings.internalFuzzy,
			itemId:'cbInternalFuzzy',
			dock:'bottom'
		}]);
    	
    	
    	//if task is in import state, add checkboxes for the pretranslation and match analysis
    	if(!task || task.isImporting()){
    		panel.addDocked([{
    			xtype:'checkbox',
    			boxLabel:this.strings.preTranslation,
    			itemId:'cbPreTranslation',
    			dock:'bottom',
    			listeners:{
    				change:{
    					fn:this.onCbPreTranslationChecked,
    					scope:this
    				}
    			}
    		},{
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
    		}]);
    		return;
    	}
    	
    	//the task exist->add buttons in the task assoc panel
    	var dockedItems=panel.getDockedItems('toolbar[dock="bottom"]');
    	dockedItems=dockedItems[0];
    	
    	dockedItems.insert(1,{
    		xtype:'button',
			text:this.strings.preTranslation,
			itemId:'btnPreTranslation',
			width:150,
			//TODO icon
			listeners:{
				click:{
					fn:this.matchAnalysisButtonHandler,
					scope:this
				}
			}
    	});
    	
    	dockedItems.insert(2,{
    		xtype:'button',
			text:this.strings.analysis,
			itemId:'btnAnalysis',
			width:150,
			//TODO icon
			listeners:{
				click:{
					fn:this.matchAnalysisButtonHandler,
					scope:this
				}
			}
    	});
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
    
    onMatchResourcesPanelActivate:function(panel){
    	if(!panel.task){
    		return;
    	}
    	var me=this,
    		addWindow=panel.up('#adminTaskAddWindow'),
    		continueBtn=addWindow.down('#continue-wizard-btn');

    	me.loadTaskAssoc(panel.task);
        
        //set the finish icon text and cls
        //continueBtn.setIconCls('ico-task-add');
        //continueBtn.setText(me.strings.finishTask);
    },
    
    /***
     * Load match resources task assoc store
     */
    loadTaskAssoc:function(task){
        var me=this,
	        taskAssoc=Editor.app.getController('Editor.plugins.MatchResource.controller.TaskAssoc');
	    
	    //load the task assoc store
	    taskAssoc.handleLoadPreferences(taskAssoc,task);
    },
    
    /***
     * Event fired when all associated match resources for the task are saved
     */
    onTaskAssocSavingFinished:function(task){
    	var me=this,
    		cbPreTranslation=me.getComponentByItemId('cbPreTranslation'),
    		cbAnalysis=me.getComponentByItemId('cbAnalysis');
    	
    	if(!cbPreTranslation || !cbAnalysis){
    		return;
    	}
    	
    	if(cbPreTranslation.checked){
    		me.startAnalysis(task.get('id'),"pretranslation");
    		return;
    	}
    	
    	if(cbAnalysis.checked){
    		me.startAnalysis(task.get('id'),"analysis");
    		return;
    	}
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
			task=win.actualTask,
			operation=button.itemId=="btnAnalysis" ? "analysis" : "pretranslation";
    	
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
                	pretranslateMatchrate:me.getComponentByItemId('cbMinMatchrate').getValue()
                },
                scope: this,
                timeout:120000,
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
     * Get component by itemId
     */
    getComponentByItemId:function(itemId){
    	var cmp=Ext.ComponentQuery.query('#'+itemId);
    	if(cmp.length<1){
    		return;
    	}
    	return cmp[0];
    }
});