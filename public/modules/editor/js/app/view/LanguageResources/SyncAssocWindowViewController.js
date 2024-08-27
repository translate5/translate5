
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.view.LanguageResources.SyncAssocWindowViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.LanguageResources.SyncAssocWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.languageResourceSyncAssocWindow',
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
});
