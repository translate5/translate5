
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

Ext.define('Editor.view.admin.task.UserAssocWizardViewController', {
    extend: 'Editor.view.admin.user.AssocViewController',
    alias: 'controller.adminTaskUserAssocWizard',

    listen:{
        component:{
            '#targetLang':{
                select:'onTargetlangSelect'
            },
            '#workflowStepName': {
                select: 'onWorkflowStepNameSelect'
            }
        }
    },

    /***
     */
    onUserAssocWizardActivate:function(){
        var me=this,
            view = me.getView(),
            workflowCombo = view.down('#workflowCombo'),
            store=view.down('grid').getStore();

        store.setExtraParams({
            projectId:view.task.get('projectId')
        });
        store.load();

        workflowCombo.setValue(view.task.get('workflow'));
    },

    onAddAssocBtnClick : function(){
        var me=this,
            newRecord,
            task = me.getView().task,
            formPanel = me.lookup('assocForm'),
            form = formPanel.getForm(),
            workflowCombo = me.getView().down('#workflowCombo');

        form.reset(true);

        newRecord = Ext.create('Editor.model.admin.TaskUserAssoc',{
            sourceLang : task.get('sourceLang'), // source language is always the same for projects or single tasks
            workflow: workflowCombo.getValue()
        });

        form.loadRecord(newRecord);

        form.findField('sourceLang').setVisible(false);

        if(!task.hasProjectTasks()){
            newRecord.set('targetLang',task.get('targetLang'));
            newRecord.set('taskGuid',task.get('taskGuid'));
            form.findField('targetLang').setVisible(false);
        }else{
            var targetLangs = [];
            for (var i=0;i<task.get('projectTasks').length;i++){
                targetLangs.push(task.get('projectTasks')[i].targetLang);
            }
            form.findField('targetLang').getStore().filter([{
                property: 'id',
                operator:"in",
                value:targetLangs
            }]);
        }

        formPanel.setDisabled(false);
    },

    onTargetlangSelect: function (combo, record){
        var me = this,
            task = me.getView().task,
            projectTasks = task.get('projectTasks'),
            formPanel = me.lookup('assocForm'),
            formRecord = formPanel.getRecord();

        for (var i=0;i<projectTasks.length;i++){
            if(projectTasks[i].targetLang === record.get('id')){
                formRecord.set('taskGuid',projectTasks[i].taskGuid);
                break;
            }
        }
    },

    /***
     * On workflow step name change event handler
     * @param combo
     * @param step
     */
    onWorkflowStepNameSelect: function (combo,step) {
        this.setWorkflowStepDefaultDeadline(step);
    },

    /***
     * Calculate and set the default deadline date from config and order date.
     */
    setWorkflowStepDefaultDeadline:function(step){
        var me=this,
            task = me.getFormTask(),
            formPanel = me.lookup('assocForm'),
            formRecord = formPanel.getRecord(),
            form = formPanel.getForm(),
            deadlineDate = form.findField('deadlineDate'),
            recordDeadlineDate = formRecord.get('deadlineDate'),//the record has deadlineDate
            orderDate = task.get('orderdate');

        //if order date is not set, no calculation is required
        //if there is no workflow step defined, no calculation is required
        //if the deadlineDate is already set, no calculation is required
        if(!orderDate || !step || recordDeadlineDate){
            return;
        }
        var workflow = task.get('workflow'),
            configName = Ext.String.format('workflow.{0}.{1}.defaultDeadlineDate',workflow,step),
            days = Editor.app.getTaskConfig(configName),
            newValue = null;

        // calculate the new date if config exist
        if(days){
            // check if the order date has timestamp 00:00:00
            if(orderDate.getHours() === 0 && orderDate.getMinutes() === 0){
                // For the deadlineDate the time is also important. This will change the time to now.
                var tmpNow = new Date();
                orderDate.setHours(tmpNow.getHours());
                orderDate.setMinutes(tmpNow.getMinutes());
                orderDate.setSeconds(tmpNow.getSeconds());
            }

            newValue = Editor.util.Util.addBusinessDays(orderDate, days);
        }

        deadlineDate.setValue(newValue);
    },

    /***
     * Get the task/projectTask based on the selected target language.
     * If no targetLang is provided, this will try to get it from the targetLang dropdown
     * @param targetLang
     * @returns {*}
     */
    getFormTask: function (targetLang){
        var me = this,
            task = me.getView().task,
            projectTasks = task.get('projectTasks'),
            form = me.lookup('assocForm').getForm(),
            formTargetLang = targetLang && form.findField('targetLang').getValue();

        for (var i=0;i<projectTasks.length;i++){
            if(projectTasks[i].targetLang === formTargetLang){
                return projectTasks[i];
            }
        }

        return null;
    }
});