
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
	alias: 'widget.projectTaskGrid',
    requires:[
    	'Editor.view.admin.ProjectGridViewController',
    	'Editor.view.admin.ProjectGridViewModel'
	],
	controller:'projectTaskGrid',
    viewModel: {
        type: 'projectTaskGrid'
    },
	itemId: 'projectTaskGrid',
	title: '#UT#Projektübersicht',
	glyph: 'xf0e8@FontAwesome',
	strings: {
		actionDelete: '#UT#Projekt komplett löschen',
		reloadBtn: '#UT#Aktualisieren',
		reloadBtnTip: '#UT#Projektliste vom Server aktualisieren.',
		expandAllBtn:'#UT#Alle Erweitern',
		expandAllBtnTip:'#UT#Alle Projekte Erweitern',
		collapseAllBtn:'#UT#Alle Reduziern',
		collapseAllBtnTip:'#UT#Alle Projekte Reduziern'
	},
	
	text_cols: {
		taskName: '#UT#Projektname',
	},
	
	store: 'admin.Project',
	autoLoad: false,
	bufferedRenderer:false,//info: this will stop the store filter binding.

	/***
	 * Expand or collapse all rowwidget rows
	 * TODO: move rowwidget in separate class 
	 */
	handleExpandCollapseAll:function(expand){
		var me = this,
			data = me.getStore().getRange();
		data.forEach(function(record){
			me.toggleRow(record,expand)
		});
	},
	
    collapseAll:function(){
    	this.handleExpandCollapseAll(false);
    },
    
    expandAll:function(){
    	this.handleExpandCollapseAll(true);
    },
    
    expandRow:function(record){
    	this.toggleRow(record,true);
    },
    
    collapseRow:function(record){
    	this.toggleRow(record,false);
    },
    
    toggleRow:function(record,expand){
    	var me = this,
			view = me.getView(),
			rowWidget = me.getPlugin('projectTasksPlugin');
		
		if (!me.rendered || !rowWidget || !record || record.get('taskType')!=='project') {//TODO: get me from constant
		    return;
		}
        // If we are handling a lockable assembly,
        // handle the normal view first
        var view = rowWidget.normalView || rowWidget.view,
        	index=view.indexOf(view.getRow(record)),
	        rowNode = view.getNode(index),
	        normalRow = Ext.fly(rowNode),
	        isCollapsed = normalRow.hasCls(rowWidget.rowCollapsedCls);
        
        if(expand==isCollapsed){
        	rowWidget.toggleRow(index, record);
        }
	},
    
    initConfig: function(instanceConfig) {
        var me = this,
        	config={
        		title: me.title, //see EXT6UPD-9
        		text_cols:me.text_cols,
        		dockedItems: [{
        	        xtype: 'toolbar',
        	        dock: 'top',
        	        items: [{
        	            xtype: 'button',
        	            iconCls: 'ico-refresh',
        	            itemId: 'reload-task-btn',
        	            text: me.strings.reloadBtn,
        	            tooltip: me.strings.reloadBtnTip
        	        },{
        	            xtype: 'button',
        	            iconCls: 'ico-task-add',
        	            itemId: 'add-task-btn',
        	            text: me.strings.addTask,
        	            hidden: ! Editor.app.authenticatedUser.isAllowed('editorAddTask'),
        	            tooltip: me.strings.addTaskTip
        	        },{
        	            xtype: 'button',
        	            enableToggle:true,
        	            itemId: 'expandCollapseAllBtn',
        	            iconCls: 'ico-toggle-expand',
        	            text: me.strings.expandAllBtn,
        	            tooltip: me.strings.expandAllBtnTip,
        	            bind:{
        	            	iconCls:'{expandCollapseIconCls}',
        	            	text:'{expandCollapseText}',
        	            	tooltip:'{expandCollapseTip}',
        	            },
        	            listeners:{
        	            	click:'onExpandCollapseAllBtnClick'
        	            }
        	        }]
        		}],
//        		columns: [{
//        	        text: 'Id',
//        	        dataIndex: 'id'
//        	    },{
//        	    	xtype:'actioncolumn',
//        	    	text:me.strings.columnProjectAction,
//        	        items: [{
//        	        	scope:'controller',
//        	            tooltip: me.strings.actionDelete,
//        	            hidden:!Editor.app.authenticatedUser.isAllowed('editorDeleteProject'),
//        	            iconCls: 'ico-task-delete',
//        	            handler:'onDeleteProjectClick'
//        	        }]
//        	    },{
//        	        text:me.strings.columnProjectName,
//        	        dataIndex: 'taskName',
//        	        width: 220
//        	    }],
        		plugins: [
        	    	Ext.create('Ext.grid.plugin.RowWidget', {
        	    		pluginId:'projectTasksPlugin',
        	    		getHeaderConfig: function () {
        	    			//hide the expand/collapse button for non project records
        	    			var defaultConfig=this.superclass.getHeaderConfig.apply(this, arguments);
        	    			defaultConfig.renderer = function (value, gridcell, record) {
        	    				if (record.get('taskType')=='project') {//TODO: get me from config
        	    					return '<div class="' + Ext.baseCSSPrefix + 'grid-row-expander" role="presentation" tabIndex="0"></div>';
    	    					}
        	    		    }
        	    		    return defaultConfig;
    	    		    },
        		    	widget: {
        		    		xtype:'adminTaskGrid',
        		    		store:null,//INFO: with this the original store reference is removed
        		    		header:false,
        		    		dockedItems:false,
    		    			bind:{
    		    				store:{
    		    					model:'Editor.model.admin.ProjectTask',
    		    					remoteSort: true,
    		    					remoteFilter: true,
    		    					pageSize: false,
    		    					filters:{
    		    		    			property: 'projectId',
    		    		        		operator:"eq",
    		    		        		value:'{record.id}'
    		    					}
    		    				}
    		    			},
        		        }
        	    })
        	]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});