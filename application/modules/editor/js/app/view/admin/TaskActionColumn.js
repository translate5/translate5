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
 * @class Editor.view.admin.TaskActionColumn
 * @extends Ext.grid.column.ActionColumn
 */
Ext.define('Editor.view.admin.TaskActionColumn', {
  alias: 'widget.taskActionColumn',
  extend: 'Ext.grid.column.Action',
  width: 90,

  messages: {
      actionOpen: '#UT# Aufgabe öffnen (nur Lesemodus)',
      actionEdit: '#UT# Aufgabe bearbeiten',
      actionFinish: '#UT# Aufgabe abschließen',
      actionUnFinish: '#UT# Aufgabe wieder öffnen',
      actionFinishAll: '#UT# Aufgabe für alle Benutzer abschließen',
      actionUnFinishAll: '#UT# Aufgabe für alle Benutzer wieder öffnen',
      actionEnd: '#UT# Aufgabe komplett Beenden',
      actionReOpen: '#UT# beendete Aufgabe wieder öffnen',
      actionDelete: '#UT#Aufgabe komplett löschen',
      assocUser: '#UT# Benutzer der Aufgabe zuweisen',
      exp: '#UT# Aufgabe exportieren'
  },
  
  /**
   * Items and Icon Class: the action dispatching is done by the iconCls, therefore the iconCls has to start with ico-task
   * The Action itself is the afterwards part, so the following class "ico-task-foo-bar" is transformed to handleTaskFooBar in the controller
   * We cant use the isAllowedFor right, because in the click handler we have only access to the DOM Element (and therefore the icon cls) 
   * @param cfg
   */
  constructor: function(cfg) {
      var me = this,
          itemFilter = function(item){
              //this filters only by systemrights. taskrights must be implemented by css
              return Editor.app.authenticatedUser.isAllowed(item.isAllowedFor);
          },
          items = Ext.Array.filter([{
              // - öffnen
              tooltip: me.messages.actionOpen,
              isAllowedFor: 'editorOpenTask',
              iconCls: 'ico-task-open'
          },{
              // - read only öffnen
              tooltip: me.messages.actionEdit,
              isAllowedFor: 'editorEditTask',
              iconCls: 'ico-task-edit'
          },{
              // - abschließen (Recht editorFinishTask benötigt, setzt den TaskUser Status des aktuellen Users auf finish)
              tooltip: me.messages.actionFinish,
              isAllowedFor: 'editorFinishTask',
              iconCls: 'ico-task-finish'
          },{
              // - wieder öffnen (Recht editorUnFinishTask benötigt, setzt den TaskUser Status des aktuellen Users auf open, aktuell nicht gefordert)
              tooltip: me.messages.actionUnFinish,
              isAllowedFor: 'editorUnfinishTask',
              iconCls: 'ico-task-unfinish'
          },{
              // - beenden (Recht editorEndTask benötigt, setzt den Task auf Status ""end"")
              tooltip: me.messages.actionEnd,
              isAllowedFor: 'editorEndTask',
              iconCls: 'ico-task-end'
          },{
              // - wieder öffnen (Recht editorReOpenTask benötigt, setzt den Task auf Status ""open"")
              tooltip: me.messages.actionReOpen,
              isAllowedFor: 'editorReopenTask',
              iconCls: 'ico-task-reopen'
          },{
              // - wieder öffnen (Recht editorReOpenTask benötigt, setzt den Task auf Status ""open"")
              tooltip: me.messages.assocUser,
              isAllowedFor: 'editorChangeUserAssocTask',
              iconCls: 'ico-task-change-user-assoc'
          },{
              // - Export Icon, bei Klick darauf öffnet sich ein Menü mit den verschiedenen Export Möglichkeiten. 
              // Die einzelnen Menüpunkte ebenfalls per isAllowed abfragen. 
              tooltip: me.messages.exp,
              isAllowedFor: 'editorExportTask',
              iconCls: 'ico-task-export'
          },{
              tooltip: me.messages.actionDelete,
              isAllowedFor: 'editorDeleteTask',
              iconCls: 'ico-task-delete'
          }],itemFilter),
          width = items.length * 18;
          
      cfg = cfg || {};
      
      //dynamic column width with configured minWith
      cfg.width = Math.max(width, me.width);
      
      me.callParent([Ext.apply({
          items: items
      }, cfg)]);
      me.setText(cfg.text);
  }
});