
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
            },
            '#notifyAssociatedUsersCheckBox':{
                change:'onNotifyAssociatedUsersCheckBoxChange'
            },
            '#usageMode':{
                select: 'onUsageModeSelect'
            }
        }
    },

    nextCardClick: function (){
        var me = this;
        me.checkOperation(null);
    },

    skipCardClick: function (){
        var me = this;
        me.checkOperation(4);
    },

    checkOperation: function (skipCards){
        var me = this,
            view = me.getView(),
            sendPreImportOperation = view.getViewModel().get('sendPreImportOperation'),
            notify = view.down('#notifyAssociatedUsersCheckBox').checked,
            workflowCombo = view.down('#workflowCombo'),
            usageMode = view.down('#usageMode');

        if(!sendPreImportOperation){
            view.fireEvent('wizardCardFinished', skipCards);
            return;
        }

        Ext.Ajax.request({
            url:Editor.data.restpath+'task/{id}/preimport/operation'.replace("{id}",view.task.get('id')),
            method: 'POST',
            params: {
                usageMode: usageMode.getValue(),
                workflow:workflowCombo.getValue(),
                notifyAssociatedUsers:notify.checked
            },
            success: function(response){
                view.fireEvent('wizardCardFinished', skipCards);
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });

    },

    /***
     * Load the taskuserassoc store for current workflow and projectId.
     * The workflow is get from the workflowCombo
     */
    loadAssocData: function (){
        var me=this,
            view = me.getView(),
            workflowCombo = view.down('#workflowCombo'),
            store=view.down('grid').getStore();

        store.setExtraParams({
            projectId:view.task.get('projectId'),
            workflow: workflowCombo.getValue()
        });
        store.load();
    },

    /***
     */
    onUserAssocWizardActivate:function(){
        var me=this,
            view = me.getView(),
            workflowCombo = view.down('#workflowCombo');

        // first set the combo value on panel activate then load the store.
        workflowCombo.setValue(view.task.get('workflow'));

        me.loadAssocData();
    },

    /***
     * @override
     */
    onSaveAssocBtnClick : function(){
        var me = this,
            formPanel = me.lookup('assocForm'),
            taskStore = Ext.StoreManager.get('admin.Tasks'),
            form = formPanel.getForm(),
            rec = formPanel.getRecord();
        form.updateRecord(rec);

        if(! form.isValid()) {
            return;
        }

        rec.saveVersioned(me.getFormTask(),{
            failure: function(rec, op) {
                var errorHandler = Editor.app.getController('ServerException');
                errorHandler.handleFormFailure(form, rec, op);
                taskStore.load();
            },
            success: function() {
                me.getView().down('grid').getStore().load();
                Editor.MessageBox.addSuccess('Assoc saved');
                me.resetRecord();
                taskStore.load();
            }
        });
    },

    onAddAssocBtnClick : function(){
        var me=this,
            newRecord,
            project = me.getView().task,
            formPanel = me.lookup('assocForm'),
            form = formPanel.getForm(),
            workflowCombo = me.getView().down('#workflowCombo'),
            hasProjectTasks = project.hasProjectTasks();

        newRecord = Ext.create('Editor.model.admin.TaskUserAssoc',{
            sourceLang : project.get('sourceLang'), // source language is always the same for projects or single tasks
            workflow: workflowCombo.getValue()
        });

        form.findField('targetLang').setVisible(hasProjectTasks);

        if(!hasProjectTasks){
            newRecord.set('targetLang',project.get('targetLang'));
            newRecord.set('taskGuid',project.get('taskGuid'));
        }else{
            var targetLangs = [];
            project.get('projectTasks').forEach(function(t){
                targetLangs.push(t.get('targetLang'));
            });
            form.findField('targetLang').getStore().filter([{
                property: 'id',
                operator:"in",
                value:targetLangs
            }]);
        }

        me.resetRecord(newRecord);

        formPanel.setDisabled(false);
    },

    onTargetlangSelect: function (combo, record){
        var me = this,
            task = me.getView().task,
            projectTasks = task.get('projectTasks'),
            formPanel = me.lookup('assocForm'),
            formRecord = formPanel.getRecord();


        projectTasks.forEach(function(t){
            if(t.get('targetLang') === record.get('id')){
                formRecord.set('taskGuid',t.get('taskGuid'));
                return false;
            }
        });
    },

    /***
     * On workflow step name change event handler
     * @param combo
     * @param step
     */
    onWorkflowStepNameSelect: function (combo,record) {
        this.setWorkflowStepDefaultDeadline(record.get('id'));
    },


    onNotifyAssociatedUsersCheckBoxChange: function (field, newValue, oldValue){
        this.updatePreImportOnChange(newValue, oldValue);
    },

    /***
     * @override
     */
    onWorkflowComboChange : function (combo, newValue,oldValue){
        var me = this;
        me.callParent(arguments);
        me.updatePreImportOnChange(newValue, oldValue);
    },

    onUsageModeSelect: function (combo, newValue, oldValue){
        var me = this;
        me.updatePreImportOnChange(newValue, oldValue);
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
            workflowCombo = me.getView().down('#workflowCombo'),
            deadlineDate = form.findField('deadlineDate'),
            recordDeadlineDate = formRecord.get('deadlineDate'),//the record has deadlineDate
            orderDate = task.get('orderdate');

        //if order date is not set, no calculation is required
        //if there is no workflow step defined, no calculation is required
        //if the deadlineDate is already set, no calculation is required
        if(!orderDate || !step || recordDeadlineDate){
            return;
        }
        var workflow = workflowCombo.getValue(),
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
     * Find the task or projectTask based on the selected combobox target language.
     */
    getFormTask: function (){
        var me = this,
            taskStore = Ext.StoreManager.get('admin.Tasks'),
            task = me.getView().task,
            projectTasks = task.get('projectTasks'),
            form = me.lookup('assocForm').getForm(),
            formTargetLang = form.findField('targetLang').getValue();

        if(!task.hasProjectTasks()){
            // return the task from the taskStore (the latest task version is stored there)
            return taskStore.getById(task.get('id'));
        }

        projectTasks.forEach(function(t){
            if(t.get('targetLang') === formTargetLang){
                // return the task from the taskStore (the latest task version is stored there)
                task = taskStore.getById(t.get('id'));;
                return false;
            }
        });

        return task;
    },

    /***
     * Update sendPreImportOperation view model variable based on changed values (new/old)
     * @param newValue
     * @param oldValue
     */
    updatePreImportOnChange: function (newValue, oldValue){
        // ignore when the new value is null. This is the case when the default values are set for the components
        if(oldValue === null){
            return;
        }
        this.getView().getViewModel().set('sendPreImportOperation',newValue !== oldValue);
    }
});