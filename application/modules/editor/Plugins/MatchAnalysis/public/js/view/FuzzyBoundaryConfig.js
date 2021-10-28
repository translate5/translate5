
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
 * Translations: since all the configurations are not translated, we just keep the text here also just in english
 * @class Editor.plugins.MatchAnalysis.view.FuzzyBoundaryConfig
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.plugins.MatchAnalysis.view.FuzzyBoundaryConfig', {
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
     * on save button click
     * @param {Ext.btn.Button} btn
     */
    onSave: function(btn) {
        var win = btn.up('window'),
            grid = win.down('grid'),
            newValue = {},
            confRec = this.record;

        grid.store.each(function(rec) {
            newValue[rec.get('begin')] = rec.get('end');
        });
        confRec.set('value', newValue);
        win.setLoading('saving...');
        confRec.save({
            success: function() {
                win.setLoading(false);
                win.close();
            },
            failure: function() {
                win.setLoading(false);
            }
        });
    },
    /**
     * on cancel click button
     * @param {Ext.btn.Button} btn
     */
    onCancel: function(btn) {
        this.record.reject();
        btn.up('window').close();
    },
    /**
     * on remove click button
     * @param {Ext.btn.Button} btn
     */
    onRemove: function(btn) {
        var win = btn.up('window'),
            grid = win.down('grid'),
            selMod = grid.getSelectionModel();

        grid.findPlugin('rowediting').cancelEdit();

        grid.store.remove(selMod.getSelection());

        if (grid.store.getCount() > 0) {
            selMod.select(0);
        }
    },
    onAdd: function(btn) {
        var win = btn.up('window'),
            grid = win.down('grid'),
            newVal = Math.min(Math.max(...grid.store.collect('end')) + 1, 104),
            rec;
        
        rec = grid.store.insert(0, {
            begin: 0,
            end: 0
        })[0];
        //we set the values after creation, so that the record looks dirty
        rec.set('begin', newVal);
        rec.set('end', newVal);
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
    },

    getConfigEditor : function(record) {
        var me = this,
            data = [],
            percent = function(v){return v+'%';};
        this.record = record;
        Ext.Object.each(record.get('value'), function(key, value) {
            data.push([parseInt(key), parseInt(value)]);
        });

        Ext.create('Ext.window.Window', {
            title: me.strings.title,
            height: 600,
            modal: true,
            width: 400,
            layout: 'fit',
            bbar: ['->', {
                text: me.strings.save,
                glyph: 'f00c@FontAwesome5FreeSolid',
                scope: this,
                handler: this.onSave
            },{
                text: me.strings.cancel,
                glyph: 'f00d@FontAwesome5FreeSolid',
                scope: this,
                handler: this.onCancel
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
                    scope: this,
                    handler: this.onAdd
                },{
                    type: 'button',
                    text: me.strings.remove,
                    glyph: 'f2ed@FontAwesome5FreeSolid',
                    scope: this,
                    handler: this.onRemove
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
        }).show();

        //prevent cell editing
        return null;
    }
});