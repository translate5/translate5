/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Encapsulates logic for projects/tasks checkboxes selection
 * and related batch set requests sending
 */

Ext.define('Editor.controller.BatchEditing', {
    extend: 'Ext.app.Controller',
    batchProjectIds: new Map(),
    batchTaskIds: new Map(),

    listen: {
        store: {
            '#projectTasks': {
                load: 'onProjectTaskLoad'
            }
        },
        component: {
            '#projectGrid checkcolumn': {
                checkchange: 'onProjectGridCheckColumnCheckChange'
            },
            '#projectTaskGrid checkcolumn': {
                checkchange: 'projectTaskGridCheckColumnCheckChange'
            }
        }
    },

    onProjectTaskLoad: function () {
        const taskStore = this.getTaskStore();
        if(taskStore.getCount() < 1){
            return;
        }
        
        const projectId = taskStore.getAt(0).get('projectId'),
            projectState = this.batchProjectIds.get(projectId);
        // if(store.buffered): store.data.forEach(function(record, recordIdx) {
        taskStore.each(function (record) {
            switch (projectState) {
                case 'A': // All
                    record.set('checked', true);
                    this.batchTaskIds.set(record.get('id'), projectId);
                    break;
                case 'D': // Deleted (when tasks were not visible), do cleanup here
                    this.batchTaskIds.delete(record.get('id'));
                    break;
                case 'C': // Custom (tasks selection)
                    record.set('checked', !!this.batchTaskIds.get(record.get('id')));
            }
        }, this);
        if (projectState === 'D') {
            this.batchProjectIds.delete(projectId);
        }
    },

    onProjectGridCheckColumnCheckChange: function (cmp, rowIndex, checked, record){

        this.redirectTo('project/' + record.get('id') + '/focus');

        const projectGridView = this.getProjectGridView();
        if (projectGridView.getNode(rowIndex)) {
            projectGridView.refreshNode(rowIndex);
        }

        const projectId = record.get('projectId'),
            projectTasksVisible = this.syncVisibleTasks(projectId, checked);

        if (checked) {
            this.batchProjectIds.set(projectId, 'A'); // All tasks will be included
        } else if (projectTasksVisible) {
            this.batchProjectIds.delete(projectId); // project tasks are visible and deselected, remove project
        } else {
            this.batchProjectIds.set(projectId, 'D'); // mark project as Deleted, do tasks cleanup later
        }
    },

    projectTaskGridCheckColumnCheckChange: function (cmp, rowIndex, checked, record) {
        if (checked) {
            this.batchTaskIds.set(record.get('id'), record.get('projectId')); // map taskId to projectId
            // All or Custom (tasks selection)
            this.batchProjectIds.set(record.get('projectId'), this.selectCurrentProjectIfAllTasksSelected() ? 'A' : 'C');
        } else {
            this.batchTaskIds.delete(record.get('id'));
            if (this.deselectCurrentProjectIfNoTasksSelected()) {
                this.batchProjectIds.delete(record.get('projectId'));
            } else {
                this.batchProjectIds.set(record.get('projectId'), 'C'); // Custom (tasks selection)
            }
        }
    },

    /**
     * Clear project/task checkboxes and related data structures
     */
    clearData: function() {
        this.batchTaskIds.clear();
        this.batchProjectIds.clear();
        // Unselect checkboxes
        this.getTaskStore().reload();
        this.getProjectGridView().getStore().reload();
    },
    /**
     * Get selected projects' and tasks' Ids
     */
    getProjectsAndTasks: function() {
        const projectsAndTasks = [];
        for (let id of this.batchProjectIds.keys()) {
            if (this.batchProjectIds.get(id) === 'A') {
                // include projects with All tasks selected
                projectsAndTasks.push(id);
            }
        }
        for (let id of this.batchTaskIds.keys()) {
            let projectId = this.batchTaskIds.get(id);
            if (this.batchProjectIds.get(projectId) === 'C') {
                // include tasks from projects with Custom tasks selected
                projectsAndTasks.push(id);
            }
        }
        return projectsAndTasks;
    },
    /**
     * Set visible project tasks' checkboxes in sync with project checkbox.
     * @param {String} projectId
     * @param {Boolean} checked
     * @return {Boolean}
     * @private
     */
    syncVisibleTasks: function(projectId, checked) {
        let success = false;
        this.getTaskStore().each(function(record) {
            if (record.get('projectId') === projectId) {
                record.set('checked', checked);
                success = true;
            }
        }, this);
        return success;
    },
    /**
     * @return {Boolean}
     * @private
     */
    deselectCurrentProjectIfNoTasksSelected: function() {
        let hasTasks = false;
        this.getTaskStore().each(function(record) {
            if (record.get('checked')) {
                hasTasks = true;
                return false;
            }
        }, this);
        if (hasTasks) {
            return false;
        }
        this.markCurrentProject(false);
        return true;
    },
    /**
     * @private
     */
    selectCurrentProjectIfAllTasksSelected: function() {
        let allTasks = true;
        this.getTaskStore().each(function(record) {
            if (!record.get('checked')) {
                allTasks = false;
                return false;
            }
        }, this);
        if (allTasks) {
            this.markCurrentProject(true);
        }
        return allTasks;
    },
    /**
     * (Un)tick current project checkbox
     * @param {Boolean} checked
     * @private
     */
    markCurrentProject: function(checked) {
        const projectGridView = this.getProjectGridView(),
            selectedRecord = projectGridView.getSelection()[0],
            rowIndex = projectGridView.store.indexOf(selectedRecord);
        selectedRecord.set('checked', checked);
        if (projectGridView.getNode(rowIndex)) {
            projectGridView.refreshNode(rowIndex);
        }
    },

    getTaskStore: function (){
        const grid = Ext.ComponentQuery.query('#projectTaskGrid')[0];
        return grid ? grid.getStore() : null;
    },

    getProjectGridView: function (){
        const grid = Ext.ComponentQuery.query('#projectGrid')[0];
        return grid ? grid.getView() : null;
    }
});
