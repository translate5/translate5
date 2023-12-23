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
    extend: 'Ext.form.Panel',
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

    layout: 'column',

    items: [{
        xtype: 'taskCustomFieldGrid',
        itemId: 'taskCustomFieldGrid',
        columnWidth: 0.65,

        bind: {
            selection: '{customField}'
        }
    }, {
        xtype: 'fieldset',
        fieldDefaults: {
            labelAlign: "left",
            labelWidth: 90,
            anchor: '100%',
            msgTarget: 'side'
        },

        title: 'Custom field details',

        columnWidth: 0.35,
        margin: '0 0 0 10',
        layout: 'anchor',
        defaultType: 'textfield',

        items: [{
            fieldLabel: 'Label',
            bind: '{customField.label}'
        }, {
            fieldLabel: 'Tooltip',
            bind: '{customField.tooltip}'
        }, {
            fieldLabel: 'Type',
            bind: '{customField.type}',
            xtype: 'combobox',
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
            store: {
                fields: ['name', 'value'],
                data: [
                    {name: 'Text', value: 'text'},
                    {name: 'Textarea', value: 'textarea'},
                    {name: 'Boolean', value: 'boolean'},
                    {name: 'Picklist', value: 'picklist'}
                ]
            }
        }, {
            xtype: 'textarea',
            fieldLabel: 'Picklist Data',
            bind: {
                value: '{customField.picklistData}',
                hidden: '{customField.type != "picklist"}'
            }
        }, {
            xtype: 'textarea',
            fieldLabel: 'Regex',
            bind: '{customField.regex}'
        }, {
            xtype: 'combo',
            fieldLabel: 'Mode',
            bind: '{customField.mode}',
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
            store: {
                fields: ['name', 'value'],
                data: [
                    {name: 'Optional', value: 'regular'},
                    {name: 'Required', value: 'required'},
                    {name: 'Hidden', value: 'readonly'}
                ]
            }
        }, {
            xtype: 'tagfield',
            fieldLabel: 'Places to show',
            bind: '{customField.placesToShow}',
            queryMode: 'local',
            displayField: 'name',
            valueField: 'value',
            store: {
                fields: ['name', 'value'],
                data: [
                    {name: 'Project wizard', value: 'projectWizard'},
                    {name: 'Project grid', value: 'projectGrid'},
                    {name: 'Task grid', value: 'taskGrid'}
                ]

            }
        }, {
            xtype: 'numberfield',
            fieldLabel: 'Position',
            bind: '{customField.position}'
        },{
            xtype: 'toolbar',
            dock: 'bottom',
            items: [{
                xtype: 'button',
                glyph: 'f0c7@FontAwesome5FreeSolid',
                text: 'Save',
                handler: 'onSave'
            },{
                xtype: 'button',
                glyph: 'f05e@FontAwesome5FreeSolid',
                text: 'Cancel',
                handler: 'onCancel'
            },{
                xtype: 'button',
                glyph: 'f1f8@FontAwesome5FreeSolid',
                text: 'Delete',
                handler: 'onDelete'
            }]

        }]
    }]
});