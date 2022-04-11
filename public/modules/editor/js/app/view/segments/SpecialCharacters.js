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

    requires:['Editor.view.segments.SpecialCharactersButton'],

    strings:{
        title:'#UT#Sonderzeichen hinzufügen:'
    },

    columns: 8,

    initConfig: function (instanceConfig) {
        var me = this,
            //specialCharacters = Editor.app.getTaskConfig('editor.segments.editorSpecialCharacters'),//TODO: when this config is moved to lvl 16, get the value from task config store
            specialCharacters = Editor.data.editor.segments.editorSpecialCharacters,
            config = {
                title: me.strings.title,
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
                    tooltip: 'TAB'
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
                    tooltip: 'SHIFT+ENTER'
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
                    tooltip: 'CTRL+SHIFT+Space'
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
        var record = Ext.JSON.decode(specialCharacters,true),
            targetLang = Editor.data.task && Ext.getStore('admin.Languages').getById(Editor.data.task.get('targetLang'));

        // get the configured values only for the matching task-target language
        if(!record || !targetLang || record[targetLang.get('rfc5646')] === undefined){
            return;
        }
        Ext.Array.each(record[targetLang.get('rfc5646')], function(rec) {
            items.push({
                xtype:'specialCharactersButton',
                text: rec.visualized,
                value: Editor.util.Util.toUnicodeCodePointEscape(rec.unicode)
            });
        });
    }
});