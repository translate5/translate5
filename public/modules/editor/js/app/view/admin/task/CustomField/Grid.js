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
    //plugins: ['cellediting'],
    itemId: 'taskCustomFieldGrid',
    controller: 'taskCustomFieldGridController',
    store: Ext.create('Editor.store.admin.task.CustomField'),
    userCls: 't5actionColumnGrid',
    title: false,
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    /*listeners: {
        beforeedit: 'onBeforeEdit',
        edit: 'onPresetEdit'
    },*/
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
                    emptyText: '{l10n.taskCustomField.grid.search}'
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
                    text: '{l10n.taskCustomField.grid.create}'
                },
                ui: 'default-toolbar-small',
                width: 'auto',
                handler: 'createCustomField'
            },
            {
                xtype: 'button',
                iconCls: 'x-fa fa-undo',
                bind: {
                    text: '{l10n.taskCustomField.grid.refresh}'
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
        },
            {
                xtype: 'gridcolumn',
                width: 260,
                dataIndex: 'label',
                stateId: 'label',
                flex: 1,
                editor: 'textfield',
                //renderer: 'editableCellRenderer',
                bind: {
                    text: '{l10n.taskCustomField.grid.label}'
                }
            },
            {
                xtype: 'gridcolumn',
                width: 260,
                dataIndex: 'tooltip',
                stateId: 'tooltip',
                flex: 1,
                editor: 'textfield',
                //renderer: 'editableCellRenderer',
                bind: {
                    text: '{l10n.taskCustomField.grid.tooltip}'
                }
            },
            {
                xtype: 'gridcolumn',
                width: 360,
                dataIndex: 'type',
                stateId: 'type',
                //renderer: 'editableUnitTypeCellRenderer',
                flex: 1,
                /*editor: {
                    field: {
                        xtype: 'combobox',
                        queryMode: 'local',
                        allowBlank: false,
                        displayField: 'title',
                        valueField: 'value',
                        store: {
                            type: 'json',
                            fields: ['title', 'value'],
                            data: [
                                {title: Editor.data.l10n.taskCustomField.grid.unitType['word']     , value: 'word'},
                                {title: Editor.data.l10n.taskCustomField.grid.unitType['character'], value: 'character'}
                            ]
                        }
                    }
                },*/
                bind: {
                    text: '{l10n.taskCustomField.grid.type.text}'
                }
            },
            {
                xtype: 'gridcolumn',
                alias: 'picklistData',
                dataIndex: 'picklistData',
                stateId: 'picklistData',
                /*editor: {
                    field: {
                        xtype: 'textfield',
                        allowBlank: false,
                        bind: {
                            emptyText: '{l10n.taskCustomField.grid.desc}'
                        }
                    }
                },
                renderer: 'editableCellRenderer',*/
                flex: 3,
                bind: {
                    text: '{l10n.taskCustomField.grid.picklistData}'
                }
            }, {
                xtype: 'gridcolumn',
                dataIndex: 'regex',
                align: 'end',
                bind: {
                    text: '{l10n.taskCustomField.grid.regex}',
                },
                width: 150
            }, {
                xtype: 'gridcolumn',
                dataIndex: 'mode',
                align: 'end',
                bind: {
                    text: '{l10n.taskCustomField.grid.mode}',
                },
                width: 150
            }, {
                xtype: 'gridcolumn',
                dataIndex: 'placesToShow',
                align: 'end',
                bind: {
                    text: '{l10n.taskCustomField.grid.placesToShow}',
                },
                width: 150
            }, {
                xtype: 'numbercolumn',
                dataIndex: 'position',
                align: 'end',
                bind: {
                    text: '{l10n.taskCustomField.grid.position}',
                },
                width: 150,
                //renderer: 'editablePriceAdjustmentCellRenderer',
                editor: {
                    xtype: 'numberfield',
                }
            }, {
                xtype: 'actioncolumn',
                stateId: 'actionColumn',
                align: 'center',
                width: 100,
                bind: {
                    text: '{l10n.taskCustomField.grid.actions.text}'
                },
                menuDisabled: true,
                items: [
                    {
                        bind: {
                            tooltip: '{l10n.taskCustomField.grid.actions.delete}'
                        },
                        tooltip: 'delete',
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        isDisabled: 'isDeleteDisabled',
                        handler: 'deleteCustomField'
                    }
                ]
            }
        ];
        return me.callParent([Ext.apply(config, instanceConfig)]);
    }
});