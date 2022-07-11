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
            close: 'handleClose'
        },
        'textfield#search': {
            change: 'handleSearch',
        }
    },

    isDeleteDisabled: function(view, rowIndex, colIndex, item, record){
        return !record.data.isCustom;
    },

    isEditDisabled: function(view, rowIndex, colIndex, item, record){
        return !record.get('editable') || record.crudState === 'C' || !record.get('guiClass');
    },

    handleSearch: function(field, searchString){
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

    /**
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
    cloneFilter: function(view, rowIndex, colIndex, item, e, record){
        var store = view.getStore(),
            newRecData = Ext.clone(record.getData());
        // we temporarily set a search value to reduce the number of shown rows
        if(!view.grid.getSearchValue()){
            view.grid.setSearchValue(record.get('name'));
        }
        view.select(rowIndex);
        delete newRecData.id;
        delete newRecData.extensions;
        newRecData.bconfId = this.getView().getBconf().get('id');
        newRecData.identifier = 'NEW@FILTER'; // this is a special identifier that triggers creating a new identifier in the BconfFilterController
        newRecData.isCustom = true;
        var newRec = store.add(newRecData)[0];
        newRec.isClonedRecord = true;
        newRec.clonedFrom = record;
        // open roweditor for clone
        var rowEditor = view.grid.findPlugin('rowediting');
        rowEditor.startEdit(newRec);
    },
    /**
     * Delete a Bconffilter from DB and extensions-mapping
     */
    deleteFilter: function(view, rowIndex, colIndex, item, e, record){
        /** @param {Editor.plugins.Okapi.store.BconfFilterStore} store */
        var store = Ext.getStore('bconffilterStore');
        record.get('extensions').forEach(function(ext){
            record.removeExtension(ext);
        });
        view.select();
        record.drop(/* cascade */ false);
        store.sync();
    },
    /**
     * Handles closing the Filter panel
     */
    handleClose: function(){
        location.hash = location.hash.replace(/\/filters.*$/,'');
    }
});