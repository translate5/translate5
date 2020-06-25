
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
  requires: ['Editor.view.admin.ExportMenu','Editor.view.admin.task.menu.TaskActionMenu','Editor.view.project.ProjectActionMenu'],
  models: ['admin.Task', 'admin.task.Log'],
  stores: [
	  'admin.Users',
	  'admin.UsersList',
	  'admin.Tasks',
	  'project.Project',
	  'admin.Languages', 
	  'admin.task.Logs', 
	  'admin.WorkflowUserRoles',
	  'admin.WorkflowState',
	  'admin.WorkflowSteps'
  ],
  views: ['admin.TaskGrid', 'admin.TaskAddWindow', 'admin.task.LogWindow', 'admin.task.ExcelReimportWindow', 'admin.task.KpiWindow','StatefulWindow'],
  refs : [{
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
  },{
      ref: 'advancedFilterToolbar',
      selector: '#advancedFilterToolbar'
  },{
      ref: 'filterHolder',
      selector: '#filterHolder'
  },{
	  ref:'adminMainSection',
	  selector:'#adminMainSection'
  },{
	  ref:'adminTaskPreferencesWindow',
	  selector:'#adminTaskPreferencesWindow'
  },{
	  ref:'projectGrid',
	  selector:'#projectGrid'
  },{
	  ref:'projectTaskGrid',
	  selector:'#projectTaskGrid'
  },{
	  ref:'projectPanel',
	  selector:'#projectPanel'
  }],
  alias: 'controller.taskOverviewController',
  
  isCardFinished:false,
  
  /***
   * the flag is true, when import workers are started via ajax
   */
  isImportStarted:false,

  /**
   * Anonymizing workflow-users will need the taskUserTracking-data
   */
  taskUserTrackingsStore: null,
  
  /***
   * Advanced filter window component
   */
  advancedFilterWindow:null,
  
  /***
   * Action menu cache component
   */
  menuCache:[],
  /**
   * Container for translated task handler confirmation strings
   * Deletion of an entry means to disable confirmation.
   */
  confirmStrings: {
      "editorFinishTask":       {title: "#UT#Aufgabe abschließen?", msg: "#UT#Wollen Sie die Aufgabe wirklich abschließen?"},
      "editorUnfinishTask":     {title: "#UT#Aufgabe wieder öffnen?", msg: "#UT#Wollen Sie die Aufgabe wirklich wieder öffnen?"},
      "editorFinishAllTask":   {title: "#UT#Aufgabe für alle Nutzer abschließen?", msg: "#UT#Wollen Sie die Aufgabe wirklich für alle Benutzer abschließen?"},
      "editorUnfinishAllTask": {title: "#UT#Aufgabe für alle Nutzer wieder öffnen?", msg: "#UT#Wollen Sie die Aufgabe wirklich für alle Benutzer wieder öffnen?"},
      "editorEndTask":          {title: "#UT#Aufgabe endgültig beenden?", msg: "#UT#Wollen Sie die Aufgabe wirklich für alle Benutzer endgültig beenden?"},
      "editorReopenTask":       {title: "#UT#Beendete Aufgabe wieder öffnen?", msg: "#UT#Wollen Sie die beendete Aufgabe wirklich wieder öffnen?"},
      "editorDeleteTask":       {title: "#UT#Aufgabe komplett löschen?", msg: "#UT#Wollen Sie die Aufgabe wirklich komplett und unwiderruflich löschen?"}
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
      loadingWindowMessage:"#UT#Dateien werden hochgeladen",
      loading:'#UT#Laden',
      importTaskMessage:"#UT#Hochladen beendet. Import und Vorbereitung laufen.",
      deleteTaskDialogMessage:'#UT#Sollte der Task gelöscht oder mit den aktuellen Einstellungen importiert werden?',
      deleteTaskDialogTitle:'#UT#Aufgabe löschen',
      taskImportButtonText:'#UT#Aufgabe importieren',
      taskDeleteButtonText:'#UT#Aufgabe löschen',
      averageProcessingTimeLabel: '#UT#Ø Bearbeitungszeit Lektor',
      excelExportUsageLabel: '#UT#Excel-Export Nutzung',
      averageProcessingTimeTranslatorLabel: '#UT#Ø Bearbeitungszeit Übersetzer',
      averageProcessingTimeSecondTranslatorLabel: '#UT#Ø Bearbeitungszeit zweiter Lektor'
  },
  listeners:{
	  afterTaskDelete:'onAfterTaskDeleteEventHandler',
	  beforeTaskDelete:'onBeforeTaskDeleteEventHandler'
  },
  listen: {
      store: {
          '#project.Project':{
        	  load:'onProjectStoreLoad'
          }
      },
      controller: {
          '#Editor.$application': {
              adminViewportClosed: 'clearTasks',
              editorViewportOpened: 'handleInitEditor'
          },
      },
      component: {
          '#adminTaskGrid,#projectTaskGrid': {
              hide: 'handleAfterHide',
              celldblclick: 'handleGridClick', 
              cellclick: 'handleGridClick',
          },
          '#adminTaskGrid': {
              filterchange:'onAdminTaskGridFilterChange'
          },
          '#adminTaskGrid #reload-task-btn': {
              click: 'handleTaskReload'
          },
          '#adminTaskGrid taskActionColumn,#projectTaskGrid taskActionColumn': {
              click: 'taskActionDispatcher'
          },
          '#projectGrid taskActionColumn': {
              click: 'projectActionDispatcher'
          },
          '#adminTaskGrid #add-project-btn,#projectGrid #add-project-btn': {
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
          },
          '#addAdvanceFilterBtn':{
        	  click:'onAddAdvanceFilterBtnClick'
          },
          'editorAdminTaskFilterFilterWindow':{
        	  advancedFilterChange:'onAdvancedFilterChange'
          },
          '#projectTaskGrid':{
        	  selectionchange:'onProjectTaskGridSelectionChange'
          },
          '#projectGrid':{
        	  selectionchange:'onProjectGridSelectionChange'
          },
          '#taskActionMenu,#projectActionMenu':{
        	  click:'onTaskActionMenuClick'
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
    //***********************************************************************************
    //End Events
    //***********************************************************************************
  /**
   * handle after hide of taskgrid
   */
  handleAfterHide: function() {
      this.closeAdvancedFilterWindow();
  },
  handleInitEditor: function() {
      this.closeAdvancedFilterWindow();
  },
  clearTasks: function() {
      this.getAdminTasksStore().removeAll();
  },
  loadTasks: function() {
      this.getAdminTasksStore().load();
  },
  handleChangeImportFile: function(field, val){
      var name = this.getTaskAddForm().down('textfield[name=taskName]'),
          srcLang = this.getTaskAddForm().down('combo[name=sourceLang]'),
          targetLang = this.getTaskAddForm().down('tagfield[name^=targetLang]'),
          customer = this.getTaskAddForm().down('combo[name=customerId]'),
          idx,
          langs = val.match(/-([a-zA-Z]{2,5})-([a-zA-Z]{2,5})\.[^.]+$/);
      if(name && name.getValue() == '') {
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
          //if we set a language automatically we also assume the default customer
          idx = customer.store.findExact('name', 'defaultcustomer');
          if(idx >= 0) {
              customer.setValue(customer.store.getAt(idx).get('id'));
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
              this.editorLogTask(rec);
          }
          return;
      }
      if(rec.isOpenable() && (isTaskNr || dbl)) {
          this.openTaskRequest(rec);
      }
  },
  
  onAdminTaskGridFilterChange:function(store,filters,eOpts){
	  var me=this;
	  //get the store active filters object as parameter
	  me.getAdvancedFilterToolbar().loadFilters(store.getFilters(false));
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
      //reset the route
      me.redirectTo('');
      Editor.util.TaskActions.openTask(task, readonly);
  },
  editorLogTask: function(task) {
      if(!this.isAllowed('editorTaskLog')){
          return;
      }
      var win = Ext.widget('adminTaskLogWindow',{
          actualTask: task
      });
      win.show();
      win.load();
  },
  
  editorPreferencesTask:function(task){
	var me=this;
    me.getProjectPanel().getController().redirectFocus(task,true);
    me.getAdminTaskPreferencesWindow().down('tabpanel').setActiveTab('adminTaskUserAssoc');
	me.fireEvent('handleTaskPreferences', task);
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
                  averageProcessingTimeMessage=[],
                  excelExportUsageMessage;
              
              
              // KPI: averageProcessingTime
              averageProcessingTimeMessage.push(me.strings.averageProcessingTimeTranslatorLabel + ': ' + resp.averageProcessingTimeTranslator)
              averageProcessingTimeMessage.push(me.strings.averageProcessingTimeLabel + ': ' + resp.averageProcessingTimeReviewer);
              averageProcessingTimeMessage.push(me.strings.averageProcessingTimeSecondTranslatorLabel + ': ' + resp.averageProcessingTimeSecondTranslator)
              
              // KPI: excelExportUsage
              excelExportUsageMessage = resp.excelExportUsage + ' ' + me.strings.excelExportUsageLabel;
              // update fields and stop loading-icon
              me.getAverageProcessingTimeDisplay().update(averageProcessingTimeMessage.join('</br>'));
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
  
  /***
   * Add advance filter button handler
   */
  onAddAdvanceFilterBtnClick:function(){
	  var me=this,
	  	  filterHolder=me.getFilterHolder(),
	  	  record=filterHolder.selection ? filterHolder.selection : [];
	  //reload the usersList store so the new task filter is applied
	  Ext.StoreMgr.get('admin.UsersList').load()
	  me.advancedFilterWindow=Ext.widget('editorAdminTaskFilterFilterWindow');
	  me.advancedFilterWindow.loadRecord(record);
	  me.advancedFilterWindow.show();
  },
  
  /***
   * Filter is applied in the advanced filter window
   */
  onAdvancedFilterChange:function(filter){
	  this.addAdvancedFilter(filter);
  },
  
  /***
   * Add filter to the advanced filter collection. This will also trigger the filtering and set an
   * active record in the advanced filter component.
   * @param {Object} filter
   */
  addAdvancedFilter:function(filter){
	  var me=this,
	  	  toolbar=me.getAdvancedFilterToolbar(),
	  	  taskGrid=me.getTaskGrid(),
	  	  taskStore=taskGrid.getStore(),
	  	  filtersarray=toolbar.getController().filterActiveFilters(filter),
	  	  addFilter=filtersarray && filtersarray.length>0;
	
	 //clear the taskGrid store from the filters
	 taskStore.clearFilter(addFilter);
	 //add the custom filtering where the filterchange event will be suspended
	 taskGrid.activateGridColumnFilter(filtersarray,true);
	 //load the filters into the filter holder tagfield
	 toolbar.loadFilters(taskStore.getFilters(false));
  },
  
  /***
   * On project task grid selection.
   */
  onProjectTaskGridSelectionChange:function(grid,selection){
      var me = this,
          task = selection ? selection[0] : null;
      if(!task){
          return;
      }
      me.getProjectPanel().getController().redirectFocus(task, true);
  },
  
  /**
   * On projectTask grid selection change
   */
  onProjectGridSelectionChange:function(grid,selection){
      var me = this,
        cnt=me.getProjectPanel().getController(),
        task = selection ? selection[0] : null;
      
      if(!task){
          return;
      }
      cnt.redirectFocus(task,false);
  },
  
  
  /***
   * On project store load
   */
  onProjectStoreLoad:function(store){
      var me = this,
          activeTab = me.isProjectPanelActive(),
          panel = me.getProjectPanel(),
          vm = panel.getViewModel(),
          record = vm.get('projectSelection'),
          task = null;

      //if the project panel is not active, ignore the redirect,
      //when we redirect, the component focus is changed
      if(!activeTab){
          return;
      }
      //if selected record already exist, use it
      if(record){
          task = store.getById(record.get('id'));
      }
      //no selected record is found, use the first in the store
      if(!task){
          task = store.getAt(0);
      }
      panel.getController().redirectFocus(task,false);
  },
  
  /***
   * Task menu item click handler. Here it will be proven if the action exist and if the user is allowed for the action.
   */
  onTaskActionMenuClick:function(com,item,ev){
	  var me = this,
	      task=com.lookupViewModel().get('task'),
	      action=item && item.action;
	      
      if(! me.isAllowed(action)){
          return;
      }
      
      if(!me[action] || ! Ext.isFunction(me[action])){
          return;
      }

      if(!this.fireEvent('beforeTaskActionConfirm', action, task, function(){
    	  me[action](task, ev);
	  })) {
	      return;
	  }
	  
	  //if NO confirmation string exists, we call the action unconfirmed. 
	  if(! me.confirmStrings[action]) {
	      me[action](task, ev);
	      return; 
	  }
	
	  confirm = me.confirmStrings[action];
	  Ext.Msg.confirm(confirm.title, confirm.msg, function(btn){
	      if(btn == 'yes') {
	          me[action](task, ev);
	      }
	  });
  },
  
  /***
   * Delete project action menu handler
   */
  editorDeleteProject:function(task,ev){
	  this.getProjectGrid().getController().handleProjectDelete(task,ev);
  },
  
  /**
   * Task grid action icon click handler
   * 
   * @param {Ext.grid.View} view
   * @param {DOMElement} cell
   * @param {Integer} row
   * @param {Integer} col
   * @param {Ext.Event} ev
   * @param {Object} record
   */
  taskActionDispatcher: function(view, cell, row, col, ev, record) {
      this.callMenuAction('Task',record,ev)
  },
  
  /**
   * Project grid action icon click handler
   * 
   * @param {Ext.grid.View} view
   * @param {DOMElement} cell
   * @param {Integer} row
   * @param {Integer} col
   * @param {Ext.Event} ev
   * @param {Object} record
   */
  projectActionDispatcher:function(view, cell, row, col, ev, record) {
	  this.callMenuAction('Project',record,ev)
  },
  
  /***
   * calls local action handler, dispatching is done by the icon CSS class of the clicked img
   * the css class ico-task-foo-bar is transformed to the method handleTaskFooBar
   * if this controller contains this method, it'll be called. 
   * 
   * @param {String} menuParrent : menu source view
   * @param {Object} record
   * @param {Ext.Event} event
   */
  callMenuAction:function(menuParrent,task,event){
      var me = this,
	      t = event.getTarget(),
	      f = t.className.match(/ico-task-([^ ]+)/),
	      camelRe = /(-[a-z])/gi,
	      camelFn = function(m, a) {
	          return a.charAt(1).toUpperCase();
	      },
	      actionIdx = ((f && f[1]) ? f[1] : "not-existing"),
	      //build camelized action out of icon css class:
	      action = ('handle'+menuParrent+'-'+actionIdx).replace(camelRe, camelFn),
	      right = action.replace(new RegExp('handle'+menuParrent), 'editor')+menuParrent;
	
	  if(! me.isAllowed(right)){
	      return;
	  }
	  
	  if(! me[action] || ! Ext.isFunction(me[action])){
	      return;
	  }
	  me[action](task, event);
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
  editorOpenTask: function(task) {
      this.openTaskRequest(task, true);
  },
  
  /**
   * Opens the task in normal (edit) mode (does internal a readonly check by task)
   * @param {Editor.model.admin.Task} task
   */
  editorEditTask: function(task) {
      this.openTaskRequest(task);
  },
  
  /**
   * Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  editorFinishTask: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskFinishing, task.get('taskName'));
      task.set('userState', task.USER_STATE_FINISH);
      task.save(me.getTaskMaskBindings());
  },
  
  /**
   * Un Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  editorUnfinishTask: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskUnFinishing, task.get('taskName'));
      task.set('userState', task.USER_STATE_OPEN);
      task.save(me.getTaskMaskBindings());
  },
  
  /**
   * Un Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  editorEndTask: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskEnding, task.get('taskName'));
      task.set('state', 'end');
      task.save(me.getTaskMaskBindings());
  },
  
  /**
   * Un Finish the task for the logged in user
   * @param {Editor.model.admin.Task} task
   */
  editorReopenTask: function(task) {
      var me = this;
      Editor.app.mask(me.strings.taskReopen, task.get('taskName'));
      task.set('state', 'open');
      task.save(me.getTaskMaskBindings());
  },
  /**
   * delete the task
   * Fires: beforeTaskDelete  and afterTaskDelete
   * INFO: beforeTaskDelete is a chained event
   * @param {Editor.model.admin.Task} task
   */
  editorDeleteTask: function(task) {
      var me = this,
          store = task.store,
          app = Editor.app;
      
      app.mask(me.strings.taskDestroy, task.get('taskName'));
      
      //the beforeTaskDelete is chained event. If one of the chained listeners does not return true,
      //the task delete will be omitted.
      if(!me.fireEvent('beforeTaskDelete',task)){
          app.unmask();
          return
      }
      
      store.sync({
          //prevent default ServerException handling
          preventDefaultHandler: true,
          success: function() {
              app.unmask();
              me.fireEvent('afterTaskDelete',task);
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
  editorCloneTask: function(task,event) {
      var me=this;
      Ext.Ajax.request({
          url: Editor.data.pathToRunDir+'/editor/task/'+task.getId()+'/clone',
          method: 'post',
          scope: this,
          success: function(response){
              if(me.isProjectPanelActive()){
                  me.getProjectTaskGrid().getStore().load();
              }
              me.handleTaskReload();
          },
          failure: function(response) {
              Editor.app.getController('ServerException').handleException(response);
          }
      });
  },

  /***
   * Task action menu click handler
   */
  handleTaskMenu: function(selectedTask, event) {
      this.showActionMenu(selectedTask, event,'taskActionMenu')
  },
  
  /***
   * Project action menu click handler
   */
  handleProjectMenu: function(selectedTask, event) {
	  this.showActionMenu(selectedTask, event,'projectActionMenu')
  },
  
  /***
   * Edit task action icon handler
   */
  handleTaskEdit: function(selectedTask, event) {
      this.editorEditTask(selectedTask, event)
  },
  
  /***
   * Show action menu by given menu xtype
   */
  showActionMenu:function(selectedTask,event,menuXtype){
	  var me = this,
          menu=me.menuCache[menuXtype],
          vm=null;

	  if(!menu){
    	  //create fresh menu instance
    	  me.menuCache[menuXtype]=menu=Ext.widget(menuXtype,{task:selectedTask});
	  }
      vm=menu.getViewModel();
	  vm && vm.set('task',selectedTask);
	  vm && vm.notify();
	  menu.showAt(event.getXY());
  },
  
  
  /**
   * displays the excel re-import fileupload dialog
   * @param {Editor.model.admin.Task} task
   * @param {Ext.EventObjectImpl} event
   */
  editorExcelreimportTask: function(task, event) {
      if(!this.isAllowed('editorExcelreimportTask')){
          return;
      }
      var tempWidget = Ext.widget('adminTaskExcelReimportWindow').show();
      tempWidget.setTask(task);
  },
  
  /**
   * Redirects the user to the task project in the project overview or in task overview (depending from where the action is triggered)
   * @param {Editor.model.admin.Task} task
   */
  handleTaskProject: function(task) {
	  var me=this,
	      menu=me.getAdminMainSection(),
	      activeTab=menu.getActiveTab(),
	      isTaskOverview=activeTab == me.getTaskGrid(),
	      redirectCmp=isTaskOverview ? me.getProjectPanel(): activeTab,
	      route='task/'+task.get('id')+'/filter';

	  if(isTaskOverview){
          route='project/'+task.get('projectId')+'/'+task.get('id')+'/focus';
      }
      Editor.app.openAdministrationSection(redirectCmp,route);
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
                	me.editorDeleteTask(task);
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
              me.getProjectGrid().getController().reloadProjects(function(){
              	  me.handleProjectAfterImport(task);
              });
              
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
	  
	  win.getViewModel().set('currentTask',task);
	  //TODO: use the current task in all other cards
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
  },
  
  /***
   * Close advanced filter window
   */
  closeAdvancedFilterWindow:function(){
	  this.advancedFilterWindow && this.advancedFilterWindow.hide();
  },

  /***
   * After import reload the project store and focus the import
   */
  handleProjectAfterImport:function(task){
	  var me=this,
	     activeTab=me.isProjectPanelActive();
	  if(!activeTab){
		  return;
	  }
	  activeTab.getController().redirectFocus(task,false);
  },
  
  /***
   * Check if the project panel is active. If the project panel is active, the project panel component is returned
   */
  isProjectPanelActive:function(){
	  var me=this,
	      menu=me.getAdminMainSection(),
	      activeTab=menu.getActiveTab();
		  
	  if(activeTab.xtype!='projectPanel'){
		  return null;
	  }
	  return activeTab;
  },

  /***
   * Before task delete request event handler.
   * Return true so the event call chain continues
   */
  onBeforeTaskDeleteEventHandler:function(task){
      Ext.StoreManager.get('admin.Tasks').remove(task);
      return true;
  },
  /***
   * After the task is removed event handler
   */
  onAfterTaskDeleteEventHandler:function(task){
      Ext.StoreManager.get('admin.Tasks').load();
  }
  
});
