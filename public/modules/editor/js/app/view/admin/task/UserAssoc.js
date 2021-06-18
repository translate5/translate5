
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
  requires: [
      'Editor.view.admin.task.UserAssocGrid',
      'Editor.view.admin.task.UserAssocViewModel',
      'Ext.ux.DateTimeField'
  ],
  alias: 'widget.adminTaskUserAssoc',
  itemId:'adminTaskUserAssoc',
    mixins:['Editor.controller.admin.IWizardCard'],
    //card type, used for card display order
    importType:'postimport',
    task:null,
  strings: {
      fieldRole: '#UT#Rolle',
      fieldState: '#UT#Status',
      fieldUser: '#UT#Benutzer',
      btnSave: '#UT#Speichern',
      btnCancel: '#UT#Abbrechen',
      formTitleAdd: '#UT#Benutzerzuweisung hinzufügen:',
      formTitleEdit: '#UT#Bearbeite Benutzer "{0}"',
      fieldDeadline:'#UT#Deadline',
      fieldSegmentrange: '#UT#Editierbare Segmente',
      fieldSegmentrangeInfo: '#UT#Bsp: 1-3,5,8-9 (Wenn die Rolle dieses Users das Editieren erlaubt und zu irgendeinem User dieser Rolle editierbare Segmente zugewiesen werden, dürfen auch alle anderen User dieser Rolle nur die Segmente editieren, die ihnen zugewiesen sind.)',
      deadlineDateInfoTooltip:'#UT#translate5 sendet standardmäßig 2 Tage vor und 2 Tage nach dem festgelegten Datum und der festgelegten Uhrzeit (+/- 10 Minuten) eine Fristerinnerung. Dies kann von Ihrem Administrator geändert werden.'
  },
  viewModel: {
      type: 'taskuserassoc'
  },
  title: '#UT#Benutzer-Plural',
  layout:'border',
  bind:{
      disabled:'{!enablePanel}'
  },
  initConfig: function(instanceConfig) {
    var me = this,
        config;

    config = {
      title: me.title, //see EXT6UPD-9
      items: [{
          xtype: 'adminTaskUserAssocGrid',
          bind:{
              //INFO: this will load only the users of the task when projectTaskGrid selection is changed
              //override the store binding in the place where the component is used/defined
              //the default usage is in the task properties panel
              store:'{userAssoc}'
          },
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
              bind: {
                  html: '{editInfoHtml}'
              }
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
                  xtype:'datetimefield',
                  name: 'deadlineDate',
                  format : Editor.DATE_HOUR_MINUTE_ISO_FORMAT,
                  fieldLabel: me.strings.fieldDeadline,
                  labelCls: 'labelInfoIcon',
                  cls:'userAssocLabelIconField',
                  autoEl: {
                      tag: 'span',
                      'data-qtip': me.strings.deadlineDateInfoTooltip
                  },
                  anchor: '100%'
              },{
                  xtype: 'textfield',
                  itemId: 'segmentrange',
                  name: 'segmentrange',
                  fieldLabel: me.strings.fieldSegmentrange,
                  labelCls: 'labelInfoIcon',
                  cls:'userAssocLabelIconField',
                  autoEl: {
                      tag: 'span',
                      'data-qtip': me.strings.fieldSegmentrangeInfo
                  },
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
                      glyph: 'f00c@FontAwesome5FreeSolid',
                      text: me.strings.btnSave
                  },
                  {
                      xtype: 'button',
                      glyph: 'f00d@FontAwesome5FreeSolid',
                      itemId: 'cancel-assoc-btn',
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