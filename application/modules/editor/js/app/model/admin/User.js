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
    {name: 'email', type: 'string'},
    {name: 'roles', type: 'string'},
    {name: 'passwd', type: 'string'},
    {name: 'editable', type: 'boolean', persist: false}
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'user',
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
  isAllowed: function(right, task) {
      var me = this,
          isAllowed = (Ext.Array.indexOf(Editor.data.app.userRights, right) >= 0);
      if(!task) {
          return isAllowed;
      }
      var notOpenable = ! task.isOpenable();
      switch(right) {
          case 'editorReopenTask':
              if(!task.isEnded()) {
                  return false;
              }
              break;
          case 'editorEndTask':
              if(task.isEnded() || task.isLocked()) {
                  return false;
              }
              break;
          case 'editorOpenTask':
              if(notOpenable) {
                  return false;
              }
              break;
          case 'editorEditTask':
              if(notOpenable || task.isReadOnly()) {
                  return false;
              }
              break;
          case 'editorFinishTask':
              //FIXME role visitor should be encapsulated some how
              //if user is not associated to the task or task is already finished, it cant be finished
              if(task.get('userRole') == 'visitor' || task.get('userRole') == '' || task.isFinished() || task.isEnded()) {
                  return false;
              }
              break;
          case 'editorUnfinishTask':
              //if user is not associated to the task or task is not finished, it cant be unfinished
              if(task.get('userRole') == '' || !task.isFinished() || task.isEnded()) {
                  return false;
              }
              break;
          case 'editorShowexportmenuTask':
              if(!task.hasQmSub() && !me.isAllowed('editorExportTask')){
                  return false;
              }
              break;
      }
      // @todo should we move the rights into the model?
      return isAllowed;
  }
});