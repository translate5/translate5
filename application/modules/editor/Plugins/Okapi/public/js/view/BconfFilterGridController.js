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
 * @class BconfFilterGridController
 * @extends Ext.app.ViewController
 */
let BconfFilterGridController = {
    //region config
    extend: 'Ext.app.ViewController',
    alias: 'controller.bconfFilterGridController',
    listen: {
        store: {
            'bconfFilterStore': {
                beforeload: function(){
                    this.lookup('gridview').suspendEvent('refresh'); // prevent repaint until records are processed
                },
                load: function(store){
                    var gridview = this.lookup('gridview');
                    this.addDefaultsToBconffilter.apply(this, arguments)
                    this.parseExtensionMapping.apply(this, arguments)
                    gridview.resumeEvent('refresh'); // enable repaint
                    gridview.refresh();
                }
            }
        }
    },
    control: {
        '#': { // # references the view
            edit: 'saveFilterAfterEdit',
            canceledit: 'deleteNewRecord'
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
        return true;
    },

    //endregion
    /**
     * Store Load listener. Assumes empty store.
     */
    addDefaultsToBconffilter: function(store, records){
        // Show defaultFilters if no others are present
        if(records.length === 0){
            this.lookup('defaultsFilterBtn').setPressed(true)
        }
        // Add default BconfFilters
        var defaultBconfFilters = Ext.getStore('defaultBconfFilterStore').getData().getRange()
        store.loadRecords(defaultBconfFilters, {addRecords: true})
    },
    /**
     * Store Load Listener
     */
    parseExtensionMapping: function(store, records, successful, operation){
        var metadata = operation.getResultSet().getMetadata(),
            extMapString = metadata['extensions-mapping'],
            matches = Array.from(extMapString.matchAll(/^\.(.*)\t(.*)$/gm), match => match.slice(1, 3)),
            bconffilters = Editor.util.Util.getUnfiltered(store)
        matches.forEach(function([extension, okapiId]){
            var filter = bconffilters.getByKey(okapiId);
            if(filter){
                filter.get('extensions').add(extension);
            } else { // okapiId in extensions-mapping not contained in Bconf
                store.add({
                    bconfId: 0,
                    okapiId,
                    extensions: [extension]
                })
            }
            store.extMap.set(extension, okapiId)
        });
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
    copy: function(view, rowIndex, colIndex, item, e, record, row){
        searchField = view.grid.down('textfield#search')
        searchField.setValue(record.get('name'))
        searchField.checkChange()
        view.select(rowIndex);
        var store = view.getStore(),
            bconfCustomerGrid = Ext.getCmp('bconfCustomerGrid'),
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
            var nameEditor = rowediting.getEditor().down('textfield[dataIndex=name]')
            //getRoweditor
        }
    },

    delete: function(view, rowIndex, colIndex, item, e, record, /*row*/){
        record.drop();
        if(record.crudState != 'C'){
            record.save()
        }
    },
// endregion
    /**
     * Save changed record
     * @param {Ext.grid.plugin.RowEditing} cellEditPlugin
     * @param {Ext.grid.CellContext} cellContext
     */
    saveFilterAfterEdit: function(cellEditPlugin, cellContext){
        var record = cellContext.record;
        var changed = Editor.util.Util.getChanged(cellContext.newValues, cellContext.originalValues);
        record.set(changed); //QUIRK TODO check why not ausosave
        //record.commit();
        if(changed){
            if(record.isNewRecord){
                record.crudState = 'C'
                record.phantom = true
                delete record.isNewRecord;
            }

            record.save(); //TODO: add new Id to search
        }

    },
    /**
     * Delete new records when edit was canceled
     * @listens event:canceledit
     * @param {Ext.grid.plugin.RowEditing} cellEditPlugin
     * @param {Ext.grid.CellContext} cellContext
     */
    deleteNewRecord: function(cellEditPlugin, cellContext){
        var record = cellContext.record;
        if(record.isNewRecord){
            record.drop();
        }
    },
};
Ext.define('Editor.plugins.Okapi.view.BconfFilterGridController', BconfFilterGridController);
BconfFilterGridController = undefined;