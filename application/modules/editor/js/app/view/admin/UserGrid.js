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
Ext.define('Editor.view.admin.UserGrid', {
  extend: 'Ext.grid.Panel',
  requires: 'Editor.view.CheckColumn',
  alias: 'widget.adminUserGrid',
  itemId: 'adminUserGrid',
  cls: 'adminUserGrid',
  title: '#UT#Benutzerübersicht',
  height: '100%',
  layout: {
      type: 'fit'
  },
  text_cols: {
      login: '#UT#Login',
      firstName: '#UT#Vorname',
      surName: '#UT#Nachname',
      gender: '#UT#Geschlecht',
      email: '#UT#E-Mail',
      roles: '#UT#Systemrollen'
  },
  strings: {
      addUser: '#UT#Benutzer hinzufügen',
      addUserTip: '#UT#Einen neuen Benutzer hinzufügen.',
      actionEdit: '#UT#Benutzer bearbeiten',
      actionDelete: '#UT#Benutzer löschen',
      actionResetPw: '#UT#Passwort des Benutzers zurücksetzen',
      gender_female: '#UT#weiblich',
      gender_male: '#UT#männlich',
      reloadBtn: '#UT#Aktualisieren',
      reloadBtnTip: '#UT#Benutzerliste vom Server aktualisieren.'
  },
  store: 'admin.Users',
  //TODO define filters for the user grid!
  //features: [{
    //ftype: 'editorGridFilter'
  //}],
  viewConfig: {
      /**
       * returns a specific row css class
       * @param {Editor.model.admin.User} user
       * @return {Boolean}
       */
      getRowClass: function(user) {
          if(!user.get('editable')) {
              return 'not-editable';
          }
          return '';
      }
  },
  initComponent: function() {
    var me = this,
        itemFilter = function(item){
            return Editor.app.authenticatedUser.isAllowed(item.isAllowedFor);
        };
    Ext.applyIf(me, {
      columns: [{
          xtype: 'gridcolumn',
          width: 100,
          dataIndex: 'login',
          text: me.text_cols.login
      },{
          xtype: 'gridcolumn',
          width: 100,
          dataIndex: 'firstName',
          text: me.text_cols.firstName
      },{
          xtype: 'gridcolumn',
          width: 100,
          dataIndex: 'surName',
          text: me.text_cols.surName
      },{
          xtype: 'gridcolumn',
          width: 60,
          renderer: function(v, meta, rec) {
              var gender = (v == 'm' ? 'male' : 'female');
              meta.tdAttr = 'data-qtip="' + this.strings['gender_'+gender]+'"';
              meta.tdCls = 'gender-'+gender;
              return '&nbsp;';
          },
          dataIndex: 'gender',
          text: me.text_cols.gender
      },{
          xtype: 'gridcolumn',
          width: 120,
          dataIndex: 'email',
          text: me.text_cols.email
      },{
          xtype: 'gridcolumn',
          width: 120,
          dataIndex: 'roles',
          renderer: function(v) {
              return Ext.Array.map(v.split(','), function(item){
                  return Editor.data.app.roles[item] || item;
              }).join(', ');
          },
          text: me.text_cols.roles
      },{
          xtype: 'actioncolumn',
          width: 60,
          items: Ext.Array.filter([{
              tooltip: me.strings.actionEdit,
              isAllowedFor: 'editorEditUser',
              iconCls: 'ico-user-edit'
          },{
              tooltip: me.strings.actionDelete,
              isAllowedFor: 'editorDeleteUser',
              iconCls: 'ico-user-delete'
          },{
              tooltip: me.strings.actionResetPw,
              isAllowedFor: 'editorResetPwUser',
              iconCls: 'ico-user-reset-pw'
          }], itemFilter)
      }],
      dockedItems: [{
          xtype: 'toolbar',
          dock: 'top',
          items: [{
              xtype: 'button',
              iconCls: 'ico-user-add',
              itemId: 'add-user-btn',
              text: me.strings.addUser,
              hidden: ! Editor.app.authenticatedUser.isAllowed('editorAddUser'), 
              tooltip: me.strings.addUserTip
          },{
              xtype: 'button',
              iconCls: 'ico-refresh',
              itemId: 'reload-user-btn',
              text: me.strings.reloadBtn,
              tooltip: me.strings.reloadBtnTip
          }]
      },{
          xtype: 'pagingtoolbar',
          store: 'admin.Users',
          dock: 'bottom',
          displayInfo: true
      }]
    });

    me.callParent(arguments);
  }
});