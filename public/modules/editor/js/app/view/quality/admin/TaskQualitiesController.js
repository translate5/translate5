
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

/**
 * View Controller for the task quality panel
 * Handles only the finished task's import refresh of qualities
 */
Ext.define('Editor.view.quality.admin.TaskQualitiesController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.taskQualities',
    listen: {
        controller: {
            'taskGrid': {
                taskProgressFinished: 'onTaskProgressFinished'
            }
        },
        store: {
            '#projectTasks': {
                load: 'onProjectTaskLoad'
            }
        }
    },
    /**
     * After an import is finished (and the AutoQA workers worked) we need to show the new state
     */
    onTaskProgressFinished: function(task,  operationType){
        // no need and unwanted to reload qualities when visual is exchanged
        if (!operationType || operationType !== Editor.model.admin.Task.operations.VISUALEXCHANGE) {
            this.getView().refreshStore(task.get('taskGuid'));
        }
    },

    /**
     * Clear qualities store if project has no tasks for some reason
     *
     * @param store
     * @param records
     * @param successful
     */
    onProjectTaskLoad: function(store, records, successful) {
        if (successful === false) {
            return;
        }
        if (!records.length) {
            this.getView().down('treepanel').getStore().loadData([]);
        }
    }
});
