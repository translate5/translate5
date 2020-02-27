
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

/**
 * Editor.controller.admin.TaskUserAssoc encapsulates the User to Task Assoc functionality
 * @class Editor.controller.admin.TaskUserAssoc
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskUserAssoc', {
  extend : 'Ext.app.Controller',
  models: ['admin.TaskUserAssoc','admin.Task','admin.task.UserPref'],
  stores: ['admin.Users', 'admin.TaskUserAssocs'],
  views: ['admin.task.PreferencesWindow','admin.task.UserAssocGrid'],
  refs : [{
      ref: 'assocDelBtn',
      selector: '#adminTaskUserAssocGrid #remove-user-btn'
  },{
      ref: 'userAssocGrid',
      selector: '#adminTaskUserAssocGrid'
  },{
      ref: 'userAssoc',
      selector: 'adminTaskUserAssoc'
  },{
      ref: 'userAssocForm',
      selector: 'adminTaskUserAssoc form'
  },{
      ref: 'editInfo',
      selector: 'adminTaskUserAssoc #editInfoOverlay'
  },{
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
  init : function() {
      var me = this;

      if(!Editor.controller.admin.TaskPreferences) {
          //controller.TaskPreferences is somekind of parent controller of controller.TaskUserAssoc so it must be loaded!
          Ext.Error.raise('TaskPreferences controller must be loaded!');
      }
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('adminViewportClosed', me.clearStores, me);
      me.getAdminTaskUserAssocsStore().on('load', me.updateUsers, me);
      
      me.control({
          '#adminTaskUserAssocGrid': {
              confirmDelete: me.handleDeleteConfirmClick,
              selectionchange: me.handleAssocSelection
          },
          '#adminTaskUserAssocGrid #add-user-btn': {
              click: me.handleAddUser
          },
          'adminTaskUserAssoc combo[name="role"]': {
              change: me.initState
          },
          'adminTaskUserAssoc #save-assoc-btn': {
              click: me.handleSaveAssoc
          },
          'adminTaskUserAssoc #cancel-assoc-btn': {
              click: me.handleCancel
          }
      });
  },
  /**
   * Method Shortcut for convenience
   * @param {String} right
   * @return {Boolean}
   */
  isAllowed: function(right) {
      return Editor.app.authenticatedUser.isAllowed(right);
  },
  /**
   * @param {Ext.button.Button} btn
   */
  handleCancel: function(btn){
      var form = this.getUserAssocForm();
      form.getForm().reset();
      form.hide();
      this.getEditInfo().show();
  },
  /**
   * @param {Ext.button.Button} btn
   */
  handleAddUser: function(btn){
      var me = this,
          assoc = me.getAdminTaskUserAssocsStore(),
          task = me.getPrefWindow().actualTask,
          meta = task.getWorkflowMetaData(),
          role = meta.steps2roles[task.get('workflowStepName')] || Ext.Object.getKeys(meta.roles)[0],
          state = Ext.Object.getKeys(meta.states)[0],
          isTranslationTask=task.get('emptyTargets'),
          newRec;
      
      
      //in competitive mode instead OPEN / UNCONFIRMED is used
      if(task.get('usageMode') == task.USAGE_MODE_COMPETITIVE && state == task.USER_STATE_OPEN){
          state = task.USER_STATE_UNCONFIRMED;
      }
      //set the default role to translator when the task is translation task and
      //the workflow name is no workflow
      if(isTranslationTask && task.WORKFLOW_STEP_NO_WORKFLOW == task.get('workflowStepName')){
    	  role=task.WORKFLOW_USER_ROLE_TRANSLATOR;
      }
      newRec = assoc.model.create({
          taskGuid: task.get('taskGuid'),
          role: role,
          state: state
      });
      me.getAssocDelBtn().disable();
      me.getEditInfo().hide();
      me.getUserAssocForm().show();
      me.getUserAssocForm().setDisabled(false);
      me.getUserAssoc().loadRecord(newRec);
      me.initState(null, role, '');
  },
  
  /**
   * Disable Delete Button if no User is selected
   * @param {Ext.grid.Panel} grid
   * @param {Array} selection
   */
  handleAssocSelection: function(grid, selection) {
      var me = this,
          emptySel = selection.length == 0,
          record=!emptySel ? selection[0] : null,
          userEditable=record && record.get('editable');
          userDeletable=record && record.get('deletable');
      me.getAssocDelBtn().setDisabled(emptySel || !userDeletable);
      me.getEditInfo().setVisible(emptySel);
      me.getUserAssocForm().setVisible(!emptySel);
      me.getUserAssocForm().setDisabled(emptySel || !userEditable);
      if(emptySel) {
          me.getUserAssocForm().getForm().reset();
      }
      else {
          me.getUserAssoc().loadRecord(selection[0]);
      }
  },
  /**
   * Removes the selected User Task Association
   */
  handleDeleteConfirmClick: function(grid, toDelete, btn) {
      var me = this,
          task = me.getPrefWindow().actualTask,
          assoc = me.getAdminTaskUserAssocsStore();
      
      Ext.Array.each(toDelete, function(toDel){
          me.getPrefWindow().lookupViewModel().set('userAssocDirty', true);
          toDel.eraseVersioned(task, {
              success: function(rec, op) {
                  assoc.remove(toDel);
                  if(assoc.getCount() == 0) {
                      //if there is no user assoc entry anymore, we consider that as not dirty
                      me.getPrefWindow().lookupViewModel().set('userAssocDirty', false);
                  }
                  me.updateUsers(assoc);
                  me.fireEvent('removeUserAssoc', me, toDel, assoc);
                  task.load();//reload only the task, not the whole task prefs, should be OK
                  Editor.MessageBox.addByOperation(op); //does nothing since content is not provided from server :(
                  Editor.MessageBox.addSuccess(me.messages.assocDeleted);
              },
              failure: function() {
                  me.application.getController('admin.TaskPreferences').handleReload();
              }
          });
      });
  },
  /**
   * save the user task assoc info.
   * @param {Ext.button.Button} btn
   */
  handleSaveAssoc: function (btn) {
      var me = this,
          form = me.getUserAssocForm(),
          task = me.getPrefWindow().actualTask,
          store = me.getUserAssocGrid().store,
          rec = form.getRecord();
      form.getForm().updateRecord(rec);
      if(! form.getForm().isValid()) {
          return;
      }
      me.getPrefWindow().setLoading(true);
      me.getPrefWindow().lookupViewModel().set('userAssocDirty', true);
      rec.saveVersioned(task, {
          success: function(savedRec, op) {
              me.handleCancel();
              if(!rec.store) {
                  store.insert(0,rec);
                  me.updateUsers(store);
                  me.fireEvent('addUserAssoc', me, rec, store);
              }
              task.load();//reload only the task, not the whole task prefs, should be OK
              Editor.MessageBox.addByOperation(op);
              Editor.MessageBox.addSuccess(me.messages.assocSave);
              me.getPrefWindow().setLoading(false);
          },
          failure: function() {
              me.application.getController('admin.TaskPreferences').handleReload();
          }
      });
  },
  clearStores: function() {
      this.getAdminTaskUserAssocsStore().removeAll();
  },
  /**
   * updates the user list to be excluded from the add user drop down
   * @param {} store
   */
  updateUsers: function(store) {
      var me = this;
      if(me.getUserAssoc()) {
          me.getUserAssoc().excludeLogins = store.collect('login');
      }
  },
  /**
   * sets the initial state value dependent on the role
   * @param {Ext.form.field.ComboBox} roleCombo
   * @param {String} newValue
   * @param {String} oldValue
   */
  initState: function(roleCombo, newValue, oldValue) {
      var me = this,
          form = me.getUserAssocForm(),
          task = me.getPrefWindow().actualTask,
          stateCombo = form.down('combo[name="state"]'),
          isCompetitive = task.get('usageMode') == task.USAGE_MODE_COMPETITIVE,
          newState = task.USER_STATE_OPEN,
          rec = form.getRecord(),
          isChanged = stateCombo.getValue() != rec.get('state'),
          meta = task.getWorkflowMetaData(),
          initialStates = meta.initialStates[task.get('workflowStepName')];
      stateCombo.store.clearFilter();
      if(!rec.phantom || isChanged) {
          return;
      }
      //on new job entries only non finished states are allowed. 
      // Everything else would make no sense and bypass workflow
      stateCombo.store.addFilter(function(item){
          return item.get('id') != Editor.model.admin.Task.prototype.USER_STATE_FINISH;
      });
      if(initialStates && initialStates[newValue]) {
          newState = initialStates[newValue];
      }
      if(isCompetitive && newState == task.USER_STATE_OPEN) {
          newState = task.USER_STATE_UNCONFIRMED;
      }
      rec.set('state', newState);
      stateCombo.setValue(newState);
  }
});
