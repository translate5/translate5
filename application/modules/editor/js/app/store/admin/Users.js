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
 * Store for Editor.model.admin.User
 * @class Editor.store.admin.Users
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.admin.Users', {
  extend : 'Ext.data.Store',
  model: 'Editor.model.admin.User',
  autoLoad: true,
  remoteSort: true,
  pageSize: 20,
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