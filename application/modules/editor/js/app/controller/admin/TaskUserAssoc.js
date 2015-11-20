
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
 * Editor.controller.admin.TaskUserAssoc encapsulates the User to Task Assoc functionality
 * @class Editor.controller.admin.TaskUserAssoc
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskUserAssoc', {
  extend : 'Ext.app.Controller',
  models: ['admin.TaskUserAssoc','admin.Task','admin.task.UserPref'],
  stores: ['admin.Users', 'admin.TaskUserAssocs'],
  views: ['admin.task.PreferencesWindow','admin.task.UserAssocGrid','Editor.view.admin.UserChooseWindow'],
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
          role = Ext.Object.getKeys(meta.roles)[0],
          state = Ext.Object.getKeys(meta.states)[0],
          newRec = assoc.model.create({
              taskGuid: task.get('taskGuid'),
              role: role,
              state: state
          });
      me.getAssocDelBtn().disable();
      me.getEditInfo().hide();
      me.getUserAssocForm().show();
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
          emptySel = selection.length == 0;
      me.getAssocDelBtn().setDisabled(emptySel);
      me.getEditInfo().setVisible(emptySel);
      me.getUserAssocForm().setVisible(!emptySel);
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
          toDel.destroyVersioned(task, {
              success: function() {
                  assoc.remove(toDel);
                  me.updateUsers(assoc);
                  me.fireEvent('removeUserAssoc', me, toDel, assoc);
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
      me.getPrefWindow().loadingShow();
      rec.saveVersioned(task, {
          success: function(savedRec, op) {
              me.handleCancel();
              if(!rec.store) {
                  store.insert(0,rec);
                  me.updateUsers(store);
                  me.fireEvent('addUserAssoc', me, rec, store);
              }
              task.reload();//reload only the task, not the whole task prefs, should be OK
              Editor.MessageBox.addSuccess(me.messages.assocSave);
              me.getPrefWindow().loadingHide();
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
          task = Editor.model.admin.Task.prototype,
          stateCombo = form.down('combo[name="state"]'),
          newState = task.USER_STATE_OPEN,
          rec = form.getRecord(),
          isChanged = stateCombo.getValue() != rec.get('state');
      if(!rec.phantom || isChanged) {
          return;
      }
      switch (newValue) {
          case 'translator':
              newState = task.USER_STATE_WAITING;
              break;
          case 'lector':
              newState = task.USER_STATE_OPEN;
              break;
      }
      rec.set('state', newState);
      stateCombo.setValue(newState);
  }
});
