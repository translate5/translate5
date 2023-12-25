/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Lists and manages the available pricing presets to choose from when creating a task
 */
Ext.define('Editor.view.admin.task.CustomField.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.admin.task.CustomField.GridController',
        'Editor.store.admin.task.CustomField'
    ],
    alias: 'widget.taskCustomFieldGrid',
    itemId: 'taskCustomFieldGrid',
    controller: 'taskCustomFieldGridController',
    store: Ext.create('Editor.store.admin.task.CustomField'),
    userCls: 't5actionColumnGrid',
    title: false,
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    dockedItems: [{
        xtype: 'toolbar',
        dock: 'top',
        enableOverflow: true,
        items: [
            {
                xtype: 'textfield',
                width: 300,
                minWidth: 100,
                bind: {
                    emptyText: '{l10n.taskCustomField.search}'
                },
                triggers: {
                    clear: {
                        cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                        handler: function (field) {
                            field.setValue(null);
                            field.focus();
                        },
                        hidden: true
                    }
                },
                listeners: {
                    change: 'filterByKeyword',
                    buffer: 150
                }
            },
            {
                xtype: 'button',
                glyph: 'f067@FontAwesome5FreeSolid',
                bind: {
                    text: '{l10n.taskCustomField.create}'
                },
                ui: 'default-toolbar-small',
                width: 'auto',
                handler: 'createCustomField'
            },
            {
                xtype: 'button',
                iconCls: 'x-fa fa-undo',
                bind: {
                    text: '{l10n.taskCustomField.refresh}'
                },
                handler: function (btn) {
                    btn.up('grid').getStore().reload();
                }
            },
            {
                xtype: 'tbspacer',
                flex: 1.6,
            },
        ],
    }],
    viewConfig: {
        enableTextSelection: true,
    },
    initConfig: function (instanceConfig) {
        var me = this,
            config = {};
        config.title = me.title; //see EXT6UPD-9
        config.userCls = 't5actionColumnGrid t5noselectionGrid';
        config.columns = [{
            xtype: 'gridcolumn',
            dataIndex: 'id',
            text: 'Id',
            width: 40,
            renderer: 'idRenderer'
        },
            {
                xtype: 'gridcolumn',
                dataIndex: 'label',
                stateId: 'label',
                flex: 1,
                editor: 'textfield',
                renderer: 'l10nRenderer',
                bind: {
                    text: '{l10n.taskCustomField.meta.label}'
                }
            },
            {
                xtype: 'gridcolumn',
                dataIndex: 'tooltip',
                stateId: 'tooltip',
                flex: 1,
                editor: 'textfield',
                renderer: 'l10nRenderer',
                bind: {
                    text: '{l10n.taskCustomField.meta.tooltip}'
                }
            },
            {
                xtype: 'gridcolumn',
                minWidth: 100,
                dataIndex: 'type',
                stateId: 'type',
                renderer: 'typeRenderer',
                bind: {
                    text: '{l10n.taskCustomField.meta.type.name}'
                }
            },
            {
                xtype: 'gridcolumn',
                alias: 'picklistData',
                dataIndex: 'picklistData',
                stateId: 'picklistData',
                hidden: true,
                flex: 3,
                bind: {
                    text: '{l10n.taskCustomField.meta.picklistData}'
                }
            }, {
                xtype: 'gridcolumn',
                dataIndex: 'regex',
                bind: {
                    text: '{l10n.taskCustomField.meta.regex}',
                },
                width: 150
            }, {
                xtype: 'gridcolumn',
                dataIndex: 'mode',
                bind: {
                    text: '{l10n.taskCustomField.meta.mode.name}',
                },
                renderer: 'modeRenderer',
                width: 100
            }, {
                xtype: 'gridcolumn',
                dataIndex: 'placesToShow',
                bind: {
                    text: '{l10n.taskCustomField.meta.placesToShow.name}',
                },
                renderer: 'placesToShowRenderer',
                width: 250,
            }, {
                xtype: 'numbercolumn',
                dataIndex: 'position',
                align: 'end',
                bind: {
                    text: '{l10n.taskCustomField.meta.position}',
                },
                format: '0',
                width: 100
            }
        ];
        return me.callParent([Ext.apply(config, instanceConfig)]);
    }
});