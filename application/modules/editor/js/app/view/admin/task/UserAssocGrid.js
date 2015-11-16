
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
           * FIXME formatting
           * @event confirmDelete
           * @param {Ext.form.Panel} grid
           * @param {Editor.model.admin.task.UserPref[]} toDelete
           * @param {Ext.button.Button} btn
           */


Ext.define('Editor.view.admin.task.UserAssocGrid', {
  extend: 'Ext.grid.Panel',
  alias: 'widget.adminTaskUserAssocGrid',
  cls: 'task-user-assoc-grid',
  itemId: 'adminTaskUserAssocGrid',
  strings: {
      confirmDeleteTitle: '#UT#Eintrag löschen?',
      confirmDelete: '#UT#Soll dieser Eintrag wirklich gelöscht werden?',
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
              handler: function() {
                  Ext.Msg.confirm(me.strings.confirmDeleteTitle, me.strings.confirmDelete, function(btn){
                      var toDelete = me.getSelectionModel().getSelection();
                      if(btn == 'yes') {
                          me.fireEvent('confirmDelete', me, toDelete, this);
                      }
                  });
              },
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
