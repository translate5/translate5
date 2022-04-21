/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

    //Add new row at last with default okapi filter
/*    addNewFilterSet: function () {
        var me = this, view = me.getView(), store = view.getStore();
        var defaultRecord = store.findRecord('isDefault','1');
        if(!defaultRecord){
            return false;
        }
        var defaultFilterSet=defaultRecord.getData();
        delete defaultFilterSet['id'];
        defaultFilterSet['isDefault']=0;
        me.addnewRecord(defaultFilterSet);
    },
*/
    editbconf: function (grid, rowIndex, /* colIndex */) {
        var rec = grid.getStore().getAt(rowIndex);
        var win = Ext.create('Editor.plugins.Okapi.view.filterDetails.BConfFilterWindow', {
            title: 'Filter Configuration -' + rec.get('name'),
        })
        win.show();
    },

    deletebconf: function (view, rowIndex, /* colIndex */) {
        view.select(rowIndex);
        Ext.Msg.confirm(view.grid.strings.confirmDeleteTitle+`: <i>"${view.selection.get('name')}"</i>`, view.grid.strings.confirmDeleteMessage, function(btnId){
            if(btnId === 'yes'){
                view.selection.drop();
            }
        });
    },

    clonebconf: function (view, rowIndex, /* colIndex */) {
        view.select(rowIndex);
        var rec = view.selection;
        var input = Ext.Msg.down('textfield');
        input.on('focus', x=>input.selectText(), this, {single:true, delay: 100});
        Ext.Msg.prompt('New bconf', 'Name of the new entry?', function (btnId, prompt) {
            if (!prompt || prompt == rec.get('name')) { // invalid name
                return;
            }
            var params =  {
                id: rec.id,
                name: prompt
            };
            var customer = view.ownerGrid.getCustomer();
            if(customer){
                params.customerId = customer.id;
            }
            Ext.Ajax.request({
                url: Editor.data.restpath + 'plugins_okapi_bconf/clone',
                params,
                callback: function (reqOpts, success, response) {
                    var data = Ext.decode(response.responseText);
                    if (success) {
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
        }, /* scope */ this, /*multiline*/ false, rec.get('name'));

    },

    exportbconf: function (view, rowIndex, /* colIndex */) {
        view.select(rowIndex);
        Editor.util.Util.download('plugins_okapi_bconf/downloadbconf', {
            bconfId: view.selection.id,
            okapiName: view.selection.get('name'),
        });
    },
    showSRXChooser: function (view, rowIndex, /* colIndex */) {
        var controller = this;
        view.select(rowIndex);
        var bconfId = view.selection.id;
        Editor.util.Util.chooseFile( '.srx').then(function(files){
            controller.uploadSRX(bconfId, files[0]);
        });
    },
    downloadSRX: function (view, rowIndex, /* colIndex */) {
        view.select(rowIndex);
        Editor.util.Util.download(Editor.data.restpath + 'plugins_okapi_bconf/downloadsrx?id='+view.selection.id)
    },
    uploadSRX: function(id, file){
        var grid = this.getView();
        Editor.util.Util.fetchXHRLike(Editor.data.restpath + 'plugins_okapi_bconf/uploadsrx/?id='+id, {
            method: 'POST', formData : { srx: file }
        }).then(function({status, responseJson: json}){
            if(status === 422){


                var extraInfo = '';
                if(json.errors && json.errors.length){
                    extraInfo = ' ' + Ext.DomHelper.createHtml({
                        tag: 'span',
                        class: 'x-fa fa-question-circle pointer',
                        'data-qtip': '<code><ul><li>' + json.errors.join('<li></li>') + '</li></ul>',
                        'data-hide': 'user',
                        'data-qwidth': '500',
                    });
                }
                Ext.Msg.show({
                    title: grid.strings.invalidSrxTitle,
                    message: grid.strings.invalidSrxMsg + extraInfo,
                    icon: Ext.Msg.WARNING
                });
            }
        })
    },
    isDeleteDisabled:function ({grid}, rowIndex, colIndex, item, {data:bconf}) {
        return bconf.isDefault || grid.isCustomerGrid && !rec.customerId || bconf.name === grid.SYSTEM_BCONF_NAME;
    },
    isSRXUploadDisabled:function (view, rowIndex, colIndex, item, record) {
        return view.ownerGrid.isCustomerGrid && !record.get('customerId');
    },

    filterByText: function (field, searchString){
        var store = this.getView().getStore(),
            searchFilterValue = searchString.trim();
        store.clearFilter();
        if (searchFilterValue) {
            var searchRE = new RegExp(searchFilterValue,'i');
            store.filterBy(({data}) => searchRE.exec(JSON.stringify(data)));
        }
        field.getTrigger('clear').setVisible(searchFilterValue);
    },

    uploadBconf: function (file){
        var controller = this;
        var grid = this.getView();
        var data = new FormData();
        data.append(this.FILE_UPLOAD_NAME, file);
        if(grid.isCustomerGrid) {
            var customer = grid.getCustomer() || {};
            data.append('customerId', customer.id);
        }
        Editor.util.Util.fetchXHRLike(Editor.data.restpath + 'plugins_okapi_bconf/uploadbconf', {
            method: 'POST',
            body: data
        }).then(function(response){
            if(response.status === 200) {
                var id = response.responseJson.id;
                var store = Ext.getStore('bconfStore');
                new store.model({id}).load({
                    callback: function(rec){
                        store.add(rec);
                        controller.startEditDescription();
                    }
                });
            } else {
                Editor.app.getController('ServerException').handleException(response);
            }
        })
    },

    startEditDescription(id){
        var grid = this.getView(),
            rec = grid.getStore().getById(id),
            descCol = grid.getColumnManager().getHeaderByDataIndex('description');
        // trigger description editing
        grid.editingPlugin.activateCell(grid.getView().getPosition(rec, descCol), /* skipBeforeCheck */ true, /* doFocus*/ true);
    }

});