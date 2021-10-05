
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
 * @class Editor.plugins.Okapi.view.filter..BConfGridController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.Okapi.view.filter.BConfGridController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.bconfGridController',

    addnewRecord: function (rec) {
        var me = this, view = me.getView(), store = view.getStore();
        store.add(rec);
    },
    //Add new row at last with default okapi filter
    addNewFilterSet: function () {
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

    editbconf: function (grid, rowIndex, colIndex) {
        var rec = grid.getStore().getAt(rowIndex);
        var win = Ext.create('Editor.plugins.Okapi.view.filterDetails.BConfFilterWindow', {
            title: 'Filter Configuration -' + rec.get('name'),
        })
        win.show();
    },
    deletebconf: function (grid, rowIndex, colIndex) {
        grid.getStore().removeAt(rowIndex);
    },

    copybconf: function (grid, rowIndex, colIndex) {
        var rec = grid.getStore().getAt(rowIndex).getData();
        delete rec['id'];
        rec['default']=0;
        this.addnewRecord(rec);
    },

    exportbconf: function (grid, rowIndex, colIndex) {
        var okapiName = grid.getStore().getAt(rowIndex).get('name');
        var okapiId = grid.getStore().getAt(rowIndex).get('id');
        var form = Ext.create('Ext.form.Panel',{
            timeout: 60000
        });

        form.submit({
            url     : Editor.data.restpath + 'plugins_okapi_bconf',
            method  : 'POST',
            params  : {
                okapiName:okapiName,
                okapiId:okapiId
            },
            scope   : this,
            success : function(responseText){
            },
            target: '_blank'
        });
    },
    getActionStatus:function (view, rowIndex, colIndex, item, record) {
        return record.get('default')=="1";
    },
    filterByText: function (text){
        var me = this, view = me.getView(), store = view.getStore();
        var searchFilterValue =text.getValue().trim().toLowerCase();
        store.clearFilter();
        if (searchFilterValue !="") {
            store.filterBy((item)=>{
               return item.get('name').toLowerCase().indexOf(searchFilterValue)>-1 || item.get('extensions').toLowerCase().indexOf(searchFilterValue)>-1  || item.get('description').toLowerCase().indexOf(searchFilterValue)>-1
            })
        }
    },

    importbconf: function (grid, rowIndex, colIndex) {
        // var okapiName = grid.getStore().getAt(rowIndex).get('name');
        // var okapiId = grid.getStore().getAt(rowIndex).get('id');
        var form = Ext.create('Ext.form.Panel',{
            timeout: 60000
        });

        form.submit({
            url     : Editor.data.restpath + 'plugins_okapi_bconf/import',
            method  : 'POST',
            // params  : {
            //     okapiName:okapiName,
            //     okapiId:okapiId
            // },
            scope   : this,
            success : function(responseText){
            },
            target: '_blank'
        });
    },
});