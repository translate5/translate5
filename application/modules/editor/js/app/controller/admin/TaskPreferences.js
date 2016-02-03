
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Editor.controller.admin.TaskPreferences encapsulates the logic around the Task Preferences Window and the UserPrefs Tab 
 * @class Editor.controller.admin.TaskPreferences
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskPreferences', {
  extend : 'Ext.app.Controller',
  models: ['admin.TaskUserAssoc','admin.Task','admin.task.UserPref'],
  //constant to be used as value in the frontend for null values in userGuid and workflowStep:
  FOR_ALL: '_forall',
  stores: ['admin.Users', 'admin.TaskUserAssocs', 'admin.task.UserPrefs'],
  views: ['Editor.view.admin.task.PreferencesWindow', 'Editor.view.admin.task.UserAssocGrid','Editor.view.admin.task.Preferences'],
  refs : [{
      ref: 'prefGrid',
      selector: 'editorAdminTaskUserPrefsGrid'
  },{
      ref: 'editInfo',
      selector: 'editorAdminTaskPreferences #editInfoOverlay'
  },{
      ref: 'prefWindow',
      selector: 'adminTaskPreferencesWindow'
  },{
      ref: 'prefForm',
      selector: 'editorAdminTaskUserPrefsForm'
  },{
      ref: 'wfStepCombo',
      selector: 'editorAdminTaskUserPrefsForm combobox[name="workflowStep"]'
  },{
      ref: 'usersCombo',
      selector: 'editorAdminTaskUserPrefsForm combobox[name="userGuid"]'
  },{
      ref: 'deleteBtn',
      selector: 'editorAdminTaskUserPrefsGrid #userPrefDelete'
  },{
      ref: 'addBtn',
      selector: 'editorAdminTaskUserPrefsGrid #userPrefAdd'
  },{
      ref: 'taskWorkflow',
      selector: 'editorAdminTaskPreferences #taskWorkflow'
  }],
  strings: {
      taskWorkflowSaved: '#UT#Änderung des Workflows der Aufgabe gespeichert!',
      entrySaved: '#UT#Eintrag gespeichert',
      entryDeleted: '#UT#Eintrag gelöscht',
      entrySaveError: '#UT#Fehler beim Speichern der Änderungen!',
      forAll: '#UT#für alle'
  },
  actualTask: null,
  init : function() {
      var me = this,
          toc = me.application.getController('admin.TaskOverview'),
          tua;
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      if(Editor.controller.admin.TaskUserAssoc){
          tua = me.application.getController('admin.TaskUserAssoc');
          tua.on('addUserAssoc', me.calculateAvailableCombinations, me);
          tua.on('removeUserAssoc', me.handleReload, me);
      }
      toc.on('handleTaskPreferences', me.handleTaskPreferences, me);
      Editor.app.on('adminViewportClosed', me.clearStores, me);

      me.control({
          'editorAdminTaskPreferences #taskWorkflow': {
              change: me.changeWorkflow
          },
          'adminTaskPreferencesWindow #close-btn': {
              click: me.handleCloseWindow
          },
          'editorAdminTaskUserPrefsForm #alternates checkboxgroup': {
              beforerender: me.prepareAlternates
          },
          'editorAdminTaskUserPrefsForm combobox[name="workflowStep"]': {
              change: me.comboChange
          },
          'editorAdminTaskUserPrefsForm': {
              beforerender: me.setActualTaskInCmp
          },
          'editorAdminTaskUserPrefsGrid': {
              beforerender: me.setActualTaskInCmp,
              confirmDelete: me.handleDeleteConfirmClick,
              selectionchange: me.handleAssocSelection
          },
          'editorAdminTaskUserPrefsGrid #userPrefReload': {
              click: me.handleReload
          },
          '#adminTaskUserAssocGrid #reload-btn': {
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
  calculateAvailableCombinations: function() {
      var me = this,
          workflow = me.actualTask.get('workflow'),
          steps = Ext.apply({}, me.actualTask.getWorkflowMetaData().steps),
          steps2roles = Ext.apply({}, me.actualTask.getWorkflowMetaData().steps2roles),
          tuas = me.getAdminTaskUserAssocsStore(),
          prefs = me.getAdminTaskUserPrefsStore(),
          used = {},
          cnt = 0,
          addButton;
      me.available = {};

      //calculate the already used step / user combinations
      prefs.each(function(rec) {
          if(rec.get('workflow') != workflow){
              return;
          }
          var step = rec.get('workflowStep') || me.FOR_ALL;
          if(!used[step]) {
              used[step] = [];
          }
          used[step].push(rec.get('userGuid') || me.FOR_ALL);
      });
      
      //calculate all combinations, without the already used ones
      Ext.Object.each(steps, function(k,v){
          var step = k;
          if(step == 'pmCheck') {
              //ignore pmCheck since this state can not be assigned to user directly and a PM sees all columns
              return;
          }
          tuas.each(function(tua){
              var guid = tua.get('userGuid');
              if(steps2roles[step] && steps2roles[step] != tua.get('role')) {
                  return; //show only the users with the role matching to the selected step 
              }
              if(used[step] && Ext.Array.indexOf(used[step], guid) >= 0){
                  return; //not available since already used!
              }
              if(!me.available[step]) {
                  me.available[step] = [];
              }
              me.available[step].push(guid);
              cnt++;
          });
          //add the forAll step and user
          if(!used[step] || Ext.Array.indexOf(used[step], me.FOR_ALL) < 0) {
              if(!me.available[step]) {
                  me.available[step] = [];
              }
              me.available[step].push(me.FOR_ALL);
              cnt++;
          }
      });
      //disable the add button if all combinations are reached
      addButton = me.getAddBtn();
      if (addButton) {
          addButton.setDisabled(cnt == 0);
      }
      me.updateUsers(me.getPrefForm().getRecord());
  }, 
  /**
   * Method Shortcut for convenience
   * @param {String} right
   * @param {Editor.model.admin.Task} task [optional]
   * @return {Boolean}
   */
  isAllowed: function(right, task) {
      return Editor.app.authenticatedUser.isAllowed(right, task);
  },
  /**
   * Loads all preferences and userassocs to the choosen Task
   * triggerd by click on the Task Preferences Button / (Cell also => @todo)
   * @param {Editor.model.admin.Task} task
   */
  loadAllPreferences: function(task) {
      var me = this,
          userPrefs = me.getAdminTaskUserPrefsStore(),
          userAssocs = me.getAdminTaskUserAssocsStore(),
          //wenn die Stores echte Filter bekommt muss der Wert hier miteingebaut werden!
          tuaParams = {
              params: {
                  filter: '[{"operator":"like","value":"'+task.get('taskGuid')+'","property":"taskGuid"}]'
              }
          };
      
      me.actualTask = task;
      me.getPrefWindow().setLoading(true);
      
      //userPrefs must be loaded after userAssocs, 
      //so add the load as a callback dynamically, based on the rights 
      if(me.isAllowed('editorUserPrefsTask')){
          userPrefs.loadData([],false); //cleanup old contents
          var tupParams = Ext.apply({}, tuaParams); //duplicate params, and add the callback
          tupParams.callback = function() {
              me.calculateAvailableCombinations();
              me.updatePrefsFilter(task.get('workflow'));
              me.getPrefWindow().setLoading(false);
          };
          tuaParams.callback = function() {
              userPrefs.load(tupParams);
          };
      }
      else {
          tuaParams.callback = function() {
              me.getPrefWindow().setLoading(false);
          };
      }
      
      userAssocs.loadData([],false); //cleanup old contents
      userAssocs.load(tuaParams);
  },
  /**
   * Opens the Preferences to the choosen Task
   * triggerd by click on the Task Preferences Button / (Cell also => @todo)
   * @param {Editor.model.admin.Task} task
   */
  handleTaskPreferences: function(task) {
      this.actualTask = task;
      var win = Ext.widget('adminTaskPreferencesWindow',{
          actualTask: task
      });
      win.show();
      win.setLoading(true);
      this.loadAllPreferences(task);
  },
  /**
   * handler if close button is pressed
   */
  handleCloseWindow: function () {
      this.getPrefWindow().close();
  },
  /**
   * adds a new userpref entry
   */
  handleAddClick: function() {
      var me = this,
          task = me.actualTask,
          fields = task.segmentFields().collect('name'),
          rec,
          firstStep = me.updateWorkflowSteps(),
          userPrefs = me.getAdminTaskUserPrefsStore(),
          defaultPref = userPrefs.getDefaultFor(task.get('workflow')),
          form = me.getPrefForm();
      form.show();
      form.down('combobox[name="workflowStep"]').setDisabled(false);
      me.getEditInfo().hide();
      rec = Ext.create(Editor.model.admin.task.UserPref, {
          fields: fields,
          anonymousCols: defaultPref.get('anonymousCols'),
          visibility: defaultPref.get('visibility'),
          workflow: task.get('workflow'),
          workflowStep: firstStep,
          taskGuid: task.get('taskGuid')
      });
      me.getPrefGrid().getSelectionModel().deselectAll();
      form.getForm().reset();
      form.loadRecord(rec, me.FOR_ALL);
      me.getUsersCombo().setValue();
  },
  /**
   * deletes a userpref entry
   * @param {Ext.grid.Panel} grid
   * @param {Editor.model.admin.task.UserPref[]} records
   */
  handleDeleteConfirmClick: function(grid, records) {
      var me = this,
          task = me.actualTask;
      Ext.Array.each(records, function(rec){
          me.getPrefWindow().setLoading(true);
          rec.eraseVersioned(task, {
              success: function() {
                  grid.store.remove(rec);
                  me.calculateAvailableCombinations();
                  Editor.MessageBox.addSuccess(me.strings.entryDeleted);
                  me.handleReload();
              },
              failure: function() {
                  me.handleReload();
              }
          });
      });
  },
  /**
   * handler to update user data if the workflowStep combo was changed
   */
  comboChange: function() {
    var me = this,
        rec = me.getPrefForm().getRecord();
    me.getUsersCombo().setValue();
    me.updateUsers(rec);
  },
  /**
   * prefills the workflow step combo in the form with the available steps for the selected workflow
   * returns the first workflow step name in the combo
   * @param {Editor.model.admin.task.UserPref} rec optional
   * @return {String}
   */
  updateWorkflowSteps: function(rec) {
      var me = this,
          data = me.actualTask.getWorkflowMetaData(),
          steps = [];
      Ext.Object.each(data.steps, function(key, val) {
          if(me.available[key] || rec && rec.get('workflowStep') == key) {
              steps.push([key, val]);
          }
      });
      if(me.available[me.FOR_ALL] || rec && rec.get('workflowStep').length == 0) {
          steps.push([me.FOR_ALL, me.strings.forAll]);
      }
      me.getWfStepCombo().store.loadData(steps);
      if(steps.length == 0){
          return "";
      }
      return steps[0][0];
  },
  
  /**
   * prefills the workflow step combo in the form with the available steps for the selected workflow
   * @param {Editor.model.admin.task.UserPref} rec
   */
  updateUsers: function(rec) {
      var me = this,
          tuas = me.getAdminTaskUserAssocsStore(),
          step = me.getWfStepCombo().getValue(),
          userCombo = me.getUsersCombo(),
          value = userCombo.getValue(),
          isAvailable = function(guid) {
              return !!me.available[step] && (Ext.Array.indexOf(me.available[step], guid) >= 0);
          },
          users = [];
      if(step && step.length == 0) {
          return;
      }
      tuas.each(function(tua){
          var ug = tua.get('userGuid'),
              isSelf = rec && rec.get('userGuid') == ug,
              userName = tua.get('surName')+', '+tua.get('firstName')+' ('+tua.get('login')+')';
              
          if(isAvailable(ug) || isSelf) {
              users.push([ug, userName]);
          }
      });
      if(isAvailable(me.FOR_ALL) || (step != me.FOR_ALL && rec && rec.get('userGuid').length == 0)) {
          users.push([me.FOR_ALL, me.strings.forAll]);
      }
      userCombo.setDisabled(rec && rec.isDefault());
      userCombo.store.loadData(users);
      userCombo.setValue(value);
  },
  /**
   * saves the new workflow into the task, and to the server
   * @param {Ext.form.fiueld.ComboBox} combo
   * @param {String} val
   */
  changeWorkflow: function(combo, val) {
      var me = this;
      me.updatePrefsFilter(val);
      if(combo.eventsSuspended) {
          return;
      }
      me.actualTask.set('workflow', val);
      me.getPrefWindow().setLoading(true);
      me.actualTask.save({
          success: function(rec, op) {
              Editor.MessageBox.addInfo(me.strings.taskWorkflowSaved);
              me.calculateAvailableCombinations();
              me.handleReload();
          },
          failure: function() {
              me.handleReload();
          }
      });
  },
  /**
   * updates the grid workflow filter
   */
  updatePrefsFilter: function(workflow) {
      var prefs = this.getAdminTaskUserPrefsStore();
      prefs.clearFilter();
      prefs.filter([{property: "workflow", value: workflow}]);
  },
  /**
   * adds one checkbox per alternate in the config form 
   * @param {Ext.form.CheckboxGroup} checkboxGroup
   */
  prepareAlternates: function(checkboxGroup) {
      this.actualTask.segmentFields().each(function(field){
          checkboxGroup.add({
              xtype: 'checkbox',
              boxLabel: field.get('label'),
              value: field.get('name'),
              inputValue: field.get('name'),
              name: 'fields'
          });
      });
  },
  /**
   * sets a reference of the actual task in the given component
   * @param {Ext.AbstractComponent} cmp
   */
  setActualTaskInCmp: function(cmp) {
      var me = this,
          labels = {};
      cmp.actualTask = me.actualTask;
      me.actualTask.segmentFields().each(function(field){
          labels[field.get('name')] = field.get('label');
      });
      cmp.fieldLabels = labels;
  },
  clearStores: function() {
      this.getAdminTaskUserPrefsStore().removeAll();
  },
  /**
   * handler for changing the selection in the userpref grid
   */
  handleAssocSelection: function(grid, selection) {
      var me = this,
          form = me.getPrefForm(),
          emptySel = selection.length == 0,
          rec = emptySel ? null : selection[0];
      
      me.getDeleteBtn().setDisabled(emptySel || rec.isDefault());
      me.getEditInfo().setVisible(emptySel);
      form.setVisible(!emptySel);
      if(emptySel) {
          form.getForm().reset();
          return;
      }
      form.down('combobox[name="workflowStep"]').setDisabled(rec.isDefault());
      me.getUsersCombo().setDisabled(rec.isDefault());
      me.updateWorkflowSteps(rec);
      form.loadRecord(rec, me.FOR_ALL);
  },
  /**
   * save handler
   */
  clickSave: function(){
      var me = this,
          form = me.getPrefForm(),
          store = me.getPrefGrid().store,
          rec = form.getRecord(),
          fields = form.getValues().fields;
      form.getForm().updateRecord(rec);
      if(! form.getForm().isValid()) {
        return;
      }
      if(Ext.isArray(fields)) {
          fields = fields.join(',');
      }
      rec.set('fields', fields);
      if(rec.get('workflowStep') == me.FOR_ALL) {
          rec.set('workflowStep', null);
      }
      if(rec.get('userGuid') == me.FOR_ALL) {
          rec.set('userGuid', null);
      }
      rec.set('workflow', me.getTaskWorkflow().getValue());
      rec.saveVersioned(me.actualTask, {
          success: function() {
              me.clickCancel();
              if(!rec.store) {
                  store.insert(0,rec);
              }
              me.calculateAvailableCombinations();
              me.handleReload();
              Editor.MessageBox.addSuccess(me.strings.entrySaved);
          },
          failure: function() {
              me.handleReload();
          }
      });
  },
  /**
   * Cancels adding / editing a userpref 
   */
  clickCancel: function() {
      var form = this.getPrefForm();
      form.getForm().reset();
      form.hide();
      this.getEditInfo().show();
  },
  /**
   * reloads all preferences and assocs of current task
   */
  handleReload: function() {
      var me = this;
      me.actualTask.reload({
          success: function(rec) {
              var combo = me.getTaskWorkflow(),
                  wf = rec.get('workflow');
              combo.suspendEvents();
              combo.setValue(wf);
              combo.resetOriginalValue();
              combo.resumeEvents();
              me.updatePrefsFilter(wf);
          }
      });
      me.loadAllPreferences(me.actualTask);
  }
});
