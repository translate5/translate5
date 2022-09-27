
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

    height: 440,
    itemId: 'preferencesWindow',
    width: 460,
    resizable: false, //needed for boxLabel width
    title: '#UT#Einstellungen',
    modal: true,

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
                bind: {
                  fieldLabel: '{l10n.preferences.window.radiogroup}',
                },
                labelAlign: 'top',
                columns: 1,
                anchor: '100%',
                items: [
                  {
                    xtype: 'radiofield',
                    name: 'alikeBehaviour',
                    width: 426, //needed for long labels to wrap
                    bind: {
                      boxLabel: '{l10n.preferences.window.alikeBehaviourAlways}',
                    },
                    inputValue: 'always'
                  },
                  {
                    xtype: 'radiofield',
                    name: 'repetitionType',
                    width: 426,
                    margin: '0 0 0 30',
                    bind: {
                      boxLabel: '{l10n.preferences.window.repetitionTypeSource}',
                    },
                    inputValue: 'source'
                  },
                  {
                    xtype: 'radiofield',
                    name: 'repetitionType',
                    width: 426,
                    margin: '0 0 0 30',
                    bind: {
                      boxLabel: '{l10n.preferences.window.repetitionTypeTarget}',
                    },
                    inputValue: 'target'
                  },
                  {
                    xtype: 'radiofield',
                    name: 'repetitionType',
                    width: 426,
                    margin: '0 0 0 30',
                    bind: {
                      boxLabel: '{l10n.preferences.window.repetitionTypeBothAnd}',
                    },
                    inputValue: 'bothAnd'
                  },
                  {
                    xtype: 'radiofield',
                    name: 'repetitionType',
                    width: 426,
                    margin: '0 0 0 30',
                    bind: {
                      boxLabel: '{l10n.preferences.window.repetitionTypeBothOr}',
                    },
                    inputValue: 'bothOr'
                  }, {
                    xtype: 'checkbox',
                    name: 'sameContextOnly',
                    margin: '0 0 0 30',
                    bind: {
                      boxLabel: '{l10n.preferences.window.sameContextOnly}',
                    },
                  },
                  {
                    xtype: 'radiofield',
                    name: 'alikeBehaviour',
                    width: 426, //needed for long labels to wrap
                    bind: {
                      boxLabel: '{l10n.preferences.window.alikeBehaviourIndividual}',
                    },
                    inputValue: 'individual'
                  },
                  {
                    xtype: 'radiofield',
                    name: 'alikeBehaviour',
                    width: 426, //needed for long labels to wrap
                    bind: {
                      boxLabel: '{l10n.preferences.window.alikeBehaviourNever}',
                    },
                    inputValue: 'never'
                  },{
            	    xtype:'checkbox',
            	    name:'showOnEmptyTarget',
                    bind: {
                      boxLabel: '{l10n.preferences.window.showOnEmptyTarget}',
                    },
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
                bind: {
                  text: '{l10n.preferences.window.saveBtn}',
                }
              },
              {
                xtype: 'button',
                glyph: 'f00d@FontAwesome5FreeSolid',
                bind: {
                  text: '{l10n.preferences.window.cancelBtn}',
                },
                itemId: 'cancelBtn'
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