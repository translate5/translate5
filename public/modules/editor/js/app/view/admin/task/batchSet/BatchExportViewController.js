
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Handler for the Batch-tasks Actions. Currenty, we only process PDF-tasks. That may changes in the future ...
 */
Ext.define('Editor.view.admin.task.batchSet.BatchExportViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.batchExport',
    listen: {
        component: {
            '#runForFiltered': {
                click: 'onRunForFilteredClick'
            },
            '#runForSelected': {
                click: 'onRunForSelectedClick'
            },
            '#startBatchProcess': {
                click: 'onStartBatchProcess'
            },
            '#refreshBatchPreview': {
                click: 'onRefreshBatchPreview'
            }
        }
    },

    /**
     * State-hadling
     * @type {boolean}
     */
    isLoadingTasks: false,

    /**
     * Refresh-store
     * @type {Object}
     */
    currentParams: null,

    MAX_BATCH_SIZE: 30,

    onRunForSelectedClick: function () {
        if (! this.isLoadingTasks) {
            this.handleBatchLoad(true);
        }
    },

    onRunForFilteredClick: function () {
        if (! this.isLoadingTasks) {
            this.handleBatchLoad(false);
        }
    },

    onStartBatchProcess: function () {
        const store = Ext.StoreManager.get('batchTasks'),
            taskIds = [];
        // find the selected tasks
        store.each(function(record) {
            if (record.get('checked') && !record.get('busy')) {
                taskIds.push(record.id);
            }
        }, this);
        if (taskIds.length === 0) {
            this.showWarning(Editor.data.l10n.batchSetWindow.noTasksSelected);
            return;
        }
        this.startExport(taskIds, this.currentParams);
        this.getView().close();
    },

    onRefreshBatchPreview: function() {
        if (! this.isLoadingTasks && this.currentParams !== null) {
            this.loadBatchTasks(this.currentParams);
        }
    },

    /**
     * Handler to evaluate the selection, via checkboxes or filter ...
     * @param {Boolean} selectedTasksOnly
     */
    handleBatchLoad: function (selectedTasksOnly) {
        const store = Ext.StoreManager.get('project.Project'),
            proxy = store.getProxy(),
            tasksData = {};

        if (selectedTasksOnly) {

            const dataController = Editor.app.getController('Editor.controller.BatchEditing'),
                projectsAndTasks = dataController.getProjectsAndTasks();

            if (projectsAndTasks.length < 1) {
                this.showWarning(Editor.data.l10n.batchSetWindow.noTasksSelected);
                return;
            } else if (projectsAndTasks.length > this.MAX_BATCH_SIZE) {
                this.showWarning(this.getView().strings.tooManyTasks.split('{0}').join(this.MAX_BATCH_SIZE));
                return;
            }
            // unselect checkboxes
            dataController.clearData();
            tasksData.projectsAndTasks = projectsAndTasks.join(',');
        } else {
            tasksData[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
        }

        this.loadBatchTasks(tasksData);
    },

    /**
     * Fetches the selected tasks from the server
     * @param {Object} params
     */
    loadBatchTasks: function (params) {
        let me = this;
        me.isLoadingTasks = true;
        params.previewTasks = 1;
        params.tasksLimit = this.MAX_BATCH_SIZE;
        Ext.Ajax.request({
            url: Editor.data.restpath + 'taskuserassoc/batchset',
            method: 'POST',
            params: params,
            success: function (response) {
                if(response.responseJson.success) {
                    me.showBatchTasksGrid({ rows: response.responseJson.rows, total: response.responseJson.total});
                } else {
                    me.showWarning(response.responseJson.error);
                }
                me.isLoadingTasks = false;
                delete params.previewTasks;
                me.currentParams = params;
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
                me.currentParams = null;
            }
        });
    },

    /**
     * Fills the store & shows the batchTasks grid
     * @param data
     */
    showBatchTasksGrid: function(data) {
        let view = this.getView();
        view.down('#batchTasksGrid').getStore().setData(data.rows);
        view.getViewModel().set('showingTasks', true);
    },

    startExport: function (taskIds, params) {
        params.batchType = 'export';
        params.taskIds = taskIds.join(',');
        Ext.Ajax.request({
            url: Editor.data.restpath + 'taskuserassoc/batchset',
            method: 'POST',
            params: params,
            success: function (response) {
                if(response.responseJson['nextUrl']){
                    window.open(response.responseJson['nextUrl']);
                }
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    showWarning: function (msg) {
        Ext.Msg.alert(Editor.data.l10n.batchSetWindow.warning, msg);
    }
});
