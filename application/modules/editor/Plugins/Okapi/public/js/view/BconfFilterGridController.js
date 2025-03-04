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
    isCustomFiltered: false,
    isSearchFiltered: false,
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
                    } else {
                        // mimic behaviour of toggle button, when store is filtered, we do not show leveling
                        this.getView().addCls('t5noLevels');
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
        return !record.get('isCustom');
    },

    isEditDisabled: function(view, rowIndex, colIndex, item, record){
        return !record.get('isCustom') || !record.get('editable') || record.crudState === 'C';
    },

    isCloneDisabled: function(view, rowIndex, colIndex, item, record){
        return record.crudState === 'C';
    },

    isWriteDisabled: function(view, rowIndex, colIndex, item, record){
        return !record.get('isCustom') || record.crudState === 'C';
    },

    handleSearch: function(field, searchString){
        var searchFilterValue = searchString.trim();
        if(searchFilterValue){
            this.setSearchFilter(searchFilterValue);
        } else {
            this.removeSearchFilter();
        }
        if(field){
            field.getTrigger('clear').setVisible(searchFilterValue);
        }
    },

    /**
     *
     * @param {string} query
     */
    setSearchFilter: function(query){
        var view = this.getView(),
            store = view.getStore(),
            searchRE = new RegExp(Editor.util.Util.escapeRegex(query), 'i');
        store.clearFilter();
        store.addFilter({
            id: 'search',
            filterFn: ({data}) => searchRE.exec(JSON.stringify(Object.values(data)))
        });
        view.removeCls('t5noLevels'); // when searching, we will distinguish custom/real filters
        this.isSearchFiltered = true;
    },

    /**
     *
     */
    removeSearchFilter: function(){
        if(this.isSearchFiltered){
            var view = this.getView(),
                store = view.getStore();
            store.clearFilter();
            if(this.isCustomFiltered){
                store.addFilter(store.customizedFilter);
                view.addCls('t5noLevels'); // was removed in setSearchFilter
            }
            this.isSearchFiltered = false;
        }
    },

    /**
     *
     * @param {Ext.button.Button} btn
     * @param {boolean} toggled
     */
    toggleDefaultsFilter: function(btn, toggled){
        var view = this.getView(),
            store = view.getStore();
        if(toggled){
            store.removeFilter('customizedFilter');
            view.removeCls('t5noLevels');
            btn.setText(this.getView().strings.hideDefaultFilters);
            btn.setIconCls('x-fa fa-eye-slash');
            this.isCustomFiltered = false;
        } else {
            store.addFilter(store.customizedFilter);
            view.addCls('t5noLevels'); // when only the default filters are shown, we do not need the levels in the grid
            btn.setText(this.getView().strings.showDefaultFilters);
            btn.setIconCls('x-fa fa-eye');
            this.isCustomFiltered = true;
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

    /**
     * @param {integer} rowIndex
     * @param {Editor.plugins.Okapi.model.BconfFilterModel} record
     */
    editTexts: function(view, rowIndex, colIndex, item, e, record){
        var rowEditor = view.grid.findPlugin('bconffilterrowediting');
        view.select(rowIndex);
        rowEditor.startEdit(record);
    },

    /**
     * @param {Editor.plugins.Okapi.model.BconfFilterModel} record
     */
    cloneFilter: function(view, rowIndex, colIndex, item, e, record){
        var store = view.getStore(),
            newRecData = Ext.clone(record.getData());
        // we temporarily set a search value to reduce the number of shown rows, but only if all filters are shown and no search is active
        if(!view.grid.getSearchValue() && !this.isCustomFiltered){
            view.grid.setSearchValue(record.get('okapiType'), true);
        }
        view.select(rowIndex);
        delete newRecData.id;
        delete newRecData.extensions;
        newRecData.bconfId = this.getView().getBconf().get('id');
        newRecData.identifier = 'NEW@FILTER'; // this is a special identifier that triggers creating a new identifier in the BconfFilterController
        newRecData.isCustom = true; // a cloned record always becomes a custom record and will be saved to the DB
        var newRec = store.add(newRecData)[0];
        newRec.isClonedRecord = true;
        // open roweditor for clone
        var rowEditor = view.grid.findPlugin('bconffilterrowediting');
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
    showFPRMChooser: function(view, rowIndex, colIndex, actionItem){
        view.select(rowIndex);
        const controller = this, fprmId = view.selection.id;
        Editor.util.Util.chooseFile('.fprm').then(function(files){
            controller.uploadFPRM(fprmId, files[0]);
        });
    },
    downloadFPRM: function(view, rowIndex, colIndex, actionItem, e, /* record */ {id}){
        view.select(rowIndex);
        Editor.util.Util.download('plugins_okapi_bconffilter/downloadfprm', {id});
    },
    uploadFPRM: function(id, fprm){
        const controller = this,
            grid = this.getView(),
            [invalidTitle, invalidMsg, fileUploaded]
            = [grid.strings.invalidTitle, grid.strings.invalidMsg, grid.strings.fileUploaded].map(x => x.replace('{0}', 'FPRM'));
        grid.setLoading(true);
        Editor.util.Util.fetchXHRLike(Editor.data.restpath + 'plugins_okapi_bconffilter/uploadfprm/?id=' + id, {
            method: 'POST', formData: {fprm}
        }).then(function(response){
            grid.setLoading(false);
            var { status, responseJson: json = {}} = response;
            if(json.errorCode === 'E1686'){
                Ext.Msg.show({
                    title: invalidTitle,
                    message: invalidMsg + controller.createInfoSpan(json),
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.Msg.WARNING
                });
            } else if(status !== 200){
                Editor.app.getController('ServerException').handleException(response);
            } else {
                Editor.MessageBox.addSuccess(fileUploaded);
            }
        });
    },
    /**
     * Handles closing the Filter panel
     */
    handleClose: function(){
        window.location.hash = window.location.hash.replace(/\/filters.*$/,'');
    }
});