
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
 * Editor.controller.admin.TaskUserAssoc encapsulates the User to Task Assoc functionality
 * @class Editor.controller.admin.TaskUserAssoc
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskUserAssoc', {
    extend: 'Ext.app.Controller',
    models: ['admin.TaskUserAssoc', 'admin.Task', 'admin.task.UserPref'],
    stores: ['admin.Users', 'admin.TaskUserAssocs'],
    views: ['admin.task.PreferencesWindow', 'admin.task.UserAssocGrid'],
    mixins: ['Editor.util.Util'],
    refs: [{
        ref: 'assocDelBtn',
        selector: '#adminTaskUserAssocGrid #remove-user-btn'
    }, {
        ref: 'userAssocGrid',
        selector: '#adminTaskUserAssocGrid'
    }, {
        ref: 'userAssoc',
        selector: 'adminTaskUserAssoc'
    }, {
        ref: 'userAssocForm',
        selector: 'adminTaskUserAssoc form'
    }, {
        ref: 'editInfo',
        selector: 'adminTaskUserAssoc #editInfoOverlay'
    }, {
        ref: 'prefWindow',
        selector: '#adminTaskPreferencesWindow'
    }],
    messages: {
        assocSave: '#UT#Eintrag gespeichert!',
        assocDeleted: '#UT#Eintrag gelöscht!',
        assocSaveError: '#UT#Fehler beim Speichern der Änderungen!'
    },
    //***********************************************************************************
    //Begin Events
    //***********************************************************************************
    /**
     * @event addUserAssoc
     * @param {Editor.controller.admin.TaskUserAssoc} me
     * @param {Editor.model.admin.TaskUserAssoc} rec
     * @param {Editor.store.admin.TaskUserAssocs} store
     * Fires after a task user assoc entry was successfully created
     */

    /**
     * @event removeUserAssoc
     * @param {Editor.controller.admin.TaskUserAssoc} me
     * @param {Editor.model.admin.TaskUserAssoc} toDel
     * @param {Editor.store.admin.TaskUserAssocs} assoc
     * Fires after a task user assoc entry was successfully deleted
     */
    //***********************************************************************************
    //End Events
    //***********************************************************************************
    init: function () {
        var me = this;

        if (!Editor.controller.admin.TaskPreferences) {
            //controller.TaskPreferences is somekind of parent controller of controller.TaskUserAssoc so it must be loaded!
            Ext.Error.raise('TaskPreferences controller must be loaded!');
        }

        //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
        Editor.app.on('adminViewportClosed', me.clearStores, me);

        me.control({
            '#adminTaskUserAssocGrid': {
                confirmDelete: me.handleDeleteConfirmClick,
                selectionchange: me.handleAssocSelection
            },
            '#adminTaskUserAssocGrid #add-user-btn': {
                click: me.handleAddUser
            },
            'adminTaskUserAssoc combo[name="workflowStepName"]': {
                change: me.initState
            },
            'adminTaskUserAssoc #save-assoc-btn': {
                click: me.handleSaveAssoc
            },
            'adminTaskUserAssoc #cancel-assoc-btn': {
                click: me.handleCancel
            },
            'adminTaskUserAssoc #userSpecialPropertiesBtn': {
                click: me.onUserSpecialPropertiesBtnClick
            },
            '#adminTaskUserAssocGrid #reload-btn': {
                click: me.reloadTaskUserAssocGrid
            }
        });
    },
    /**
     * Method Shortcut for convenience
     * @param {String} right
     * @return {Boolean}
     */
    isAllowed: function (right) {
        return Editor.app.authenticatedUser.isAllowed(right);
    },
    /**
     * @param {Ext.button.Button} btn
     */
    handleCancel: function () {
        var form = this.getUserAssocForm();
        form.getForm().reset();
        form.hide();
        this.getEditInfo().show();
    },
    /**
     * @param {Ext.button.Button} btn
     */
    handleAddUser: function () {
        var me = this,
            assoc = me.getAdminTaskUserAssocsStore(),
            task = me.getPrefWindow().getCurrentTask(),
            meta = task.getWorkflowMetaData(),
            usageMode = task.get('usageMode'),
            step = task.get('workflowStepName'),
            state = Ext.Object.getKeys(meta.states)[0],
            isTranslationTask = task.get('emptyTargets'),
            userAssoc = me.getUserAssoc(),
            userAssocForm = me.getUserAssocForm(),
            newRec;

        if (!meta.usableSteps[step]) {
            step = Ext.Object.getKeys(meta.usableSteps)[0];
        }

        //in competitive mode instead OPEN / UNCONFIRMED is used
        if (usageMode === Editor.model.admin.Task.USAGE_MODE_COMPETITIVE && state === task.USER_STATE_OPEN) {
            state = task.USER_STATE_UNCONFIRMED;
        }
        //set the default step to the first translation step when the task is translation task and
        //the workflow name is no workflow
        if (isTranslationTask && task.isNoWorkflowStep()) {
            //load first translation step
            step = Ext.Object.getKey(meta.steps2roles, Editor.data.app.workflow.CONST.ROLE_TRANSLATOR);
        }
        newRec = assoc.model.create({
            taskGuid: task.get('taskGuid'),
            workflowStepName: step,
            state: state
        });
 
        userAssoc.fireEvent('addnewassoc', newRec, userAssocForm);
        
        me.getAssocDelBtn().disable();
        me.getEditInfo().hide();
        userAssocForm.show();
        userAssocForm.setDisabled(false);
        me.filterStepsCombo(newRec);
        userAssoc.loadRecord(newRec);
        me.initState(null, step, '');
    },

    /**
     * Disable Delete Button if no User is selected
     * @param {Ext.grid.Panel} grid
     * @param {Array} selection
     */
    handleAssocSelection: function (grid, selection) {
        var me = this,
            formPanel = me.getUserAssocForm(),
            emptySel = selection.length === 0,
            record = !emptySel ? selection[0] : null,
            userEditable = record && record.get('editable'),
            userDeletable = record && record.get('deletable'),
            task = me.getPrefWindow().getCurrentTask();

        me.getAssocDelBtn().setDisabled(emptySel || !userDeletable);

        me.getEditInfo().setVisible(emptySel);

        formPanel.setVisible(!emptySel);

        formPanel.setDisabled(emptySel || !userEditable);

        me.filterStepsCombo(selection[0]);

        if (emptySel) {
            formPanel.getForm().reset();
        } else {
            me.getUserAssoc().loadRecord(selection[0]);
        }
    },

    /**
     * Removes the selected User Task Association
     */
    handleDeleteConfirmClick: function (grid, toDelete) {
        var me = this,
            userAssocPanel = me.getPrefWindow(),
            task = userAssocPanel.getCurrentTask(),
            assoc = me.getAdminTaskUserAssocsStore();

        userAssocPanel.setLoading(true);

        Ext.Array.each(toDelete, function (toDel) {

            toDel.eraseVersioned(task, {

                success: function (rec, op) {

                    assoc.remove(toDel);

                    me.fireEvent('removeUserAssoc', me, toDel, assoc);

                    //reload only the task, not the whole task prefs, should be OK
                    task.load({
                        callback:function (){
                            Editor.MessageBox.addByOperation(op); //does nothing since content is not provided from server :(
                            Editor.MessageBox.addSuccess(me.messages.assocDeleted);
                            userAssocPanel.setLoading(false);
                        }
                    });
                },
                failure: function () {
                    me.application.getController('admin.TaskPreferences').handleReload();
                    userAssocPanel.setLoading(false);
                }
            });
        });
    },
    /**
     * save the user task assoc info.
     */
    handleSaveAssoc: function () {
        var me = this,
            form = me.getUserAssocForm(),
            task = me.getPrefWindow().getCurrentTask(),
            grid = me.getUserAssocGrid(),
            win = me.getPrefWindow(),
            store = grid.store,
            rec = form.getRecord();
        form.getForm().updateRecord(rec);
        if (!form.getForm().isValid()) {
            return;
        }
        win.setLoading(true);
        rec.saveVersioned(task, {
            success: function (savedRec, op) {
                me.handleCancel();
                if (!rec.store) {
                    store.insert(0, rec);
                    grid.getSelectionModel().select(rec);
                    me.fireEvent('addUserAssoc', me, rec, store);
                }
                task.load();//reload only the task, not the whole task prefs, should be OK
                Editor.MessageBox.addByOperation(op);
                Editor.MessageBox.addSuccess(me.messages.assocSave);
                win.setLoading(false);
            },
            failure: function () {
                store.load();
                win.setLoading(false);
            }
        });
    },

    reloadTaskUserAssocGrid: function () {
        var me = this,
            prefWindow = me.getPrefWindow(),
            task = prefWindow.getCurrentTask(),
            store = me.getUserAssocGrid().getStore();


        me.getUserAssocGrid().getSelectionModel().deselectAll();
        store.load({
            callback:function (){
                task && task.load();
            }
        });
    },

    onUserSpecialPropertiesBtnClick: function () {
        var me = this,
            preferences = Ext.create('Editor.view.admin.task.Preferences', {
                task: me.getPrefWindow().getViewModel().get('currentTask')
            });
        preferences.show();
    },

    clearStores: function () {
        this.getAdminTaskUserAssocsStore().removeAll();
    },

    /**
     * sets the initial state value dependent on the step
     * @param {Ext.form.field.ComboBox} roleCombo
     * @param {String} step
     * @param {String} oldValue
     */
    initState: function (roleCombo, step) {
        var me = this,
            form = me.getUserAssocForm(),
            task = me.getPrefWindow().getCurrentTask(),
            stateCombo = form.down('combo[name="state"]'),
            isCompetitive = task.get('usageMode') === Editor.model.admin.Task.USAGE_MODE_COMPETITIVE,
            newState = task.USER_STATE_OPEN,
            rec = form.getRecord(),
            isChanged = stateCombo.getValue() && stateCombo.getValue() !== rec.get('state'),
            meta = task.getWorkflowMetaData(),
            initialStates = meta.initialStates[task.get('workflowStepName')];

        stateCombo.getStore().clearFilter();

        //set the default deadline date when the form state is initialized
        me.setWorkflowStepDefaultDeadline(task, step, rec);

        if (!rec.phantom || isChanged) {
            return;
        }
        //on new job entries only non finished states are allowed.
        // Everything else would make no sense and bypass workflow
        stateCombo.store.addFilter(function (item) {
            return item.get('id') !== Editor.model.admin.Task.prototype.USER_STATE_FINISH;
        });
        if (initialStates && initialStates[step]) {
            newState = initialStates[step];
        }
        if (isCompetitive && newState === task.USER_STATE_OPEN) {
            newState = task.USER_STATE_UNCONFIRMED;
        }
        rec.set('state', newState);
        stateCombo.setValue(newState);
        me.filterUserCombo(step);
    },

    /***
     * Filter the user combo store based on the selected user role. The user can be assigned only once per role.
     */
    filterUserCombo: function (userStep) {
        var me = this,
            form = me.getUserAssocForm(),
            userAssocGrid = me.getUserAssocGrid(),
            usersCombo = form.down('combo[name="userGuid"]'),
            tuaUsers = [];

        //collect all userGuids for the current role
        userAssocGrid.getStore().each(function (rec) {
            if (rec.get('workflowStepName') === userStep) {
                tuaUsers.push(rec.get('userGuid'));
            }
        });

        //filter out all current assoc users from the usersStore
        usersCombo.getStore().addFilter([{
            property: 'userGuid',
            value: tuaUsers,
            operator: 'notin'
        }], true);
    },

    /***
     * Filter out all used options in the workflowStep combo based on the selected assoc record.
     * One user is only able to be assigned to one workflowStep of a workflow.
     * @param selectedRecord
     */
    filterStepsCombo: function (selectedRecord){
        var me = this,
            form = me.getUserAssocForm(),
            userAssocGrid = me.getUserAssocGrid(),
            workflowStepNameCombo = form.down('combo[name="workflowStepName"]'),
            workflowStepStore = workflowStepNameCombo.getStore(),
            usedSteps = [];


        if(!selectedRecord){
            return;
        }
        
        workflowStepStore.clearFilter();

        //collect all used steps for selected record user
        userAssocGrid.getStore().each(function (rec) {
            if (selectedRecord.get('userGuid') === rec.get('userGuid') && selectedRecord.get('workflowStepName')!==rec.get('workflowStepName')) {
                usedSteps.push(rec.get('workflowStepName'));
            }
        });

        // filter out the used workflow steps from the workflow combo
        workflowStepStore.addFilter([{
            property: 'id',
            value: usedSteps,
            operator: 'notin'
        }], true);
    },

    /***
     * Calculate and set the default deadline date from config and order date.
     */
    setWorkflowStepDefaultDeadline: function (task, step, record) {
        var me = this,
            form = me.getUserAssocForm() && me.getUserAssocForm().getForm(),
            deadlineDate = form && form.findField('deadlineDate'),
            recordDeadlineDate = record && record.get('deadlineDate'),//the record has deadlineDate
            orderDate = task && task.get('orderdate');

        //if order date is not set, no calculation is required
        //if there is no workflow step defined, no calculation is required
        //if there is no deadlineDate form field, no calculation is required
        //if the deadlineDate is already set, no calculation is required
        if (!orderDate || !step || !deadlineDate || recordDeadlineDate) {
            return null;
        }
        var workflow = task.get('workflow'),
            configName = Ext.String.format('workflow.{0}.{1}.defaultDeadlineDate', workflow, step),
            days = Editor.app.getTaskConfig(configName),
            newValue = null;

        // calculate the new date if config exist
        if (days) {
            // check if the order date has timestamp 00:00:00
            if (orderDate.getHours() === 0 && orderDate.getMinutes() === 0) {
                // For the deadlineDate the time is also important. This will change the time to now.
                var tmpNow = new Date();
                orderDate.setHours(tmpNow.getHours());
                orderDate.setMinutes(tmpNow.getMinutes());
                orderDate.setSeconds(tmpNow.getSeconds());
            }

            newValue = Editor.util.Util.addBusinessDays(orderDate, days);
        }

        deadlineDate.setValue(newValue);
    }
});
