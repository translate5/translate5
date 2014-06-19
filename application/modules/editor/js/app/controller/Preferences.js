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
 * Die Einstellungen werden in einem Cookie gespeichert
 * @class Editor.controller.Preferences
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Preferences', {
  extend : 'Ext.app.Controller',
  views: ['preferences.Window'],
  requires: ['Ext.state.CookieProvider'],
  storageKey: 'EditorPreferences',
  initialPreferences: {
    alikeBehaviour: Editor.data.preferences.alikeBehaviour
  },
  messages: {
    preferencesSaved: '#UT#Einstellungen für diese Sitzung gespeichert!'
  },
  window: null,
  refs:[{
      ref : 'segmentGrid',
      selector : '#segmentgrid'
  },{
    ref : 'form',
    selector : '#preferencesWindow .form'
  }],
  init : function() {
    this.control({
      '#preferencesWindow #saveBtn': {
        click: this.handleSave
      },
      '#preferencesWindow #cancelBtn': {
        click: this.handleCancel
      },
      '#segmentgrid #optionsBtn' : {
        click: this.showPreferences
      }
    });
    this.storage = Ext.create('Ext.state.CookieProvider');
    Editor.data.preferences = this.storage.get(this.storageKey);
    if(!Editor.data.preferences){
      Editor.data.preferences = this.initialPreferences;
    }
  },
  /**
   * zeigt das Preferences Fenster an
   */
  showPreferences: function() {
    this.window = new Editor.view.preferences.Window;
    this.getForm().getForm().setValues(Editor.data.preferences);
    //disable change alike settings if a segment is currently opened. 
    // If not a user would be able to change the change alike behaviour, 
    // while alikes are already loaded or not loaded. This would lead to bugs.
    this.getForm().down('.radiogroup').setDisabled(this.getSegmentGrid().editingPlugin.openedRecord !== null);
    this.window.show();
  },
  /**
   * Speichert die Einstellungen und schließt das Fenster
   */
  handleSave: function() {
    Editor.data.preferences = this.getForm().getForm().getValues();
    this.storage.set(this.storageKey, Editor.data.preferences);
    Editor.MessageBox.addSuccess(this.messages.preferencesSaved);
    this.window.close();
  },
  handleCancel: function() {
    this.window.close();
  }
});
