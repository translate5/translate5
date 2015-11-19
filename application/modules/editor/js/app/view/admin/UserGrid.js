
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

Ext.define('Editor.view.admin.UserGrid', {
  extend: 'Ext.grid.Panel',
  requires: 'Editor.view.CheckColumn',
  alias: 'widget.adminUserGrid',
  plugins: ['gridfilters'],
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
          filter: {
              type: 'string'
          },
          text: me.text_cols.login
      },{
          xtype: 'gridcolumn',
          width: 100,
          dataIndex: 'firstName',
          filter: {
              type: 'string'
          },
          text: me.text_cols.firstName
      },{
          xtype: 'gridcolumn',
          width: 100,
          dataIndex: 'surName',
          filter: {
              type: 'string'
          },
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
          filter: {
            type: 'list',
            options: [
                ['m', me.strings.gender_male],
                ['f', me.strings.gender_female]
            ],
            phpMode: false
         },
          text: me.text_cols.gender
      },{
          xtype: 'gridcolumn',
          width: 120,
          dataIndex: 'email',
          filter: {
              type: 'string'
          },
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
          filter: {
              type: 'string'
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