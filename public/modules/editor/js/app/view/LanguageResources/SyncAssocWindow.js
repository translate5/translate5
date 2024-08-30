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

Ext.define('Editor.view.LanguageResources.SyncAssocWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.languageResourceSyncAssocWindow',
    requires: [
        'Editor.view.LanguageResources.TmWindowViewModel',
        'Editor.model.LanguageResources.SyncAssoc',
        'Editor.store.LanguageResources.SyncAssocStore',
        'Editor.view.LanguageResources.SyncAssocWindowViewController',
    ],
    xtype: 'associationwindow',
    bind: {
        title: '{l10n.crossLanguageResourceSynchronization.confirmSynchronisation}',
    },
    width: 800,
    height: 400,
    modal: true,
    controller: 'languageResourceSyncAssocWindow',
    items: [
        {
            xtype: 'container',
            region: 'north',
            height: 'auto',
            layout: {
                type: 'hbox',
            },
            items: [
                {
                    xtype: 'form',
                    reference: 'associationForm',
                    bodyPadding: 10,
                    layout: {
                        type: 'hbox',
                    },
                    hidden: true,
                    defaults: {
                        margin: '0 15 0 0',
                    },
                    items: [
                        {
                            xtype: 'combo',
                            bind: {
                                fieldLabel: '{l10n.general.targetLanguageResource}',
                            },
                            name: 'connectionOption',
                            store: {
                                xtype: 'store',
                                fields: ['id', 'name'],
                                data: [] // Initially empty, will be set dynamically
                            },
                            queryMode: 'local',
                            displayField: 'name',
                            valueField: 'id',
                            allowBlank: false,
                            width: 300,
                            listConfig: {
                                getInnerTpl: function () {
                                    return '<div style="white-space: nowrap; overflow: visible;">{[Ext.String.htmlEncode(values.name)]}</div>'; // Prevent text wrapping
                                }
                            },
                            listeners: {
                                afterrender: function(combo) {
                                    var store = combo.getStore();
                                    store.on('refresh', function() {
                                        var longestText = '';
                                        store.each(function(record) {
                                            var text = record.get(combo.displayField);
                                            if (text.length > longestText.length) {
                                                longestText = text;
                                            }
                                        });

                                        // Create a temporary element to calculate the width of the longest text
                                        var tempEl = Ext.getBody().createChild({
                                            tag: 'div',
                                            html: longestText,
                                            style: {
                                                'position': 'absolute',
                                                'visibility': 'hidden',
                                                'font-family': combo.getEl().getStyle('font-family'),
                                                'font-size': combo.getEl().getStyle('font-size')
                                            }
                                        });

                                        var textWidth = tempEl.getWidth() + 10; // Add some padding
                                        tempEl.destroy(); // Remove the temporary element

                                        combo.listConfig.minWidth = textWidth > combo.width ? textWidth : combo.width;
                                    });
                                }
                            }
                        },
                        {
                            xtype: 'button',
                            glyph: 'f0c1@FontAwesome5FreeSolid',
                            text: 'Connect',
                            handler: 'onAddAssociation',
                            margin: '5 0',
                        }
                    ]
                },
                {
                    xtype: 'tbspacer',
                    flex: 1
                },
                {
                    xtype: 'button',
                    hidden: true,
                    glyph: 'f021@FontAwesome5FreeSolid',
                    reference: 'queueSynchronizeAll',
                    bind: {
                        text: '{l10n.crossLanguageResourceSynchronization.queueSynchronizeAll}',
                    },
                    handler: 'queueSynchronizeAll',
                    margin: 15,
                }
            ]
        },
        {
            xtype: 'grid',
            reference: 'associationGrid',
            flex: 1,
            store: {
                type: 'LanguageResources.SyncAssoc'
            },
            bind: {
                emptyText: '{l10n.crossLanguageResourceSynchronization.emptyTableText}'
            },
            columns: [
                {
                    bind: {
                        text: '{l10n.general.sourceLanguageResource}'
                    },
                    dataIndex: 'sourceLanguageResourceName',
                    flex: 1,
                    renderer: v => Ext.String.htmlEncode(v)
                },
                {
                    bind: {
                        text: '{l10n.general.targetLanguageResource}'
                    },
                    dataIndex: 'targetLanguageResourceName',
                    flex: 1,
                    renderer: v => Ext.String.htmlEncode(v)
                },
                {
                    bind: {
                        text: '{l10n.general.sourceLang}'
                    },
                    dataIndex: 'sourceLanguage',
                    flex: 1,
                    renderer: v => Ext.String.htmlEncode(v)
                },
                {
                    bind: {
                        text: '{l10n.general.targetLang}'
                    },
                    dataIndex: 'targetLanguage',
                    flex: 1,
                    renderer: v => Ext.String.htmlEncode(v)
                },
                {
                    bind: {
                        text: '{l10n.crossLanguageResourceSynchronization.customers}'
                    },
                    dataIndex: 'customers',
                    flex: 1,
                    renderer: function (v, meta) {
                        let customers = [];
                        for (let c of v) {
                            // double escape because of Ext tooltip bug
                            customers.push(Ext.String.htmlEncode(Ext.String.htmlEncode(c)))
                        }
                        meta.tdAttr = 'data-qtip="' + customers.join('<br />') + '"';

                        return v.length;
                    }
                },
                {
                    xtype: 'gridcolumn',
                    bind: {
                        text: '{l10n.general.additionalInfo}'
                    },
                    flex: 1,
                    tdCls: 'specificData',
                    renderer: (v, meta, r) => {
                        ! Ext.isEmpty(v) ? meta.tdCls = 'gridColumnInfoIconTooltipCenter' : ''
                    },
                    dataIndex: 'additionalInfo'
                },
                {
                    xtype: 'actioncolumn',
                    width: 50,
                    items: [
                        {
                            iconCls: 'x-fa fa-trash',
                            bind: {
                                tooltip: '{l10n.crossLanguageResourceSynchronization.deleteTooltip}'
                            },
                            handler: 'onDeleteConnection'
                        },
                        {
                            iconCls: 'x-fa fa-refresh',
                            bind: {
                                tooltip: '{l10n.crossLanguageResourceSynchronization.queueSynchronizationTooltip}'
                            },
                            handler: 'onSynchronizeConnection'
                        }
                    ],
                    flex: 0.5
                }
            ],
            listeners: {
                render: 'onAssociationGridRender',
                afterrender: 'onAssociationGridAfterRender'
            }
        }
    ],

    loadRecord: function (record) {
        let associations = this.getViewModel().getStore('associations'),
            url = Editor.model.LanguageResources.SyncAssoc.proxy.url + '?languageResource=' + record.get('id');

        this.getViewModel().set('record', record);

        associations.load({url: url});
    }
});
