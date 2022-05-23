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
 * @class BConfGridController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.Okapi.view.BconfGridController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.bconfGridController',
    /** @const {string} FILE_UPLOAD_NAME */
    FILE_UPLOAD_NAME: 'bconffile',

    deletebconf: function(view, rowIndex, /* colIndex */){
        view.select(rowIndex);
        Ext.Msg.confirm(view.grid.strings.confirmDeleteTitle + `: <i>"${view.selection.get('name')}"</i>`, view.grid.strings.confirmDeleteMessage, function(btnId){
            if(btnId === 'yes'){
                view.selection.drop();
            }
        });
    },

    clonebconf: async function(view, rowIndex, /* colIndex */){
        view.select(rowIndex);
        var name, rec = view.selection;
        try {
            name = await this.promptUniqueBconfName(rec.get('name'));
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
            = [s.invalidTitle, s.invalidMsg, s.fileUploaded].map(x => x.replace('{}', 'SRX'));

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
    isDeleteDisabled: function({grid}, rowIndex, colIndex, item, {data: bconf}){
        return bconf.isDefault || grid.isCustomerGrid && !bconf.customerId || bconf.name === Editor.data.plugins.Okapi.systemDefaultBconfName;
    },
    isSRXUploadDisabled: function(view, rowIndex, colIndex, item, record){
        return ((view.ownerGrid.isCustomerGrid && !record.get('customerId')) || (record.get('name') === Editor.data.plugins.Okapi.systemDefaultBconfName));
    },

    filterByText: function(field, searchString){
        var store = this.getView().getStore(),
            searchFilterValue = searchString.trim();
        store.clearFilter();
        if(searchFilterValue){
            var searchRE = new RegExp(Editor.util.Util.escapeRegex(Editor.util.Util.escapeRegex(searchFilterValue)), 'i');
            store.filterBy(({data}) => searchRE.exec(JSON.stringify(data, ['id', 'name', 'description'])));
        }
        field.getTrigger('clear').setVisible(searchFilterValue);
    },

    uploadBconf: async function(file){
        var me = this,
            cutPosition = Math.min(file.name.search(/\.bconf$/), 50),
            fileName = file.name.substring(0, cutPosition); // remove .bconf

        if(!fileName || Ext.getStore('bconfStore').getData().find('name', fileName, 0, true, true, true)){ //...start, startsWith, endsWith, ignoreCase
            try {
                fileName = await this.promptUniqueBconfName(fileName);
            } catch(e){
                return;
            }
        }
        var controller = this;
        var grid = this.getView();
        var {invalidTitle, invalidMsg} = grid.strings;
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
                        controller.startEditDescription();
                    }
                });
            } else if(response.status === 422){
                var extraInfo = controller.createInfoSpan(response.responseJson);
                Ext.Msg.show({
                    title: invalidTitle.replace('{}', 'Bconf'),
                    message: invalidMsg.replace('{}', 'Bconf') + extraInfo,
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

    promptUniqueBconfName: function(nameToPrefill = '', allowedRec = null){
        var grid = this.getView(),
            {uniqueName, nameExists, newBconf, editBconf} = grid.strings,
            bconfs = Ext.getStore('bconfStore').getData();
        return new Promise(function(resolve, reject){
            var panel = new Ext.form.Panel({
                floating: true,
                title: allowedRec ? editBconf : newBconf,
                defaultFocus: 'textfield',
                modal: true,
                closable: true,
                bodyPadding: 15,
                buttonAlign: 'center', // for fbar
                iconCls: allowedRec ? 'fa fa-edit' : 'fa fa-plus',
                listeners: {
                    close: form => form.isValid() ? resolve(form.down('textfield').value) : reject(),
                },
                items: [{
                    xtype: 'textfield',
                    fieldLabel: uniqueName,
                    maxLength: 50, // DB constraint
                    width: 300,
                    selectOnFocus: true,
                    labelSeparator: ':',
                    labelWidth: 70,
                    lastVal: ['', false],
                    value: nameToPrefill,
                    name: 'bconfName',
                    allowOnlyWhitespace: false, // trims before validation
                    validator: function(v){
                        if(this.lastVal[0] === v){ // already validated
                            return this.lastVal[1];
                        }
                        var existingRec = v && bconfs.find('name', v, 0, true, true, true);
                        var ret = !existingRec || existingRec === allowedRec || nameExists; //...start, startsWith, endsWith, ignoreCase
                        this.lastVal = [v, ret]; // cache validation result
                    },
                    listeners: {
                        specialkey: function(field, e){
                            if([e.ENTER, e.ESC].includes(e.keyCode)){
                                panel.close();
                            }
                        }
                    }
                }],
                fbar: [{xtype: 'button', text: 'OK', formBind: true, handler: () => panel.close()}]
            }).show();
            panel.isValid(); // trigger display of red border when invalid
        });
    },

    handleBeforeedit: function(cellEditPlugin, cellContext){
        var grid = this.getView(),
            {name, customerId} = cellContext.record.getData();
        grid.view.select(cellContext.record);
        if(name === Editor.data.plugins.Okapi.systemDefaultBconfName || grid.isCustomerGrid && !customerId){
            return false; // Can't change system default and globals bconfs in customer view
        }
        if(cellContext.field === 'name'){
            this.promptUniqueBconfName(name, cellContext.record).then(function(changedName){
                cellContext.record.set('name', changedName);
            }).catch();
            return false;
        }
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
    },

});