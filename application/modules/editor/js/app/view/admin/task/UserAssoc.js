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
  
  initComponent: function() {
    var me = this,
        wf = me.actualTask.getWorkflowMetaData(),
        states = [],
        roles = [];
    Ext.Object.each(wf.states, function(key, state) {
        states.push([key, state]);
    });
    Ext.Object.each(wf.roles, function(key, role) {
        roles.push([key, role]);
    });
    
    Ext.applyIf(me, {
      items: [{
          xtype: 'adminTaskUserAssocGrid',
          region: 'center',
          actualTask: me.actualTask
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
    });

    me.callParent(arguments);
  },
  /**
   * loads all or all available users into the dropdown, the store is reused to get the username to userguids
   * @param {Boolean} edit true if edit an assoc, false if add a new one
   */
  loadUsers: function() {
      var me = this,
          user = me.down('.combo[name="userGuid"]'),
      store = user.store;
      if(!me.excludeLogins || me.excludeLogins.length == 0) {
          store.load();
      }
      else {
          store.load({
              params: {
                  defaultFilter: '[{"field":"login","type":"notInList","value":["'+me.excludeLogins.join('","')+'"]}]'
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
          form = me.down('.form'),
          user = me.down('.combo[name="userGuid"]');
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