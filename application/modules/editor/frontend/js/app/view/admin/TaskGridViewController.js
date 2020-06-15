
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

/**
 * @class Editor.view.admin.TaskGridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.admin.TaskGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.taskGrid',
    routes: {
    	//INFO: this route will filter the task in the task grid
    	//add another route for open task for editing
    	'task': 'onTaskRoute',
        'task/:id/filter': 'onTaskFilterRoute'
    },
    
    listen: {
        messagebus: {
            '#translate5 task': {
                triggerReload: 'onTriggerTaskReload'
            }
        }
    },
    onTriggerTaskReload: function(params) {
        var store = this.getView().getStore(),
            task;
        if(params.taskId) {
            task = store.getById(params.taskId);
        }
        else {
            task = store.findRecord( 'taskGuid', params.taskGuid, 0, false, true, true);
        }
        task && task.load();
    },
    
    onTaskRoute:function(){
    	var me=this;
    	Editor.app.openAdministrationSection(me.getView(), 'task');
    },
    
    onTaskFilterRoute: function(id) {
    	var me=this,
    		route=['task',id,'filter'];
		Editor.app.getController('admin.TaskOverview').addAdvancedFilter({
  			property: 'id',
    		operator:"eq",
    		value:id
		});
        Editor.app.openAdministrationSection(me.getView(), route.join('/'));
    },
});