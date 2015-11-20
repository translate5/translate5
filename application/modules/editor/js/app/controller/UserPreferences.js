
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.controller.UserPreferences
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.UserPreferences', {
    extend : 'Ext.app.Controller',
    views: ['preferences.UserWindow'],
    strings: {
        settings: '#UT# Meine Einstellungen',
        pwSave: '#UT#Passwort erfolgreich gespeichert!'
    },
    window: null,
    refs:[{
        ref : 'topMenu',
        selector : 'headPanel #top-menu'
    },{
        ref : 'form',
        selector : '#preferencesUserWindow form'
    }],
    init : function() {
        var me = this;
        me.control({
            'headPanel': {
                afterrender: me.handleRenderHeadPanel
            },
            '#preferencesUserWindow #saveBtn': {
                click: me.handleSave
            },
            '#preferencesUserWindow #cancelBtn': {
                click: me.handleCancel
            },
            '#mySettingsBtn': {
                click: me.showPreferences
            }
        });
    },
    handleRenderHeadPanel: function() {
        var pos = this.getTopMenu().items.length - 1;
        this.getTopMenu().insert(pos, {
            xtype: 'button',
            itemId: 'mySettingsBtn',
            text: this.strings.settings
        });
    },
  /**
   * zeigt das Preferences Fenster an
   */
  showPreferences: function() {
      var me = this;
      me.window = Ext.create('Editor.view.preferences.UserWindow');
      me.getForm().setDisabled(! Editor.app.authenticatedUser.get('editable'));
      me.window.show();
  },
  /**
   * Speichert die Einstellungen und schließt das Fenster
   */
  handleSave: function() {
      var me = this, 
          form = me.getForm().getForm(),
          pw = form.getValues().passwd,
          user = Editor.app.authenticatedUser;
      if(form.isValid()) {
          user.set('passwd', pw);
          user.save({
              url: Editor.data.restpath+'user/authenticated',
              success: function() {
                  Editor.MessageBox.addSuccess(me.strings.pwSave);
              }
          });
          me.window.close();
      }
  },
  handleCancel: function() {
    this.window.close();
  }
});
