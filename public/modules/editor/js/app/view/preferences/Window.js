
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
 * @class Editor.view.preferences.Window
 * @extends Editor.view.ui.preferences.Window
 * @initalGenerated
 */
Ext.define('Editor.view.preferences.Window', {
    extend: 'Ext.window.Window',

    height: 414,
    itemId: 'preferencesWindow',
    width: 460,
    resizable: false, //needed for boxLabel width
    title: '#UT#Einstellungen',
    modal: true,
    
    //Item Strings:
    item_radiogroup_fieldLabel: 'Verhalten des Wiederholungseditor',
    item_alikeBehaviour_always_boxLabel: 'Immer automatisch ersetzen und Status setzen',
    item_alikeBehaviour_individual_boxLabel: 'Bei jeder Wiederholung einzeln entscheiden',
    item_alikeBehaviour_never_boxLabel: 'Nie automatisch ersetzen und Status setzen',
    item_cancelBtn: 'Abbrechen',
    item_saveBtn: 'Speichern',
    item_show_on_empty_target:'Wiederholung nur automatisch ersetzen bzw. Ersetzung der Wiederholung vorschlagen, wenn Ziel leer ist',
    
    initConfig: function(instanceConfig) {
      var me = this,
      config = {
        title: me.title, //see EXT6UPD-9
        items: [
          {
            xtype: 'form',
            frame: true,
            ui: 'default-framed',
            bodyPadding: 10,
            items: [
              {
                xtype: 'radiogroup',
                fieldLabel: this.item_radiogroup_fieldLabel,
                labelAlign: 'top',
                columns: 1,
                anchor: '100%',
                items: [
                  {
                    xtype: 'radiofield',
                    name: 'alikeBehaviour',
                    width: 426, //needed for long labels to wrap
                    boxLabel: this.item_alikeBehaviour_always_boxLabel,
                    inputValue: 'always'
                  },
                  {
                    xtype: 'radiofield',
                    name: 'repetitionType',
                    width: 426, //needed for long labels to wrap
                    margin: '0 0 0 30',
                    boxLabel: 'Source repetitions',
                    inputValue: 'source'
                  },
                  {
                    xtype: 'radiofield',
                    name: 'repetitionType',
                    width: 426, //needed for long labels to wrap
                    margin: '0 0 0 30',
                    boxLabel: 'Target repetitions',
                    inputValue: 'target'
                  },
                  {
                    xtype: 'radiofield',
                    name: 'repetitionType',
                    width: 426, //needed for long labels to wrap
                    margin: '0 0 0 30',
                    boxLabel: 'Both types',
                    inputValue: 'both'
                  }, {
                    xtype: 'checkbox',
                    name: 'sameContextOnly',
                    margin: '0 0 0 30',
                    boxLabel: 'Same context only',
                  },
                  {
                    xtype: 'radiofield',
                    name: 'alikeBehaviour',
                    width: 426, //needed for long labels to wrap
                    boxLabel: this.item_alikeBehaviour_individual_boxLabel,
                    inputValue: 'individual'
                  },
                  {
                    xtype: 'radiofield',
                    name: 'alikeBehaviour',
                    width: 426, //needed for long labels to wrap
                    boxLabel: this.item_alikeBehaviour_never_boxLabel,
                    inputValue: 'never'
                  },{
            	    xtype:'checkbox',
            	    name:'showOnEmptyTarget',
            	    boxLabel:this.item_show_on_empty_target,
            	    width: 426 //needed for long labels to wrap
                  }
                ]
              }
            ]
          }
        ],
        dockedItems: [
          {
            xtype: 'toolbar',
            ui: 'footer',
            dock: 'bottom',
            layout: {
              pack: 'end',
              type: 'hbox'
            },
            items: [
              {
                xtype: 'button',
                glyph: 'f00c@FontAwesome5FreeSolid',
                itemId: 'saveBtn',
                text: this.item_saveBtn
              },
              {
                xtype: 'button',
                glyph: 'f00d@FontAwesome5FreeSolid',
                itemId: 'cancelBtn',
                text: this.item_cancelBtn
              }
            ]
          }
        ]
      };
      if (instanceConfig) {
          me.self.getConfigurator().merge(me, config, instanceConfig);
      }
      return me.callParent([config]);
    }
});