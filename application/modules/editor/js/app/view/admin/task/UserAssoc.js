
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

Ext.define('Editor.view.admin.task.UserAssoc', {
  extend: 'Ext.panel.Panel',
  requires: ['Editor.view.admin.task.UserAssocGrid'],
  alias: 'widget.adminTaskUserAssoc',
  strings: {
      fieldRole: '#UT#Rolle',
      fieldState: '#UT#Status',
      fieldUser: '#UT#Benutzer',
      btnSave: '#UT#Speichern',
      btnCancel: '#UT#Abbrechen',
      formTitleAdd: '#UT#Benutzerzuweisung hinzufügen:',
      formTitleEdit: '#UT#Bearbeite Benutzer "{0}"',
      editInfo: '#UT#Wählen Sie einen Eintrag in der Tabelle aus um diesen zu bearbeiten!'
  },
  layout: {
      type: 'border'
  },
  title : '#UT#Benutzer zu Aufgabe zuordnen',
  
  initConfig: function(instanceConfig) {
    var me = this,
        wf = me.initialConfig.actualTask.getWorkflowMetaData(),
        states = [],
        config,
        roles = [];
    Ext.Object.each(wf.states, function(key, state) {
        states.push([key, state]);
    });
    Ext.Object.each(wf.roles, function(key, role) {
        roles.push([key, role]);
    });
    
    config = {
      items: [{
          xtype: 'adminTaskUserAssocGrid',
          region: 'center',
          actualTask: me.initialConfig.actualTask
      },{
          xtype: 'container',
          region: 'east',
          autoScroll: true,
          height: 'auto',
          width: 300,
          items: [{
              xtype: 'container',
              itemId: 'editInfoOverlay',
              cls: 'edit-info-overlay',
              padding: 10,
              html: me.strings.editInfo
          },{
              xtype: 'form',
              title : me.strings.formTitleAdd,
              hidden: true,
              bodyPadding: 10,
              region: 'east',
              items:[{
                  anchor: '100%',
                  xtype: 'combo',
                  allowBlank: false,
                  listConfig: {
                      loadMask: false
                  },
                  store: Ext.create('Ext.data.Store', {
                      model: 'Editor.model.admin.User',
                      autoLoad: false,
                      pageSize: 0
                  }),
                  forceSelection: true,
                  queryMode: 'local',
                  name: 'userGuid',
                  displayField: 'longUserName',
                  valueField: 'userGuid',
                  fieldLabel: me.strings.fieldUser
              },{
                  anchor: '100%',
                  xtype: 'combo',
                  allowBlank: false,
                  editable: false,
                  forceSelection: true,
                  queryMode: 'local',
                  name: 'role',
                  fieldLabel: me.strings.fieldRole,
                  store: roles
              },{
                  anchor: '100%',
                  xtype: 'combo',
                  allowBlank: false,
                  editable: false,
                  forceSelection: true,
                  name: 'state',
                  queryMode: 'local',
                  fieldLabel: me.strings.fieldState,
                  store: states
              }],
              dockedItems: [{
                  xtype: 'toolbar',
                  dock: 'bottom',
                  ui: 'footer',
                  items: [{
                      xtype: 'tbfill'
                  },{
                      xtype: 'button',
                      itemId: 'save-assoc-btn',
                      iconCls : 'ico-save',
                      text: me.strings.btnSave
                  },
                  {
                      xtype: 'button',
                      itemId: 'cancel-assoc-btn',
                      iconCls : 'ico-cancel',
                      text: me.strings.btnCancel
                  }]
              }]
          }]
      }]
    };

    if (instanceConfig) {
        me.getConfigurator().merge(me, config, instanceConfig);
    }
    return me.callParent([config]);
  },
  /**
   * loads all or all available users into the dropdown, the store is reused to get the username to userguids
   * @param {Boolean} edit true if edit an assoc, false if add a new one
   */
  loadUsers: function() {
      var me = this,
          user = me.down('combo[name="userGuid"]'),
      store = user.store;
      store.clearFilter(true);
      if(!me.excludeLogins || me.excludeLogins.length == 0) {
          store.load();
      }
      else {
          store.load({
              params: {
                  defaultFilter: '[{"property":"login","operator":"notInList","value":["'+me.excludeLogins.join('","')+'"]}]'
              }
          });
      }
  },
  /**
   * loads the given record into the userAssoc form
   * @param {Editor.data.model.admin.TaskUserAssoc} rec
   */
  loadRecord: function(rec) {
      var me = this,
          edit = !rec.phantom,
          form = me.down('form'),
          user = me.down('combo[name="userGuid"]');
      form.loadRecord(rec);
      if(edit) {
          form.setTitle(Ext.String.format(me.strings.formTitleEdit, rec.get('longUserName')));
      }
      else {
          me.loadUsers(edit);
          form.setTitle(me.strings.formTitleAdd);
      }
      user.setVisible(!edit);
      user.setDisabled(edit);
  }
});