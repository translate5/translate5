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

Ext.define('Editor.view.segments.SpecialCharacters', {
    extend: 'Ext.container.ButtonGroup',
    alias: 'widget.specialCharacters',
    itemId: 'specialCharacters',

    requires:['Editor.view.segments.SpecialCharactersButton', 'Editor.view.segments.SpecialCharactersButtonTagged'],

    strings:{
        title:'#UT#Sonderzeichen hinzufügen:',
    },

    columns: 8,

    initConfig: function (instanceConfig) {
        var me = this,
            //specialCharacters = Editor.app.getTaskConfig('editor.segments.editorSpecialCharacters'),//TODO: when this config is moved to lvl 16, get the value from task config store
            specialCharacters = Editor.data.editor.segments.editorSpecialCharacters,
            config = {
                title: me.strings.title,
                disabled: true,
                enableOnEdit: true,
                items:[{
                    xtype: 'button',
                    border: 1,
                    style: {
                        borderColor: '#d0d0d0',
                        borderStyle: 'solid'
                    },
                    width:28,
                    height:28,
                    padding:0,
                    text: '→',
                    itemId: 'btnInsertWhitespaceTab',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.chars.tab}'
                    }
                },{
                    xtype: 'button',
                    border: 1,
                    style: {
                        borderColor: '#d0d0d0',
                        borderStyle: 'solid'
                    },
                    width:28,
                    height:28,
                    padding:0,
                    text: '↵',
                    itemId: 'btnInsertWhitespaceNewline',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.chars.shiftEnter}'
                    }
                },{
                    xtype: 'button',
                    border: 1,
                    style: {
                        borderColor: '#d0d0d0',
                        borderStyle: 'solid'
                    },
                    width:28,
                    height:28,
                    padding:0,
                    text: '⎵',
                    itemId: 'btnInsertWhitespaceNbsp',
                    tooltip: 'CTRL+SHIFT+Space',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.chars.ctrlShiftSpace}'
                    }
                }]
            };

        if(specialCharacters){
            me.addSpecialCharactersButtonConfig(specialCharacters,config.items);
        }

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Add special characters buttons from configuration
     * @param specialCharacters
     * @param items
     */
    addSpecialCharactersButtonConfig: function (specialCharacters,items){
        var decoded = Ext.JSON.decode(specialCharacters,true),
            targetLang = Editor.data.task && Ext.getStore('admin.Languages').getById(Editor.data.task.get('targetLang'));

        // get the configured values only for the matching task-target language
        if(!decoded || !targetLang){
            return;
        }
        var matches =  Editor.util.Util.getFuzzyLanguagesForCode(targetLang.get('rfc5646'));

        // in case there are characters defined for all languages using the all key, add them to the matches
        matches.push('all');

        // To keep track of added values
        let addedValues = new Set(), comboData= [];

        Ext.Array.each(matches, function(rec) {
            if(decoded[rec] !== undefined){
                Ext.Array.each(decoded[rec], function(r) {

                    var value = Editor.util.Util.toUnicodeCodePointEscape(r.unicode),
                    hasTag = r.hasOwnProperty('tagInfo');

                    if (!addedValues.has(value)) {

                        if(hasTag){
                            comboData.push({
                                txt: r.visualized,
                                val: r.tagInfo + '|' + r.visualized,
                            });
                        } else {
                            items.push({
                                xtype: 'specialCharactersButton',
                                text: r.visualized,
                                value: Editor.util.Util.toUnicodeCodePointEscape(r.unicode),
                                tooltip: Editor.data.l10n.segmentGrid.toolbar.chars[r.unicode]
                            });
                        }

                        addedValues.add(value);
                    }
                });
            }
        });

        if(comboData.length){
            items.push(
            {   xtype: "container",
                items: [
            {
                xtype: 'combo',
                store: Ext.create('Ext.data.Store', {
                    fields: ['txt', 'val'],
                    data: comboData
                }),
                id: 'specialCharactersCombo',
                emptyText: Editor.data.l10n.general.plsSelect,
                displayField: 'txt',
                valueField: 'val'
            }]});
        }
    }
});