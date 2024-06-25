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

// app/view/AssociationWindow.js
Ext.define('Editor.view.LanguageResources.SyncAssocWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.languageResourceSyncAssocWindow',
    requires: [
        'Editor.view.LanguageResources.TmWindowViewModel',
        'Editor.model.LanguageResources.SyncAssoc',
        'Editor.store.LanguageResources.SyncAssocStore'
    ],
    xtype: 'associationwindow',
    title: 'Manage Associations',
    width: 600,
    height: 400,
    // layout: 'fit',
    modal: true,

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
                    fieldLabel: 'Target Language Resource',
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
                    glyph: 'f2f1@FontAwesome5FreeSolid',
                    text: 'Add',
                    handler: 'onAddAssociation'
                },
                {
                    xtype: 'tbspacer',
                    flex: 1
                },
                {
                    xtype: 'button',
                    text: 'Connect to all available resources',
                    handler: 'onConnectToAllAvailableResources'
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
                { text: 'Source Language Resource', dataIndex: 'sourceLanguageResourceName', flex: 1 },
                { text: 'Target Language Resource', dataIndex: 'targetLanguageResourceName', flex: 1 },
                {
                    xtype: 'actioncolumn',
                    width: 50,
                    items: [
                        {
                            iconCls: 'x-fa fa-trash',
                            tooltip: 'Delete',
                            handler: 'onDeleteAssociation'
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
                }
            });

            var combo = this.lookupReference('associationForm').down('combo[name=targetLanguageResourceId]');
            var store = combo.getStore();

            Ext.Ajax.request({
                url: Editor.data.restpath + 'languageresourcesync/' + this.getView().languageResource.get('id') + '/available-for-connection',
                method: 'GET',
                success: response => {
                    const form = this.lookupReference('associationForm');
                    form.hide();
                    const data = Ext.decode(response.responseText);

                    if (data.total > 0) {
                        form.show();
                        store.loadData(data.rows)
                    }
                },
                failure: function() {
                    Ext.Msg.alert('Error', 'Failed to load data for Target Language Resource.');
                }
            });
        },

        onDeleteAssociation: function(view, rowIndex, colIndex, item, e, record) {
            record.erase({
                success: function() {
                    view.getStore().remove(record);
                }
            });
        },

        onAddAssociation: function(button) {
            var form = button.up('form').getForm();
            if (form.isValid()) {
                var association = Ext.create('Editor.model.LanguageResources.SyncAssoc', form.getValues());
                association.set('sourceLanguageResourceId', this.getView().languageResource.get('id'));
                association.save({
                    success: function() {
                        button.up('window').down('grid').getStore().load();
                    }
                });
            }
        },

        onConnectToAllAvailableResources: function(button) {
            Ext.Ajax.request({
                url: Editor.data.restpath + 'languageresourcesync/' + this.getView().languageResource.get('id') + '/connect-available',
                method: 'POST',
                success: response => {
                    button.up('window').down('grid').getStore().load();
                },
                failure: function() {
                    Ext.Msg.alert('Error', 'Failed to connect to all available resources.');
                }
            });
        }
    },
    loadRecord: function (record) {
        let tasks = this.getViewModel().getStore('associations'),
            proxy = Editor.model.LanguageResources.SyncAssoc.proxy,
            url = proxy.url;

        this.getViewModel().set('record', record);

        url += '?sourceLanguageResource=' + record.get('id');
        tasks.load({url: url});
    }
});
