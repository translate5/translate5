
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
    {name: 'anonymousCols', type: 'boolean'},
    {name: 'visibility', type: 'string'},
    {name: 'userGuid', type: 'string'},
    {name: 'fields', type: 'string'}
  ],
  validations: [
      {type: 'presence', field: 'taskGuid'},
      {type: 'presence', field: 'workflow'},
      {type: 'inclusion', field: 'visibility', list: ['show','hide','disable']},
      {type: 'inclusion', field: 'workflow', list: Ext.Object.getKeys(Editor.data.app.workflows)}
      //FIXME can we do this out of segmentfields dynamically?
      //if yes, than we can move the visibility flags to CONSTs
      //{type: 'inclusion', field: 'fields', list: Ext.Object.getKeys(Editor.data.app.utRoles)}
  ],
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
      root: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      root: 'data',
      writeAllFields: false
    }
  }
});
