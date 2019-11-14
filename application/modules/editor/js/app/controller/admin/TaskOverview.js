
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
 * Editor.controller.admin.TaskOverview encapsulates the Task Overview functionality
 * @class Editor.controller.admin.TaskOverview
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskOverview', {
  extend : 'Ext.app.Controller',
  requires: ['Editor.view.admin.ExportMenu'],
  models: ['admin.Task', 'admin.task.Log'],
  stores: ['admin.Users', 'admin.Tasks','admin.Languages', 'admin.task.Logs'],
  views: ['admin.TaskGrid', 'admin.TaskAddWindow', 'admin.task.LogWindow', 'admin.task.ExcelReimportWindow', 'admin.task.KpiWindow'],
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
  },{
      ref: 'exportMetaDataBtn',
      selector: '#adminTaskGrid #export-meta-data-btn'
  },{
      ref: 'averageProcessingTimeDisplay',
      selector: '#kpiWindow #kpi-average-processing-time-display'
  },{
      ref: 'excelExportUsageDisplay',
      selector: '#kpiWindow #kpi-excel-export-usage-display'
  }],
  alias: 'controller.taskOverviewController',
  
  isCardFinished:false,
  
  /***
   * the flag is true, when import workers are started via ajax
   */
  isImportStarted:false,

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
      taskImported: '#UT#Aufgabe "{0}" bereit.',
      taskError: '#UT#Die Aufgabe konnte aufgrund von Fehlern nicht importiert werden!',
      taskFinishing: '#UT#Aufgabe wird abgeschlossen...',
      taskUnFinishing: '#UT#Aufgabe wird abgeschlossen...',
      taskReopen: '#UT#Aufgabe wird wieder eröffnet...',
      taskEnding: '#UT#Aufgabe wird beendet...',
      taskDestroy: '#UT#Aufgabe wird gelöscht...',
      taskNotDestroyed : '#UT#Aufgabe wird noch verwendet und kann daher nicht gelöscht werden!',
      openTaskAdminBtn: "#UT#Aufgabenübersicht",
      loadingWindowMessage:"#UT#Dateien werden hochgeladen",
      loading:'#UT#Laden',
      importTaskMessage:"#UT#Hochladen beendet. Import und Vorbereitung laufen.",
      deleteTaskDialogMessage:'#UT#Sollte der Task gelöscht oder mit den aktuellen Einstellungen importiert werden?',
      deleteTaskDialogTitle:'#UT#Aufgabe löschen',
      taskImportButtonText:'#UT#Aufgabe importieren',
      taskDeleteButtonText:'#UT#Aufgabe löschen',
      averageProcessingTimeLabel: 'Ø Bearbeitungszeit Lektor',
      averageProcessingTimeDays: '{0} Tage',
      excelExportUsageLabel: 'Excel-Export Nutzung'
  },
  listen: {
      controller: {
          '#Editor.$application': {
              adminViewportClosed: 'clearTasks',
              editorViewportOpened: 'handleInitEditor'
          },
      },
      component: {
          'headPanel toolbar#top-menu' : {
              beforerender: 'initMainMenu'
          },
          'button#task-admin-btn': {
              click: 'openTaskGrid'
          },
          '#adminTaskGrid': {
              hide: 'handleAfterHide',
              show: 'handleAfterShow',
              celldblclick: 'handleGridClick', 
              cellclick: 'handleGridClick'
          },
          '#adminTaskGrid #reload-task-btn': {
              click: 'handleTaskReload'
          },
          '#adminTaskGrid taskActionColumn': {
              click: 'taskActionDispatcher'
          },
          '#adminTaskGrid #add-task-btn': {
              click: 'handleTaskAddShow'
          },
          '#adminTaskGrid #export-meta-data-btn': {
              click: 'handleMetaDataExport'
          },
          '#adminTaskGrid #show-kpi-btn': {
              click: 'handleKPIShow'
          },
          '#adminTaskAddWindow': {
              show: 'onAdminTaskAddWindowShow',
              close: 'onAdminTaskAddWindowClose'
           },
          '#adminTaskAddWindow #add-task-btn': {
              click: 'handleTaskAdd'
          },
          '#adminTaskAddWindow #cancel-task-btn': {
              click: 'handleTaskCancel'
          },
          '#adminTaskAddWindow #continue-wizard-btn': {
              click: 'handleContinueWizardClick'
          },
          '#adminTaskAddWindow #skip-wizard-btn': {
              click: 'handleSkipWizardClick'
          },
          '#adminTaskAddWindow filefield[name=importUpload]': {
              change: 'handleChangeImportFile'
          },
          'adminTaskAddWindow panel:not([hidden])': {
              wizardCardFinished: 'onWizardCardFinished',
              wizardCardSkiped: 'onWizardCardSkiped'
          }
      }
  },
    //***********************************************************************************
    //Begin Events
    //***********************************************************************************
    /**
     * @event taskCreated
     * @param {Ext.form.Panel} form
     * @param {Ext.action.Submit} submit
     * Fires after a task has successfully created
     */
    /**
     * @event handleTaskPreferences
     * @param {Editor.model.admin.Task} task
     * Fires after the User has clicked on the icon to edit the Task Preferences
     */
    /**
     * @event handleTaskChangeUserAssoc
     * @param {Editor.model.admin.Task} task
     * Fires after the User has clicked on the button / cell to edit the Task User Assoc
     */
    //***********************************************************************************
    //End Events
    //***********************************************************************************
  /**
   * injects the task menu into the main menu
   */
  initMainMenu: function() {
      var toolbar = this.getHeadToolBar(),
          insertIdx = 1,
          logout = this.getLogoutButton()
      if(logout) {
          insertIdx = toolbar.items.indexOf(logout) + 1;
      }
      if(Editor.data.helpUrl){
    	  insertIdx=insertIdx+1;
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
  handleAfterShow: function(grid) {
      this.getHeadToolBar().down('#task-admin-btn').hide();
      Editor.data.helpSection = 'taskoverview';
      Editor.data.helpSectionTitle = grid.getTitle();
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
          //set the value used for displaying the help pages
          grid.show();
      }
      else {
          grid = me.getCenterRegion().add({
              xtype: 'adminTaskGrid',
              height: '100%'
          });
          me.handleAfterShow(grid);
      }
  },
  handleInitEditor: function() {
      this.getHeadToolBar() && this.getHeadToolBar().down('#task-admin-btn').hide();
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
   * Checks if all currently loaded tasks are imported or available completly. 
   * If there are locked tasks with a state which needs periodical reload, reload them
   */
  checkImportState: function() {
      var me = this, 
          tasks = me.getAdminTasksStore(),
          foundImporting = 0,
          taskReloaded = function(rec) {
              if(rec.isErroneous()) {
                  Editor.MessageBox.addSuccess(Ext.String.format(me.strings.taskError, rec.get('taskName')));
                  me.fireEvent('importStateCheckFinished',me,rec);
                  return;
              }
              if(!me.isImportingCheck(rec)) {
                  Editor.MessageBox.addSuccess(Ext.String.format(me.strings.taskImported, rec.get('taskName')));
                  me.fireEvent('importStateCheckFinished',me,rec);
              }
          };
      tasks.each(function(task){
          if(!me.isImportingCheck(task) || task.dropped){
              return;
          }
          task.load({
              success: taskReloaded,
              failure: function(records, op){
                  //handle 404, so the user does not receive error messages
                if(op.getError().status != '404') {
                    Editor.app.getController('ServerException').handleException(op.error.response);
                }
            }
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
          langs = val.match(/-([a-zA-Z]{2,5})-([a-zA-Z]{2,5})\.[^.]+$/);
      if(name.getValue() == '') {
          name.setValue(val.replace(/\.[^.]+$/, '').replace(/^C:\\fakepath\\/,''));
      }
      //simple algorithmus to get the language from the filename
      if(langs && langs.length == 3) {
          //try to convert deDE language to de-DE for searching in the store
          var regex = /^([a-z]+)([A-Z]+)$/;
          if(regex.test(langs[1])) {
              langs[1] = langs[1].match(/^([a-z]+)([A-Z]+)$/).splice(1).join('-');
          }
          if(regex.test(langs[2])) {
              langs[2] = langs[2].match(/^([a-z]+)([A-Z]+)$/).splice(1).join('-');
          }
          
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
      var dataIdx = view.up('grid').getColumns()[colIdx].dataIndex,
          isState = (dataIdx == 'state'),
          isTaskNr = (dataIdx == 'taskNr'),
          dbl = e.type == 'dblclick'; 
      if(rec.isErroneous() || rec.isImporting()) {
          if(isState || dbl) {
              this.handleTaskLog(rec);
          }
          return;
      }
      if(rec.isOpenable() && (isTaskNr || dbl)) {
          this.openTaskRequest(rec);
      }
  },
  /**
   * general method to open a task, starting in readonly mode is calculated
   * @param {Editor.model.admin.Task} task
   * @param {Boolean} readonly (optional)
   */
  openTaskRequest: function(task, readonly) {
      var me = this;
      if(!me.isAllowed('editorOpenTask', task) && !me.isAllowed('editorEditTask', task)){
          return;
      }
      Editor.util.TaskActions.openTask(task, readonly);
  },
  handleTaskLog: function(task) {
      if(!this.isAllowed('editorTaskLog')){
          return;
      }
      var win = Ext.widget('adminTaskLogWindow',{
          actualTask: task
      });
      win.show();
      win.load();
  },
  handleTaskCancel: function() {
	  var me=this;
	  if(!me.getTaskAddForm()){
		  return;
	  }
      me.getTaskAddForm().getForm().reset();
      me.getTaskAddWindow().close();
  },

  handleTaskAdd: function(button) {
      var me=this,
          win=me.getTaskAddWindow(),
          vm=win.getViewModel(),
          winLayout=win.getLayout(),
          nextStep=win.down('#taskUploadCard');
      
      if(me.getTaskAddForm().isValid()){
          if(nextStep.strings && nextStep.strings.wizardTitle){
              win.setTitle(nextStep.strings.wizardTitle);
          }
          
          vm.set('activeItem',nextStep);
          winLayout.setActiveItem(nextStep);
      }
  },
  
  
  /**
   * is called after clicking continue, if there are wizard panels, 
   * then the next available wizard panel is set as active 
   */
  handleContinueWizardClick:function(){
      var me = this,
          win = me.getTaskAddWindow(),
          winLayout=win.getLayout(),
          activeItem=winLayout.getActiveItem();
      
      activeItem.triggerNextCard(activeItem);
  },
  
  /***
   * Called when skip button in wizard window is clicked.
   * The function of this button is to skup the next card group (in the current wizard group, sure if there is one)
   * 
   */
  handleSkipWizardClick:function(){
      var me = this,
          win = me.getTaskAddWindow(),
          winLayout=win.getLayout(),
          activeItem=winLayout.getActiveItem();
      
      activeItem.triggerSkipCard(activeItem);
  },
  
  onWizardCardFinished:function(skipCards){
      var me = this,
          win = me.getTaskAddWindow(),
          winLayout=win.getLayout(),
          nextStep=winLayout.getNext(),
          activeItem=winLayout.getActiveItem(),
          vm=win.getViewModel();
      
      if(skipCards){
          for(var i=1;i < skipCards;i++){
        	  if(win.isTaskUploadNext()){
        		  break;
        	  }
              winLayout.setActiveItem(nextStep);
              nextStep=winLayout.getNext();
          }
      }

      //check for next step
      if(!nextStep){
          
          //if no next step, and no task, save it and start the import 
          if(!activeItem.task){
              me.saveTask(function(task){
            	  me.startImport(task);
              });
          }else{
              //the task is already saved, start the import 
        	  me.startImport(activeItem.task);
          }
          return;
      }
      
      //switch to next card help function
      var goToNextCard=function(task){
          if(nextStep.strings && nextStep.strings.wizardTitle){
              win.setTitle(nextStep.strings.wizardTitle);
          }
          //if the task is provided, set the next card task variable
          if(task){
              nextStep.task=task;
          }
          
          vm.set('activeItem',nextStep);
          winLayout.setActiveItem(nextStep);
      };
      
      //when switch from import to postimport, save the task
      if(activeItem.importType=="import" && nextStep.importType=="postimport"){
          me.saveTask(goToNextCard);
          return;
      }
      //change the card
      goToNextCard();
  },
  
  onWizardCardSkiped:function(){
      this.saveTask();
  },
  
  handleTaskAddShow: function() {
      if(!this.isAllowed('editorAddTask')){
          return;
      }
      Ext.widget('adminTaskAddWindow').show();
  },
  
  /**
   * Show Key Performance Indicators (KPI) for currently filtered tasks.
   */
  handleKPIShow: function() {
      var me = this,
          win = Ext.widget('adminTaskKpiWindow'),
          taskStore = Ext.StoreManager.get('admin.Tasks'),
          proxy = taskStore.getProxy(),
          url = Editor.data.restpath+'task/kpi',
          method = 'POST',
          params = {};
      
      win.show();
      win.setLoading(true);
      
      params[proxy.getFilterParam()] = proxy.encodeFilters(taskStore.getFilters().items);
      
      Ext.Ajax.request({
          url: url,
          method: method,
          params: params,
          success: function(response){
              var resp = Ext.util.JSON.decode(response.responseText),
                  kpiStatistics,
                  average,
                  averageProcessingTimeMessage,
                  excelExportUsageMessage;
              kpiStatistics = resp.kpiStatistics;
              // KPI: averageProcessingTimeMessage
              average = kpiStatistics.averageProcessingTime;
              if (average !== '-') {
                  average = Ext.String.format(me.strings.averageProcessingTimeDays, average);
              }
              averageProcessingTimeMessage = me.strings.averageProcessingTimeLabel + ': ' + average;
              // KPI: excelExportUsage
              excelExportUsageMessage = kpiStatistics.excelExportUsage + ' ' + me.strings.excelExportUsageLabel;
              // update fields and stop loading-icon
              me.getAverageProcessingTimeDisplay().update(averageProcessingTimeMessage);
              me.getExcelExportUsageDisplay().update(excelExportUsageMessage);
              win.setLoading(false);
          },
          failure: function(){
              // TODO: show error-message?
              win.setLoading(false);
          } 
      });
  },
  
  /**
   * Export the current state of taskGrid for all currently filtered tasks
   * and their KPI-statistics.
   */
  handleMetaDataExport: function() {
      var taskStore = Ext.StoreManager.get('admin.Tasks'),
          proxy = taskStore.getProxy(),
          params = {},
          taskGrid = Ext.ComponentQuery.query('#adminTaskGrid')[0],
          visibleColumns = taskGrid.getVisibleColumns(),
          length = visibleColumns.length,
          i,
          col,
          visibleColumnsNames = [],
          href;
      for (i=0; i < length; i++){
          col = visibleColumns[i];
          if (col.hasOwnProperty('dataIndex')) {
              // taskActionColumn has no dataIndex, but is not needed anyway
              visibleColumnsNames.push(col.dataIndex);
          }
      }
      params['format'] = 'xlsx';
      params[proxy.getFilterParam()] = proxy.encodeFilters(taskStore.getFilters().items);
      params['visibleColumns'] = JSON.stringify(visibleColumnsNames);
      href = Editor.data.restpath + 'task/kpi?' + Ext.urlEncode(params);
      // TODO: this might get too long for GET => use POST instead
      window.open(href);
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
          camelRe = /(-[a-z])/gi,
          camelFn = function(m, a) {
              return a.charAt(1).toUpperCase();
          },
          actionIdx = ((f && f[1]) ? f[1] : "not-existing"),
          //build camelized action out of icon css class:
          action = ('handleTask-'+actionIdx).replace(camelRe, camelFn),
          right = action.replace(/^handleTask/, 'editor')+'Task',
          task = view.getStore().getAt(row),
          confirm;

      if(! me.isAllowed(right)){
          return;
      }
      
      if(! me[action] || ! Ext.isFunction(me[action])){
          //fire event if no handler function for the action button is defined
          me.fireEvent('taskUnhandledAction', action, t, task);
          return;
      }

      if(!this.fireEvent('beforeTaskActionConfirm', action, task, function(){
          me[action](task, ev);
      })) {
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
          callback: function(rec, op) {
              Editor.MessageBox.addByOperation(op);
          },
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
      task.dropped = true; //doing the drop / erase manually
      task.save({
          //prevent default ServerException handling
          preventDefaultHandler: true,
          callback: function(rec, op) {
              Editor.MessageBox.addByOperation(op);
          },
          success: function() {
              store.load();
              app.unmask();
          },
          failure: function(records, op){
              task.reject();
              app.unmask();
              if(op.getError().status == '405') {
                  Editor.MessageBox.addError(me.strings.taskNotDestroyed);
              }
              else {
                  Editor.app.getController('ServerException').handleException(op.error.response);
              }
          }
      });
  },
  /**
   * Clones the task
   * @param {Editor.model.admin.Task} task
   */
  handleTaskClone: function(task) {
      Ext.Ajax.request({
          url: Editor.data.pathToRunDir+'/editor/task/'+task.getId()+'/clone',
          method: 'post',
          scope: this,
          success: function(response){
              this.handleTaskReload();
          },
          failure: function(response) {
              Editor.app.getController('ServerException').handleException(response);
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
          exportAllowed = me.isAllowed('editorExportTask', task),
          menu;
      
      menu = Ext.widget('adminExportMenu', {
          task: task,
          fields: hasQm ? task.segmentFields() : false
      });
      menu.down('#exportItem') && menu.down('#exportItem').setVisible(exportAllowed);
      menu.down('#exportDiffItem') && menu.down('#exportDiffItem').setVisible(exportAllowed);
      menu.showAt(event.getXY());
  },
  
  /**
   * displays the excel re-import fileupload dialog
   * @param {Editor.model.admin.Task} task
   * @param {Ext.EventObjectImpl} event
   */
  handleTaskExcelreimport: function(task, event) {
      if(!this.isAllowed('editorExcelreimportTask')){
          return;
      }
      var tempWidget = Ext.widget('adminTaskExcelReimportWindow').show();
      tempWidget.setTask(task);
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
  },

  /**
   * On admin add task window show handler
   */
  onAdminTaskAddWindowShow:function(win){
      //set the default values for the window fields
      this.setTaskAddFieldDefaults(win);
  },
  
  /**
   * On admin add task window close handler
   */
  onAdminTaskAddWindowClose:function(win){
      var me = this,
	      winLayout=win.getLayout(),
          activeItem=winLayout.getActiveItem(),
          task=activeItem.task;
      
      //if the task exist start it if the import is not started yet
      if(activeItem.task && !me.isImportStarted){
          Ext.Msg.show({
            title:me.strings.deleteTaskDialogTitle,
            message: me.strings.deleteTaskDialogMessage,
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            closable:false,
            buttonText: {
                yes: me.strings.taskDeleteButtonText,
                no: me.strings.taskImportButtonText
            },
            fn: function(btn) {
                //yes -> the task will be deleted
                //no  -> the task will be imported
                if (btn === 'yes') {
                	me.handleTaskDelete(task);
                } else if (btn === 'no') {
                    me.startImport(task);
                }
            }
          });
      }
  },

  /***
   * Set the default values for the add task window fields. The values are configured zf config
   */
  setTaskAddFieldDefaults:function(win){
    var key, fieldDefaults=[];

    if(Editor.data.frontend.importTask && Editor.data.frontend.importTask.fieldsDefaultValue){
        fieldDefaults=Editor.data.frontend.importTask.fieldsDefaultValue;
    }
    if(fieldDefaults.length<1){
        return;
    }
    var field=null;
    for(key in fieldDefaults){
        field=win.down('field[name="'+key+'"]');
        field && field.setValue(fieldDefaults[key]);
    }
  },

  /***
   * starts the upload / form submit
   * 
   */
  saveTask:function(successCallback){
      var me = this,
          win = me.getTaskAddWindow(),
          form = this.getTaskAddForm();
      
      win.setLoading(me.strings.loadingWindowMessage);
      
      form.submit({
          //Accept Header of submitted file uploads could not be changed:
          //http://stackoverflow.com/questions/13344082/fileupload-accept-header
          //so use format parameter jsontext here, for jsontext see REST_Controller_Action_Helper_ContextSwitch
          
          params: {
              format: 'jsontext',
              autoStartImport: 0
          },
          timeout: 3600,
          url: Editor.data.restpath+'task',
          scope: this,
          success: function(form, submit) {
              var task = me.getModel('admin.Task').create(submit.result.rows);
              me.fireEvent('taskCreated', task);
              win.setLoading(false);
              me.getAdminTasksStore().load();
              
              //set the store reference to the model(it is missing), it is used later when the task is deleted
              task.store=me.getAdminTasksStore();
              
              me.setCardsTask(task);
              
              //call the callback if exist
              if(successCallback){
                  successCallback(task);
              }
          },
          failure: function(form, submit) {
              var card, errorHandler = Editor.app.getController('ServerException');
              win.setLoading(false);
              if(submit.failureType == 'server' && submit.result && !submit.result.success){
                  if(submit.result.httpStatus == "422") {
                      win.getLayout().setActiveItem('taskMainCard');
                      win.getViewModel().set('activeItem',win.down('#taskMainCard'));
                      form.markInvalid(submit.result.errorsTranslated);
                  }
                  else {
                      card = win.down('#taskUploadCard');
                      if(card.isVisible()){
                          card.update(errorHandler.renderHtmlMessage(me.strings.taskError, submit.result));
                      }
                      errorHandler.handleException(submit.response);
                  }
              }
          }
      });
  },
  
  /***
   * Start the import for the given task
   */
  startImport:function(task){
	  var me=this,
	  	  url=Editor.data.restpath+"task/"+task.get('id')+"/import",
	  	  win = me.getTaskAddWindow();
      
      //if the window exist, add loading mask
	  win && win.setLoading(me.strings.loading);
	  
	  //set the import started flag
	  me.isImportStarted=true;
	  
	  Ext.Ajax.request({
		 url:url,
		 method:'GET',
         success: function(response){
        	 win && win.setLoading(false);
             Editor.MessageBox.addSuccess(me.strings.importTaskMessage,2);
             me.handleTaskCancel();
             me.isImportStarted=false;
         },
         failure: function(response){
        	 win && win.setLoading(false);
             Editor.app.getController('ServerException').handleException(response);
             me.isImportStarted=false;
         }
	  });
  },
  
  /***
   * For each card item set a task
   */
  setCardsTask:function(task){
	  var me=this,
	  	  win = me.getTaskAddWindow(),
	  	  items=win.items.items;
	  
	  items.forEach(function(item){
		  item.task=task;
	  });
  },

  /***
   * Is the given task importing
   */
  isImportingCheck:function(task){

      if(task.isImporting() || task.get('state') == 'ExcelExported') {
          return true;
      }
      if(task.isCustomState()) {
          //if one of the triggered handler return false, the fireEvent returns false,
          // so we have to flip logic here: if one of the events should trigger the reload they have to return false 
          return !this.fireEvent('periodicalTaskReloadIgnore', task);
      }          
      return false;
  }
});
