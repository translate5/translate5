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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class
 * @name BconffilterGridController
 * @extends Ext.app.ViewController
 * @property {Object} extMapChangelog Tracks changes to the filter's extensions-mapping
 */
let BconffilterGridController = {
    //region config
    extend: 'Ext.app.ViewController',
    alias: 'controller.bconffilterGridController',
    extMapChangelog: {},
    listen: {
        store: {
            'bconffilterStore': {
                beforeload: function(){
                    this.lookup('gridview').suspendEvent('refresh'); // prevent repaint until records are processed
                },
                load: function(store){
                    this.handleStoreLoad(...arguments)
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
            canceledit: 'cancelEdit'
        },

        'textfield#search': {
            change: 'search',
        },

        'tagfield#extMap': {
            change: 'handleExtMapChange',
        },

    },

    //endregion config
    //region authorization

    isDeleteDisabled: function(view, rowIndex, colIndex, item, record){
        return !record.data.isCustom;
    },

    isEditDisabled: function(/*view, rowIndex, colIndex, item, record*/){
        return true;
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
        if(records.length === 0){
            this.lookup('showDefaultsBtn').setPressed(true)
        }
        store.add(Ext.getStore('defaultBconffilters').getRange());
        store.setExtMapString(operation.getResultSet().getMetadata()['extensions-mapping']);
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
            store.removeFilter('defaultsFilter')
        } else {
            store.addFilter(store.defaultsFilter)
        }
    },
// region grid columns
    copy: function(view, rowIndex, colIndex, item, e, record){
        searchField = view.grid.down('textfield#search')
        searchField.setValue(record.get('name'))
        searchField.checkChange()
        view.select(rowIndex);
        var store = view.getStore(),
            /*bconfCustomerGrid = Ext.getCmp('bconfCustomerGrid'),
            suffix,
            //newId = record.id.replace(/@.*$/,)
            /* TODO calc correct customerId
            if(bconfCustomerGrid.isVisible()){
                newId = bconfCustomerGrid
            }
            */
            newRecData = {
                name: record.get('name'),
                //name:'',
                okapiId: record.get('okapiId').replace(/@.*$/, '') + '@' + location.host + '-' + Date.now(),
                bconfId: view.grid.getBconf().getId(),
            };

        newRec = store.add(newRecData)[0];
        newRec.isNewRecord = true;

        var rowediting = view.grid.findPlugin('rowediting'),
            editingStarted = rowediting.startEdit(newRec);

        if(editingStarted){
            //var nameEditor = rowediting.getEditor().down('textfield[dataIndex=name]')
            //getRoweditor
        }
    },

    delete: function(view, rowIndex, colIndex, item, e, record, /*row*/){
        record.drop();
        if(record.crudState != 'C'){
            record.save() // TODO: delete on success only
        }
    },
// endregion
    prepareFilterEdit: function(rowEditPlugin, cellContext){
        var record = cellContext.record,
            tagfield = rowEditPlugin.getEditor().down('tagfield'),
            extensions = Array.from(this.getView().getStore().extMap.keys());
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
    saveEdit: function(plugin, cellContext){
        var ctlr = this,
            store = Ext.getStore('bconffilterStore'),
            record = cellContext.record,
            changed = Editor.util.Util.getChanged(cellContext.newValues, cellContext.originalValues);
        if(record.get('extensions').unchanged){
            delete changed.extensions // complex value is always seen as changed
        } else { // extensions changed
            store.getProxy().setExtraParam('extensions-mapping', store.getExtMapString());
        }
        //record.commit();
        if(Object.keys(changed).length){

            record.set(changed); //QUIRK TODO check why not ausosave

            if(record.isNewRecord){
                record.crudState = 'C'
                record.phantom = true
                delete record.isNewRecord;
            }

            store.sync({
                batch: {
                    listeners: {
                        operationcomplete: {
                            single: true,
                            fn: function(batch, operation){
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
                callback: function(batch, batchOptions){
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
                    affected = filter.addExtension(extension, affected, isRevert)
                } else {
                    affected = filter.removeExtension(extension, affected, isRevert)
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
            var {filter, affected} = changelog[extension],
                added = !changelog[extension].added;
            delete changelog[extension];
        }
        if(added){
            affected = filter.addExtension(extension, affected, isRevert)
        } else {
            affected = filter.removeExtension(extension, affected, isRevert)
        }
        //TODO:this.lookup('gridview').refreshNode(filter);
        if(affected){
            //this.lookup('gridview').refreshNode(affected);
        }
        if(!isRevert){ // Save log for revert
            tagfield.changelog[extension] = {added, filter, affected}
        }
        return
        if(added){
            change.to = filter.id
            affected = store.getById(changelog[extension]?.to || store.extMap.get(extension));
            if(affected && affected !== filter){
                change.from = affected;
                detail = ` from '${affected.get('name')}'`
            }
            msg = `Added extension <i>${extension}</i>${detail}`
        } else { // removed
            change.from = filter.id
            affected = store.getById(changelog[extension]?.from) // was added from other filter before
            // || TODO default's extensionMapping ...as in default
            if(affected && affected !== filter){
                change.to = affected
                detail = ` and added to '${affected.get('name')}' `
            }
            msg = `Removed extension <i>${extension}</i> ${detail}`

            filter.removeExtension(extension, affected)
        }

        this.changeSingleExtension(extension, change);
    }
    ,

    changeSingleExtension: function(extension, change,){
        var store = this.getView().getStore(), // BconffilterStore
            changelog = this.extMapChangelog, change = {},
            affected, msg, detail = '';
        changelog[extension] = change
        Editor.MessageBox.addInfo(msg, 2)


    }
    ,

};
Ext.define('Editor.plugins.Okapi.view.BconffilterGridController', BconffilterGridController);
delete window.BconffilterGridController;