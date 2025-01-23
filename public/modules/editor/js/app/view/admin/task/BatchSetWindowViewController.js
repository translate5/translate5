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

Ext.define('Editor.view.admin.task.BatchSetWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminTaskBatchSetWindow',

    listen: {
        component: {
            '#batchWorkflow': {
                afterrender: function (cmp) {
                    // pre-select 1st workflow or cmp.fireEvent('change', cmp);
                    cmp.setValue(cmp.getStore().getAt(0).get('id'));
                },
                change: 'onWorkflowFieldChange'
            },
            '#setForFiltered': {
                click: 'onSetForFilteredClick'
            },
            '#setForSelected': {
                click: 'onSetForSelectedClick'
            }
        }
    },

    onSetForSelectedClick: function () {
        this.handleBatchSet(true);
    },

    /**
     * Handler for "Set for all" buttons
     * @param {Boolean} selectedTasksOnly
     */
    handleBatchSet: function (selectedTasksOnly) {
        const batchParams = {},
            deadLineParams = this.initBatchParams(['batchWorkflow', 'batchWorkflowStep', 'deadlineDate']);
        if (deadLineParams) {
            batchParams['deadlineDate'] = deadLineParams;
        }
        if(Object.keys(batchParams).length < 1) {
            return this.showWarning(Editor.data.l10n.batchSetWindow.noPropertySet);
        }

        const store = Ext.StoreManager.get('project.Project'),
            proxy = store.getProxy(),
            tasksData = {};

        if (selectedTasksOnly) {

            const dataController = Editor.app.getController('Editor.controller.BatchEditing'),
                projectsAndTasks = dataController.getProjectsAndTasks();

            if (projectsAndTasks.length<1) {
                return this.showWarning(Editor.data.l10n.batchSetWindow.noTasksSelected);
            }
            // unselect checkboxes
            dataController.clearData();
            tasksData['projectsAndTasks'] = projectsAndTasks.join(',');
        } else {
            tasksData[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
        }

        for (const [updateType, params] of Object.entries(batchParams)) {

            params['updateType'] = updateType;

            Ext.Ajax.request({
                url: Editor.data.restpath + 'taskuserassoc/batchset',
                method: 'POST',
                params: {...params, ...tasksData},
                success: function (response) {
                    Editor.MessageBox.addSuccess('Success');
                },
                failure: function (response) {
                    Editor.app.getController('ServerException').handleException(response);
                }
            });
        }
    },

    /**
     * Init parameters for batch set; returns null unless all fields are set
     * @param {String[]} fieldIds
     * @return {Object|null}
     */
    initBatchParams: function (fieldIds) {
        const me = this.getView(), params = {};
        fieldIds.every(function (fieldId) {
            const field = me.down('#' + fieldId);
            if (!field.value) {
                return false;
            }
            params[fieldId] = field.value;
            return true;
        });
        return Object.keys(params).length === fieldIds.length ? params : null;
    },

    showWarning: function (msg) {
        Ext.Msg.alert(Editor.data.l10n.batchSetWindow.warning, msg);
    },

    onSetForFilteredClick: function () {
        const me = this, store = Ext.StoreManager.get('project.Project'),
            proxy = store.getProxy(), params = {countTasks:1};
        params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
        Ext.Ajax.request({
            url: Editor.data.restpath + 'taskuserassoc/batchset',
            method: 'POST',
            params: params,
            success: function (response) {
                let tasksCount = response.responseJson.total, l10n = Editor.data.l10n.batchSetWindow,
                    question = l10n.allFilteredWarning.replace('. ', ' ('+tasksCount+' '+l10n.tasksLabel+'). ');
                if (tasksCount>50) {
                    question = '<b style="color:red">'+ question + '</b>';
                }
                Ext.MessageBox.confirm(
                    l10n.setForFiltered, question, function (btn) {
                        if (btn !== 'yes') {
                            me.handleBatchSet(false);
                        }
                    });
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    onWorkflowFieldChange: function (fld, workflowIds) {
        var allSteps = [];

        Ext.Object.each(Editor.data.app.workflows, function (key, workflow) {
            Ext.Object.each(workflow.steps, function (stepId, stepText) {
                if (workflowIds.length > 0 && !workflowIds.includes(workflow.id)) {
                    return;
                }
                if (!["no workflow", "pmCheck", "workflowEnded"].includes(stepId)) {
                    allSteps.push({
                        id: stepId,
                        text: stepText,
                        group: workflow.label
                    });
                }
            });
        });

        const wfStepCmp = Ext.ComponentQuery.query('#batchWorkflowStep')[0],
            store = new Ext.data.Store();
        store.loadData(allSteps, false);
        wfStepCmp.setStore(store);
    }

});