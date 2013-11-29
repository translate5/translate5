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
        pwSave: '#UT#Passwort erfolgreich gespeichert!',
    },
    window: null,
    refs:[{
        ref : 'topMenu',
        selector : 'headPanel #top-menu'
    },{
        ref : 'form',
        selector : '#preferencesUserWindow .form'
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
