
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.view.admin.ProjectGrid', {
	extend: 'Editor.view.admin.TaskGrid',
	alias: 'widget.projectGrid',
    requires:[
    	'Editor.view.admin.ProjectGridViewController',
	],
	controller:'projectGrid',
	itemId: 'projectGrid',
	strings: {
		addProject:'#UT#Projekt hinzufügen',
		addProjectTip:'#UT#Neues Projekt hinzufügen',
		reloadBtn: '#UT#Aktualisieren',
		reloadBtnTip: '#UT#Projektliste vom Server aktualisieren.'
	},
	
	visibleColumns:[
		'id',
		'taskGridActionColumn',
		'taskName',
		'customerId',
		'pmName',
		'orderdate',
		'emptyTargets',
		'enableSourceEditing'
	],
	
	text_cols: { // in case of any changes, pls also update getTaskGridTextCols() in editor_Models_Task
	      // sorted by appearance
		workflow: '#UT#Workflow',
		taskActions: '#UT#Aktionen',
		state: '#UT#Aufgabenstatus',
		customerId: '#UT#Endkunde',
		taskName: '#UT#Projektname',
		taskNr: '#UT#Auftragsnr.',
		wordCount: '#UT#Wörter',
		wordCountTT: '#UT#Anzahl Wörter',
		fileCount: '#UT#Dateien',
		sourceLang: '#UT#Quellsprache',
		relaisLang: '#UT#Relaissprache',
		targetLang: '#UT#Zielsprache',
		referenceFiles: '#UT#Referenzdateien',
		terminologie: '#UT#Terminologie',
		userCount: '#UT#Zahl zugewiesener Benutzer',
		users: '#UT#Benutzer',
		taskassocs: '#UT#Anzahl zugewiesene Sprachresourcen',
		pmName: '#UT#Projektmanager',
		pmGuid: '#UT#Projektmanager',
		orderdate: '#UT#Bestelldatum',
		edit100PercentMatch: '#UT#100%-Treffer editierbar',
		fullMatchEdit: '#UT#100% Matches sind editierbar',
		emptyTargets: '#UT#Übersetzungsaufgabe (kein Review)',
		lockLocked: '#UT#In importierter Datei gesperrte Segmente sind in translate5 gesperrt',
		enableSourceEditing: '#UT#Quellsprache bearbeitbar',
		workflowState:'#UT#Workflow-Status',//Info:(This is not task grid column header) this is an advanced filter label text. It is used only for advanced filter label in the tag field
		workflowUserRole:'#UT#Benutzer-Rolle',//Info:(This is not task grid column header) this is an advanced filter label text. It is used only for advanced filter label in the tag field
		userName:'#UT#Benutzer',//Info:(This is not task grid column header) this is an advanced filter label text. It is used only for advanced filter label in the tag field
		segmentCount:'#UT#Segmentanzahl',
		segmentFinishCount:'#UT#% abgeschlossen',
		id:'#UT#Id',
		taskGuid:'#UT#Task-Guid',
		workflowStepName:'#UT#Aktueller Workflow-Schritt',
		userState:'#UT#Mein Job-Status',
		userJobDeadline:'#UT#Meine Deadline',
		assignmentDate:'#UT#Benutzer-Zuweisungsdatum',
		finishedDate:'#UT#Benutzer-Abschlussdatum',
		deadlineDate:'#UT#Benutzer-Deadline/s',
		assignmentDateHeader:'#UT#Zuweisungsdatum',
		finishedDateHeader:'#UT#Abschlussdatum',
		deadlineDateHeader:'#UT#Deadline Datum',
	},
	
	store: 'admin.Project',
	autoLoad: false,
	bufferedRenderer:false,//info: this will stop the store filter binding.

	viewConfig: {
	      getRowClass: function(task) {
	          var res = [],
	              user = Editor.app.authenticatedUser,
	              actions = this.panel.availableActions;
	          Ext.Array.each(actions, function(action) {
	              if(user.isAllowed(action, task)) {
	                  res.push(action);
	              }
	          });
	          return res.join(' ');
	      }
	},
    initConfig: function(instanceConfig) {
        var me = this,
        	config={
        		dockedItems: [{
        	        xtype: 'toolbar',
        	        dock: 'top',
        	        items: [{
        	            xtype: 'button',
        	            iconCls: 'ico-refresh',
        	            itemId: 'reloadProjectbtn',
        	            text: me.strings.reloadBtn,
        	            tooltip: me.strings.reloadBtnTip,
        	            handler:'onReloadProjectClick'
        	        },{
        	            xtype: 'button',
        	            iconCls: 'ico-task-add',
        	            itemId: 'add-project-btn',
        	            text: me.strings.addProject,
        	            hidden: ! Editor.app.authenticatedUser.isAllowed('editorAddTask'),
        	            tooltip: me.strings.addProjectTip
        	        }]
        		}]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    initComponent:function(){
    	var me=this;
    	me.callParent();
    	me.configureActionColumn();
    },
    
    /***
     * Configure the project action columns 
     */
    configureActionColumn:function(){
    	var me=this,
			actions = me.down('taskActionColumn');

    	actions.setWidth(90);
    	actions.on({
    		click:{
    			fn:'projectActionDispatcher',
    			scope:me.getController()
    		}
    	});
	    me.availableActions = ['editorDeleteProject'];
    },
});
