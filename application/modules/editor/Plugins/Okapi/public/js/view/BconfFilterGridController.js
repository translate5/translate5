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

/**
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.Okapi.view.BconfFilterGridController', {
    //region config
    extend: 'Ext.app.ViewController',
    alias: 'controller.bconffilterGridController',
    listen: {
        store: {
            'bconffilterStore': {
                beforeload: function(){
                    this.lookup('gridview').suspendEvent('refresh'); // prevent repaint until records are processed
                },
                load: function(store, records, success){
                    if(success && store.getCount() === 0 && store.loadCount === 1){
                        // Show defaults when no custom filters are available
                        this.lookup('showDefaultsBtn').setPressed(true);
                    }
                    this.lookup('gridview').resumeEvent('refresh'); // enable repaint
                    this.lookup('gridview').refresh();
                }
            }
        }
    },
    control: {
        '#': { // # references the view
            beforeedit: 'prepareEdit',
            edit: 'saveEdit',
            canceledit: 'cancelEdit',
            close: 'onClose'
        },
        'textfield#search': {
            change: 'search',
        }
    },

    //endregion config

    //region authorization

    isDeleteDisabled: function(view, rowIndex, colIndex, item, record){
        return !record.data.isCustom;
    },

    isEditDisabled: function(view, rowIndex, colIndex, item, record){
        return !record.get('editable') || record.crudState === 'C' || !record.get('guiClass');
    },

    //endregion

    search: function(field, searchString){
        var store = this.getView().getStore(),
            searchFilterValue = searchString.trim();
        if(searchFilterValue){
            var searchRE = new RegExp(Editor.util.Util.escapeRegex(searchFilterValue), 'i');
            store.addFilter({
                id: 'search',
                filterFn: ({data}) => searchRE.exec(JSON.stringify(Object.values(data)))
            });
        } else {
            store.removeFilter('search');
        }
        field.getTrigger('clear').setVisible(searchFilterValue);
    },

    toggleDefaultsFilter: function(btn, toggled){
        var store = this.getView().getStore();
        if(toggled){
            store.removeFilter('defaultsFilter');
        } else {
            store.addFilter(store.defaultsFilter);
        }
    },
// region grid columns
    /** @method
     * @param {Editor.plugins.Okapi.model.BconfFilterModel} record
     */
    editFPRM: function(view, rowIndex, colIndex, item, e, record){
        Ext.create(record.get('guiClass'),{
            bconfFilter: record
        }).show();
    },

    /** @method
     * @param {Editor.plugins.Okapi.model.BconfFilterModel} record
     */
    copy: function(view, rowIndex, colIndex, item, e, record){
        var searchField = view.grid.down('textfield#search');
        searchField.setValue(record.get('name'));
        searchField.checkChange();
        view.select(rowIndex);
        var store = view.getStore(),
            newRecData = Ext.clone(record.getData());
        delete newRecData.id;
        delete newRecData.extensions;
        newRecData.isCustom = true;
        var newRec = store.add(newRecData)[0];
        newRec.isNewRecord = true;

        var rowediting = view.grid.findPlugin('rowediting'),
            editingStarted = rowediting.startEdit(newRec);

        if(editingStarted){
            //var nameEditor = rowediting.getEditor().down('textfield[dataIndex=name]')
            //getRoweditor
        }
    },
    /**
     * Delete a Bconffilter from DB and extensions-mapping
     */
    delete: function(view, rowIndex, colIndex, item, e, record){
        /** @param {Editor.plugins.Okapi.store.BconfFilterStore} store */
        var store = Ext.getStore('bconffilterStore');
        record.get('extensions').forEach(function(ext){
            record.removeExtension(ext);
        });
        view.select();
        record.drop(/* cascade */ false);
        store.sync();
    },
// endregion
    /**
     *
     * @param {Ext.grid.plugin.RowEditing} rowEditPlugin
     * @param {Ext.grid.CellContext} cellContext
     */
    prepareEdit: function(rowEditPlugin, cellContext){
        var record = cellContext.record,
            tagfield = rowEditPlugin.getEditor().down('tagfield');
        tagfield.setStore(this.getView().getStore().getAllExtensions());
        record.extensionsBeforeEdit = record.get('extensions');
    },
    /**
     * Save changed record
     * @param {Ext.grid.plugin.RowEditing} plugin
     * @param {Ext.grid.CellContext} cellContext
     */
    saveEdit: async function(plugin, cellContext){
        var store = Ext.getStore('bconffilterStore'),
            record = cellContext.record,
            isCustom = record.get('isCustom'),
            extensions = record.get('extensions'),
            identifier = record.get('identifier'),
            // checks, if the extensions have been changed
            extensionsChanged = !Editor.util.Util.arraysAreEqual(extensions, record.extensionsBeforeEdit),
            // checks if the record has been changed
            recordChanged = Editor.util.Util.objectWasChanged(cellContext.newValues, cellContext.originalValues, ['name','description','mimeType']);
        // cleanup tmp data
        delete record.extensionsBeforeEdit;
        // save a custom record or just transfere the new extensions for a non-custom record
        if(isCustom && (recordChanged || extensionsChanged)){
            // transfere changed data of a custom entry
            record.set({
                'name': cellContext.newValues.name,
                'description': cellContext.newValues.description,
                'mimeType': cellContext.newValues.mimeType,
                'extensions': extensions
            });
            // "heal" new records
            if(record.isNewRecord){
                record.crudState = 'C';
                record.phantom = true;
                delete record.isNewRecord;
            }
            // save back
            store.sync({
                batch: {
                    listeners: {
                        operationcomplete: {
                            single: true,
                            fn: function(){
                                store.getProxy().setExtraParams({});
                            }
                        }
                    }
                },
                /**
                 * @param {Ext.data.Batch} batch
                 * @param batchOptions
                 */
                callback: function(batch){
                    var success = !batch.hasException();
                    if(success){
                        // update record state
                        record.commit();
                        // update the maps in the store & remove extension from other items
                        store.updateExtensionsByIdentifier(identifier, extensions);
                    }
                }
                // TODO BCONF: add new Id to search
            });
        } else if(!isCustom && extensionsChanged){
            Ext.Ajax.request({
                url: Editor.data.restpath + 'plugins_okapi_bconfdefaultfilter/setextensions',
                params: {
                    identifier: identifier,
                    bconfId: store.getProxy().bconfId,
                    extensions: extensions.join(',')
                },
                success: function(){
                    // update the record silently
                    record.set('extensions', extensions, { silent: true, dirty: false });
                    record.commit();
                    // update the maps in the store & remove extension from other items
                    store.updateExtensionsByIdentifier(identifier, extensions);
                },
                failure: function(response){
                    Editor.app.getController('ServerException').handleException(response);
                }
            });
        } else {
            // to remove the "red corner" when the extension-editor changed anything
            record.commit();
        }
    },
    /**
     * Delete new records when edit was canceled
     * @listens event: canceledit
     * @param {Ext.grid.plugin.RowEditing} plugin
     * @param {Ext.grid.CellContext} cellContext
     */
    cancelEdit: function(plugin, cellContext){
        delete cellContext.record.extensionsBeforeEdit;
        if(cellContext.record.isNewRecord){
            cellContext.record.drop();
        }
    },
    /**
     *
     */
    onClose: function(){
        location.hash = location.hash.replace(/\/filters.*$/,'');
    }
});