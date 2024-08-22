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
        'lsp': 'onLspRoute',
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
        const win = this.editWindow = Ext.widget('lspEditWindow', {editMode: true});
        win.show();
    },

    onEditClick: function (table, row, column, button, event, record) {
        const win = this.editWindow = Ext.widget('lspEditWindow', {editMode: true});
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
            params: {data: JSON.stringify(values)},
            async: false,
            method: method,
            success: (xhr) => {
                store.reload();
                this.editWindow.close();
                Editor.MessageBox.addSuccess('');
            },
            error: (xhr) => {
                debugger;
            },
            failure: (xhr) => {
                debugger;
            }
        });
    },

    onDeleteClick: function (table, row, column, button, event, record) {
        const l10n = Editor.data.l10n.lsp;
        const text = Ext.String.format(l10n.confirmDeleteText, record.get('name'));
        const store = this.getView().down('gridpanel').getStore();

        Ext.Msg.confirm(
            l10n.confirmDeleteTitle,
            text,
            (btn) => {
                if (btn !== 'yes') {
                    return;
                }

                record.dropped = true;
                record.save({
                    failure: function () {
                        store.reload();
                    },
                    success: function () {
                        store.remove(record);
                    }
                });
            }
        );
    },
});