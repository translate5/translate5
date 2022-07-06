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
// QUIRK: To support named regions this separate config object is used and later fed to Ext.define()
let BconfFilterGridController = {
    //region config
    extend: 'Ext.app.ViewController',
    alias: 'controller.bconffilterGridController',
    listen: {
        store: {
            'bconffilterStore': {
                beforeload: function(){
                    this.lookup('gridview').suspendEvent('refresh'); // prevent repaint until records are processed
                },
                load: function(){
                    this.handleStoreLoad(...arguments);
                    this.lookup('gridview').resumeEvent('refresh'); // enable repaint
                    this.lookup('gridview').refresh();
                }
            },
        }
    },
    control: {
        '#': { // # references the view
            beforeedit: 'prepareFilterEdit',
            edit: 'saveEdit',
            canceledit: 'cancelEdit',
            close: 'onClose'
        },

        'textfield#search': {
            change: 'search',
        },

        'tagfield#extensionMap': {
            change: 'handleExtMapChange',
        },

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

    /**
     * Store Load listener. Assumes empty store.
     */
    handleStoreLoad: function(store, records, successful, operation){
        if(!successful){
            Editor.app.getController('ServerException').handleCallback(records, operation);
            return;
        }
        store.add(Ext.getStore('defaultBconfFilters').getRange());
        store.setExtensionMapping(operation.getResultSet().getMetadata().extensionMapping);
        if(store.getCount() === 0 && store.loadCount === 1){ // Show defaults on empty Bconffilters
            this.lookup('showDefaultsBtn').setPressed(true);
        }
    },

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
     * TODO Remove files
     */
    delete: function(view, rowIndex, colIndex, item, e, record, /*row*/){
        /** @param {Editor.plugins.Okapi.store.BconfFilterStore} store */
        var store = Ext.getStore('bconffilterStore');
        record.get('extensions').forEach(function(ext){
            record.removeExtension(ext);
        });
        view.select();


        record.drop(/* cascade */ false);
        store.saveExtensionMapping().then(function(){
            store.sync();
        });
    },
// endregion
    prepareFilterEdit: function(rowEditPlugin, cellContext){
        var record = cellContext.record,
            tagfield = rowEditPlugin.getEditor().down('tagfield'),
            extensions = Array.from(this.getView().getStore().extensionMap.keys());
        tagfield.setStore(extensions);
        tagfield.changelog = {};
        //record.extensionsBeforeEdit = record.get('extensions').toString();
        record.get('extensions').unchanged = true;
    },
    /**
     * Save changed record
     * @param {Ext.grid.plugin.RowEditing} plugin
     * @param {Ext.grid.CellContext} cellContext
     */
    saveEdit: async function(plugin, cellContext){
        var store = Ext.getStore('bconffilterStore'),
            record = cellContext.record,
            changed = Editor.util.Util.getChanged(cellContext.newValues, cellContext.originalValues);
        if(record.get('extensions').unchanged){
            delete changed.extensions; // complex value is always seen as changed
        } else { // extensions changed
            await store.saveExtensionMapping();
        }
        //record.commit();
        if(Object.keys(changed).length){

            record.set(changed); //QUIRK TODO check why not ausosave
            //record.commit();
            if(record.isNewRecord){
                record.crudState = 'C';
                record.phantom = true;
                delete record.isNewRecord;
            }

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
                 *
                 * @param {Ext.data.Batch} batch
                 * @param batchOptions
                 */
                callback: function(batch){
                    var success = !batch.hasException();
                    if(success){
                        record.commit();
                        //TODO clear other records

                    }
                }
            }); //TODO: add new Id to search
        }

    },
    /**
     * Delete new records when edit was canceled
     * @listens event:canceledit
     * @param {Ext.grid.plugin.RowEditing} plugin
     * @param {Ext.grid.CellContext} cellContext
     */
    cancelEdit: function(plugin, cellContext){
        var record = cellContext.record,
            changelog = plugin.getEditor().down('tagfield').changelog,
            isRevert = true, extension;
        if(!record.get('extensions').unchanged){ // revert changes
            for(extension in changelog){
                var {filter, affected} = changelog[extension],
                    added = !changelog[extension].added;
                delete changelog[extension];
                if(added){
                    affected = filter.addExtension(extension, affected, isRevert);
                } else {
                    affected = filter.removeExtension(extension, affected, isRevert);
                }
            }
        }
        if(record.isNewRecord){
            record.drop();
        }
    },

    /**
     * Handles changes of a filter's extensions
     * Precondition: current and previous differ by exactly one element
     */
    handleExtMapChange: function(tagfield, current, previous){
        var added = current.length > previous.length,
            [longer, shorter] = added ? [current, previous] : [previous, current],
            extension = Ext.Array.difference(longer, shorter)[0],
            filter = this.getView().editingPlugin.getEditor().getRecord(),
            changelog = tagfield.changelog,
            affected, isRevert;

        if(changelog[extension]){ // revert prior change
            isRevert = true;
            filter = changelog[extension].filter;
            affected = changelog[extension].affected;
            added = !changelog[extension].added;
            delete changelog[extension];
        }
        if(added){
            affected = filter.addExtension(extension, affected, isRevert);
        } else {
            affected = filter.removeExtension(extension, affected, isRevert);
        }
        //TODO:this.lookup('gridview').refreshNode(filter);
        if(affected){
            //this.lookup('gridview').refreshNode(affected);
        }
        if(!isRevert){ // Save log for revert
            tagfield.changelog[extension] = {added, filter, affected}
        }
    },
    onClose: function(){
        location.hash = location.hash.replace(/\/filters.*$/,'');
    }

};
Ext.define('Editor.plugins.Okapi.view.BconfFilterGridController', BconfFilterGridController);
delete window.BconfFilterGridController;