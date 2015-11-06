
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
 * @class Editor.model.admin.TaskUserAssoc
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.TaskUserAssoc', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'entityVersion', type: 'int'}, //does not exist in DB, for versioning only
    {name: 'taskGuid', type: 'string'},
    {name: 'userGuid', type: 'string'},
    {name: 'login', type: 'string', persist: false},
    {name: 'surName', type: 'string', persist: false},
    {name: 'firstName', type: 'string', persist: false},
    {name: 'longUserName', type: 'string', persist: false, convert: function(v, rec) {
        return Editor.model.admin.User.getLongUserName(rec);
    }},
    {name: 'state', type: 'string'},
    {name: 'role', type: 'string'}
  ],
  validations: [
      {type: 'presence', field: 'taskGuid'},
      {type: 'presence', field: 'userGuid'}//,
      //FIXME make me dynamic? {type: 'inclusion', field: 'state', list: Ext.Object.getKeys(Editor.data.app.utStates)},
      //FIXME make me dynamic? {type: 'inclusion', field: 'role', list: Ext.Object.getKeys(Editor.data.app.utRoles)}
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'taskuserassoc',
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