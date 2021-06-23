
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
  mixins:['Editor.util.Util'],
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
      ref: 'userAssocSegmentrange',
      selector: 'adminTaskUserAssoc #segmentrange'
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
          },
          'adminTaskUserAssoc #userSpecialPropertiesBtn':{
              click:me.onUserSpecialPropertiesBtnClick
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
          task = me.getPrefWindow().getCurrentTask(),
          meta = task.getWorkflowMetaData(),
          usageMode = task.get('usageMode'),
          step = task.get('workflowStepName'),
          state = Ext.Object.getKeys(meta.states)[0],
          isTranslationTask=task.get('emptyTargets'),
          newRec;
      
      if(! meta.usableSteps[step]) {
          step = Ext.Object.getKeys(meta.usableSteps)[0];
      }

      //in competitive mode instead OPEN / UNCONFIRMED is used
      if(usageMode == task.USAGE_MODE_COMPETITIVE && state == task.USER_STATE_OPEN){
          state = task.USER_STATE_UNCONFIRMED;
      }
      //set the default step to the first translation step when the task is translation task and
      //the workflow name is no workflow
      if(isTranslationTask && task.isNoWorkflowStep()){
          //load first translation step
          step = Ext.Object.getKey(meta.steps2roles, Editor.data.app.workflow.CONST.ROLE_TRANSLATOR);
      }
      newRec = assoc.model.create({
          taskGuid: task.get('taskGuid'),
          workflowStepName: step,
          state: state
      });
      me.getAssocDelBtn().disable();
      me.getEditInfo().hide();
      me.getUserAssocForm().show();
      me.getUserAssocForm().setDisabled(false);
      me.getUserAssocSegmentrange().setDisabled(usageMode !== task.USAGE_MODE_SIMULTANEOUS);
      me.getUserAssoc().loadRecord(newRec);
      me.initState(null, step, '');
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
          userEditable=record && record.get('editable'),
          userDeletable=record && record.get('deletable'),
          task = me.getPrefWindow().getCurrentTask();
      me.getAssocDelBtn().setDisabled(emptySel || !userDeletable);
      me.getEditInfo().setVisible(emptySel);
      me.getUserAssocForm().setVisible(!emptySel);
      me.getUserAssocForm().setDisabled(emptySel || !userEditable);
      me.getUserAssocSegmentrange().setDisabled(task.get('usageMode') !== task.USAGE_MODE_SIMULTANEOUS);
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
          task = me.getPrefWindow().getCurrentTask(),
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
          task = me.getPrefWindow().getCurrentTask(),
          grid = me.getUserAssocGrid(),
          win = me.getPrefWindow(),
          store = grid.store,
          rec = form.getRecord();
      form.getForm().updateRecord(rec);
      if(! form.getForm().isValid()) {
          return;
      }
      win.setLoading(true);
      win.lookupViewModel().set('userAssocDirty', true);
      rec.saveVersioned(task, {
          success: function(savedRec, op) {
              me.handleCancel();
              if(!rec.store) {
                  store.insert(0,rec);
                  grid.getSelectionModel().select(rec);
                  me.fireEvent('addUserAssoc', me, rec, store);
              }
              task.load();//reload only the task, not the whole task prefs, should be OK
              Editor.MessageBox.addByOperation(op);
              Editor.MessageBox.addSuccess(me.messages.assocSave);
              win.setLoading(false);
          },
          failure: function() {
              store.load();
              win.setLoading(false);
          }
      });
  },
  
  reloadTaskUserAssocGrid:function(){
      var me = this,
        store = me.getUserAssocGrid().getStore();
        me.getUserAssocGrid().getSelectionModel().deselectAll();
      store.load();
  },
  
  onUserSpecialPropertiesBtnClick:function(){
      var me=this,
        preferences=Ext.create('Editor.view.admin.task.Preferences',{
            task:me.getPrefWindow().getViewModel().get('currentTask')
        });
      preferences.show();
  },
  
  clearStores: function() {
      this.getAdminTaskUserAssocsStore().removeAll();
  },

  /**
   * sets the initial state value dependent on the step
   * @param {Ext.form.field.ComboBox} roleCombo
   * @param {String} step
   * @param {String} oldValue
   */
  initState: function(roleCombo, step, oldValue) {
      var me = this,
          form = me.getUserAssocForm(),
          task = me.getPrefWindow().getCurrentTask(),
          stateCombo = form.down('combo[name="state"]'),
          isCompetitive = task.get('usageMode') == task.USAGE_MODE_COMPETITIVE,
          newState = task.USER_STATE_OPEN,
          rec = form.getRecord(),
          isChanged = stateCombo.getValue() != rec.get('state'),
          meta = task.getWorkflowMetaData(),
          initialStates = meta.initialStates[task.get('workflowStepName')];
      
      stateCombo.store.clearFilter();
      
      //set the default deadline date when the form state is initialized
      me.setWorkflowStepDefaultDeadline(task,step,rec);
      
      if(!rec.phantom || isChanged) {
          return;
      }
      //on new job entries only non finished states are allowed. 
      // Everything else would make no sense and bypass workflow
      stateCombo.store.addFilter(function(item){
          return item.get('id') != Editor.model.admin.Task.prototype.USER_STATE_FINISH;
      });
      if(initialStates && initialStates[step]) {
          newState = initialStates[step];
      }
      if(isCompetitive && newState == task.USER_STATE_OPEN) {
          newState = task.USER_STATE_UNCONFIRMED;
      }
      rec.set('state', newState);
      stateCombo.setValue(newState);
      me.filterUserCombo(step)
  },

  /***
   * Filter the user combo store based on the selected user role. The user can be assigned only onece per role.
   */
  filterUserCombo:function(userStep){
      var me = this,
        form = me.getUserAssocForm(),
        userAssocGrid=me.getUserAssocGrid(),
        usersCombo=form.down('combo[name="userGuid"]'),
        tuaUsers=[];
      
      //collect all userGuids for the current role
      userAssocGrid.getStore().each(function(rec){
          if(rec.get('workflowStepName')==userStep){
            tuaUsers.push(rec.get('userGuid'));
          }
      });
      
      //filter out all current assoc users from the usersStore
      usersCombo.getStore().addFilter([{
          property: 'userGuid',
          value:tuaUsers,
          operator:'notin'
      }],true);
  },
  
  /***
   * Calculate and set the default deadline date from config and order date.
   */
  setWorkflowStepDefaultDeadline:function(task,step,record){
      var me=this,
        form = me.getUserAssocForm() && me.getUserAssocForm().getForm(),
        deadlineDate = form && form.findField('deadlineDate'),
        recordDeadlineDate = record && record.get('deadlineDate'),//the record has deadlineDate
        orderDate = task && task.get('orderdate');
      
      //if order date is not set, no calculation is required
      //if there is no workflow step defined, no calculation is required
      //if there is no deadlineDate form field, no calculation is required
      //if the deadlineDate is already set, no calculation is required
      if(!orderDate || !step || !deadlineDate || recordDeadlineDate){
          return null;
      }
      var workflow=task.get('workflow'),
          configName = Ext.String.format('workflow.{0}.{1}.defaultDeadlineDate',workflow,step),
          days = Editor.app.getTaskConfig(configName),
          newValue = null;
      
      // calculate the new date if config exist
      if(days){
          // check if the order date has timestamp 00:00:00
          if(orderDate.getHours() === 0 && orderDate.getMinutes() === 0){
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
