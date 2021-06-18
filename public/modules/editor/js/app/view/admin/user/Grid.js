
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

Ext.define('Editor.view.admin.user.Grid', {
  extend: 'Ext.grid.Panel',
  requires: [
      'Editor.view.CheckColumn',
      'Editor.view.admin.user.GridViewController',
      'Editor.view.admin.user.AddWindow'
  ],
  alias: 'widget.adminUserGrid',
  plugins: ['gridfilters'],
  itemId: 'adminUserGrid',
  controller: 'adminUserGrid',
  stateId: 'adminUserGrid',
  stateful: true,
  cls: 'adminUserGrid',
  title: '#UT#Benutzer',
  helpSection: 'useroverview',
  glyph: 'xf0c0@FontAwesome5FreeSolid',
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
      locale: '#UT#Sprache',
      roles: '#UT#Systemrollen',
      openIdIssuer:'#UT#OpenID Emittent'
  },
  strings: {
      addUser: '#UT#Benutzer hinzufügen',
      addUserTip: '#UT#Einen neuen Benutzer hinzufügen.',
      actionEdit: '#UT#Benutzer bearbeiten',
      actionDelete: '#UT#Benutzer löschen',
      actionResetPw: '#UT#Passwort des Benutzers zurücksetzen',
      gender_female: '#UT#weiblich',
      gender_male: '#UT#männlich',
      gender_neutral: '#UT#keine Angabe',
      reloadBtn: '#UT#Aktualisieren',
      reloadBtnTip: '#UT#Benutzerliste vom Server aktualisieren.',
      sourceLangageLabel:'#UT#Quellsprache(n)',
      sourceLangageTip:'#UT#Quellsprache(n)',
      targetLangageLabel:'#UT#Zielsprache(n)',
      targetLangageTip:'#UT#Zielsprache(n)',
      localeTooltip:'#UT#Benutzersprache'
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
  initConfig: function(instanceConfig) {
    var me = this,
        itemFilter = function(item){
            return Editor.app.authenticatedUser.isAllowed(item.isAllowedFor);
        },
    config = {
      title: me.title, //see EXT6UPD-9
      columns: [{
          xtype: 'gridcolumn',
          width: 100,
          dataIndex: 'login',
          stateId: 'login',
          filter: {
              type: 'string'
          },
          text: me.text_cols.login
      },{
          xtype: 'gridcolumn',
          width: 100,
          dataIndex: 'firstName',
          stateId: 'firstName',
          filter: {
              type: 'string'
          },
          text: me.text_cols.firstName
      },{
          xtype: 'gridcolumn',
          width: 100,
          dataIndex: 'surName',
          stateId: 'surName',
          filter: {
              type: 'string'
          },
          text: me.text_cols.surName
      },{
          xtype: 'gridcolumn',
          width: 100,
          dataIndex: 'openIdIssuer',
          stateId: 'openIdIssuer',
          hidden:true,
          filter: {
              type: 'string'
          },
          text: me.text_cols.openIdIssuer
      },{
          xtype: 'gridcolumn',
          width: 60,
          renderer: function(v, meta, rec) {
              var gender = 'neutral';
              switch(v) {
                  case 'm':
                      gender = 'male';
                      break;
                  case 'f':
                      gender = 'female';
                      break;
              }
              meta.tdAttr = 'data-qtip="' + this.strings['gender_'+gender]+'"';
              meta.tdCls = 'gender-'+gender;
              return '&nbsp;';
          },
          dataIndex: 'gender',
          stateId: 'gender',
          filter: {
            type: 'list',
            options: [
                ['n', me.strings.gender_neutral],
                ['m', me.strings.gender_male],
                ['f', me.strings.gender_female]
            ],
            phpMode: false
         },
          text: me.text_cols.gender
      },{
          xtype: 'gridcolumn',
          width: 160,
          dataIndex: 'email',
          stateId: 'email',
          filter: {
              type: 'string'
          },
          text: me.text_cols.email
      },{
          xtype: 'gridcolumn',
          width: 120,
          dataIndex: 'roles',
          stateId: 'roles',
          renderer: function(v) {
              if(!v || v==""){
                  return "";
              }
              return Ext.Array.map(v.split(','), function(item){
                  return Editor.data.app.roles[item].label || item;
              }).join(', ');
          },
          filter: {
              type: 'string'
          },
          text: me.text_cols.roles
      },
        me.getLanguagesConfig('sourceLanguage',me.strings.sourceLangageLabel,me.strings.sourceLangageTip)
      ,
        me.getLanguagesConfig('targetLanguage',me.strings.targetLangageLabel,me.strings.targetLangageTip)
      ,{
          xtype: 'gridcolumn',
          width: 160,
          dataIndex: 'locale',
          stateId: 'locale',
          filter: {
              type: 'string'
          },
          text: me.text_cols.locale,
          tooltip: me.strings.localeTooltip
      },
      {
          xtype: 'actioncolumn',
          stateId:'userGridActionColumn',
          width: 80,
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
              glyph: 'f2f1@FontAwesome5FreeSolid',
              itemId: 'reload-user-btn',
              text: me.strings.reloadBtn,
              tooltip: me.strings.reloadBtnTip
          },{
              xtype: 'button',
              glyph: 'f234@FontAwesome5FreeSolid',
              itemId: 'add-user-btn',
              text: me.strings.addUser,
              hidden: ! Editor.app.authenticatedUser.isAllowed('editorAddUser'), 
              tooltip: me.strings.addUserTip
          }]
      }]
    };

    if (instanceConfig) {
        me.self.getConfigurator().merge(me, config, instanceConfig);
    }
    return me.callParent([config]);
  },

    /**
     * Return the language field configuration for type of language (sourceLanguage,targetLanguage)
     */
    getLanguagesConfig:function(langageType,text,tooltip){
        var field={
                xtype: 'gridcolumn',
                minWidth: 160,
                dataIndex: langageType,
                stateId:langageType,
                renderer: this.userRenderer,
                text:text,
                tooltip: tooltip,
                filter: {
                    type: 'list',
                    idField:'id',
                    labelField:'label',
                    options: Editor.data.languages,
                    phpMode: false
                }
        };
        return field;
    },
    userRenderer: function(value,metaData){
        if(value === null || value.length<1){
            return [];
        }
        var langstore=Ext.getStore('admin.Languages'),
        lang,
        label=[],
        fullLang=[];
        value.forEach(function(v) {
            lang = langstore.findRecord('id',v,0,false,true,true);
            if(lang){
                label.push(lang.get('rfc5646'));
                fullLang.push(lang.get('label'));
            }
        }, this);
        metaData.tdAttr = 'data-qtip="' +fullLang.join('<br/>')+ '"';
        return label.join(', ');
    }
});