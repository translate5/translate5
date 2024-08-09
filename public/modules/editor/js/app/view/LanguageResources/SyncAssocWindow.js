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
    title: Editor.data.l10n.crossLanguageResourceSynchronization.confirmSynchonisation,
    width: 600,
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
                            fieldLabel: Editor.data.l10n.crossLanguageResourceSynchronization.targetLanguageResource,
                            name: 'targetLanguageResourceId',
                            store: {
                                xtype: 'store',
                                fields: ['id', 'name'],
                                data: [] // Initially empty, will be set dynamically
                            },
                            queryMode: 'local',
                            displayField: 'name',
                            valueField: 'id',
                            allowBlank: false
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
                    xtype: 'editorAdminTaskUserPrefsForm',
                    hidden: true
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
                    text: Editor.data.l10n.crossLanguageResourceSynchronization.queueSynchronyzeAll,
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
            columns: [
                {
                    text: Editor.data.l10n.crossLanguageResourceSynchronization.sourceLanguageResource,
                    dataIndex: 'sourceLanguageResourceName',
                    flex: 1,
                    renderer: v => Ext.String.htmlEncode(v)
                },
                {
                    text: Editor.data.l10n.crossLanguageResourceSynchronization.targetLanguageResource,
                    dataIndex: 'targetLanguageResourceName',
                    flex: 1,
                    renderer: v => Ext.String.htmlEncode(v)
                },
                {
                    text: Editor.data.l10n.crossLanguageResourceSynchronization.customers,
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
                    xtype: 'actioncolumn',
                    width: 50,
                    items: [
                        {
                            iconCls: 'x-fa fa-trash',
                            tooltip: Editor.data.l10n.crossLanguageResourceSynchronization.deleteTooltip,
                            handler: 'onDeleteConnection'
                        },
                        {
                            iconCls: 'x-fa fa-refresh',
                            tooltip: Editor.data.l10n.crossLanguageResourceSynchronization.queueSynchronizationTooltip,
                            handler: 'onSynchronizeConnection'
                        }
                    ],
                    flex: 0.5
                }
            ],
            listeners: {
                render: 'onAssociationGridRender'
            }
        }
    ],

    loadRecord: function(record) {
        let associations = this.getViewModel().getStore('associations'),
            url = Editor.model.LanguageResources.SyncAssoc.proxy.url + '?languageResource=' + record.get('id');

        this.getViewModel().set('record', record);

        associations.load({url: url});
    }
});
