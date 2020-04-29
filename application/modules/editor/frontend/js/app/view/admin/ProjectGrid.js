
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
	extend: 'Ext.grid.Panel',
	alias: 'widget.projectTaskGrid',
    requires:[
    	'Editor.view.admin.ProjectGridViewController'
	],
	controller:'projectTaskGrid',
	itemId: 'projectTaskGrid',
	title: '#UT#Projektübersicht',
	layout: {
		type: 'fit'
	},
	glyph: 'xf0e8@FontAwesome',
	strings: {
		actionDelete: '#UT#Projekt komplett löschen',
		columnProjectAction:'#UT#Aktionen',
		columnProjectName:'#UT#Projektname',
		columnProjectTaskName:'#UT#Aufgabenname-Projekt',
	},
	store: 'admin.Project',
    viewConfig: {
        listeners: {
            expandbody:'onProjectExpandBody'
        }
    },
    initConfig: function(instanceConfig) {
        var me = this,
        	config={
        		title:me.title,
        		columns: [{
        	        text: 'Id',
        	        dataIndex: 'id'
        	    },{
        	    	xtype:'actioncolumn',
        	    	text:me.strings.columnProjectAction,
        	        items: [{
        	        	scope:'controller',
        	            tooltip: me.strings.actionDelete,
        	            hidden:!(Ext.Array.indexOf(Editor.data.app.userRights, 'editorDeleteProject') >= 0),
        	            iconCls: 'ico-task-delete',
        	            handler:'onDeleteProjectClick'
        	        }]
        	    },{
        	        text:me.strings.columnProjectName,
        	        dataIndex: 'taskName',
        	        width: 220
        	    }],
        		plugins: [
        	    	Ext.create('Ext.grid.plugin.RowWidget', {
        		    	widget: {
        		            xtype: 'grid',
        		            itemId:'rowWidgetGrid',
        		            autoLoad: false,
        		            header:false,
        		            bind:{
        		            	//INFO: set the bind to empty object to disable the autobind from the project record after expand
        		            },
        	                columns: [{
        	                    xtype: 'gridcolumn',
        	                    dataIndex: 'id',
        	                    text: 'Id'
        	                },{
        	                    xtype: 'gridcolumn',
        	                    width: 220,
        	                    dataIndex: 'taskName',
        	                    text:me.strings.columnProjectTaskName
        	                }]
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