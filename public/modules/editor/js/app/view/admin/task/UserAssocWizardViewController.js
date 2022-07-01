
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

/***
 * @event wizardCardFinished
 * Fires when next card should be loaded after all operations are finished by the current card.
 *
 * @param skipCards how many cards should be skipped after the current card
 *
 * This controller is extension of AssocViewController and will overwrite some parent methods and event handlers.
 */
Ext.define('Editor.view.admin.task.UserAssocWizardViewController', {
    extend: 'Editor.view.admin.user.AssocViewController',
    alias: 'controller.adminTaskUserAssocWizard',

    listeners:{
        'userAssocRecordDeleted':'onUserAssocRecordDeleted'
    },

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
                beforeselect:'onUsageModeBeforeSelect'
            }
        }
    },

    nextCardClick: function (){
        var me = this;
        me.checkOperation(null);
    },

    skipCardClick: function (){
        var me = this;
        me.checkOperation(5);
    },

    checkOperation: function (skipCards){
        var me = this,
            view = me.getView(),
            sendPreImportOperation = view.getViewModel().get('sendPreImportOperation'),
            notify = view.down('#notifyAssociatedUsersCheckBox').checked,
            usageMode = view.down('#usageMode'),
            workflowCombo = view.down('#workflowCombo');

        if(!sendPreImportOperation){
            view.fireEvent('wizardCardFinished', skipCards);
            return;
        }
        me.preimportOperation({
            usageMode: usageMode.getValue(),
            workflow:workflowCombo.getValue(),
            notifyAssociatedUsers:notify ? 1 : 0
        },function (){
            view.fireEvent('wizardCardFinished', skipCards);
        });
    },

    /***
     * Load the taskuserassoc store for current workflow and projectId
     */
    loadAssocData: function (){
        var me=this,
            view = me.getView(),
            project = view.task,
            workflowCombo = view.down('#workflowCombo'),
            store=view.down('grid').getStore();

        store.setExtraParams({
            projectId:project.get('projectId'),
            workflow: workflowCombo.getValue()
        });
        store.load();
    },

    /***
     */
    onUserAssocWizardActivate:function(){
        var me=this,
            view = me.getView(),
            project = me.getFormTask(),
            workflowCombo = view.down('#workflowCombo'),
            usersStore = Ext.StoreManager.get('admin.Users'),
            usageMode = view.down('#usageMode');

        // first set the combo value on panel activate then load the store.
        workflowCombo.setValue(project.get('workflow'));
        // set the usageMode default from the task. The default value is set from the config after the task is created
        usageMode.setValue(project.get('usageMode') ? project.get('usageMode') : Editor.model.admin.Task.USAGE_MODE_COOPERATIVE);

        me.loadAssocData();

        // if the users are not loaded (ex: pmlight roles), load the store
        if(usersStore.getCount() === 0){
            usersStore.load();
        }

        // set the checkbox default value from config
        me.setNotifyAllUsersTaskConfig();

        me.onAddAssocBtnClick();
    },

    /***
     * @override Editor.view.admin.user.AssocViewController::onSaveAssocBtnClick
     */
    onSaveAssocBtnClick : function(){
        var me = this,
            formPanel = me.lookup('assocForm'),
            taskStore = Ext.StoreManager.get('admin.Tasks'),
            form = formPanel.getForm(),
            rec = formPanel.getRecord(),
            task = me.getFormTask();
        
        form.updateRecord(rec);

        if(! form.isValid()) {
            return;
        }

        rec.saveVersioned(task,{
            failure: function(rec, op) {
                var errorHandler = Editor.app.getController('ServerException');
                errorHandler.handleFormFailure(form, rec, op);
                taskStore.load();
            },
            success: function() {
                me.getView().down('grid').getStore().load();
                Editor.MessageBox.addSuccess('Assoc saved');
                me.onAddAssocBtnClick();
                taskStore.load();
            }
        });
    },

    /***
     *
     * @override Editor.view.admin.user.AssocViewController::onAddAssocBtnClick
     */
    onAddAssocBtnClick : function(){
        var me=this,
            newRecord = me.getView().getDefaultFormRecord(),
            project = me.getView().task,
            formPanel = me.lookup('assocForm'),
            form = formPanel.getForm(),
            targetLangField =  form.findField('targetLang'),
            hasProjectTasks = project.hasProjectTasks();


        targetLangField.setVisible(hasProjectTasks);

        if(!hasProjectTasks){
            // it is single task project, set the taskGuid and the target langauge as record values
            newRecord.set('targetLang',project.get('targetLang'));
            newRecord.set('taskGuid',project.get('taskGuid'));
        } else {
            // it is multi task project, the target language dropdown should contain only the project-tasks target languages
            var targetLangs = [];
            project.get('projectTasks').forEach(function(t){
                targetLangs.push(t.get('targetLang'));
            });
            targetLangField.getStore().filter([{
                property: 'id',
                operator:"in",
                value:targetLangs
            }]);
            targetLangField.suspendEvents();
            targetLangField.setValue(null);
            targetLangField.resumeEvents(true);
        }
     
        // reset the current form and load the new record
        me.resetRecord(newRecord);

        formPanel.setDisabled(false);
    },

    /***
     * After the user assoc record is deleted, reload the form task if exist
     */
    onUserAssocRecordDeleted: function (){
        var me = this,
            task = me.getFormTask();
        if(task){
            task.load();
        }
    },

    /***
     * On target language select update the taskGuid in the form record
     */
    onTargetlangSelect: function (){
        var me = this,
            formPanel = me.lookup('assocForm'),
            formRecord = formPanel.getRecord(),
            task = me.getFormTask();

        // on target language change, set the current form taskGuid to matching project task/single task
        formRecord.set('taskGuid',task.get('taskGuid'));
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

    /***
     *
     * @param combo
     * @param record
     * @param index
     * @param eOpts
     */
    onUsageModeBeforeSelect:function (combo, record){
        // for simultaneous usage state the message bus must be active
        if(record && record.get('id') === Editor.model.admin.Task.USAGE_MODE_SIMULTANEOUS && !Editor.plugins.FrontEndMessageBus) {
            Editor.MessageBox.addError('In order to use that mode the FrontEndMessageBus plug-in must be active!');
            return false;
        }
        this.updatePreImportOnChange(record.get('id'), combo.getValue());
    },

    /***
     * Calculate and set the default deadline date from config and order date.
     */
    setWorkflowStepDefaultDeadline:function(step){
        var me = this,
            formTask = me.getFormTask(),
            orderDate = formTask && formTask.get('orderdate'); // task can be null if created after a faulty task
        
        //if order date is not set, no calculation is required
        //if there is no workflow step defined, no calculation is required
        if(!orderDate || !step){
            return;
        }
        var workflow = me.getView().down('#workflowCombo').getValue(),
            configName = Ext.String.format('workflow.{0}.{1}.defaultDeadlineDate',workflow,step),
            days = Editor.app.getTaskConfig(configName),
            deadlineDate = me.lookup('assocForm').getForm().findField('deadlineDate'),
            newValue;

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
     * The matched task will always be loaded from the tasks store
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
                task = taskStore.getById(t.get('id'));
                return false;
            }
        });

        return task;
    },

    /***
     * Preimport operation request for given params. This is used to update some task properties before the task import is started
     * @param params
     * @param successCallback
     */
    preimportOperation:function (params,successCallback){
        var me=this,
            view = me.getView(),
            project = view.task;
        Ext.Ajax.request({
            url:Editor.data.restpath+'task/{id}/preimport/operation'.replace("{id}",project.get('id')),
            method: 'POST',
            params: params,
            success: successCallback,
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /***
     * Update sendPreImportOperation view model variable based on changed values (new/old)
     * @param newValue
     * @param oldValue
     */
    updatePreImportOnChange: function (newValue, oldValue){
        this.getView().getViewModel().set('sendPreImportOperation',newValue !== oldValue);
    },

    /***
     * This will set the notify associated users checkbox value from runtimeOptions.workflow.notifyAllUsersAboutTask config.
     * This config is overridable on customer level!
     */
    setNotifyAllUsersTaskConfig:function(){
        var me=this,
            view = me.getView(),
            project = view.task,
            notifyAssociatedUsersCheckBox = view.down('#notifyAssociatedUsersCheckBox'),
            store = Ext.create('Editor.store.admin.CustomerConfig');

        view.mask();

        store.loadByCustomerId(project.get('customerId'),function (){
            notifyAssociatedUsersCheckBox.setValue(store.getConfig('workflow.notifyAllUsersAboutTask'));
            view.unmask();
        });
    }
});