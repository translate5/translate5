
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
Ext.define('Editor.view.project.ProjectTaskGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.projectTaskGrid',
    listen: {
        messagebus: {
            '#translate5 task': {
                triggerReload: 'onTriggerTaskReload',
                updateProgress: 'onUpdateProgress'//INFO: the listener and the event handler are also defined in the ProjectGridViewController. To unify this we should use mixins, they are 2 different components and the scope is not the same.
            }
        }
    },
    
    onTriggerTaskReload: function(params) {
        var me = this,
            task = me.getTaskRecordFromParams(params);
        
        if(task && task.isLoading()) {
            //it may happen that two reloads are triggered fast after each other, then the second one would not start,
            // but the first one receives old data then. Therefore, we defer the second call in that situation
            Ext.defer(task.load, 500, task);
        } else {
            task && task.load();
        }
    },
    
    onUpdateProgress: function(params) {
        var me = this,
            task = me.getTaskRecordFromParams(params);
        
        if(!task){
            return;
        }
        task.set('importProgress',params.progress);
    },
    
    /***
     * Return task store record from given messagebus params.
     * For this to work, taskId or taskGuid must exist in the params argument
     */
    getTaskRecordFromParams:function(params){
        var store = this.getView().getStore(),
            task;
        if(params.taskId) {
            task = store.getById(params.taskId);
        }
        else {
            task = store.findRecord( 'taskGuid', params.taskGuid, 0, false, true, true);
        }
        return task;
    }
});