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

Ext.define('Editor.plugins.Okapi.view.BconfGridController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.Editor.plugins.Okapi.view.BconfGridController',
    /** @const {string} FILE_UPLOAD_NAME */
    FILE_UPLOAD_NAME: 'bconffile',
    listen: {
        store: {
            '#bconffilterStore': {
                // listens to changes in extensions of custom filters (which need to be updated in our view
                customFilterExtensionsChanged: function(bconfId, extensions){
                    var record = this.getView().getStore().getById(bconfId);
                    if(record){
                        record.set('customExtensions', extensions, {silent: true, dirty: false});
                    }
                }
            }
        }
    },
    routesToSet: {
        ':bconfId': 'onBconfRoute',
        ':bconfId/filters': async function(bconfId){
            bconfId = parseInt(bconfId, 10);
            var grid = this.getView();
            var sel = grid.selection;
            if(sel?.id !== bconfId){
                Editor.util.Util.parentRoute();
                sel = await Editor.util.Util.awaitSelection(grid, bconfId);
            }
            if(sel?.id === bconfId){
                var col = grid.getColumnManager().getHeaderById('bconfFilters');
                var cell = grid.view.getCell(grid.store.getById(bconfId), col);
                cell?.focus().down('.x-action-col-0')?.dom.click(); // triggers showFilterGrid
            }
        }
    },
    beforeInit: function(view){
        var itemId = view.getItemId(),
            routes = {};
        /** @link Editor.controller.admin.Customer TODO support routing in Customer Controller */
        for(const [route, action] of Object.entries(this.routesToSet)){
            routes[view.routePrefix + itemId + '/' + route] = action;
        }
        this.setRoutes(routes);
        this.callParent(arguments);
    },
    control: {
        '#':
            { // # references the view
                'selectionchange':
                    {
                        fn: function(selModel, selected){
                            var rec = selected.length && selected[0];
                            if(rec){
                                this.redirectTo(rec);
                            }
                        }
                    }
            }
    },
    /** The argument depends on the routePrefix of the view */
    onBconfRoute: async function(/* bconfId */){
        var grid = this.getView(),
            bconfIdArgIndex = (grid.routePrefix.match(/\/:/g) || []).length,
            bconfId = arguments[bconfIdArgIndex];
        Editor.util.Util.closeWindows();
        await Editor.util.Util.awaitStore(grid.getStore());
        var selected = grid.getSelectionModel().getSelectionStart(),
            toSelect = grid.getStore().getById(bconfId);
        if(!toSelect){
            var correctRoute = Editor.util.Util.trimLastSlash(Ext.util.History.getToken()) + (selected ? '/' + selected.id : '');
            this.redirectTo(correctRoute);
        } else if(toSelect !== selected){
            grid.setSelection(toSelect);
        }
    },
    /**
     * Delete button handler
     * @param {Editor.plugins.Okapi.view.BconfGrid} view
     * @param {int} rowIndex
     */
    deleteBconf: function(view, rowIndex){
        view.select(rowIndex); // we need a selected row
        var me = this;
        // UGLY/FIXME: it seems the row selection events interfere with the prompt, which is immediately closed when clicking on a delete-icon of an unselected row.
        Ext.defer(function(){ this.doDeleteBconf(view); }, 50, this);
    },
    doDeleteBconf: function(view){
        Ext.Msg.confirm(view.grid.strings.confirmDeleteTitle + `: <i>"${view.selection.get('name')}"</i>`, view.grid.strings.confirmDeleteMessage, function(btnId){
            if(btnId === 'yes'){
                view.selection.drop();
            }
        });
    },
    /**
     *
     * @param view
     * @param rowIndex
     */
    cloneBconf: function(view, rowIndex){
        view.select(rowIndex);
        var me = this;
        // UGLY/FIXME: it seems the row selection events interfere with the prompt, which is immediately closed when clicking on a clone-icon of an unselected row.
        Ext.defer(function(){ this.doCloneBconf(view); }, 50, this);
    },
    doCloneBconf: async function(view){
        var name,
            rec = view.selection;
        try {
            name = await this.promptUniqueBconfName(view.ownerGrid, rec.get('name'));
        } catch(e){
            return;
        }
        var params = {id: rec.id, name: name};
        var customer = view.ownerGrid.getCustomer();
        if(customer){
            params.customerId = customer.id;
        }
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_okapi_bconf/clone',
            params,
            callback: function(reqOpts, success, response){
                var data = Ext.decode(response.responseText);
                if(success){
                    rec.store.add(data);
                    rec.store.sync();
                    rec.store.getFilters().notify('endupdate'); // trigger update
                    this.startEditDescription(data.id);
                } else {
                    Editor.app.getController('ServerException').handleException(response);
                }
            },
            scope: this,
        });
    },

    downloadBconf: function(view, rowIndex, /* colIndex */){
        view.select(rowIndex);
        Editor.util.Util.download('plugins_okapi_bconf/downloadbconf', {
            id: view.selection.id
        });
    },

    showFilterGrid: function(view, rowIndex){
        view.select(rowIndex);
        if(!location.hash.endsWith('/filters')){
            location.hash += '/filters';
        }
        var filterGrid = Ext.getCmp('bconfFilterGrid'),
            bconf = view.store.getById(view.selection.id);
        if(filterGrid){
            if(filterGrid.bconf.get('id') === bconf.get('id')){
                return;
            } else {
                filterGrid.close();
                filterGrid.destroy();
            }
        }
        filterGrid = Ext.create('Editor.plugins.Okapi.view.BconfFilterGrid', {
            bconf: bconf,
            constrain: true,
            modal: true,
            //renderTo: this.getView().up('viewport'),
            floating: true,
            closable: true,
            height: window.innerHeight - 50,
            width: window.innerWidth - 50,
            maximizable: true,
            //height: '95%',
            //width: '95%',
            resizable: true,
        });
        filterGrid.show();
    },

    showSRXChooser: function(view, rowIndex, colIndex, actionItem){
        var controller = this;
        view.select(rowIndex);
        var bconfId = view.selection.id;
        Editor.util.Util.chooseFile('.srx').then(function(files){
            controller.uploadSRX(bconfId, files[0], actionItem.purpose);
        });
    },
    downloadSRX: function(view, rowIndex, colIndex, /* actionItem */ {purpose}, e, /* record */ {id}){
        view.select(rowIndex);
        Editor.util.Util.download('plugins_okapi_bconf/downloadsrx', {id, purpose});
    },
    uploadSRX: function(id, srx, purpose){
        var controller = this,
            s = this.getView().strings;
        var [invalidTitle, invalidMsg, fileUploaded]
            = [s.invalidTitle, s.invalidMsg, s.fileUploaded].map(x => x.replace('{0}', 'SRX'));

        Editor.util.Util.fetchXHRLike(Editor.data.restpath + 'plugins_okapi_bconf/uploadsrx/?id=' + id, {
            method: 'POST', formData: {purpose, srx}
        }).then(function(response){
            var {status, responseJson: json = {}} = response;
            if(json.errorCode === 'E1390'){
                var extraInfo = controller.createInfoSpan(json);
                Ext.Msg.show({
                    title: invalidTitle,
                    message: invalidMsg + extraInfo,
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
    isDeleteDisabled: function(view, rowIndex, colIndex, item, record){
        return record.get('isDefault') || view.grid.isCustomerGrid && !record.get('customerId') || record.get('name') === Editor.data.plugins.Okapi.systemStandardBconfName;
    },
    isEditDisabled: function(view, rowIndex, colIndex, item, record){
        return ((view.ownerGrid.isCustomerGrid && !record.get('customerId')) || (record.get('name') === Editor.data.plugins.Okapi.systemStandardBconfName));
    },

    filterByText: function(field, searchString){
        var store = this.getView().getStore(),
            searchFilterValue = searchString.trim();
        if(searchFilterValue){
            var searchRE = new RegExp(Editor.util.Util.escapeRegex(searchFilterValue), 'i');
            store.addFilter({ //
                id: 'search',
                filterFn: ({data}) => searchRE.exec(JSON.stringify(data, ['id', 'name', 'description']))
            });
        } else {
            store.removeFilter('search');
        }
        field.getTrigger('clear').setVisible(searchFilterValue);
    },

    uploadBconf: async function(file){
        var me = this,
            grid = me.getView(),
            cutPosition = Math.min(file.name.search(/\.bconf$/), 50),
            fileName = file.name.substring(0, cutPosition); // remove .bconf
        if(!fileName || Ext.getStore('bconfStore').findUnfilteredByName(fileName)){
            try {
                fileName = await this.promptUniqueBconfName(grid, fileName);
            } catch(e){
                return;
            }
        }
        var data = new FormData();
        data.append('name', fileName);
        data.append(this.FILE_UPLOAD_NAME, file);
        if(grid.isCustomerGrid){
            var customer = grid.getCustomer() || {};
            data.append('customerId', customer.id);
        }
        Editor.util.Util.fetchXHRLike(Editor.data.restpath + 'plugins_okapi_bconf/uploadbconf', {
            method: 'POST',
            body: data
        }).then(function(response){
            if(response.status === 200 && response.responseJson){
                var id = response.responseJson.id;
                var store = Ext.getStore('bconfStore');
                new store.model({id}).load({
                    callback: function(rec){
                        store.add(rec);
                        me.startEditDescription();
                    }
                });
            } else if(response.status === 422){
                var extraInfo = me.createInfoSpan(response.responseJson);
                Ext.Msg.show({
                    title: grid.strings.invalidTitle.replace('{0}', 'Bconf'),
                    message: grid.strings.invalidMsg.replace('{0}', 'Bconf') + extraInfo,
                    icon: Ext.Msg.WARNING
                });
            } else if(response.status === 500 && response.responseJson?.errorCode === 'E1015'){
                me.uploadBconf(file); // bconf with same name was uploaded outside this session
            } else {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    startEditDescription: function(id){
        var grid = this.getView(),
            rec = grid.getStore().getById(id),
            descCol = grid.getColumnManager().getHeaderByDataIndex('description');
        // trigger description editing
        grid.editingPlugin.activateCell(grid.getView().getPosition(rec, descCol), /* skipBeforeCheck */ true, /* doFocus*/ true);
    },

    /**
     *
     * @param {Editor.plugins.Okapi.view.BconfGrid} grid
     * @param {string} nameToPrefill
     * @param {Editor.plugins.Okapi.model.BconfModel} allowedRec
     * @returns {Promise<unknown>}
     */
    promptUniqueBconfName: function(grid, nameToPrefill = '', allowedRec = null){
        return new Promise(function(resolve, reject){
            var panel = new Ext.form.Panel({
                floating: true,
                title: allowedRec ? grid.strings.editBconf : grid.strings.newBconf,
                defaultFocus: 'textfield',
                modal: true,
                closable: true,
                bodyPadding: 15,
                buttonAlign: 'center', // for fbar
                iconCls: allowedRec ? 'fa fa-edit' : 'fa fa-plus',
                listeners: {
                    close: function(form){
                        if(form.isValid()){
                            resolve(form.down('textfield').value);
                        } else {
                            reject();
                        }
                    }
                },
                items: [{
                    xtype: 'textfield',
                    fieldLabel: grid.strings.uniqueName,
                    maxLength: 50, // DB constraint
                    width: 300,
                    selectOnFocus: true,
                    labelSeparator: ':',
                    labelWidth: 70,
                    value: nameToPrefill,
                    name: 'bconfName',
                    allowOnlyWhitespace: false, // trims before validation
                    lastValidationResult: [null, false],
                    validator: function(name){
                        if(!name){
                            return false;
                        }
                        // QUIRK: the validator runs 3x in a row when validating, so to avoid checking the store permanently, we cache the result for a value
                        if(this.lastValidationResult[0] === name){ // already validated
                            return this.lastValidationResult[1];
                        }
                        var existingRec = Ext.getStore('bconfStore').findUnfilteredByName(name);
                        var valid = !existingRec || existingRec === allowedRec;
                        this.lastValidationResult = [name, valid]; // cache validation result
                        return valid;
                    },
                    listeners: {
                        specialkey: function(field, e){
                            if([e.ENTER, e.ESC].includes(e.keyCode)){
                                panel.close();
                            }
                        }
                    }
                }],
                fbar: [{
                    xtype: 'button',
                    text: 'OK',
                    formBind: true,
                    handler: function(){ panel.close(); }
                }]
            });
            panel.show();
            panel.isValid(); // trigger display of red border when invalid
        });
    },

    // Forbid systemDefault editing, show Name prompt
    handleBeforeedit: function(cellEditPlugin, cellContext){
        var grid = this.getView(),
            {name, customerId} = cellContext.record.getData();
        grid.view.select(cellContext.record);
        if(name === Editor.data.plugins.Okapi.systemStandardBconfName || grid.isCustomerGrid && !customerId){
            return false; // Can't change system default and globals bconfs in customer view
        }
        if(cellContext.field === 'name'){
            this.promptUniqueBconfName(grid, name, cellContext.record).then(function(changedName){
                cellContext.record.set('name', changedName);
            }).catch();
            return false;
        }
    },

    loadOkapiFilters: function(){
        Ext.create('Editor.plugins.Okapi.store.DefaultBconfFilterStore');
    },

    createInfoSpan: function(json){
        var extraInfo = '',
            errorMessage = json.errorMessage || '',
            errors = json.errors || errorMessage.split('\n').slice(1);
        errors = errors.flat().filter(err => err.trim()).map(Ext.htmlEncode);
        if(errors.length){
            var errorTable = `<code><ul><li>${errors.join('</li><li>')}</li></ul>`;
            extraInfo = ' ' + Ext.DomHelper.createHtml({
                tag: 'span',
                class: 'x-fa fa-question-circle pointer',
                'data-hide': 'user',
                'data-qwidth': '800',
                'data-qtip': errorTable
            });
        }
        return extraInfo;
    }
});