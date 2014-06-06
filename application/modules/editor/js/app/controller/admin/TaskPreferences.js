/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
      selector: '.editorAdminTaskUserPrefsGrid'
  },{
      ref: 'editInfo',
      selector: '#editInfoOverlay'
  },{
      ref: 'prefWindow',
      selector: '.adminTaskPreferencesWindow'
  },{
      ref: 'prefForm',
      selector: '.editorAdminTaskUserPrefsForm'
  },{
      ref: 'wfStepCombo',
      selector: '.editorAdminTaskUserPrefsForm .combobox[name="workflowStep"]'
  },{
      ref: 'usersCombo',
      selector: '.editorAdminTaskUserPrefsForm .combobox[name="userGuid"]'
  },{
      ref: 'deleteBtn',
      selector: '.editorAdminTaskUserPrefsGrid #userPrefDelete'
  },{
      ref: 'addBtn',
      selector: '.editorAdminTaskUserPrefsGrid #userPrefAdd'
  },{
      ref: 'taskWorkflow',
      selector: '.editorAdminTaskPreferences #taskWorkflow'
  }],
  strings: {
      taskWorkflowSaved: '#UT#Änderung des Workflows der Aufgabe gespeichert!',
      forAll: '#UT#für alle'
  },
  actualTask: null,
  init : function() {
      var me = this,
          toc = me.application.getController('admin.TaskOverview');
      
      toc.on('handleTaskPreferences', me.handleTaskPreferences, me);     
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('adminViewportClosed', me.clearStores, me);

      me.control({
          '.editorAdminTaskPreferences #taskWorkflow': {
              change: me.changeWorkflow
          },
          '.editorAdminTaskUserPrefsForm #alternates .checkboxgroup': {
              beforerender: me.prepareAlternates
          },
          '.editorAdminTaskUserPrefsForm .combobox[name="workflowStep"]': {
              change: me.comboChange
          },
          '.editorAdminTaskUserPrefsForm': {
              beforerender: me.setActualTaskInCmp
          },
          '.editorAdminTaskUserPrefsGrid': {
              beforerender: me.setActualTaskInCmp,
              confirmDelete: me.handleDeleteConfirmClick,
              deselect: me.handleDeselect,
              itemclick: me.handleGridClick
          },
          '.editorAdminTaskUserPrefsGrid #userPrefReload': {
              click: me.handleReload
          },
          '.editorAdminTaskUserPrefsGrid #userPrefAdd': {
              click: me.handleAddClick
          },
          '.editorAdminTaskUserPrefsForm #cancelBtn': {
              click: me.clickCancel
          },
          '.editorAdminTaskUserPrefsForm #saveBtn': {
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
          tuas = me.getAdminTaskUserAssocsStore(),
          prefs = me.getAdminTaskUserPrefsStore(),
          used = {},
          cnt = 0;
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
          tuas.each(function(tua){
              var guid = tua.get('userGuid');
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
      this.getAddBtn().setDisabled(cnt == 0);
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
                  filter: '[{"type":"string","value":"'+task.get('taskGuid')+'","field":"taskGuid"}]'
              }
          };
      
      me.actualTask = task;
      me.getPrefWindow().loadingShow();
      
      if(me.isAllowed('editorPreferencesTask')){
          //FIXME dieses isAllowed nur in der view!
      }
      if(me.isAllowed('editorChangeUserAssocTask')){
          //FIXME dieses isAllowed nur in der view!
      }
      
      //userPrefs must be loaded after userAssocs, 
      //so add the load as a callback dynamically, based on the rights 
      if(me.isAllowed('editorUserPrefsTask')){
          userPrefs.loadData([],false); //cleanup old contents
          var tupParams = Ext.apply({}, tuaParams); //duplicate params, and add the callback
          tupParams.callback = function() {
              me.calculateAvailableCombinations();
              me.updatePrefsFilter(task.get('workflow'));
              me.getPrefWindow().loadingHide();
          };
          tuaParams.callback = function() {
              userPrefs.load(tupParams);
          };
      }
      else {
          tuaParams.callback = function() {
              me.getPrefWindow().loadingHide();
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
      Ext.defer(function(){
          win.loadingShow(); //deferred because of weired xmask bug in ext 4.0.7
      },50);
      this.loadAllPreferences(task);
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
      form.down('.combobox[name="workflowStep"]').setDisabled(false);
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
      var me = this;
      Ext.Array.each(records, function(rec){
          Ext.defer(function(){
              me.getPrefWindow().loadingShow();
          },10);//weired xmask extjs bug
          rec.destroy({
              success: function() {
                  //FIXME do we have to update some other stores? taskStore && taskStore.load();
                  grid.store.remove(rec);
                  me.calculateAvailableCombinations();
                  me.getPrefWindow().loadingHide();
              },
              failure: function() {
                  me.getPrefWindow().loadingHide();
              }
          });
      });
  },
  
  handleDeselect: function() {
      this.getDeleteBtn().setDisabled();
  },
  
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
      userCombo.setDisabled(rec.isDefault());
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
      me.actualTask.set('workflow', val);
      me.updatePrefsFilter(val);
      me.getPrefWindow().loadingShow();
      me.actualTask.save({
          success: function(rec, op) {
              Editor.MessageBox.addInfo(me.strings.taskWorkflowSaved);
              me.calculateAvailableCombinations();
              me.getPrefWindow().loadingHide();
          },
          failure: function() {
              me.getPrefWindow().loadingHide();
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
  //FIXME clear the local used stores for task change?
  clearStores: function() {
  },
  /**
   * edit an entry handler
   */
  handleGridClick: function(grid, rec) {
      var me = this,
          form = me.getPrefForm();
      form.show();
      me.getEditInfo().hide();
      form.down('.combobox[name="workflowStep"]').setDisabled(rec.isDefault());
      me.getUsersCombo().setDisabled(rec.isDefault());
      me.getDeleteBtn().setDisabled(rec.isDefault());
      me.updateWorkflowSteps(rec);
      //form.getForm().reset();
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
      me.getPrefWindow().loadingShow();
      rec.save({
          success: function() {
              me.clickCancel();
              if(!rec.store) {
                  store.insert(0,rec);
              }
              me.calculateAvailableCombinations();
              me.getPrefWindow().loadingHide();
          },
          failure: function() {
              me.getPrefWindow().loadingHide();
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
  handleReload: function() {
      this.loadAllPreferences(this.actualTask);
  }
});