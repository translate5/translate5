
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

/**
 * Translations: since all the configurations are not translated, we just keep the text here also just in english
 * @class Editor.plugins.MatchAnalysis.view.FuzzyBoundaryConfig
 * @extends Ext.grid.Panel
 *
 */
Ext.define('Editor.plugins.MatchAnalysis.view.FuzzyBoundaryConfig', {
    extend: 'Ext.window.Window',
    requires: [
        'Editor.plugins.MatchAnalysis.view.FuzzyBoundaryConfigController'
    ],
    controller: 'pluginMatchAnalysisBoundaryConfig',

    record: null,

    strings: {
        title: 'Edit the match analysis boundaries',
        start: 'Start',
        end: 'End',
        add: 'add',
        save: 'Save',
        cancel: 'Cancel',
        remove: 'remove',
        beginBiggerEnd: 'Start value must be lesser or equal to end value!',
        endLesserBegin: 'End value must be bigger or equal to start value!'
    },
    /**
     * This statics must be implemented in classes used as custom config editors
     */
    statics: {
        getConfigEditor: function(record) {
            var win = new this({record: record});
            win.show();

            //prevent cell editing:
            return null;
        },
        renderer: function(value) {
            var res = [];
            Ext.Object.each(value, function(key, item){
                item = item.toString();
                if(key === item) {
                    res.push(item);
                }
                else {
                    res.push(key+'-'+item);
                }
            });
            return res.join('; ');
        }
    },
    initConfig: function(instanceConfig) {
        var me = this,
            data = [], config,
            percent = function(v){return v+'%';};

        Ext.Object.each(instanceConfig.record.get('value'), function(key, value) {
            data.push([parseInt(key), parseInt(value)]);
        });
        config = {
            title: me.strings.title,
            height: 600,
            modal: true,
            width: 400,
            layout: 'fit',
            bbar: ['->', {
                text: me.strings.save,
                glyph: 'f00c@FontAwesome5FreeSolid',
                handler: 'onSave'
            },{
                text: me.strings.cancel,
                glyph: 'f00d@FontAwesome5FreeSolid',
                handler: 'onCancel'
            }],
            items: {
                xtype: 'grid',
                selModel: 'rowmodel',
                plugins: [{
                    ptype: 'rowediting',
                    clicksToEdit: 2
                }],
                border: false,
                tbar: [{
                    type: 'button',
                    text: me.strings.add,
                    glyph: 'f067@FontAwesome5FreeSolid',
                    handler: 'onAdd'
                },{
                    type: 'button',
                    text: me.strings.remove,
                    glyph: 'f2ed@FontAwesome5FreeSolid',
                    handler: 'onRemove'
                }],
                columns: [{
                    header: me.strings.start,
                    dataIndex: 'begin',
                    renderer: percent,
                    editor: {
                        xtype: 'numberfield',
                        itemId: 'begin',
                        minValue: 0,
                        maxValue: 104,
                        validator: function(value) {
                            var end = this.ownerCt.down('numberfield#end');
                            if(end && end.getValue() < value) {
                                return me.strings.beginBiggerEnd;
                            }
                            return true;
                        }
                    }
                },{
                    header: me.strings.end,
                    dataIndex: 'end',
                    renderer: percent,
                    editor: {
                        xtype: 'numberfield',
                        itemId: 'end',
                        minValue: 0,
                        maxValue: 104,
                        validator: function(value) {
                            var begin = this.ownerCt.down('numberfield#begin');
                            if(begin && begin.getValue() > value) {
                                return me.strings.endLesserBegin;
                            }
                            return true;
                        }
                    }
                }],
                store: Ext.create('Ext.data.ArrayStore', {
                    data: data,
                    sorters: [{
                        property: 'end',
                        direction: 'DESC'
                    }],
                    fields: [
                        {name: 'begin', type: 'integer'},
                        {name: 'end', type: 'integer'}
                    ]
                })
            }
        };
        if (instanceConfig) {
            config=me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});