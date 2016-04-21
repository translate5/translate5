
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
 * @class Editor.model.admin.Task
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.Task', {
  extend: 'Ext.data.Model',
  requires: ['Editor.model.segment.Field'],
  //currently we have 3 places to define userStates: IndexController for translation, JS Task Model and PHP TaskUserAssoc Model for programmatic usage
  USER_STATE_OPEN: 'open',
  USER_STATE_EDIT: 'edit',
  USER_STATE_VIEW: 'view',
  USER_STATE_WAITING: 'waiting',
  USER_STATE_FINISH: 'finished',
  STATE_IMPORT: 'import',
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
    {name: 'workflow', type: 'string'},
    {name: 'pmGuid', type: 'string'},
    {name: 'pmName', type: 'string'},
    {name: 'wordCount', type: 'integer'},
    {name: 'fileCount', type: 'integer', persist: false},
    {name: 'targetDeliveryDate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
    {name: 'realDeliveryDate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
    {name: 'referenceFiles', type: 'boolean'},
    {name: 'terminologie', type: 'boolean'},
    {name: 'orderdate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
    {name: 'edit100PercentMatch', type: 'boolean'},
    {name: 'lockLocked', type: 'boolean'},
    {name: 'enableSourceEditing', type: 'boolean'},
    {name: 'qmSubEnabled', type: 'boolean'},
    {name: 'qmSubFlags', type: 'auto'},
    {name: 'qmSubSeverities', type: 'auto'},
    {name: 'userState', type: 'string', isEqual: function() {
      return false;
    }},
    {name: 'userRole', type: 'string', persist: false},
    {name: 'isUsed', type: 'boolean', persist: false}, //actually not used, so no isUsed method
    {name: 'userStep', type: 'string', persist: false},
    {name: 'users', type: 'auto', persist: false},
    {name: 'userCount', type: 'integer', persist: false},
    {name: 'defaultSegmentLayout', type: 'boolean', persist: false},
    {name: 'notEditContent', type: 'boolean'}
  ],
  hasMany: [{
      model: 'Editor.model.segment.Field',
      foreignKey: 'taskGuid',
      name: 'segmentFields'
  },{
      model: 'Editor.model.admin.task.UserPref', 
      name: 'userPrefs'
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
   * returns if QM Subsegments are enabled for this task
   * @returns
   */
  hasQmSub: function() {
      return this.get('qmSubEnabled');
  },
  /**
   * returns if task is locked
   * @return {Boolean}
   */
  isLocked: function() {
      return !!this.get('locked') && this.get('lockingUser') != Editor.app.authenticatedUser.get('userGuid');
  },
  /**
   * returns if task is not in a known state, known are: open, end, import
   * unknown state means in general something is happening with the task, the state itself gives info what this is.
   * In consequence tasks with an unknown state are like import not usable.
   * @return {Boolean}
   */
  isCustomState: function() {
      return this.get('state') !== 'open' && this.get('state') !== 'end' && this.get('state') !== 'import';
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
      return this.get('state') == this.STATE_IMPORT;
  },
  /**
   * returns if task is openable
   * @returns {Boolean}
   */
  isOpenable: function() {
      if(this.get('state') == this.STATE_IMPORT) {
          return false;
      }
      //a user with editorEditAllTasks (normally PMs) can always open the task
      if(Editor.app.authenticatedUser.isAllowed('editorEditAllTasks')){
          return true;
      }
      //per default all tasks associated to a user are openable
      return this.get('userRole') != '' && this.get('userState') != '';
  },
  /**
   * returns if task is readonly
   * @returns {Boolean}
   */
  isReadOnly: function() {
      var me = this;
      //FIXME nextRelease This should be done by userRights, a clear way isnt specified yet. Perhaps move to Editor.model.admin.User.isAllowed!
      if(me.get('userRole') == 'visitor' || me.get('userState') == me.USER_STATE_VIEW){
          return true;
      }
      return me.isLocked() || me.isFinished() || me.isWaiting() || me.isEnded();
  },
  /**
   * returns if task is ended
   * @returns {Boolean}
   */
  isEnded: function(){
      return this.get('state')=='end';
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
   * @todo improve workflow handling in Javascript, => adapt the php workflow in js, a class with same methods (like getNextStep step2Role etc)
   * actually all workflow information is encapsulated in frontendRights (thats OK) 
   * and the methods in this class (isXXX Methods and initWorkflow) method, this could be improved
   * for the logic when the filter should be triggered see: E-Mail "Re: Nachfrage Segment Filter" to thomas on 02.10.13 18:03
   */
  initWorkflow: function () {
      var me = this,
          filter = null,
          idx = 0,
          data = Editor.data,
          stepChain = me.getWorkflowMetaData().stepChain,
          task = data.task,
          step = task.get('userStep'),
          useFilter = !(me.isFinished() || me.isWaiting() || me.isEnded());
      
      if(step && useFilter) {
          //preset grid filtering:
          if(!data.initialGridFilters) {
              filter = data.initialGridFilters = {};
          }
          filter = data.initialGridFilters; //use filter as reference 
          //reset workflowstep filters of formerly opened tasks, since initialGridFilters is persistent between tasks!
          delete filter.segmentgrid;
          idx = Ext.Array.indexOf(stepChain, step);
          if(idx > 0 && filter) {
              filter.segmentgrid = [{
                  type: 'workflowStep',
                  dataIndex: 'workflowStep',
                  property: 'workflowStep',
                  disabled: false,
                  value: [stepChain[idx], stepChain[idx - 1]]
              }];
          }
      }
  }
});