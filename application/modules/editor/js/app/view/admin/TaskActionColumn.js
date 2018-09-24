
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.view.admin.TaskActionColumn
 * @extends Ext.grid.column.ActionColumn
 */
Ext.define('Editor.view.admin.TaskActionColumn', {
  alias: 'widget.taskActionColumn',
  extend: 'Ext.grid.column.Action',
  width: 90,
  menuText: null,
  messages: {
      actionOpen: '#UT# Aufgabe öffnen (nur Lesemodus)',
      actionEdit: '#UT# Aufgabe bearbeiten',
      actionClone: '#UT# Aufgabe klonen',
      actionFinish: '#UT# Aufgabe abschließen',
      actionUnFinish: '#UT# Aufgabe wieder öffnen',
      actionFinishAll: '#UT# Aufgabe für alle Benutzer abschließen',
      actionUnFinishAll: '#UT# Aufgabe für alle Benutzer wieder öffnen',
      actionEnd: '#UT# Aufgabe komplett Beenden',
      actionReOpen: '#UT# beendete Aufgabe wieder öffnen',
      actionDelete: '#UT#Aufgabe komplett löschen',
      actionAnalysis:'#UT#Match analysis',
      taskPrefs: '#UT# Aufgabenspezifische Einstellungen',
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
              tooltip: me.messages.taskPrefs,
              isAllowedFor: 'editorPreferencesTask',
              iconCls: 'ico-task-preferences'
          },{
              // - Export Icon, bei Klick darauf öffnet sich ein Menü mit den verschiedenen Export Möglichkeiten. 
              // Die einzelnen Menüpunkte ebenfalls per isAllowed abfragen. 
              tooltip: me.messages.exp,
              isAllowedFor: 'editorShowexportmenuTask',
              iconCls: 'ico-task-showexportmenu'
          },{
              tooltip: me.messages.actionClone,
              isAllowedFor: 'editorCloneTask',
              iconCls: 'ico-task-clone'
          },{
              tooltip: me.messages.actionDelete,
              isAllowedFor: 'editorDeleteTask',
              iconCls: 'ico-task-delete'
          }],itemFilter),
          width = items.length * 18;
          
          if(Editor.plugins && Editor.plugins.MatchAnalysis){
        	  items.push({
        		  tooltip:me.messages.actionAnalysis,
                  iconCls: 'ico-task-analysis',
                  isAllowedFor: 'editorAnalysisTask'	  
        	  })
          }
          
      cfg = cfg || {};
      
      //dynamic column width with configured minWith
      cfg.width = Math.max(width, me.width);
      
      me.callParent([Ext.apply({
          items: items
      }, cfg)]);
  }
});