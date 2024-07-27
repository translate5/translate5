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
        'Editor.store.LanguageResources.SyncAssocStore'
    ],
    xtype: 'associationwindow',
    title: Editor.data.l10n.crossLanguageResourceSynchronization.confirmSynchonisation,
    width: 600,
    height: 400,
    modal: true,

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

    controller: {
        onAssociationGridRender: function(grid) {
            grid.getStore().load({
                params: {
                    languageResource: this.getView().languageResource.get('id')
                },
                callback: (records, operation, success) => {
                    if (! success) {
                        return;
                    }

                    if (records.length === 0) {
                        return;
                    }

                    this.lookupReference('queueSynchronizeAll').show();
                }
            });

            this.updateForm();
        },

        updateForm: function() {
            const form = this.lookupReference('associationForm');

            var combo = form.down('combo[name=targetLanguageResourceId]');
            var store = combo.getStore();

            Ext.Ajax.request({
                url: Editor.data.restpath + 'languageresourcesync/' + this.getView().languageResource.get('id') + '/available-for-connection',
                method: 'GET',
                success: response => {
                    form.hide();
                    const data = Ext.decode(response.responseText);

                    if (data.total > 0) {
                        form.show();
                        store.loadData(data.rows)
                    }
                },
                failure: function() {
                    Ext.Msg.alert(
                        'Error',
                        Editor.data.l10n.crossLanguageResourceSynchronization.failedToLoadDataForTargetLanguageResource
                    );
                }
            });
        },

        onDeleteConnection: function(view, rowIndex, colIndex, item, e, record) {
            const callback = (btn) => btn === 'yes' && record.erase({
                success: () => {
                    view.getStore().remove(record);
                    this.updateForm();
                }
            });

            Ext.MessageBox.confirm(
                Editor.data.l10n.crossLanguageResourceSynchronization.confirm_deletion_title,
                Editor.data.l10n.crossLanguageResourceSynchronization.confirm_deletion_message,
                callback
            );
        },

        onSynchronizeConnection: function(view, rowIndex, colIndex, item, e, record) {
            const callback = (btn) => btn === 'yes' && Ext.Ajax.request({
                url: Editor.data.restpath + 'languageresourcesyncconnection/' + record.get('id') + '/queue-synchronize',
                method: 'POST',
                success: response => {
                    Ext.Msg.alert(
                        Editor.data.l10n.crossLanguageResourceSynchronization.tooltip,
                        Editor.data.l10n.crossLanguageResourceSynchronization.synchronisationQueued
                    );
                },
                failure: function() {
                    Ext.Msg.alert('Error', Editor.data.l10n.crossLanguageResourceSynchronization.failedToQueue);
                }
            });

            Ext.MessageBox.confirm(
                Editor.data.l10n.crossLanguageResourceSynchronization.tooltip,
                Editor.data.l10n.crossLanguageResourceSynchronization.confirmSynchonisation,
                callback
            );
        },

        onAddAssociation: function(button) {
            const form = button.up('form').getForm();
            if (form.isValid()) {
                const
                    association = Ext.create('Editor.model.LanguageResources.SyncAssoc', form.getValues()),
                    lrId = this.getView().languageResource.get('id')
                ;

                association.set('sourceLanguageResourceId', lrId);
                association.save({
                    success: () => {
                        const
                            store = button.up('window').down('grid').getStore(),
                            url = Editor.model.LanguageResources.SyncAssoc.proxy.url + '?languageResource=' + lrId
                        store.load({url: url});
                        this.updateForm();
                    }
                });
            }
        },

        queueSynchronizeAll: function(button) {
            const callback = (btn) => btn === 'yes' && Ext.Ajax.request({
                url: Editor.data.restpath + 'languageresourcesync/' + this.getView().languageResource.get('id') + '/queue-synchronize-all',
                method: 'POST',
                success: response => {
                    Ext.Msg.alert(
                        Editor.data.l10n.crossLanguageResourceSynchronization.tooltip,
                        Editor.data.l10n.crossLanguageResourceSynchronization.synchronisationQueued
                    );
                },
                failure: function() {
                    Ext.Msg.alert('Error', Editor.data.l10n.crossLanguageResourceSynchronization.failedToQueue);
                }
            });

            Ext.MessageBox.confirm(
                Editor.data.l10n.crossLanguageResourceSynchronization.tooltip,
                Editor.data.l10n.crossLanguageResourceSynchronization.confirmSynchonisation,
                callback
            );
        }
    },
    loadRecord: function(record) {
        let associations = this.getViewModel().getStore('associations'),
            url = Editor.model.LanguageResources.SyncAssoc.proxy.url + '?languageResource=' + record.get('id');

        this.getViewModel().set('record', record);

        associations.load({url: url});
    }
});
