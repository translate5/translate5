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

Ext.define('Editor.view.admin.token.TokenGridController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.Editor.view.admin.token.TokenGridController',

    deleteToken: function(view, rowIdx, colIdx, actionCfg, evt, rec) {
        const callback = (btn) => btn === 'yes' && Ext.Ajax.request({
            url: Editor.data.restpath + 'token',
            method: 'DELETE',
            params: {
                id : rec.get('id')
            },
            success: xhr => rec.store.remove(rec)
        });

        Ext.MessageBox.confirm(
            Editor.data.l10n.token.confirm_deletion_title,
            Editor.data.l10n.token.confirm_deletion_message,
            callback
        );
    },

    onTokenEdit: function(plugin, context) {
        var params = {}, prop;
        for (prop in context.record.modified) {
            params[prop] = context.record.get(prop);
        }
        params.id = context.record.get('id');

        Ext.Ajax.request({
            url: Editor.data.restpath + 'token',
            method: 'PUT',
            params: params,
            success: () => context.record.commit()
        });
    },
    onBeforeEdit: function(cellEditPlugin, cellContext) {
        var grid = this.getView(),
            rec = cellContext.record,
            {description, expires} = rec.getData();

        grid.view.select(rec);

        return cellContext.field === 'description' || cellContext.field === 'expires';
    },

    filterByKeyword: function(field, keyword) {
        var store = this.getView().getStore(),
            userStore = Ext.StoreMgr.get('admin.Users'),
            trimmed = keyword.trim(),
            rex;

        if (trimmed) {
            const regString = Editor.util.Util.escapeRegex(trimmed);

            rex = new RegExp(regString, 'i');

            const users = userStore.getData().items.filter(
                (user) => rex.exec(JSON.stringify(user.data, ['firstName', 'surName', 'login']))
            );
            let guids = [];
            for (let user of users) {
                guids.push(Editor.util.Util.escapeRegex(user.get('userGuid')));
            }

            if (0 !== guids.length) {
                rex = new RegExp(regString + '|' + guids.join('|'), 'i');
            }

            store.addFilter({
                id: 'search',
                filterFn: ({data}) => rex.exec(JSON.stringify(data, ['id', 'description', 'userGuid']))
            });

        } else {
            store.removeFilter('search');
        }

        // Toggle clear-trigger on keyword-field
        field.getTrigger('clear').setVisible(trimmed);
    },
    createToken: function () {
        var win = Ext.widget('adminCreateTokenWindow');
        win.show();
    },
    onGridActivate: function(){
        var store = this.getView().getStore();
        if(!store.isLoaded()){
            store.load();
        }
    },
    onRefreshClick: function(){
        this.getView().getStore().reload();
    },
    /**
     * Event listeners
     */
    setEditableCellHint: function(view, record, metaData) {
        var hint = view.up('[viewModel]').getViewModel().get('l10n.editableCellHint');
        metaData.tdAttr = 'data-qtip="' + hint + '"';
    },

    editableCellRenderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
        this.setEditableCellHint(view, record, metaData);
        return value;
    }
});