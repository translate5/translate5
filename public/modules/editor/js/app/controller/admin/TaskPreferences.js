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
 * Editor.controller.admin.TaskPreferences encapsulates the logic around the Task Preferences Window and the UserPrefs Tab
 * @class Editor.controller.admin.TaskPreferences
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskPreferences', {
    extend: 'Ext.app.Controller',
    requires: [
        'Editor.view.admin.customer.Combo',
        'Editor.view.admin.customer.UserCustomersCombo',
    ],
    models: ['admin.TaskUserAssoc', 'admin.Task', 'admin.task.UserPref'],
    //constant to be used as value in the frontend for null values in userGuid and workflowStep:
    FOR_ALL: '',
    stores: [
        'admin.Users',
        'admin.Customers',
        'admin.UserCustomers',
        'admin.TaskUserAssocs',
        'admin.task.UserPrefs',
    ],
    views: ['Editor.view.admin.task.TaskManagement', 'Editor.view.admin.task.UserAssocGrid', 'Editor.view.admin.task.Preferences'],
    refs: [{
        ref: 'editorAdminTaskPreferences',
        selector: 'editorAdminTaskPreferences'
    }, {
        ref: 'prefGrid',
        selector: 'editorAdminTaskUserPrefsGrid'
    }, {
        ref: 'editInfo',
        selector: 'editorAdminTaskPreferences #editInfoOverlay'
    }, {
        ref: 'prefWindow',
        selector: 'adminTaskTaskManagement'
    }, {
        ref: 'prefForm',
        selector: 'editorAdminTaskUserPrefsForm'
    }, {
        ref: 'wfStepCombo',
        selector: 'editorAdminTaskUserPrefsForm combobox[name="workflowStep"]'
    }, {
        ref: 'usersCombo',
        selector: 'editorAdminTaskUserPrefsForm combobox[name="taskUserAssocId"]'
    }, {
        ref: 'deleteBtn',
        selector: 'editorAdminTaskUserPrefsGrid #userPrefDelete'
    }, {
        ref: 'addBtn',
        selector: 'editorAdminTaskUserPrefsGrid #userPrefAdd'
    }, {
        ref: 'taskWorkflow',
        selector: 'editorAdminTaskPreferences #taskWorkflow'
    }, {
        ref: 'userAssocGrid',
        selector: 'adminTaskUserAssocGrid'
    }],
    strings: {
        taskWorkflowSaved: '#UT#Änderung des Workflows der Aufgabe gespeichert!',
        entrySaved: '#UT#Eintrag gespeichert',
        entryDeleted: '#UT#Eintrag gelöscht',
        entrySaveError: '#UT#Fehler beim Speichern der Änderungen!',
        forAll: '#UT#für alle',
        customerTip: '#UT#Kunde der Aufgabe (Angabe notwendig)',
        customerLabel: '#UT#Kunde'
    },
    actualTask: null,
    alias: 'controller.taskPreferencesController',
    listen: {
        component: {
            '#taskMainCard': {
                added: 'onTaskMainCardAdded'
            }
        }
    },

    init: function () {
        var me = this;

        Editor.app.on('adminViewportClosed', me.clearStores, me);

        me.control({
            'editorAdminTaskPreferences': {
                render: me.onEditorAdminTaskPreferencesRender
            },
            'editorAdminTaskPreferences #taskWorkflow': {
                change: me.changeWorkflow
            },
            'editorAdminTaskUserPrefsForm #alternates checkboxgroup': {
                beforerender: me.prepareAlternates
            },
            'editorAdminTaskUserPrefsForm combobox[name="workflowStep"]': {
                change: me.comboChange
            },
            'editorAdminTaskUserPrefsGrid': {
                confirmDelete: me.handleDeleteConfirmClick,
                selectionchange: me.handleAssocSelection
            },
            'editorAdminTaskUserPrefsGrid #userPrefReload': {
                click: me.handleReload
            },
            'editorAdminTaskUserPrefsGrid #userPrefAdd': {
                click: me.handleAddClick
            },
            'editorAdminTaskUserPrefsForm #cancelBtn': {
                click: me.clickCancel
            },
            'editorAdminTaskUserPrefsForm #saveBtn': {
                click: me.clickSave
            }
        });
    },

    /**
     * calculates the available combinations of steps and users
     */
    calculateAvailableCombinations: function () {
        var me = this,
            workflow = me.getActualTask().get('workflow'),
            steps = Ext.apply({}, me.getActualTask().getWorkflowMetaData().assignableSteps),
            steps2roles = Ext.apply({}, me.getActualTask().getWorkflowMetaData().steps2roles),
            tuas = me.getEditorAdminTaskPreferences().getViewModel().get('userAssocStoreData'),
            prefs = me.getPrefGrid().getStore(),
            used = {},
            cnt = 0,
            addButton,
            prefForm = me.getPrefForm();

        me.available = {};

        //calculate the already used step / user combinations
        prefs.each(function (rec) {
            if (rec.get('workflow') != workflow) {
                return;
            }
            var step = rec.get('workflowStep');
            if (!used[step]) {
                used[step] = [];
            }
            if (!rec.get('userGuid')) {
                used[step].push(me.FOR_ALL);
            } else {
                used[step].push(rec.get('taskUserAssocId'));
            }
        });

        //calculate all combinations, without the already used ones
        Ext.Object.each(steps, function (k, v) {
            var step = k;
            for (var i = 0; i < tuas.length; i++) {
                var tua = tuas[i],
                    id = tua.get('id');
                if (steps2roles[step] && steps2roles[step] != tua.get('role')) {
                    return; //show only the users with the role matching to the selected step
                }
                if (used[step] && Ext.Array.indexOf(used[step], id) >= 0) {
                    return; //not available since already used!
                }
                if (!me.available[step]) {
                    me.available[step] = [];
                }
                me.available[step].push(id);
                cnt++;
            }
            //add the forAll step and user
            if (!used[step]) {
                if (!me.available[step]) {
                    me.available[step] = [];
                }
                cnt++;
            }
        });
        //disable the add button if all combinations are reached
        addButton = me.getAddBtn();
        if (addButton) {
            addButton.setDisabled(cnt == 0);
        }

        me.updateUsers(prefForm && prefForm.getRecord());
    },
    /**
     * Method Shortcut for convenience
     * @param {String} right
     * @param {Editor.model.admin.Task} task [optional]
     * @return {Boolean}
     */
    isAllowed: function (right, task) {
        return Editor.app.authenticatedUser.isAllowed(right, task);
    },
    /**
     * Loads all preferences and userassocs to the choosen Task
     * triggerd by click on the Task Preferences Button / (Cell also => @todo)
     * @param {Editor.model.admin.Task} task
     */
    loadAllPreferences: function (task) {
        var me = this;
        me.getPrefGrid() && me.getPrefGrid().getStore().load({
            callback: function() {
                me.calculateAvailableCombinations();
            }
        });
    },

    /**
     * adds a new userpref entry
     */
    handleAddClick: function () {
        var me = this,
            task = me.getActualTask(),
            fields = task.segmentFields().collect('name'),
            rec,
            firstStep = me.updateWorkflowSteps(),
            userPrefs = me.getPrefGrid().getStore(),
            defaultPref = userPrefs.getDefaultFor(task.get('workflow')),
            form = me.getPrefForm();
        form.show();
        form.down('combobox[name="workflowStep"]').setDisabled(false);
        me.getEditInfo().hide();
        rec = Ext.create(Editor.model.admin.task.UserPref, {
            fields: fields,
            notEditContent: defaultPref.get('notEditContent'),
            anonymousCols: defaultPref.get('anonymousCols'),
            visibility: defaultPref.get('visibility'),
            workflow: task.get('workflow'),
            workflowStep: firstStep,
            taskGuid: task.get('taskGuid')
        });
        me.getPrefGrid().getSelectionModel().deselectAll();
        form.getForm().reset();
        form.loadRecord(rec, me.FOR_ALL);
    },
    /**
     * deletes a userpref entry
     * @param {Ext.grid.Panel} grid
     * @param {Editor.model.admin.task.UserPref[]} records
     */
    handleDeleteConfirmClick: function (grid, records) {
        var me = this,
            task = me.getActualTask();
        Ext.Array.each(records, function (rec) {
            rec.eraseVersioned(task, {
                success: function () {
                    grid.store.remove(rec);
                    if (me.isAllowed('editorWorkflowPrefsTask')) {
                        me.calculateAvailableCombinations();
                    }
                    Editor.MessageBox.addSuccess(me.strings.entryDeleted);
                    me.handleReload();
                },
                failure: function () {
                    me.handleReload();
                }
            });
        });
    },
    /**
     * handler to update user data if the workflowStep combo was changed
     */
    comboChange: function () {
        var me = this,
            rec = me.getPrefForm().getRecord();
        me.updateUsers(rec);
    },
    /**
     * prefills the workflow step combo in the form with the available steps for the selected workflow
     * returns the first workflow step name in the combo
     * @param {Editor.model.admin.task.UserPref} rec optional
     * @return {String}
     */
    updateWorkflowSteps: function (rec) {
        var me = this,
            data = me.getActualTask().getWorkflowMetaData(),
            wfStepCombo = me.getWfStepCombo(),
            steps = [];
        Ext.Object.each(data.steps, function (key, val) {
            if (me.available[key] || rec && rec.get('workflowStep') == key) {
                wfStepCombo.getStore().add({
                    id: key,
                    label: val,
                    role: data.steps2roles[key] ? data.steps2roles[key] : null
                });
            }
        });

        if (steps.length == 0) {
            return "";
        }
        return steps[0][0];
    },

    /**
     * prefills the workflow step combo in the form with the available steps for the selected workflow
     * @param {Editor.model.admin.task.UserPref} rec
     */
    updateUsers: function (rec) {
        var me = this,
            tuas = me.getEditorAdminTaskPreferences().getViewModel().get('userAssocStoreData'),
            step = me.getWfStepCombo().getValue(),
            userCombo = me.getUsersCombo(),
            value = userCombo.getValue(),
            userComboStore = userCombo.getStore(),
            isAvailable = function (key) {
                return !!me.available[step] && (Ext.Array.indexOf(me.available[step], key) >= 0);
            },
            users = [];

        userComboStore.clearFilter(true);
        if (step && step.length == 0) {
            return;
        }
        var active = [];

        if (rec) {
            active.push(rec.get('taskUserAssocId'));
        }

        for (var i = 0; i < tuas.length; i++) {
            var tua = tuas[i];
            isAvailable(tua.get('id')) && active.push(tua.get('id'));
        }
        if (active.length > 0 || !rec) {
            userComboStore.addFilter({
                property: 'id',
                operator: 'in',
                value: active
            });
        }
        userCombo.setDisabled(rec && rec.isDefault());
    },
    /**
     * saves the new workflow into the task, and to the server
     * @param {Ext.form.fiueld.ComboBox} combo
     * @param {String} val
     */
    changeWorkflow: function (combo, val) {
        var me = this;
        if (!val) {
            return;
        }
        me.updatePrefsFilter(val);
        if (combo.eventsSuspended) {
            return;
        }
        me.getActualTask().set('workflow', val);
        me.getActualTask().save({
            success: function (rec, op) {
                Editor.MessageBox.addInfo(me.strings.taskWorkflowSaved);
                me.calculateAvailableCombinations();
                me.handleReload();
            },
            failure: function () {
                me.handleReload();
            }
        });
    },

    /**
     * updates the grid workflow filter
     */
    updatePrefsFilter: function (workflow) {
        var prefs = this.getPrefGrid().getStore();
        prefs.addFilter({
            property: "workflow",
            operator: "eq",
            value: workflow
        });
    },

    /**
     * adds one checkbox per alternate in the config form
     * @param {Ext.form.CheckboxGroup} checkboxGroup
     */
    prepareAlternates: function (checkboxGroup) {
        this.getActualTask().segmentFields().each(function (field) {
            checkboxGroup.add({
                xtype: 'checkbox',
                boxLabel: field.get('label'),
                value: field.get('name'),
                inputValue: field.get('name'),
                name: 'fields'
            });
        });
    },
    clearStores: function () {
        var me = this,
            prefGrid = me.getPrefGrid();
        prefGrid && prefGrid.getStore().removeAll();
    },
    /**
     * handler for changing the selection in the userpref grid
     */
    handleAssocSelection: function (grid, selection) {
        var me = this,
            form = me.getPrefForm(),
            emptySel = selection.length == 0,
            rec = emptySel ? null : selection[0];

        me.getDeleteBtn().setDisabled(emptySel || rec.isDefault());
        me.getEditInfo().setVisible(emptySel);
        form.setVisible(!emptySel);
        if (emptySel) {
            form.getForm().reset();
            return;
        }
        form.down('combobox[name="workflowStep"]').setDisabled(rec.isDefault());
        me.getUsersCombo().setDisabled(rec.isDefault());
        me.calculateAvailableCombinations();
        me.updateWorkflowSteps(rec);
        me.updateUsers(rec);
        form.loadRecord(rec, me.FOR_ALL);
    },
    /**
     * save handler
     */
    clickSave: function () {
        var me = this,
            form = me.getPrefForm(),
            store = me.getPrefGrid().store,
            rec = form.getRecord(),
            fields = form.getValues().fields;
        form.getForm().updateRecord(rec);
        if (!form.getForm().isValid()) {
            return;
        }
        if (Ext.isArray(fields)) {
            fields = fields.join(',');
        }
        rec.set('fields', fields);
        if (!rec.get('workflowStep')) {
            rec.set('workflowStep', null);
        }
        if (!rec.get('userGuid')) {
            rec.set('userGuid', null);
            rec.set('taskUserAssocId', null);
        }
        rec.set('workflow', me.getTaskWorkflow().getValue());
        rec.saveVersioned(me.getActualTask(), {
            success: function () {
                me.clickCancel();
                if (!rec.store) {
                    store.insert(0, rec);
                }
                me.getActualTask().load({
                    callback: function () {
                        me.calculateAvailableCombinations();
                        me.handleReload();
                        Editor.MessageBox.addSuccess(me.strings.entrySaved);
                    }
                });
            },
            failure: function () {
                me.handleReload();
            }
        });
    },
    /**
     * Cancels adding / editing a userpref
     */
    clickCancel: function () {
        var form = this.getPrefForm();
        form.getForm().reset();
        form.hide();
        this.getEditInfo().show();
    },
    /**
     * reloads all preferences and assocs of current task
     */
    handleReload: function () {
        var me = this;
        me.loadAllPreferences(me.getActualTask());
    },

    /**
     * Called when task add window (task main card) is rendered.
     * Adds the item for assigning a customer to the new task.
     * If there is only one customer filtered (eg by the CustomerSwitch),
     * this customer is preselected.
     */
    onTaskMainCardAdded: function (taskMainCard, eOpts) {
        var me = this, store,
            auth = Editor.app.authenticatedUser,
            taskMainCardContainer = taskMainCard.down('#taskMainCardContainer'),
            comboBox;
        // add the customer field to the taskUpload window
        if (auth.isAllowed('editorCustomerSwitch')) {
            comboBox = taskMainCardContainer.insert(0, {
                xtype: 'customersCombo', // user is allowed to see the CustomerSwitch => show all customers
                name: 'customerId',
                itemId: 'customerId',
                toolTip: me.strings.customerTip,
                fieldLabel: me.strings.customerLabel + '¹'
            });
        } else {
            store = Ext.getStore('userCustomers');
            if (!store.isLoaded()) {
                store.load();
            }
            comboBox = taskMainCardContainer.insert(0, {
                xtype: 'usercustomerscombo', // show only those customers that are assigned to the user
                name: 'customerId',
                itemId: 'customerId',
                toolTip: me.strings.customerTip,
                fieldLabel: me.strings.customerLabel + '¹'
            });
        }
    },

    onEditorAdminTaskPreferencesRender: function (component) {
        var me = this,
            tuas = me.getUserAssocGrid().getStore(),
            records = [];

        tuas.each(function (r) {
            records.push(r.copy());
        });
        component.getViewModel().set('userAssocStoreData', records);
        me.calculateAvailableCombinations();
    },

    getActualTask: function () {
        return this.getPrefWindow().getCurrentTask();
    }
});
