
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
      actionLog: '#UT# Ereignis-Protokoll',
      taskPrefs: '#UT# Aufgabenspezifische Einstellungen',
      exp: '#UT# Aufgabe exportieren',
      actionExcelReimport: '#UT# Excel Re-Importieren',
      projectOverview:'#UT#zum Projekt springen',
      taskOverview:'#UT#zur Aufgabe springen',
      actionDeleteProject: '#UT#Projekt komplett löschen',
      taskActionMenu:'#UT#Aufgabenmenü anzeigen'
  },
  
  /**
   * Items and Icon Class: the action dispatching is done by the iconCls, therefore the iconCls has to start with ico-task
   * The Action itself is the afterwards part, so the following class "ico-task-foo-bar" is transformed to handleTaskFooBar in the controller
   * We cant use the isAllowedFor right, because in the click handler we have only access to the DOM Element (and therefore the icon cls) 
   * @param instanceConfig
   */
  constructor: function(instanceConfig) {
    var me = this,
    config = {
        itemFilter:function(item){
            //this filters only by systemrights. taskrights must be implemented by css
            return Editor.app.authenticatedUser.isAllowed(item.isAllowedFor);
        },
        items:[{
        	tooltip: me.messages.taskActionMenu,
            isAllowedFor: 'editorMenuTask',
            iconCls: 'ico-task-menu',
            sortIndex:1
        },{
          getTip:function(v,meta,record,row,col,store,table){
	        	if(table.ownerGrid.getXType()=='projectTaskGrid'){
	        		return me.messages.taskOverview;
	        	}
	        	return me.messages.projectOverview;
	        },
	        isAllowedFor: 'editorProjectTask',
	        iconCls: 'ico-task-project',
	        sortIndex:2
        },{
        	tooltip: me.messages.actionDeleteProject,
            isProjectActionIcon:true,
            isAllowedFor: 'editorDeleteProject',
            iconCls: 'ico-project-delete',
            sortIndex:3
        }]
    };

    config.items=Ext.Array.sort(config.items,function(a,b){
        return a.sortIndex - b.sortIndex;
    });

    config.items= Ext.Array.filter(config.items,config.itemFilter);
    config.width = config.items.length * 18;

    //dynamic column width with configured minWith
    config.width = Math.max(config.width, me.width);

    if (instanceConfig) {
        me.self.getConfigurator().merge(me, config, instanceConfig);
    }
    
    me.callParent([Ext.apply({
        items: config.items
    }, config)]);
  },
  
});