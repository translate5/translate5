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

Ext.define('Editor.view.admin.lsp.PanelViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.lspPanelView',
    routes: {
        lsp: 'onLspRoute',
    },

    editWindow: null,

    onLspRoute: function () {
        Editor.app.openAdministrationSection(this.getView());
    },

    onGridActivate: function () {
        const store = this.getView().down('gridpanel').getStore();

        if (!store.isLoaded()) {
            store.load();
        }
    },

    onRefreshClick: function () {
        this.getView().down('gridpanel').getStore().reload();
    },

    onCreateClick: function () {
        const win = (this.editWindow = Ext.widget('lspEditWindow', { editMode: true }));
        win.show();
    },

    onEditClick: function (table, row, column, button, event, record) {
        if (!record.get('canEdit')) {
            return;
        }

        const win = (this.editWindow = Ext.widget('lspEditWindow', { editMode: true }));
        win.show();
        win.loadRecord(record);
    },

    onCancelEditClick: function () {
        this.editWindow.close();
    },

    onSaveClick: function (values, record) {
        let url = '/editor/lsp';
        let method = 'POST';

        if (values.id) {
            url += '/' + values.id;
            method = 'PUT';
        }

        this.editWindow.setLoading(true);
        const store = this.getView().down('gridpanel').getStore();

        Ext.Ajax.request({
            url: url,
            params: { data: JSON.stringify(values) },
            async: false,
            method: method,
            success: (xhr) => {
                store.reload();
                this.editWindow.setLoading(false);
                this.editWindow.close();
                // TODO translation
                Editor.MessageBox.addSuccess('Success');
            },
            error: (xhr) => {
                this.editWindow.setLoading(false);
                // TODO translation
                Editor.MessageBox.getInstance().showDirectError('Error occurred while saving the LSP');
            },
            failure: (xhr) => {
                this.editWindow.setLoading(false);
                // TODO translation
                Editor.MessageBox.getInstance().showDirectError('Error occurred while saving the LSP');
            },
        });
    },

    onDeleteClick: function (table, row, column, button, event, record) {
        if (!record.get('canDelete')) {
            return;
        }

        this._showDeletePrompt(record, null);
    },

    onEditCustomersClick: function (table, row, column, button, event, record) {
        if (!record.get('canEdit')) {
            return;
        }

        const win = (this.editWindow = Ext.widget('lspEditCustomerWindow', { editMode: true }));
        win.show();
        win.loadRecord(record);
    },

    unassignCustomer: function (lsp, customer) {
        const l10n = Editor.data.l10n.lsp;

        Ext.Msg.confirm(
            l10n.unassignCustomer,
            Ext.String.format(l10n.confirmUnassignCustomer, customer.get('name'), lsp.get('name')),
            (btn) => {
                if (btn === 'no') {
                    return;
                }

                Ext.Ajax.request({
                    url: '/editor/lsp/' + lsp.get('id') + '/customer/' + customer.get('id'),
                    method: 'DELETE',
                    success: () => {
                        lsp.load({
                            callback: (record) => {
                                Ext.ComponentQuery.query('lspEditCustomerWindow')[0].loadRecord(record);
                            },
                        });
                    },
                    failure: (response) => {
                        Editor.app.getController('ServerException').handleException(response);
                    },
                });
            },
        );
    },

    assignCustomer: function (lsp, customer) {
        Ext.Ajax.request({
            url: '/editor/lsp/' + lsp.get('id') + '/customer/',
            method: 'POST',
            params: {
                data: JSON.stringify({
                    customer: customer.get('id'),
                }),
            },
            success: () => {
                lsp.load({
                    callback: (record) => {
                        Ext.ComponentQuery.query('lspEditCustomerWindow')[0].loadRecord(record);
                    },
                });
            },
            failure: (response) => {
                Editor.app.getController('ServerException').handleException(response);
            },
        });
    },

    _showDeletePrompt: function (record, value) {
        const l10n = Editor.data.l10n.lsp;
        const text = Ext.String.format(l10n.confirmDeleteText, record.get('name')) + '<br><br>' + l10n.enterLspName;
        const store = this.getView().down('gridpanel').getStore();

        Ext.Msg.prompt(
            l10n.confirmDeleteTitle,
            text,
            (btn, value) => {
                if (btn === 'cancel') {
                    return;
                }

                if (record.get('name') !== value) {
                    Editor.MessageBox.addWarning(l10n.confirmDeleteWrongName);
                    this._showDeletePrompt(record, value);

                    return;
                }

                Ext.Ajax.request({
                    url: '/editor/lsp/' + record.get('id'),
                    method: 'DELETE',
                    params: {
                        data: JSON.stringify({
                            id: record.get('id'),
                            name: value,
                        }),
                    },
                    success: () => {
                        Editor.MessageBox.addSuccess(Editor.data.l10n.general.entryHasBeenDeleted);
                        store.reload();
                    },
                    failure: (response) => {
                        Editor.app.getController('ServerException').handleException(response);
                        store.reload();
                    },
                });
            },
            this,
            false,
            value,
        );
    },
});
