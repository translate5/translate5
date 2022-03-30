
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
 * @class Editor.plugins.Okapi.view.filter.BConfGridController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.Okapi.view.filter.BConfGridController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.bconfGridController',

    //Add new row at last with default okapi filter
/*    addNewFilterSet: function () {
        var me = this, view = me.getView(), store = view.getStore();
        var defaultRecord = store.findRecord('default','1');
        if(!defaultRecord){
            return false;
        }
        var defaultFilterSet=defaultRecord.getData();
        delete defaultFilterSet['id'];
        defaultFilterSet['default']=0;
        me.addnewRecord(defaultFilterSet);
    },
*/
    editbconf: function (grid, rowIndex, colIndex) {
        var rec = grid.getStore().getAt(rowIndex);
        var win = Ext.create('Editor.plugins.Okapi.view.filterDetails.BConfFilterWindow', {
            title: 'Filter Configuration -' + rec.get('name'),
        })
        win.show();
    },

    deletebconf: function (view, rowIndex, colIndex) {
        var rec = view.getStore().getAt(rowIndex);
        rec.drop();
        //rec.store.sync(); // needed when autoSync:false on the store
    },

    clonebconf: function (view, rowIndex, colIndex) {
                var rec = view.getStore().getAt(rowIndex);
        Ext.Msg.prompt('New bconf','Name of the new entry?',function(btnId, value, msgConfig){
            if(!value || value == rec.get('name')){
                return;
            }
                Ext.Ajax.request({
                    url: Editor.data.restpath + 'plugins_okapi_bconf/clone',
                    params: {
                        id: rec.id,
                        customer_id: view.grid.getCustomer()?.id,
                        name: value
                    },
                    success: function(response){
                        var data = Ext.decode(response.responseText);
                        rec.store.add(data);
                        rec.store.sync();
                        rec.store.getFilters().notify('endupdate'); // trigger update
                    },
                    scope: this
                });
        })
    },

    exportbconf: function ({grid}, rowIndex, colIndex) {
        var okapiName = grid.getStore().getAt(rowIndex).get('name');
        var bconfId = grid.getStore().getAt(rowIndex).get('id');
        var form = Ext.create('Ext.form.Panel',{
            timeout: 60000
        });
        form.submit({
            url     : Editor.data.restpath + 'plugins_okapi_bconf/exportbconf',
            method  : 'GET',
            standardSubmit: true,
            params  : {
                okapiName: okapiName,
                bconfId: bconfId
            },
            target: '_blank'
        });
    },
    showSRXChooser: function (view, rowIndex, colIndex) {
        var rec = view.getStore().getAt(rowIndex),
            srxInput = view.ownerGrid.down('#srxInput');
        srxInput.el.dom.recId = rec.id;
        srxInput.el.dom.click();
    },
    downloadSRX: function (view, rowIndex, colIndex) {
        var dlAnchor = view.dlAnchor || (view.dlAnchor = Ext.DomHelper.createDom({
            tag: 'a',
            download: ''
        }));
        var rec = view.getStore().getAt(rowIndex);
        dlAnchor.setAttribute('href', Editor.data.restpath + 'plugins_okapi_bconf/downloadSRX?id='+rec.id)
        dlAnchor.click();
    },
    getActionStatus:function (view, rowIndex, colIndex, item, record) {
        return record.get('default') == "1";
    },

    filterByText: function (filterField){
        var store = this.getView().getStore(),
            searchFilterValue =filterField.getValue().trim();
        store.clearFilter();
        if (searchFilterValue) {
            var searchRE = new RegExp(searchFilterValue,'i');
            store.filterBy(({data}) => searchRE.exec(JSON.stringify(data)));
        }
    },

    uploadBconf: function (e, input, eOpts) {
        var data = new FormData()
        data.append('bconffile', input.files[0]);

        fetch(Editor.data.restpath + 'plugins_okapi_bconf/importbconf', {
            method: 'POST',
            body: data
        }).then(function(response){
            Ext.getStore('bconfStore').reload();
        })
        input.value = ''; // reset file input
    }

});