
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
 * @class Editor.model.admin.Task
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.Task', {
  extend: 'Ext.data.Model',
  requires: [
      'Editor.model.segment.Field',
      'Editor.model.admin.task.UserPref',
      'Editor.model.admin.TaskUserTracking',
      'Editor.model.TaskConfig',
  ],
  //currently we have 3 places to define userStates: IndexController for translation, JS Task Model and PHP TaskUserAssoc Model for programmatic usage
  USER_STATE_OPEN: 'open',
  USER_STATE_EDIT: 'edit',
  USER_STATE_VIEW: 'view',
  USER_STATE_WAITING: 'waiting',
  USER_STATE_FINISH: 'finished',
  USER_STATE_UNCONFIRMED: 'unconfirmed',
  USAGE_MODE_COMPETITIVE: 'competitive',
  USAGE_MODE_COOPERATIVE: 'cooperative',
  USAGE_MODE_SIMULTANEOUS: 'simultaneous',
  states: {
      ERROR: 'error',
      IMPORT: 'import',
      UNCONFIRMED: 'unconfirmed',
      OPEN: 'open',
      END: 'end',
  },
  WORKFLOW_STEP_NO_WORKFLOW:'no workflow',//default workflow step constant
  WORKFLOW_STEP_TRANSLATION:'translation',//default workflow step constant
  WORKFLOW_STEP_REVIEWING:'reviewing',//default workflow step constant
  
  WORKFLOW_USER_ROLE_TRANSLATOR:'translator',//TODO: when needed add the other constants
  fields: [
    {name: 'id', type: 'int'},
    {name: 'taskGuid', type: 'string'},
    {name: 'entityVersion', type: 'integer', critical: true},
    {name: 'taskNr', type: 'string'},
    {name: 'taskName', type: 'string'},
    {name: 'sourceLang', type: 'string'},
    {name: 'targetLang', type: 'string'},
    {name: 'relaisLang', type: 'string'},
    {name: 'locked', type: 'date', persist: false, dateFormat: Editor.DATE_ISO_FORMAT},
    {name: 'lockingUser', type: 'string', persist: false},
    {name: 'lockingUsername', type: 'string', persist: false},
    {name: 'state', type: 'string'},
    {name: 'customerId', type: 'integer'},
    {name: 'customerName', type: 'string'},
    {name: 'workflow', type: 'string'},
    {name: 'pmGuid', type: 'string'},
    {name: 'pmName', type: 'string'},
    {name: 'wordCount', type: 'integer'},
    {name: 'fileCount', type: 'integer', persist: false},
    {name: 'orderdate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
    {name: 'referenceFiles', type: 'boolean'},
    {name: 'terminologie', type: 'boolean'},
    {name: 'edit100PercentMatch', type: 'boolean'},
    {name: 'lockLocked', type: 'boolean'},
    {name: 'enableSourceEditing', type: 'boolean'},
    {name: 'qualityErrorCount', type: 'integer', defaultValue: 0},
    {name: 'qualityHasFaults', type: 'boolean'},
    {name: 'qualityHasMqm', type: 'boolean'},
    {name: 'qualityMqmCategories', type: 'auto'},
    {name: 'qualityMqmSeverities', type: 'auto'},
    {name: 'lastErrors', type: 'auto'},
    {name: 'emptyTargets', type: 'boolean', persist: false},
    {name: 'userState', type: 'string', isEqual: function() {
      return false; //ensures that the value is also send when it was set to the same value
    }},
    //for workflow actions are mostly basing on state transitions. There fore the previous state is very useful.
    {name: 'userStatePrevious', type: 'string', critical: true, mapping: function(data) {
        //init the value with the old value 
        return data.userState;
    }},
    {name: 'userRole', type: 'string', persist: false},
    {name: 'isUsed', type: 'boolean', persist: false}, //actually not used, so no isUsed method
    {name: 'userStep', type: 'string', persist: false},
    {name: 'users', type: 'auto', persist: false},
    {name: 'userCount', type: 'integer', persist: false},
    {name: 'defaultSegmentLayout', type: 'boolean', persist: false},
    {name: 'notEditContent', type: 'boolean'},
    {name: 'usageMode', type: 'string'},
    {name: 'segmentCount', type: 'int', persist: false},
    {name: 'segmentFinishCount', type: 'int', persist: false},
    {name: 'diffExportUsable', type: 'boolean'}
  ],
  hasMany: [{
      model: 'Editor.model.segment.Field',
      name: 'segmentFields'
  },{
      model: 'Editor.model.admin.task.UserPref', 
      name: 'userPrefs'
  },{
      model: 'Editor.model.admin.TaskUserTracking', 
      name: 'userTracking'
  }],
  idProperty: 'id',
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'task',
    reader : {
      rootProperty: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      rootProperty: 'data',
      writeAllFields: false
    }
  },
  /**
   * returns if MQM qualities are enabled for this task
   * @return {Boolean}
   */
  hasMqm: function() {
      return this.get('qualityHasMqm');
  },
  /**
   * returns if MQM qualities are enabled for this task
   * @return {Array}
   */
  getMqmCategories: function() {
      if(this.get('qualityMqmCategories')){
          return this.get('qualityMqmCategories');
      }
      return [];
  },
  /**
   * returns if MQM qualities are enabled for this task
   * @return {Array}
   */
  getMqmSeverities: function() {
      if(this.get('qualityMqmSeverities')){
          return this.get('qualityMqmSeverities');
      }
      return [];
  },
  /**
   * returns if task is editable depending on task locking and usagemode
   * does not evaluate waiting/finished etc. Therefore is isReadonly 
   * @return {Boolean}
   */
  isEditable: function() {
      //if the task is edited by multiple users, it is not locked in the frontend.
      if(this.isLocked() && this.get('lockedInternalSessionUniqId') == Editor.data.tasks.simultaneousEditingKey) {
          return true; 
      }
      return !this.isLocked();
  },
  /**
   * returns if task is locked (either by a user or by system)
   * With simultaneous editing the task is also locked, but user should be able to edit, therefore is the isEditable. 
   * @return {Boolean}
   */
  isLocked: function() {
      // TODO: A task must also be locked if not all language-resources that are assigned to the task are available.
      // - 'not available' can be: STATUS_NOVALIDLICENSE, STATUS_ERROR, STATUS_NOCONNECTION; see also: checkStatusForAction() in editor_Plugins_GroupShare_Init
      // - result for status: comes from getStatus() in editor_Plugins_GroupShare_Connector
      // => (1) create a new check hasOnlyAvailableLangugeResources() or so
      //    (2) add this check either for this.get('locked') or do it extra at all places where needed
      return !!this.get('locked') && this.get('lockingUser') != Editor.app.authenticatedUser.get('userGuid');
  },
  /**
   * returns if task is not in a known state, known are: open, end, import
   * unknown state means in general something is happening with the task, the state itself gives info what this is.
   * In consequence tasks with an unknown state are like import not usable.
   * @return {Boolean}
   */
  isCustomState: function() {
      var knownStates = Ext.Object.getValues(this.states);
      return !Ext.Array.contains(knownStates, this.get('state'));
  },
  /**
   * must consider also the old value (temporary set to open / edit)
   * @returns {Boolean}
   */
  isFinished: function() {
      var me = this, finish = me.USER_STATE_FINISH;
      return me.modified && me.modified.userState == finish || me.get('userState') == finish;
  },
  /**
   * must consider also the old value (temporary set to open / edit)
   * @returns {Boolean}
   */
  isWaiting: function() {
      var me = this, wait = me.USER_STATE_WAITING;
      return me.modified && me.modified.userState == wait || me.get('userState') == wait;
  },
  /**
   * edit or view state implies currently, that a saving request of the task is running,
   * since view and edit states should not be shown in the task grid. 
   * After successful save request the userState will be corrected.
   * No translations should provided for this states.
   * @returns {Boolean}
   */
  isPending: function() {
      var me = this, 
          state = me.get('userState');
      return state == me.USER_STATE_VIEW || state == me.USER_STATE_EDIT;
  },
  /**
   * returns if task is still in import state
   * @returns {Boolean}
   */
  isImporting: function() {
      return this.get('state') == this.states.IMPORT;
  },
  /**
   * returns if task is unconfirmed for the current user
   * @returns {Boolean}
   */
  isUnconfirmed: function() {
      var me = this, unconfirmed = me.USER_STATE_UNCONFIRMED;
      if(this.get('state') == this.states.UNCONFIRMED) {
          return true;
      }
      return me.modified && me.modified.userState == unconfirmed || me.get('userState') == unconfirmed;
  },
  /**
   * returns if task had errors while import
   * @returns {Boolean}
   */
  isErroneous: function() {
      return this.get('state') == this.states.ERROR;
  },
  
  /***
   * Is the task state matchanalysis
   */
  isAnalysis: function() {
      return this.get('state') == 'matchanalysis';
  },
  
  /**
   * Is the current task state open
   * @returns {Boolean}
   */
  isOpen:function(){
      return this.get('state') == this.states.OPEN;
  },
  /**
   * returns if task is openable
   * @returns {Boolean}
   */
  isOpenable: function() {
      if(this.get('state') == this.states.IMPORT) {
          return false;
      }
      //a user with editorEditAllTasks (normally PMs) can always open the task or if the current loged user is a pm to that project
      if(Editor.app.authenticatedUser.isAllowed('editorEditAllTasks') || this.get('pmGuid')===Editor.data.app.user.userGuid){
          return true;
      }
      //per default all tasks associated to a user are openable
      return this.get('userRole') != '' && this.get('userState') != '';
  },
  /**
   * returns if task is readonly
   * @param ignoreUnconfirmed : ignore the unconfirmed state as readonly case
   * @returns {Boolean}
   */
  isReadOnly: function(ignoreUnconfirmed) {    
      var me = this,
        isUnconfirmed=!ignoreUnconfirmed && me.isUnconfirmed();//ingore the unconfirmed state as readonly
      if(me.get('userRole') == 'visitor' || me.get('userState') == me.USER_STATE_VIEW){
          return true;
      }
      return !me.isEditable() || me.isFinished() || me.isWaiting() || me.isEnded() || isUnconfirmed;
  },
  /**
   * returns if task is ended
   * @returns {Boolean}
   */
  isEnded: function(){
      return this.get('state')==this.states.END;
  },
  /***
   * Is the task not in importing,pending or custom state
   * @returns {Boolean}
   */
  isNotImportPendingCustom:function(){
      return !this.isImporting() && !this.isPending() && !this.isCustomState();
  },

  /***
   * Is the task not in error,import,pending
   * @returns {Boolean}
   */
  isNotErrorImportPending:function(){
    return !this.isErroneous() && !this.isImporting() && !this.isPending();
  },

  /***
   * Is the task not in error,import,pending and custom state
   * @returns {Boolean}
   */
  isNotErrorImportPendingCustom:function(){
      return this.isNotErrorImportPending() && !this.isCustomState();
  },
  
  /**
   * Is the current ask in excel export state
   */
  isExcelExported:function(){
      return this.get('state') == 'ExcelExported';
  },

  /**
   * returns the the metadata for the workflow of the task
   */
  getWorkflowMetaData: function() {
      var me = this,
          wf = me.get('workflow');
      if(!Editor.data.app.workflows[wf]) {
          Ext.Error.raise('requested workflow meta data not found! (workflow '+wf+')');
      }
      return Editor.data.app.workflows[wf];
  },
  /**
   * returns the taskName with (taskNr), if set.
   * @return {String}
   */
  getTaskName: function() {
      var nr = this.get('taskNr');
      if(nr) {
          return this.get('taskName')+' ('+nr+')';
      }
      return this.get('taskName');
  },
  
  /**
   * @todo improve workflow handling in Javascript, => adapt the php workflow in js, a class with same methods (like getNextStep step2Role etc)
   * actually all workflow information is encapsulated in frontendRights (thats OK) 
   * and the methods in this class (isXXX Methods and initWorkflow) method, this could be improved
   */
  initWorkflow: function () {
      var me = this,
          filter = null,
          chainIdx = 0,
          data = Editor.data,
          meta = me.getWorkflowMetaData(),
          stepChain = meta.stepChain,
          stepsWithFilter = meta.stepsWithFilter,
          task = data.task,
          step = task.get('userStep'),
          useFilter = !(me.isFinished() || me.isWaiting() || me.isEnded()),
          filteredValues = [];
      
      if(step && useFilter) {
          //preset grid filtering:
          if(!data.initialGridFilters) {
              data.initialGridFilters = {};
          }
          filter = data.initialGridFilters; //use filter as reference 
          //reset workflowstep filters of formerly opened tasks, since initialGridFilters is persistent between tasks!
          delete filter.segmentgrid;
          chainIdx = Ext.Array.indexOf(stepChain, step);
          filteredValues = [stepChain[chainIdx], stepChain[chainIdx - 1]];
          if(meta.steps.pmCheck) {
              filteredValues.push("pmCheck"); //pmCheck'ed segments should also be in the filter!
          }
          if(Ext.Array.indexOf(stepsWithFilter, step) >= 0 && filter) {
              filter.segmentgrid = [{
                  type: 'workflowStep',
                  dataIndex: 'workflowStep',
                  property: 'workflowStep',
                  disabled: false,
                  value: filteredValues
              }];
          }
      }
  }
});