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

Ext.define('Editor.view.admin.task.batchSet.BatchSetDeadlineViewController', {
    extend: 'Editor.view.admin.task.batchSet.BatchSetViewController',
    alias: 'controller.adminTaskBatchSetDeadlineWindow',

    childParamField: 'deadlineDate',
    batchFields: ['batchWorkflow', 'batchWorkflowStep', 'deadlineDate'],

    listen: {
        component: {
            '#batchWorkflow': {
                afterrender: function (cmp) {
                    // pre-select 1st workflow or cmp.fireEvent('change', cmp);
                    cmp.setValue(cmp.getStore().getAt(0).get('id'));
                },
                change: 'onWorkflowFieldChange'
            }
        }
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