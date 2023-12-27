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

Ext.define('Editor.view.admin.task.CustomField.Panel', {
    extend: 'Ext.panel.Panel',
    requires: [
        'Editor.view.admin.task.CustomField.Grid',
        'Editor.view.admin.task.CustomField.GridController',
        'Editor.view.admin.task.CustomField.PanelController'
    ],
    alias: 'widget.adminTaskCustomFieldPanel',
    controller: 'adminTaskCustomFieldPanel',

    viewModel: {
        data: {
            customField: null
        }
    },
    width: '100%',
    height: '100%',
    border: 0,
    layout: 'fit',
    items: {
        xtype: 'taskCustomFieldGrid',
        itemId: 'taskCustomFieldGrid',
        border: 0,
        bind: {
            selection: '{customField}'
        }
    },
    dockedItems: {
        xtype: 'form',
        dock: 'right',
        width: 400,
        fieldDefaults: {
            labelAlign: "left",
            labelWidth: 90,
            anchor: '100%',
            msgTarget: 'side',
        },
        defaults: {
            bind: {
                disabled: '{!customField}'
            },
        },
        dockedItems: [{
            xtype: 'toolbar',
            dock: 'top',
            ui: 'default',
            border: 0,
            defaults: {
                width: '33%',
            },
            items: [{
                glyph: 'f0c7@FontAwesome5FreeSolid',
                bind: {
                    text: '{l10n.taskCustomField.save}',
                    disabled: '{!customField}'
                },
                handler: 'onSave'
            }, {
                glyph: 'f05e@FontAwesome5FreeSolid',
                bind: {
                    text: '{l10n.taskCustomField.cancel}',
                    disabled: '{!customField}'
                },
                handler: 'onCancel'
            }, {
                glyph: 'f2ed@FontAwesome5FreeSolid',
                bind: {
                    text: '{l10n.taskCustomField.delete}',
                    disabled: '{!customField}'
                },
                handler: 'onDelete',
            }]
        }],
        bodyPadding: 15,
        margin: 0,
        layout: 'anchor',
        defaultType: 'textfield',

        items: [{
            itemId: 'label',
            allowBlank: false,
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
            value: 'text',
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.type.name}',
                value: '{customField.type}',
                store: {
                    fields: ['name', 'value'],
                    data: '{l10n.taskCustomField.meta.type.data}'
                }
            },
            displayField: 'name',
            valueField: 'value'
        }, {
            xtype: 'textarea',
            itemId: 'comboboxData',
            readOnly: true,
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.comboboxData}',
                value: '{customField.comboboxData}',
                hidden: '{customField.type != "combobox"}'
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
            xtype: 'numberfield',
            bind: {
                fieldLabel: '{l10n.taskCustomField.meta.position}',
                value: '{customField.position}'
            }
        }]
    },
});