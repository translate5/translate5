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
 * @class Editor.model.admin.Task
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.Task', {
  extend: 'Ext.data.Model',
  //currently we have 3 places to define userStates: IndexController for translation, JS Task Model and PHP TaskUserAssoc Model for programmatic usage
  USER_STATE_OPEN: 'open',
  USER_STATE_EDIT: 'edit',
  USER_STATE_VIEW: 'view',
  USER_STATE_WAITING: 'waiting',
  USER_STATE_FINISH: 'finished',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'taskGuid', type: 'string'},
    {name: 'taskNr', type: 'string'},
    {name: 'taskName', type: 'string'},
    {name: 'sourceLang', type: 'string'},
    {name: 'targetLang', type: 'string'},
    {name: 'relaisLang', type: 'string'},
    {name: 'locked', type: 'date', persist: false, dateFormat: Editor.DATE_ISO_FORMAT},
    {name: 'lockingUser', type: 'string', persist: false},
    {name: 'lockingUsername', type: 'string', persist: false},
    {name: 'state', type: 'string'},
    {name: 'pmGuid', type: 'string'},
    {name: 'pmName', type: 'string'},
    {name: 'wordCount', type: 'integer'},
    {name: 'targetDeliveryDate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
    {name: 'realDeliveryDate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
    {name: 'referenceFiles', type: 'boolean'},
    {name: 'terminologie', type: 'boolean'},
    {name: 'orderdate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
    {name: 'edit100PercentMatch', type: 'boolean'},
    {name: 'enableSourceEditing', type: 'boolean'},
    {name: 'qmSubEnabled', type: 'boolean'},
    {name: 'qmSubFlags', type: 'auto'},
    {name: 'qmSubSeverities', type: 'auto'},
    {name: 'userState', type: 'string'},
    {name: 'userRole', type: 'string', persist: false},
    {name: 'userStep', type: 'string', persist: false},
    {name: 'users', type: 'auto', persist: false},
    {name: 'userCount', type: 'integer', persist: false}
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'task',
    reader : {
      root: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      root: 'data',
      writeAllFields: false
    }
  },
  /**
   * ensures that the userState is always send to the server.
   * ExtJS sends per default only modified fields, this can lead to errors here.
   * It could be, that after a session time out, the actual task isn't active anymore, but he is still marked es "edit"
   * In this case the user would not be able to open the task again! 
   * @param {String} field
   * @param {String} value
   */
  set: function(field, value) {
      var res = this.callParent(arguments);
      if(field == 'userState' && !this.modified.userState) {
          this.modified.userState = value;
      }
      return res; 
  },
  /**
   * returns if task has relais language
   * @return {Boolean}
   */
  hasRelaisSource: function() {
      return this.get('relaisLang')>0;
  },
  /**
   * returns if QM Subsegments are enabled for this task
   * @returns
   */
  hasQmSub: function() {
      return this.get('qmSubEnabled');
  },
  /**
   * returns if tasks source is editable
   * @return {Boolean}
   */
  isSourceEditable: function() {
      return this.get('enableSourceEditing');
  },
  /**
   * returns if task is locked
   * @return {Boolean}
   */
  isLocked: function() {
      return !!this.get('locked') && this.get('lockingUser') != Editor.app.authenticatedUser.get('userGuid');
  },
  /**
   * must consider also the old value (temporary set to open / edit)
   * @returns {Boolean}
   */
  isFinished: function() {
      var me = this, finish = me.USER_STATE_FINISH;
      return me.modified.userState == finish || me.get('userState') == finish;
  },
  /**
   * must consider also the old value (temporary set to open / edit)
   * @returns {Boolean}
   */
  isWaiting: function() {
      var me = this, wait = me.USER_STATE_WAITING;
      return me.modified.userState == wait || me.get('userState') == wait;
  },
  /**
   * returns if task is openable
   * @returns {Boolean}
   */
  isOpenable: function() {
      //actually all task associated to a user are openable
      return this.get('userRole') != '' && this.get('userState') != '';
  },
  /**
   * FIXME wenn ich den Editor mutwillig readOnly öffne, dann liefert isReadOnly noch false, isReadOnly wird aber an Stellen benutzt die dann auch true  liefern müssen. daher in isReadOnly das isEditAble recht abprüfen?
   * returns if task is readonly
   * @returns {Boolean}
   */
  isReadOnly: function() {
      var me = this;
      //FIXME nextRelease This should be done by userRights, a clear way isnt specified yet. Perhaps move to Editor.model.admin.User.isAllowed!
      if(me.get('userRole') == 'visitor'){
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
          stepChain = data.app.wfStepChain,
          task = data.task,
          step = task.get('userStep'),
          useFilter = !(me.isFinished() || me.isWaiting() || me.isEnded());
      
      if(step && useFilter) {
          //preset grid filtering:
          if(!data.initialGridFilters) {
              filter = data.initialGridFilters = {};
          }
          idx = Ext.Array.indexOf(stepChain, step);
          if(idx > 0 && filter && !filter.editorGridFilter) {
              filter.editorGridFilter = {
                  workflowStep: {
                      value: [stepChain[idx], stepChain[idx - 1]]
                  }
              };
          }
      }
      
      
  }
});