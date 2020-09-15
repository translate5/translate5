
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
  listen: {
      component: {
          '#preferencesWindow #saveBtn': {
              click: 'handleSave'
          },
          '#preferencesWindow #cancelBtn': {
              click: 'handleCancel'
          },
          '#segmentgrid #optionsBtn' : {
              click: 'showPreferences'
          }
      }
  },
  init : function() {
    var alike = Editor.data.preferences.alikeBehaviour;
    if(!alike.isModel) {
        Editor.data.preferences.alikeBehaviour = Editor.model.UserConfig.create(alike);
    }
  },
  /**
   * zeigt das Preferences Fenster an
   */
  showPreferences: function() {
      var me = this;
      me.window = new Editor.view.preferences.Window;
      
      //TODO if there will be more preferences, we should refactor that, so that we can loop over form.getValues and instead of 
      // Editor.data.preferences a preferences store is used.
      me.getForm().getForm().setValues({
          alikeBehaviour: Editor.data.preferences.alikeBehaviour.get('value')
      });
      //disable change alike settings if a segment is currently opened. 
      // If not a user would be able to change the change alike behaviour, 
      // while alikes are already loaded or not loaded. This would lead to bugs.
      me.getForm().down('radiogroup').setDisabled(me.getSegmentGrid().editingPlugin.editing);
      me.window.show();
  },
  /**
   * Speichert die Einstellungen und schließt das Fenster
   */
  handleSave: function() {
    var me = this,
        alike = Editor.data.preferences.alikeBehaviour;
    alike.set('value', this.getForm().getForm().getValues().alikeBehaviour);
    alike.save({
        success: function() {
            Editor.MessageBox.addSuccess(me.messages.preferencesSaved);
            me.window.close();
        } 
    });
  },
  handleCancel: function() {
    this.window.close();
  }
});
