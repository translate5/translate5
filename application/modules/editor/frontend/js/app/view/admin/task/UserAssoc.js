
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

Ext.define('Editor.view.admin.task.UserAssoc', {
  extend: 'Ext.panel.Panel',
  requires: ['Editor.view.admin.task.UserAssocGrid','Editor.view.admin.task.UserAssocViewModel'],
  alias: 'widget.adminTaskUserAssoc',
  strings: {
      fieldRole: '#UT#Rolle',
      fieldState: '#UT#Status',
      fieldUser: '#UT#Benutzer',
      btnSave: '#UT#Speichern',
      btnCancel: '#UT#Abbrechen',
      formTitleAdd: '#UT#Benutzerzuweisung hinzufügen:',
      formTitleEdit: '#UT#Bearbeite Benutzer "{0}"',
      editInfo: '#UT#Wählen Sie einen Eintrag in der Tabelle aus um diesen zu bearbeiten!',
      fieldDeadline:'#UT#Deadline'
  },
  viewModel: {
      type: 'taskuserassoc'
  },
  layout: {
      type: 'border'
  },
  title: '#UT#Benutzer-Plural',
  
  initConfig: function(instanceConfig) {
    var me = this,
        config;

    config = {
      title: me.title, //see EXT6UPD-9
      items: [{
          xtype: 'adminTaskUserAssocGrid',
          region: 'center'
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
              defaults: {
                  labelAlign: 'top'  
              },
              items:[{
                  anchor: '100%',
                  xtype: 'combo',
                  allowBlank: false,
                  editable: false,
                  forceSelection: true,
                  queryMode: 'local',
                  name: 'role',
                  fieldLabel: me.strings.fieldRole,
                  valueField: 'id',
                  bind: {
                      store: '{roles}'
                  }
              },{
                  anchor: '100%',
                  xtype: 'combo',
                  allowBlank: false,
                  listConfig: {
                      loadMask: false
                  },
                  bind: {
                      store: '{users}'
                  },
                  forceSelection: true,
                  anyMatch: true,
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
                  name: 'state',
                  queryMode: 'local',
                  fieldLabel: me.strings.fieldState,
                  valueField: 'id',
                  bind: {
                      store: '{states}'
                  }
              },{
            	  xtype: 'datefield',
            	  name: 'deadlineDate',
            	  fieldLabel: me.strings.fieldDeadline,
            	  submitFormat: Editor.DATE_ISO_FORMAT,
                  anchor: '100%'
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
        me.self.getConfigurator().merge(me, config, instanceConfig);
    }
    return me.callParent([config]);
  },
  initComponent: function() {
      var me = this,
          vm = me.lookupViewModel(),
          states = [],
          roles = [],
          metaData = vm.get('currentTask').getWorkflowMetaData();
      
      Ext.Object.each(metaData.states, function(key, state) {
          states.push({id: key, text: state});
      });
      Ext.Object.each(metaData.editableRoles, function(key, role) {
          roles.push({id: key, text: role});
      });
      vm.set('statesData', states);
      vm.set('rolesData', roles);
      me.callParent(arguments);
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
      store.load();
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