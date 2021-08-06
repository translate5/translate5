
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
 * Store for Editor.model.admin.User
 * @class Editor.store.admin.Users
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.admin.Users', {
  extend : 'Ext.data.Store',
  model: 'Editor.model.admin.User',
  autoLoad: false,
  remoteFilter: true,
  remoteSort: true,
  pageSize: 0,
  userGuidName: {},
  /**
   * returns the Username either by id or by guid
   * getting by guid caches the association guid => username internally
   */
  getUserName: function(id) {
      var me = this, 
          user = null,
          idx = -1;

      if(Ext.isString(id)){
          if(me.userGuidName[id]) {
              return me.userGuidName[id].getUserName();
          }
          idx = me.find('userGuid', id);
          if(idx < 0) {
              return '';
          }
          user = me.getAt(idx);
          me.userGuidName[id] = user;
      }
      else if(Ext.isNumeric(id)) {
          user = me.getById(id);
      }
      if(user) {
          return user.getUserName(); 
      }
      return '';
  }
});