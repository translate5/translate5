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

var numberRecognitionClone;
Ext.define('Editor.view.admin.numberProtection.outputMapping.CreateWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.adminCreateOutputMappingWindow',
    itemId: 'adminCreateOutputMappingWindow',
    cls: 'adminCreateOutputMappingWindow',
    requires: [
        'Editor.view.admin.numberProtection.outputMapping.CreateWindowViewController'
    ],
    controller: 'adminCreateOutputMappingWindowViewController',
    modal: true,
    layout: 'fit',
    firstInit: true,
    initComponent: function () {
        this.callParent(arguments);
        if (this.firstInit) {
            this.firstInit = false;
            this.initConfig();
        }
    },
    initConfig: function (instanceConfig) {
        var me = this,
            config;
        //     numberRecognition = Ext.getStore('admin.numberProtection.NumberRecognitionStore'),
        //     inputMapping = Ext.getStore('admin.numberProtection.InputMappingStore'),
        //     // Define a Set to store unique values
        //     namesInMapping = new Set();
        //     numberRecognitionClone = Ext.create('Ext.data.Store', {
        //         storeId: 'namesStoreId',
        //         model: numberRecognition.getModel(),
        //         data: numberRecognition.getRange()
        //     });
        //
        // inputMapping.each(function (record) {
        //     console.log(record.get('name'))
        //     namesInMapping.add(record.get('name'));
        // });
        //
        // console.log(namesInMapping.size)
        //
        // numberRecognitionClone.filter({
        //     id: 'nameIsInInputMapping',
        //     filterFn: recognition => namesInMapping.has(recognition.get('name'))
        // });
        //
        // numberRecognitionClone.each(r => console.log(r.get('name')))

        config = {
            title: Editor.data.l10n.numberProtection.mapping.create_title,
            layout: {
                type: 'fit'
            },
            items: [
                {
                    xtype: 'form',
                    reference: 'form',
                    padding: 5,
                    ui: 'default-frame',
                    scrollable: 'vertical',
                    defaults: {
                        labelWidth: 160,
                        width: 480,
                        anchor: '100%'
                    },
                    items: [
                        {
                            xtype: 'combo',
                            itemId: 'typeField',
                            name: 'type',
                            ref: 'typeField',
                            allowBlank: false,
                            typeAhead: true,
                            anyMatch: true,
                            forceSelection: true,
                            displayField: 'type',
                            valueField: 'type',
                            store: {
                                fields: ['type'],
                                data: [{
                                    type: 'date'
                                },{
                                    type: 'float'
                                },{
                                    type: 'integer'
                                }]
                            },
                            listeners: {
                                change: (fld, newValue) => fld.up('form').down('#numberRecognitionId').getStore().filter('type', newValue)
                            },
                            bind: {
                                fieldLabel: '{l10n.general.type}'
                            }
                        },
                        {
                            xtype: 'combo',
                            itemId: 'numberRecognitionId',
                            name: 'numberRecognitionId',
                            ref: 'numberRecognitionId',
                            allowBlank: false,
                            typeAhead: true,
                            anyMatch: true,
                            forceSelection: true,
                            displayField: 'name',
                            valueField: 'id',
                            bind: {
                                fieldLabel: '{l10n.general.name}'
                            },
                            store: {
                                type: 'numberProtection.outputMapping.NumberRecognitionComboMapping'
                            }
                        },
                        {
                            xtype: 'combo',
                            itemId: 'languageId',
                            name: 'languageId',
                            allowBlank: false,
                            typeAhead: true,
                            anyMatch: true,
                            forceSelection: true,
                            displayField: 'label',
                            valueField: 'id',
                            bind: {
                                fieldLabel: '{l10n.general.language}'
                            },
                            store: {
                                type: 'languagestore'
                            }
                        },
                        {
                            xtype: 'textfield',
                            name: 'format',
                            allowBlank: true,
                            bind: {
                                fieldLabel: '{l10n.general.format}'
                            }
                        }
                    ]
                }
            ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    layout: {
                        type: 'hbox',
                        pack: 'start'
                    },
                    items: [
                        {
                            xtype: 'tbfill'
                        },
                        {
                            xtype: 'button',
                            glyph: 'f234@FontAwesome5FreeSolid',
                            itemId: 'create-btn',
                            bind: {
                                text: '{l10n.configuration.add}'
                            }
                        },
                        {
                            xtype: 'button',
                            glyph: 'f00d@FontAwesome5FreeSolid',
                            itemId: 'cancel-btn',
                            bind: {
                                text: '{l10n.configuration.cancel}'
                            }
                        }
                    ]
                }
            ]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});