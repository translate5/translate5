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
    border: 0,
    viewModel: {
        data: {
            customField: null
        }
    },
    bind: {
        selection: '{customField}'
    },

    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: 'taskCustomFields',
    dockedItems: [{
        xtype: 'form',
        dock: 'right',
        width: 450,
        fieldDefaults: {
            labelAlign: "left",
            labelWidth: 150,
            anchor: '100%',
            msgTarget: 'side',
        },
        defaults: {
            //disabled: true,
            bind: {
                //disabled: '{!customField || customField.mode == "readonly"}'
            },
        },
        dockedItems: [{
            xtype: 'toolbar',
            dock: 'top',
            ui: 'default',
            border: 0,
            defaults: {
                width: '33%',
                //disabled: true
            },
            items: [{
                glyph: 'f0c7@FontAwesome5FreeSolid',
                bind: {
                    text: '{l10n.taskCustomField.save}',
                    //disabled: '{!customField || customField.mode == "readonly"}'
                },
                handler: 'onSave'
            }, {
                glyph: 'f05e@FontAwesome5FreeSolid',
                bind: {
                    text: '{l10n.taskCustomField.cancel}',
                    disabled: '{!customField.id || customField.mode == "readonly"}'
                },
                handler: 'onCancel'
            }, {
                glyph: 'f2ed@FontAwesome5FreeSolid',
                bind: {
                    text: '{l10n.taskCustomField.delete.button}',
                    //disabled: '{!customField || customField.mode == "readonly"}'
                },
                handler: 'onDelete',
            }]
        }],
        bodyPadding: 15,
        margin: 0,
        overflowY: 'auto',
        layout: 'anchor',
        defaultType: 'textfield',

        items: [{
            itemId: 'label',
            readOnly: true,
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.label}',
                value: '{customField.label}'
            }
        }, {
            itemId: 'tooltip',
            readOnly: true,
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.tooltip}',
                value: '{customField.tooltip}'
            }
        }, {
            xtype: 'combobox',
            forceSelection: true,
            allowBlank: false,
            queryMode: 'local',
            value: 'textfield',
            itemId: 'type',
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.type.name}',
                value: '{customField.type}',
                disabled: '{!customField || customField.id}',
                store: {
                    fields: ['name', 'value'],
                    data: '{l10n.taskCustomField.meta.type.data}'
                }
            },
            displayField: 'name',
            valueField: 'value'
        }, {
            xtype: 'grid',
            height: 200,
            itemId: 'comboboxDataGrid',
            selModel: 'cellmodel',
            plugins: [{
                ptype: 'cellediting',
                clicksToEdit: 1
            }],
            tbar: [{
                bind: {
                    text: '{l10n.configuration.add}',
                },
                glyph: 'f067@FontAwesome5FreeSolid',
                handler: 'onComboboxOptionAdd'
            }, {
                bind: {
                    text: '{l10n.configuration.remove}',
                },
                glyph: 'f2ed@FontAwesome5FreeSolid',
                handler: 'onComboboxOptionRemove'
            }],
            columns: [{
                dataIndex: 'index',
                text: '-',
                bind: {
                    text: '{l10n.taskCustomField.meta.comboboxData.value}',
                },
                getEditor: record => record.modified && 'index' in record.modified
                    ? {xtype: 'textfield', maxLength: 10}
                    : false
            }, {
                dataIndex: 'value',
                flex: 1,
                text: '-',
                bind: {
                    text: '{l10n.taskCustomField.meta.comboboxData.title}',
                },
                renderer: value => Editor.view.admin.config.type.SimpleMap.renderer(value),
                getEditor: record => Editor.view.admin.config.type.SimpleMap.getConfigEditor({
                    record: record,
                    hideTbar: true,
                    readonlyIndex: true,
                    preventSave: true
                })
            }],
            margin: '0 0 10 0',
            width: '100%',
            border: 1,
            hidden: true,
            bind: {
                hidden: '{!customField || customField.type != "combobox"}'
            },
            store: {
                type: 'json',
                storeId: 'comboboxDataStore',
                fields: ['index', 'value'],
                data: []
            }
        }, {
            xtype: 'textarea',
            itemId: 'comboboxData',
            readOnly: true,
            hidden: true,
            bind: {
                value: '{customField.comboboxData}',
            }
        }, {
            xtype: 'textfield',
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.regex}',
                value: '{customField.regex}',
                hidden: '{customField.type == "combobox" || customField.type == "checkbox"}'
            }
        }, {
            xtype: 'combo',
            forceSelection: true,
            allowBlank: false,
            value: 'optional',
            itemId: 'mode',
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.mode.name}',
                value: '{customField.mode}',
                store: {
                    fields: ['name', 'value'],
                    data: '{l10n.taskCustomField.meta.mode.data}'
                }
            },
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
        }, {
            xtype: 'tagfield',
            forceSelection: true,
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.placesToShow.name}',
                value: '{customField.placesToShow}',
                store: {
                    fields: ['name', 'value'],
                    data: '{l10n.taskCustomField.meta.placesToShow.data}'
                }
            }
        }, {
            xtype: 'checkboxgroup',
            itemId: 'roles',
            cls: 'x-check-group-alt',
            labelAlign: 'top',
            name: 'roles',
            columns: 2,
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.roles.name} &#8505;',
                value: '{customField.roles}',
            },
            autoEl: {
                tag: 'div',
                'data-qtip': Ext.String.htmlEncode(Editor.data.l10n.taskCustomField.meta.roles.tooltip)
            }
        }, {
            xtype: 'numberfield',
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.position}',
                value: '{customField.position}'
            }
        }],
        weight: 1,
    }, {
        xtype: 'toolbar',
        dock: 'top',
        weight: 2,
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
                alias: 'comboboxData',
                dataIndex: 'comboboxData',
                stateId: 'comboboxData',
                hidden: true,
                flex: 3,
                bind: {
                    text: '{l10n.taskCustomField.meta.comboboxData}'
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