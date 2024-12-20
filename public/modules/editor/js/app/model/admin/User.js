
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.model.admin.User
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.User', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'userGuid', type: 'string'},
    {name: 'firstName', type: 'string'},
    {name: 'surName', type: 'string'},
    {name: 'gender', type: 'string'},
    {name: 'login', type: 'string'},
    {name: 'longUserName', type: 'string', persist: false, convert: function(v, rec) {
        return Editor.model.admin.User.getLongUserName(rec);
    }},
    {name: 'email', type: 'string'},
    {name: 'roles', type: 'string'},
    {name: 'passwd', type: 'string'},
    {name: 'editable', type: 'boolean', persist: false},
    {name: 'locale', type: 'string'},
    {name: 'customers', type: 'string'},
    {name: 'openIdIssuer', type: 'string'},
    {name: 'lsp', type: 'int', convert: (v, rec) => v > 0 ? v : null},
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'user',
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
  statics: {
      getUserName: function(rec) {
        return Ext.String.htmlEncode(rec.get('firstName')+' '+rec.get('surName'));
      },
      getUserGuid: function(rec) {
          return rec.get('userGuid');
      },
      getLongUserName: function(rec) {
          return Ext.String.htmlEncode(rec.get('surName')+', '+rec.get('firstName')+' ('+rec.get('login')+')');
      },
      getRoles: function(rec) {
          return rec.get('roles').split(',');
      }
  },
  /**
   * @return {String}
   */
  getUserName: function() {
      return this.self.getUserName(this);
  },
  /**
   * @return {String}
   */
  getUserGuid: function() {
      return this.self.getUserGuid(this);
  },
  /**
   * @return {Array}
   */
  getRoles: function() {
      return this.self.getRoles(this);
  },
  /**
   * @param role {String}
   * @return {Boolean}
   */
  hasRole: function(role) {
      return Ext.Array.contains(this.self.getRoles(this), role);
  },
  /**
   * @param roles {Array}
   * @return {Boolean}
   */
  hasRoles: function(roles) {
      for(var i=0; i < roles.length; i++){
          if(!this.hasRole(roles[i])){
              return false;
          }
      }
      return true;
  },
  /**
   * @param right {String}
   * @param task {Editor.model.admin.Task}
   * @return {Boolean}
   */
  isAllowed: function(right, task) {
      var me = this,
          isAllowed = Editor.data.app.userRights.includes(right),
          wf = task && task.getWorkflowMetaData(),
          isJobInStepChain = wf && wf.stepChain.includes(task.get('userStep'));
      if(!task) {
          return isAllowed;
      }
      var notOpenable = ! task.isOpenable();
      switch(right) {
          case 'editorCancelImport':
              //cancel import is only allowed on import and if either admin or pm of the task
              return task.isImporting() && (isAllowed || task.get('pmGuid') === Editor.data.app.user.userGuid);
          case 'editorReopenTask':
              if(!task.isEnded()) {
                  return false;
              }
              break;
          case 'editorEndTask':
              if(task.isEnded() || task.isLocked() || task.isUnconfirmed()) {
                  return false;
              }
              break;
          case 'editorOpenTask':
              if(notOpenable) {
                  return false;
              }
              break;
          case 'editorEditTask':
              //it will ignore unconfirmed state as readonly
              if(notOpenable || task.isReadOnly(true)) {
                  return false;
              }
              break;
          case 'editorFinishTask':
              if(!isJobInStepChain || task.isWaiting() || task.isFinished() || task.isEnded() || task.isUnconfirmed() || task.isAutoFinish()) {
                  return false;
              }
              break;
          case 'editorUnfinishTask':
              //if user is not associated to the task or task is not finished, it cant be unfinished
              if(task.get('userRole') === '' || !task.isFinished() || task.isEnded()) {
                  return false;
              }
              break;
          case 'editorShowexportmenuTask':
              if(!task.hasMqm() && !me.isAllowed('editorExportTask')){
                  return false;
              }
              break;
          case 'editorExportTask':
              if(task.isUnconfirmed()){
                  return false;
              }
              break;
          case 'useChangeAlikes':
              if(!task.get('defaultSegmentLayout')) {
                  return false;
              }
              break;
      }
      // @todo should we move the rights into the model?
      return isAllowed;
  },
    /**
     * retrieves the id's of the customers bound to the user
     * @returns {int[]}
     */
    getCustomerIds: function(){
        var customers = Editor.util.Util.removeLeadingTrailingCommas(this.get('customers'));
        return Editor.util.Util.integerizeArray(customers.split(','));
    },
    /**
     * Evaluates, if the user-rights make it neccessary to load the customers-store
     * @returns {Boolean}
     */
    needsCustomersStore: function(){
        // QUIRK: there might be more rights/functionalities requiring the customers-store. But they are all bound to role "clintpm" or "pm"
        // instead of this evaluation we may better test for those two roles instead
        return Editor.data.app.userRights.includes('customerAdministration') ||
            Editor.data.app.userRights.includes('customerAssociation') ||
            Editor.data.app.userRights.includes('editorAddLangresource') ||
            Editor.data.app.userRights.includes('editorAddTask') ||
            Editor.data.app.userRights.includes('editorAddUser') ||
            Editor.data.app.userRights.includes('editorCustomerSwitch');
    },
    /**
     * @returns {Boolean}
     */
    isClientRestricted: function(){
        return Editor.data.app.user.isClientRestricted;
    },
    /**
     * This will retrieve the restricted customer-ids in case the current authenticated user is client-restricted
     * @returns {int[]}
     */
    getRestrictedClientIds: function(){
        return Editor.util.Util.integerizeArray(Editor.data.app.user.restrictedClientIds);
    },

    /**
     * @returns {Boolean}
     */
    isLspUser: function() {
        return this.get('lsp') > 0;
    }
});