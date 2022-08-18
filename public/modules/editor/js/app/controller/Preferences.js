
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
          '#preferencesWindow [name=alikeBehaviour]': {
              change: 'onAlikeBehaviourChange'
          },
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
  /**
   * zeigt das Preferences Fenster an
   */
  showPreferences: function() {
      var me = this;
      me.window = new Editor.view.preferences.Window;
      
      //TODO if there will be more preferences, we should refactor that, so that we can loop over form.getValues and instead of 
      // Editor.data.preferences a preferences store is used.
      me.getForm().getForm().setValues({
          alikeBehaviour: Editor.app.getUserConfig('alike.defaultBehaviour'),
          showOnEmptyTarget: Editor.app.getUserConfig('alike.showOnEmptyTarget'),
          repetitionType: Editor.app.getUserConfig('alike.repetitionType'),
          sameContextOnly: Editor.app.getUserConfig('alike.sameContextOnly')
      });
      //disable change alike settings if a segment is currently opened. 
      // If not a user would be able to change the change alike behaviour, 
      // while alikes are already loaded or not loaded. This would lead to bugs.
      me.getForm().down('radiogroup').setDisabled(me.getSegmentGrid().editingPlugin.editing);
      me.window.show();

      // Refresh form items disability
      me.onAlikeBehaviourChange();
  },
  /**
   * Speichert die Einstellungen und schließt das Fenster
   * 
   * TODO very ugly implementation: should be refactored with TRANSLATE-471
   * Idea: make a store which contains all configs updateable by the user. From that store we can then fill the preferences here
   */
  handleSave: function() {
    var me = this, value,
        alike = Editor.app.getUserConfig('alike.defaultBehaviour',true),
        emptyTarget = Editor.app.getUserConfig('alike.showOnEmptyTarget',true),
        repetitionType = Editor.app.getUserConfig('alike.repetitionType',true),
        sameContextOnly = Editor.app.getUserConfig('alike.sameContextOnly',true);

    // Temporarily enable form items that are currently disabled
    // to make sure their values to be picked by below getValues() call
    me.getForm().query('[disabled=true]').forEach((item) => {item.enable()});

    // Get values
    values = me.getForm().getForm().getValues();

    // Disable back if need
    me.onAlikeBehaviourChange();

    alike.set('value', values.alikeBehaviour);
    emptyTarget.set('value', values.showOnEmptyTarget);
    repetitionType.set('value', values.repetitionType);
    sameContextOnly.set('value', values.sameContextOnly);
    emptyTarget.save({
        success: function() {
            alike.save({
                success: function() {
                    repetitionType.save({
                        success: function() {
                            sameContextOnly.save({
                                success: function() {
                                    me.window.close();
                                }
                            });
                        }
                    });
                }
            });
        } 
    });
  },
  handleCancel: function() {
    this.window.close();
  },

    onAlikeBehaviourChange: function() {
        var me = this, disabled = !me.window.down('[name=alikeBehaviour][inputValue=always]').checked;
        me.window.query('[name/="repetitionType|sameContextOnly"]').forEach(function(item){
            item.setDisabled(disabled);
        });
    }
});
