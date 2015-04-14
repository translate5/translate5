
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
 * Editor.controller.admin.TaskOverview encapsulates the Task Overview functionality
 * @class Editor.controller.admin.TaskOverview
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskOverview', {
  extend : 'Ext.app.Controller',
  models: ['admin.Task'],
  stores: ['admin.Users', 'admin.Tasks','admin.Languages'],
  views: ['admin.TaskGrid', 'admin.TaskAddWindow'],
  refs : [{
      ref: 'headToolBar',
      selector: 'headPanel toolbar#top-menu'
  },{
      ref: 'logoutButton',
      selector: 'headPanel toolbar#top-menu #logoutSingle'
  },{
      ref: 'taskAddForm',
      selector: '#adminTaskAddWindow form'
  },{
      ref: 'tbxField',
      selector: '#adminTaskAddWindow form filefield[name="importTbx"]'
  },{
      ref: 'centerRegion',
      selector: 'viewport container[region="center"]'
  },{
      ref: 'taskGrid',
      selector: '#adminTaskGrid'
  },{
      ref: 'taskAddWindow',
      selector: '#adminTaskAddWindow'
  }],
  /**
   * Container for translated task handler confirmation strings
   * Deletion of an entry means to disable confirmation.
   */
  confirmStrings: {
      "finish":       {title: "#UT#Aufgabe abschließen?", msg: "#UT#Wollen Sie die Aufgabe wirklich abschließen?"},
      "unfinish":     {title: "#UT#Aufgabe wieder öffnen?", msg: "#UT#Wollen Sie die Aufgabe wirklich wieder öffnen?"},
      "finish-all":   {title: "#UT#Aufgabe für alle Nutzer abschließen?", msg: "#UT#Wollen Sie die Aufgabe wirklich für alle Benutzer abschließen?"},
      "unfinish-all": {title: "#UT#Aufgabe für alle Nutzer wieder öffnen?", msg: "#UT#Wollen Sie die Aufgabe wirklich für alle Benutzer wieder öffnen?"},
      "end":          {title: "#UT#Aufgabe endgültig beenden?", msg: "#UT#Wollen Sie die Aufgabe wirklich für alle Benutzer endgültig beenden?"},
      "reopen":       {title: "#UT#Beendete Aufgabe wieder öffnen?", msg: "#UT#Wollen Sie die beendete Aufgabe wirklich wieder öffnen?"},
      "delete":       {title: "#UT#Aufgabe komplett löschen?", msg: "#UT#Wollen Sie die Aufgabe wirklich komplett und unwiderruflich löschen?"}
  },
  strings: {
      taskImported: '#UT#Aufgabe "{0}" vollständig importiert',
      taskOpening: '#UT#Aufgabe wird im Editor geöffnet...',
      taskFinishing: '#UT#Aufgabe wird abgeschlossen...',
      taskUnFinishing: '#UT#Aufgabe wird abgeschlossen...',
      taskReopen: '#UT#Aufgabe wird wieder eröffnet...',
      taskEnding: '#UT#Aufgabe wird beendet...',
      taskDestroy: '#UT#Aufgabe wird gelöscht...',
      taskNotDestroyed : '#UT#Aufgabe wird noch verwendet und kann daher nicht gelöscht werden!',
      forcedReadOnly: '#UT#Aufgabe wird durch Benutzer "{0}" bearbeitet und ist daher schreibgeschützt!',
      openTaskAdminBtn: "#UT#Aufgabenübersicht"
  },
  init : function() {
      var me = this;
      
      me.addEvents(
              /**
               * @event taskCreated
               * @param {Ext.form.Panel} form
               * @param {Ext.action.Submit} submit
               * Fires after a task has successfully created
               */
              'taskCreated',
              
              /**
               * @event handleTaskPreferences
               * @param {Editor.model.admin.Task} task
               * Fires after the User has clicked on the icon to edit the Task Preferences
               */
              'handleTaskPreferences',
              
              /**
               * @event handleTaskChangeUserAssoc
               * @param {Editor.model.admin.Task} task
               * Fires after the User has clicked on the button / cell to edit the Task User Assoc
               */
              'handleTaskChangeUserAssoc'
      );
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('adminViewportClosed', me.clearTasks, me);
      Editor.app.on('editorViewportOpened', me.handleInitEditor, me);
      
      me.getAdminTasksStore().on('load', me.startCheckImportStates, me);

      me.control({
          'headPanel toolbar#top-menu' : {
              beforerender: me.initMainMenu
          },
          'button#task-admin-btn': {
              click: me.openTaskGrid
          },
          '#adminTaskGrid': {
              hide: me.handleAfterHide,
              show: me.handleAfterShow,
              celldblclick: me.handleGridClick, 
              cellclick: me.handleGridClick 
          },
          '#adminTaskGrid #reload-task-btn': {
              click: me.handleTaskReload
          },
          '#adminTaskGrid taskActionColumn': {
              click: me.taskActionDispatcher
          },
          '#adminTaskGrid #add-task-btn': {
              click: me.handleTaskAddShow
          },
          '#adminTaskAddWindow #add-task-btn': {
              click: me.handleTaskAdd
          },
          '#adminTaskAddWindow #cancel-task-btn': {
              click: me.handleTaskCancel
          },
          '#adminTaskAddWindow filefield[name=importUpload]': {
              change: me.handleChangeImportFile
          },
          '#segmentgrid': {
              afterrender: me.initTaskReadMode
          }
      });
  },
  /**
   * injects the task menu into the main menu
   */
  initMainMenu: function() {
      var toolbar = this.getHeadToolBar(),
          insertIdx = 1,
          logout = this.getLogoutButton();
      if(logout) {
          insertIdx = toolbar.items.indexOf(logout) + 1;
      }
      toolbar.insert(insertIdx, {
          itemId: 'task-admin-btn',
          xtype: 'button',
          hidden: true,
          text: this.strings.openTaskAdminBtn
      });
  },
  /**
   * handle after show of taskgrid
   */
  handleAfterShow: function() {
      this.getHeadToolBar().down('#task-admin-btn').hide();
  },
  /**
   * handle after hide of taskgrid
   */
  handleAfterHide: function() {
      this.getHeadToolBar().down('#task-admin-btn').show();
  },
  /**
   * opens the task grid, hides all other
   */
  openTaskGrid: function() {
      var me = this, 
          grid = me.getTaskGrid();

      me.getCenterRegion().items.each(function(item){
          item.hide();
      });
      
      if(grid) {
          grid.show();
      }
      else {
          me.getCenterRegion().add({
              xtype: 'adminTaskGrid'
          });
          me.handleAfterShow();
      }
  },
  handleInitEditor: function() {
      this.getHeadToolBar().down('#task-admin-btn').hide();
  },
  clearTasks: function() {
      this.getAdminTasksStore().removeAll();
  },
  loadTasks: function() {
      this.getAdminTasksStore().load();
  },
  startCheckImportStates: function(store) {
      if(!this.checkImportStateTask) {
          this.checkImportStateTask = {
                  run: this.checkImportState,
                  scope: this,
                  interval: 10000
          };
      }
      Ext.TaskManager.start(this.checkImportStateTask);
  },
  /**
   * Checks if all actually loaded tasks are imported completly
   */
  checkImportState: function() {
      var me = this, 
          tasks = me.getAdminTasksStore(),
          foundImporting = 0,
          taskReloaded = function(rec) {
              if(!rec.isImporting()) {
                  Editor.MessageBox.addSuccess(Ext.String.format(me.strings.taskImported, rec.get('taskName')));
              }
          };
      tasks.each(function(task){
          if(!task.isImporting()){
              return;
          }
          task.reload({
              success: taskReloaded
          });
          foundImporting++;
      });
      if(foundImporting == 0) {
          Ext.TaskManager.stop(this.checkImportStateTask);
      }
  },
  handleChangeImportFile: function(field, val){
      var name = this.getTaskAddForm().down('textfield[name=taskName]'),
          srcLang = this.getTaskAddForm().down('combo[name=sourceLang]'),
          targetLang = this.getTaskAddForm().down('combo[name=targetLang]'),
          langs = val.match(/-([a-zA-Z]{2,3})-([a-zA-Z]{2,3})\.[^.]+$/);
      if(name.getValue() == '') {
          name.setValue(val.replace(/\.[^.]+$/, ''));
      }
      //simple algorithmus to get the language from the filename
      if(langs && langs.length == 3) {
          var srcStore = srcLang.store,
              targetStore = targetLang.store,
              srcIdx = srcStore.find('label', '('+langs[1]+')', 0, true, true),
              targetIdx = targetStore.find('label', '('+langs[2]+')', 0, true, true);
          
          if(srcIdx >= 0) {
              srcLang.setValue(srcStore.getAt(srcIdx).get('id'));
          }
          if(targetIdx >= 0) {
              targetLang.setValue(targetStore.getAt(targetIdx).get('id'));
          }
      }
  },
  /**
   * Inits the loaded task and the segment grid read only if necessary
   */
  initTaskReadMode: function(grid) {
      var vm = this.application.getController('ViewModes'),
          task = Editor.data.task,
          sep;
      if(task.isReadOnly() || ! this.isAllowed('editorEditTask', task)) {
          //readonly:
          grid.down('#viewModeMenu').hide();
          sep = grid.down('#viewModeMenu').nextNode('.tbseparator');
          sep && sep.hide();
          vm && vm.viewMode(true); //true means called on init
      }
      else {
          //show not needed
          vm && vm.editMode(true); //true means called on init
      }
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
   * opens the editor by click or dbl click
   * @param {Ext.grid.View} view
   * @param {Element} colEl
   * @param {Integer} colIdx
   * @param {Editor.model.admin.Task} rec
   * @param {Element} rowEl
   * @param {Integer} rowIdxindex
   * @param {Event} e
   * @param {Object} eOpts
   */
  handleGridClick: function(view, colEl, colIdx, rec, rowEl, rowIdxindex, e, eOpts) {
      //logic for handling single clicks on column taskNr and dblclick on other cols
      var isTaskNr = (view.up('grid').columns[colIdx].dataIndex == 'taskNr'),
          dbl = e.type == 'dblclick'; 
      
      if(!rec.isLocked() && (isTaskNr || dbl)) {
          this.openTaskRequest(rec);
      }
  },
  /**
   * general method to open a task, starting in readonly mode is calculated
   * @param {Editor.model.admin.Task} task
   * @param {Boolean} readonly (optional)
   */
  openTaskRequest: function(task, readonly) {
      var me = this,
          initialState,
          app = Editor.app;
      if(!me.isAllowed('editorOpenTask', task) && !me.isAllowed('editorEditTask', task)){
          return;
      }
      readonly = (readonly === true || task.isReadOnly());
      initialState = readonly ? task.USER_STATE_VIEW : task.USER_STATE_EDIT;
      task.set('userState', initialState);
      app.mask(me.strings.taskOpening, task.get('taskName'));
      task.save({
          success: function(rec, op) {
              if(rec && initialState == task.USER_STATE_EDIT && rec.get('userState') == task.USER_STATE_VIEW) {
                  Editor.MessageBox.addInfo(Ext.String.format(me.strings.forcedReadOnly, rec.get('lockingUsername')));
              }
              app.unmask();
              Editor.app.openEditor(rec, readonly);
          },
          failure: app.unmask
      });
  },
  handleTaskCancel: function() {
      this.getTaskAddForm().getForm().reset();
      this.getTaskAddWindow().close();
  },
  /**
   * setting a loading mask for the window / grid is not possible, using savingShow / savingHide instead.
   * perhaps because of bug for ext-4.0.7 (see http://www.sencha.com/forum/showthread.php?157954)
   * This Fix is better as in {Editor.view.changealike.Window} because of useing body as LoadMask el.
   */
  savingShow: function() {
      var win = this.getTaskAddWindow();
      if(!win.loadingMask) {
          win.loadingMask = new Ext.LoadMask(Ext.getBody(), {store: false});
      }
      win.loadingMask.show();
  },
  savingHide: function() {
      var win = this.getTaskAddWindow();
      win.loadingMask.hide();
  },
  /**
   * is called after clicking save task, starts the upload / form submit
   */
  handleTaskAdd: function() {
      var me = this,
          error = me.getTaskAddWindow().down('#feedbackBtn');
      error.hide();
      me.savingShow();
      this.getTaskAddForm().submit({
          //Accept Header of submitted file uploads could not be changed:
          //http://stackoverflow.com/questions/13344082/fileupload-accept-header
          //so use format parameter jsontext here, for jsontext see REST_Controller_Action_Helper_ContextSwitch
          params: {
              format: 'jsontext'
          },
          url: Editor.data.restpath+'task',
          scope: this,
          success: function(form, submit) {
              var task = me.getModel('admin.Task').create(submit.result.rows);
              me.fireEvent('taskCreated', task);
              me.savingHide();
              me.getAdminTasksStore().load();
              me.handleTaskCancel();
          },
          failure: function(form, submit) {
              me.savingHide();
              if(submit.failureType == 'server' && submit.result && submit.result.errors && !Ext.isDefined(submit.result.success)) {
                  //all other failures should mark a field invalid
                  this.getTbxField().setDisabled(true);
                  error.show();
              }
          }
      });
  },
  handleTaskAddShow: function() {
      if(!this.isAllowed('editorAddTask')){
          return;
      }
      Ext.widget('adminTaskAddWindow').show();
  },
  /**
   * reloads the Task Grid, will also be called from other controllers
   */
  handleTaskReload: function () {
      this.getAdminTasksStore().load();
  },
  /**
   * calls local task handler, dispatching is done by the icon CSS class of the clicked img
   * the css class ico-task-foo-bar is transformed to the method handleTaskFooBar
   * if this controller contains this method, it'll be called. 
   * First Parameter is the task record.
   * 
   * @param {Ext.grid.View} view
   * @param {DOMElement} cell
   * @param {Integer} row
   * @param {Integer} col
   * @param {Ext.Event} ev
   * @param {Object} evObj
   */
  taskActionDispatcher: function(view, cell, row, col, ev, evObj) {
      var me = this,
          t = ev.getTarget(),
          f = t.className.match(/ico-task-([^ ]+)/),
          camelRe = Ext.Element.camelRe,
          camelFn = Ext.Element.camelReplaceFn,
          actionIdx = ((f && f[1]) ? f[1] : "not-existing"),
          //build camelized action out of icon css class:
          action = ('handleTask-'+actionIdx).replace(camelRe, camelFn),
          right = action.replace(/^handleTask/, 'editor')+'Task',
          task = view.getStore().getAt(row),
          confirm;

      if(! me.isAllowed(right) || ! me[action] || ! Ext.isFunction(me[action])){
          return;
      }

      //if NO confirmation string exists, we call the action unconfirmed. 
      if(! me.confirmStrings[actionIdx]) {
          me[action](task, ev);
          return; 
      }

      confirm = me.confirmStrings[actionIdx];
      Ext.Msg.confirm(confirm.title, confirm.msg, function(btn){
          if(btn == 'yes') {
              me[action](task, ev);
          }
      });
  },
  /**
   * Shorthand method to get the default task save handlers
   * @return {Object}
   */
  getTaskMaskBindings: function(){
      var app = Editor.app;
      return {
          success: app.unmask,
          failure: function(rec, op){
              var recs = op.getRecords(),
                  task = recs && recs[0] || false;
              task && task.reject();
              app.unmask();
          }
      };
  },
  
  //
  //Task Handler:
  //
  
  /**
   * Opens the task readonly
   * @param {Editor.model.admin.Task} task
   */
  handleTaskOpen: function(task) {
      this.openTaskRequest(task, true);
  },
  
  /**
   * Opens the task in normal (edit) mode (does internal a readonly check by task)
   * @param {Editor.model.admin.Task} task
   */
  handleTaskEdit: function(task) {
      this.openTaskRequest(task);
  },
  
  /**
   * Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  handleTaskFinish: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskFinishing, task.get('taskName'));
      task.set('userState', task.USER_STATE_FINISH);
      task.save(me.getTaskMaskBindings());
  },
  
  /**
   * Un Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  handleTaskUnfinish: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskUnFinishing, task.get('taskName'));
      task.set('userState', task.USER_STATE_OPEN);
      task.save(me.getTaskMaskBindings());
  },
  
  /**
   * Un Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  handleTaskEnd: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskEnding, task.get('taskName'));
      task.set('state', 'end');
      task.save(me.getTaskMaskBindings());
  },
  
  /**
   * Un Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  handleTaskReopen: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskReopen, task.get('taskName'));
      task.set('state', 'open');
      task.save(me.getTaskMaskBindings());
  },
  /**
   * delete the task
   * @param {Editor.model.admin.Task} task
   */
  handleTaskDelete: function(task) {
      var me = this,
          store = task.store,
          app = Editor.app;
      app.mask(me.strings.taskDestroy, task.get('taskName'));
      task.destroy({
          //callback as empty function to prevent default ServerException handling
          callback: Ext.emptyFn,
          success: function() {
              store.load();
              app.unmask();
          },
          failure: function(records, op){
              app.unmask();
              if(op.getError().status == '405') {
                  Editor.MessageBox.addError(me.strings.taskNotDestroyed);
              }
              else {
                  Editor.app.getController('ServerException').handleException(op.response);
              }
          }
      });
  },
  /**
   * displays the export menu
   * @param {Editor.model.admin.Task} task
   * @param {Ext.EventObjectImpl} event
   */
  handleTaskShowexportmenu: function(task, event) {
      var me = this,
          hasQm = task.hasQmSub(),
          exportAllowed = me.isAllowed('editorExportTask'),
          menu;
      
      menu = Ext.widget('adminExportMenu', {
          task: task,
          fields: hasQm ? task.segmentFields() : false
      });
      menu.down('#exportItem').setVisible(exportAllowed);
      menu.down('#exportDiffItem').setVisible(exportAllowed);
      menu.showAt(event.getXY());
  },
  
  /**
   * triggerd by click on the Task Preferences Icon
   * fires only an event to allow flexible handling of this click
   * @param {Editor.model.admin.Task} task
   */
  handleTaskPreferences: function(task) {
      this.fireEvent('handleTaskPreferences', task);
  },
  
  /**
   * triggerd by click on the Change Task User Assoc Button / (Cell also => @todo)
   * fires only an event to allow flexible handling of this click
   * @param {Editor.model.admin.Task} task
   */
  handleTaskChangeUserAssoc: function(task) {
      this.fireEvent('handleTaskChangeUserAssoc', task);
  }
});