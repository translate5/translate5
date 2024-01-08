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

Ext.define('Editor.view.admin.contentProtection.contentRecognition.GridController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.Editor.view.admin.contentProtection.contentRecognition.GridController',

    deleteContentRecognition: function(view, rowIdx, colIdx, actionCfg, evt, rec) {
        const callback = (btn) => btn === 'yes' && Ext.Ajax.request({
            url: Editor.data.restpath + 'contentprotectioncontentrecognition',
            method: 'DELETE',
            params: {
                id : rec.get('id')
            },
            success: xhr => rec.store.remove(rec)
        });

        Ext.MessageBox.confirm(
            Editor.data.l10n.contentProtection.confirm_deletion_title,
            Editor.data.l10n.contentProtection.contentRecognition.confirm_deletion_message,
            callback
        );
    },

    onContentRecognitionEdit: function(plugin, context) {
        context.record.save({
            preventDefaultHandler: true,
            success: function() {
                Editor.MessageBox.addSuccess('Success');
                context.record.commit();
            },
            failure: function(rec, op) {
                var json = Ext.decode(op.error.response.responseText);
                if (json.errorsTranslated || json.errors) {
                    const errors = json.errorsTranslated || json.errors;

                    for (let error of errors) {
                        Editor.MessageBox.addWarning(error.msg || error.message);
                    }
                }
            }
        });
    },
    onBeforeEdit: function(cellEditPlugin, cellContext) {
        var grid = this.getView(),
            rec = cellContext.record;

        if (rec.getData().isDefault) {
            return false;
        }

        grid.view.select(rec);

        return cellContext.field !== 'id';
    },

    onBeforeCheckChange: function (col, recordIndex, checked, record){
        // at times extJs fires this event without record what in theory must not happen
        if(!record){
            return;
        }

        if (record.getData().isDefault && col.dataIndex !== 'rowEnabled') {
            return false;
        }

        if ('keepAsIs' === col.dataIndex && ['mac-address', 'ip-address'].includes(record.get('type'))) {
            return false;
        }

        const callback = () => {
            record.set(col.dataIndex, checked);
            record.save();
        }

        if (col.dataIndex === 'rowEnabled') {
            Ext.MessageBox.confirm(
                Editor.data.l10n.contentProtection.contentRecognition.confirm_enable_title,
                Editor.data.l10n.contentProtection.contentRecognition.confirm_enable_message,
                callback
            );

            return true;
        }

        callback();

        return true;
    },


    createContentRecognition: function () {
        var win = Ext.widget('adminCreateContentRecognitionWindow');
        win.show();
        const form = win.down('form');
        const newRecord = Ext.create('Editor.view.admin.contentProtection.contentRecognition.Model');
        // newRecord.set('id', null);
        // Clear form
        form.reset();

        // Set record
        form.loadRecord(newRecord);
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
    onToggleDefaultFilter: function (btn, pressed) {
        if (pressed) {
            btn.setText(Editor.data.l10n.general.showDefault);
            this.getView().getStore().addFilter({
                property: 'isDefault',
                value: false
            });

            return;
        }

        btn.setText(Editor.data.l10n.general.hideDefault);
        this.getView().getStore().removeFilter('isDefault');
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