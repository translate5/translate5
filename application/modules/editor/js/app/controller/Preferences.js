
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
    selector : '#preferencesWindow form'
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
    this.getForm().down('radiogroup').setDisabled(this.getSegmentGrid().editingPlugin.openedRecord !== null);
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
