
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
 * @class Editor.model.admin.workflow.UserPref
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.task.UserPref', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'entityVersion', type: 'int'}, //does not exist in DB, for versioning only
    {name: 'taskGuid', type: 'string'},
    {name: 'workflow', type: 'string'},
    {name: 'workflowStep', type: 'string'},
    {name: 'notEditContent', type: 'boolean'},
    {name: 'anonymousCols', type: 'boolean'},
    {name: 'visibility', type: 'string'},
    {name: 'userGuid', type: 'string'},
    {name: 'fields', type: 'string'},
    {name: 'taskUserAssocId', type: 'int',defaultValue:null,allowNull:true}
  ],
  validators: {
      taskGuid: 'presence',
      workflow: 'presence',
      visibility: {type: 'inclusion', list: ['show','hide','disable']}
      //FIXME can we do this out of segmentfields dynamically?
      //if yes, than we can move the visibility flags to CONSTs
      //workflow: {type: 'inclusion, list: Ext.Object.getKeys(Editor.data.app.workflows)},
      //fields: {type: 'inclusion', list: Ext.Object.getKeys(Editor.data.app.utRoles)}
  },
  
  idProperty: 'id',
  /**
   * is the Default entry if userGuid and workflowStep are empty
   * @return {Boolean} 
   */
  isDefault: function() {
      return !this.phantom && this.get('userGuid').length == 0 && this.get('workflowStep').length == 0;
  },
  isNonEditableColumnVisible: function() {
      return (this.get('visibility') == 'show');
  },
  isNonEditableColumnDisabled: function() {
      return (this.get('visibility') == 'disable');
  },
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'workflowuserpref',
    reader : {
      rootProperty: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      rootProperty: 'data',
      writeAllFields: false
    }
  }
});
