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
      ref: 'taskPreferencesWindow',
      selector: '#adminTaskPreferencesWindow'
  }],
  messages: {
      assocSave: '#UT#Änderungen gespeichert!',
      assocSaveError: '#UT#Fehler beim Speichern der Änderungen!'
  },
  init : function() {
      var me = this;

      if(!Editor.controller.admin.TaskPreferences) {
          //controller.TaskPreferences is somekind of parent controller of controller.TaskUserAssoc so it must be loaded!
          Ext.Error.raise('TaskPreferences controller must be loaded!');
      }
      
      //FIXME nextRelease Thomas Fehlerfälle werden aktuell nicht abgefangen und angezeigt!
      //Den Speichervorgang umbauen, so dass entweder jeder record direct gespeichert und damit success / failure handlebar wird
      //Oder den aktuellen Ansatz lassen, und die einzelnen Batch Vorgänge mit handlern versehen, aktuell unklar ob das geht-
      me.getAdminTaskUserAssocsStore().on('write',function(){
          me.application.getController('admin.TaskOverview').handleTaskReload();
          Editor.MessageBox.addSuccess(me.messages.assocSave);
      });
      
      me.control({
          '#adminTaskUserAssocGrid': {
              selectionchange: me.handleAssocSelection,
              edit: me.initStates
          },
          '#adminTaskUserAssocGrid #add-user-btn': {
              click: me.showUserChooser
          },
          '#adminTaskUserAssocGrid #remove-user-btn': {
              click: me.handleRemoveAssoc
          },
          '#adminUserChooseWindow #add-user-btn': {
              click: me.handleAddUser
          },
          '#adminUserChooseWindow #cancel-btn': {
              click: me.handleCancel
          },
          '#adminTaskUserAssocGrid #save-assoc-btn': {
              click: me.handleSaveAssoc
          },
          '#adminTaskUserAssocGrid #cancel-assoc-btn': {
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
   * Display window to choose users to add to the task
   */
  showUserChooser: function() {
      Ext.widget('adminUserChooseWindow',{
          excludeLogins: this.getAdminTaskUserAssocsStore().collect('login'),
          task: this.getTaskPreferencesWindow().actualTask
      }).show();
  },
  /**
   * @param {Ext.button.Button} btn
   */
  handleCancel: function(btn){
      btn.up('window').close();
  },
  /**
   * @param {Ext.button.Button} btn
   */
  handleAddUser: function(btn){
      var me = this,
          assoc = me.getAdminTaskUserAssocsStore(),
          win = btn.up('window'),
          sel = win.down('grid').getSelectionModel().getSelection(),
          role = Ext.Object.getKeys(Editor.data.app.utRoles)[0],
          state = Ext.Object.getKeys(Editor.data.app.utStates)[0];
          
      Ext.Array.each(sel, function(rec){
          var mod = assoc.model.create({
              userGuid: rec.get('userGuid'),
              surName: rec.get('surName'),
              firstName: rec.get('firstName'),
              login: rec.get('login'),
              taskGuid: me.getTaskPreferencesWindow().actualTask.get('taskGuid'),
              role: role,
              state: state
          });
          mod.setDirty();
          assoc.insert(0, mod);
      });
      win.close();
  },
  /**
   * Disable Delete Button if no User is selected
   * @param {Ext.grid.Panel} grid
   * @param {Array} selection
   */
  handleAssocSelection: function(grid, selection) {
      this.getAssocDelBtn().setDisabled(selection.length == 0);
  },
  /**
   * Removes the selected User Task Association
   * @param {Ext.button.Button} btn
   */
  handleRemoveAssoc: function(btn) {
      var win = btn.up('window'),
      sel = win.down('grid').getSelectionModel().getSelection(),
      assoc = this.getAdminTaskUserAssocsStore();
      assoc.remove(sel);
  },
  /**
   * save the user task assoc info.
   * @param {Ext.button.Button} btn
   */
  handleSaveAssoc: function (btn) {
      var me = this,
          grid = me.getUserAssocGrid();
      grid.editingPlugin && grid.editingPlugin.completeEdit();
      btn.up('window').close();
      me.getAdminTaskUserAssocsStore().sync();
  },
  /**
   * sets the initial state values dependent on the role
   * @param {Ext.grid.plugin.CellEditing} plug
   * @param {Object} context
   */
  initStates: function(plug, context) {
      var rec = context.record,
          task = Editor.model.admin.Task.prototype;

      if(context.field == 'role' && context.value == 'translator' && rec.phantom) {
          rec.set('state', task.USER_STATE_WAITING);
      }
      if(context.field == 'role' && context.value == 'lector' && rec.phantom) {
          rec.set('state', task.USER_STATE_OPEN);
      }
  }
});