
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
        var defaultFilterSet = store.getAt(0).getData();
        delete defaultFilterSet['id'];
        me.addnewRecord(defaultFilterSet);
    },

    editbconf: function (grid, rowIndex, colIndex) {
        var rec = grid.getStore().getAt(rowIndex);
        var win = Ext.create('Editor.view.admin.okapi.filter.BConfFilterWindow', {
            title: 'Filter Configuration -' + rec.get('name'),
        })
        win.show();
    },
    deletebconf: function (grid, rowIndex, colIndex) {
        grid.getStore().removeAt(rowIndex);
    },

    copybconf: function (grid, rowIndex, colIndex) {
        var rec = grid.getStore().getAt(rowIndex);
        delete rec['id'];
        this.addnewRecord(rec);
    },

    exportbconf: function (grid, rowIndex, colIndex) {
        var rec = grid.getStore().getAt(rowIndex);
        alert("Expoting");
    }
});