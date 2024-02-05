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
 * @class Editor.view.admin.config.type.SimpleMap
 * @extends Ext.grid.Panel
 *
 */
Ext.define('Editor.view.admin.config.type.SimpleMap', {
    extend: 'Ext.window.Window',
    alias: 'widget.simplemap',
    requires: [
        'Editor.view.admin.config.type.SimpleMapController'
    ],
    controller: 'configTypeSimpleMap',

    record: null,
    jsonField: null,
    hideTbar: false,
    readonlyIndex: false,
    valueMaxLength: Number.MAX_VALUE,
    preventSave: false,

    /**
     * This statics must be implemented in classes used as custom config editors
     */
    statics: {
        getConfigEditor: function (record) {
            var win = new this(record.isModel ? {record: record} : record);
            win.show();

            //prevent cell editing:
            return null;
        },
        getJsonFieldEditor: function (config) {
            var win = new this(config);
            win.show();

            //prevent cell editing:
            return null;
        },
        renderer: function (value) {
            var res = [];
            Ext.Object.each(value, function (key, item) {
                item = item.toString();
                if (key === item) {
                    res.push(item);
                } else {
                    res.push(key + '-' + item);
                }
            });
            return res.join('; ');
        }
    },
    initConfig: function (instanceConfig) {
        var me = this,
            data = [], config, value;

        if (instanceConfig.record) {
            value = instanceConfig.record.get('value');
        } else if (instanceConfig.jsonField) {
            value = (Ext.JSON.decode(instanceConfig.jsonField.getValue(), true) || {});
        } else {
            value = {};
        }

        Ext.Object.each(value, function (key, value) {
            data.push([key, value]);
        });
        config = {
            bind: {
                title: '{l10n.configuration.title}',
            },
            height: 600,
            modal: true,
            width: 400,
            layout: 'fit',
            bbar: ['->', {
                bind: {
                    text: '{l10n.configuration.save}',
                },
                glyph: 'f00c@FontAwesome5FreeSolid',
                handler: 'onSave'
            }, {
                bind: {
                    text: '{l10n.configuration.cancel}',
                },
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
                tbar: {
                    hidden: instanceConfig.hideTbar,
                    items: [{
                        type: 'button',
                        bind: {
                            text: '{l10n.configuration.add}',
                        },
                        glyph: 'f067@FontAwesome5FreeSolid',
                        handler: 'onAdd'
                    }, {
                        type: 'button',
                        bind: {
                            text: '{l10n.configuration.remove}',
                        },
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        handler: 'onRemove'
                    }]
                },
                columns: [{
                    bind: {
                        text: '{l10n.configuration.index}',
                    },
                    dataIndex: 'index',
                    editor: !(instanceConfig.readonlyIndex || {
                        xtype: 'textfield',
                        itemId: 'index'
                    })
                }, {
                    bind: {
                        text: '{l10n.configuration.value}',
                    },
                    dataIndex: 'value',
                    editor: {
                        xtype: 'textfield',
                        itemId: 'value',
                        maxLength: instanceConfig.valueMaxLength
                    }
                }],
                store: Ext.create('Ext.data.ArrayStore', {
                    data: data,
                    sorters: [{
                        property: 'index',
                        direction: 'DESC'
                    }],
                    fields: [
                        {name: 'index', type: 'string'},
                        {name: 'value', type: 'string'}
                    ]
                })
            }
        };
        if (instanceConfig) {
            config = me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
