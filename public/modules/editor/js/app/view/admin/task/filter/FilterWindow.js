
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

Ext.define('Editor.view.admin.task.filter.FilterWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.editorAdminTaskFilterFilterWindow',
    requires: [
        'Editor.view.admin.task.filter.FilterWindowViewController',
        'Editor.view.admin.task.filter.DateFilter'
    ],
    controller: 'editorAdminTaskFilterFilterWindow',
    itemId: 'editorAdminTaskFilterFilterWindow',
    strings: {
        workflowStateFilterLabel:'#UT#Workflow-Status',
        workflowUserRoleLabel:'#UT#Zugewiesene Benutzer-Rolle/n',
        userNameLabel:'#UT#Zugewiesene/r Benutzer',
        applyBtn:'#UT#Anwenden',
        cancelBtn:'#UT#Abbrechen',
        title: '#UT#Erweiterte Filter',
        anonymizedUsersInfo:'#UT#Anonymisierte Benutzer nicht auswählbar',
        gridFiltersInfo:'#UT#Weitere Filter im Kopf jeder Spalte',
        assignmentDateText:'#UT#Benutzer-Zuweisungsdatum',
        finishedDateText:'#UT#Benutzer-Abschlussdatum',
        deadlineDateText:'#UT#Benutzer-Deadline/s'
    },
    listeners:{
        render:'onFilterWindowRender'
    },
    bodyPadding: 20,
    layout: 'hbox',
    border:false,
    width:800,
    height:440,
    bodyStyle: {
        borderWidth:0,
    },

    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.strings.title,
                scrollable: true,
                defaults: {
                    xtype: 'container',
                    flex: 1,
                    margin: '0 5 0 0',
                    autoSize: true
                },
                items: [{
                    items: [{
                        xtype: 'tagfield',
                        name:'userName',
                        itemId:'userName',
                        typeAhead: true,
                        queryMode: 'local',
                        displayField: 'longUserName',
                        valueField: 'userGuid',
                        store:'admin.UsersList',
                        fieldLabel:me.strings.userNameLabel+'¹',
                        labelAlign: 'top',
                        labelWidth:'100%',
                        filter: {
                            operator: 'in',
                            property:'userName',
                            type:'list',
                            textLabel:me.strings.userNameLabel
                        }
                    },{
                        xtype:'editorAdminTaskFilterDateFilter',
                        filterLabel:me.strings.assignmentDateText,
                        filterProperty:'assignmentDate',
                        itemId:'assignmentDate',
                        title:me.strings.assignmentDateText
                    }]
			    }, {
			        items: [{
				        xtype: 'tagfield',
				        name:'workflowState',
				        itemId:'workflowState',
				        typeAhead: true,
				        queryMode: 'local',
				        valueField: 'id',
				        displayField: 'label',
				        store:'admin.WorkflowState',
				        fieldLabel: me.strings.workflowStateFilterLabel,
				        labelAlign: 'top',
				        labelWidth:'100%',
				        filter:{
				        	operator: 'in',
				        	property:'workflowState',
				        	type:'list',
				        	textLabel:me.strings.workflowStateFilterLabel
				        }
			        }, {
	    	        	xtype:'editorAdminTaskFilterDateFilter',
	    	        	filterLabel:me.strings.finishedDateText,
	    	        	filterProperty:'finishedDate',
	    	        	itemId:'finishedDate',
	    	        	title:me.strings.finishedDateText
			        }]
			    },{
			        items: [{
				        xtype: 'tagfield',
				        name:'workflowUserRole',
				        itemId:'workflowUserRole',
				        typeAhead: true,
				        queryMode: 'local',
				        valueField: 'id',
				        displayField: 'label',
				        store:'admin.WorkflowUserRoles',
				        fieldLabel: me.strings.workflowUserRoleLabel,
				        labelWidth:'100%',
				        labelAlign: 'top',
				        filter:{
				        	operator: 'in',
				        	property:'workflowUserRole',
				        	type:'list',
				        	textLabel:me.strings.workflowUserRoleLabel
				        }
			        }, {
	    	        	xtype:'editorAdminTaskFilterDateFilter',
	    	        	filterLabel:me.strings.deadlineDateText,
	    	        	filterProperty:'deadlineDate',
	    	        	itemId:'deadlineDate',
	    	        	title:me.strings.deadlineDateText
			        }]
			    }],
			    dockedItems: [{
					xtype: 'toolbar',
					dock: 'bottom',
					ui: 'footer',
					align:'left',
					layout: {
						type: 'vbox',
						align: 'left'
					},
					items: [{
						xtype: 'tbfill'
					},{
						xtype: 'container',
						padding: '10',
						html:me.strings.gridFiltersInfo
					},{
						xtype: 'container',
						padding: '10',
						html:"¹ "+me.strings.anonymizedUsersInfo
					}]
				}]
	      };
	  if (instanceConfig) {
	      me.self.getConfigurator().merge(me, config, instanceConfig);
	  }
	  return me.callParent([config]);
    },
    
    /***
     * Load the selected fields data 
     */
    loadRecord:function(record){
    	var me=this,
    		field=null;
    	Ext.each(record, function(rec) {
    		field=me.down('#'+rec.get('property'));
			field && field.setValue(rec.get('value'),rec);
        });
    }
});