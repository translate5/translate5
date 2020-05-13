
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

Ext.define('Editor.view.project.ProjectGrid', {
	extend: 'Editor.view.admin.TaskGrid',
	alias: 'widget.projectGrid',
    requires:[
    	'Editor.view.project.ProjectGridViewController',
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
		'sourceLang',
		'taskNr',
		'orderdate'
	],
	
	store: 'project.Project',
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
