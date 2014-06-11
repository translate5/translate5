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
Ext.define('Editor.view.admin.task.UserAssocGrid', {
  extend: 'Ext.grid.Panel',
  alias: 'widget.adminTaskUserAssocGrid',
  cls: 'task-user-assoc-grid',
  itemId: 'adminTaskUserAssocGrid',
  strings: {
      userGuidCol: '#UT#Benutzer',
      roleCol: '#UT#Rolle',
      stateCol: '#UT#Status',
      addUser: '#UT#Benutzer hinzufügen',
      addUserTip: '#UT#Einen Benutzer dieser Aufgabe zuordnen.',
      removeUser: '#UT#Benutzer entfernen',
      removeUserTip: '#UT#Den gewählten Benutzer aus dieser Aufgabe entfernen.',
      save: '#UT#Änderungen speichern',
      reload: '#UT#Aktualisieren',
      cancel: '#UT#Abbrechen'
  },
  viewConfig: {
      loadMask: false
  },
  store: 'admin.TaskUserAssocs',
  //features: [{
    //ftype: 'editorGridFilter'
  //}],
  initComponent: function() {
    var me = this,
        wf = me.actualTask.getWorkflowMetaData();
    Ext.applyIf(me, {
      columns: [{
          xtype: 'gridcolumn',
          width: 160,
          dataIndex: 'login',
          renderer: function(v, meta, rec) {
              return rec.get('surName')+', '+rec.get('firstName')+' ('+v+')';
          },
          text: me.strings.userGuidCol
      },{
          xtype: 'gridcolumn',
          width: 90,
          dataIndex: 'role',
          renderer: function(v) {
              return wf.roles[v];
          },
          text: me.strings.roleCol
      },{
          xtype: 'gridcolumn',
          width: 90,
          dataIndex: 'state',
          renderer: function(v) {
              return wf.states[v];
          },
          text: me.strings.stateCol
      }],
      dockedItems: [{
          xtype: 'toolbar',
          dock: 'top',
          items: [{
              xtype: 'button',
              iconCls: 'ico-user-add',
              itemId: 'add-user-btn',
              text: me.strings.addUser,
              tooltip: me.strings.addUserTip
          },{
              xtype: 'button',
              iconCls: 'ico-user-del',
              disabled: true,
              itemId: 'remove-user-btn',
              text: me.strings.removeUser,
              tooltip: me.strings.removeUserTip
          },{
              xtype: 'button',
              itemId: 'reload-btn',
              iconCls: 'ico-refresh',
              text: me.strings.reload
          }]
        }]
    });

    me.callParent(arguments);
  }
});
