/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.plugins.Okapi.view.fprm.component.Grid', {
    filterObj: null,
    gridCols: {},
    strings: {
        actions: '#UT#Aktionen',
        actionDelete: '#UT#Löschen',
        actionClone: '#UT#Klonen'
    },

    constructor: function(filterObj){
        this.filterObj = filterObj;
    },

    addCustomFieldControl: function (data, id, name, config, holder, disabled) {
        if (data.type !== 'grid') {
            throw new Error('addCustomFieldControl: unknown field type "' + data.type + '"');
        }
        this.gridCols[this.getId(id)] = config.cols;
        config.columns = [];
        for (let i = 0; i < config.cols.length; i++) {
            const header = this.filterObj.getFieldCaption(config.cols[i].itemId, {});
            config.columns.push(Object.assign({
                xtype: 'gridcolumn',
                menuDisabled: true,
                sortable: false,
                dataIndex: i.toString(),
                header: header,
                tooltip: header + ' (' + config.cols[i].itemId + ')',
                flex: 1,
                editor: {}
            }, config.cols[i]));
        }
        config.columns.push({
            xtype: 'actioncolumn',
            align: 'center',
            width: 100,
            header: this.strings.actions,
            menuDisabled: true,
            items: [
                {
                    tooltip: this.strings.actionDelete,
                    isDisabled: function (view, rowIndex, colIndex, item, record) {
                        return record.get('id') === 1;
                    },
                    glyph: 'f2ed@FontAwesome5FreeSolid',
                    handler: function (view, rowIdx, colIdx, actionCfg, evt, rec) {
                        rec.store.remove(rec);
                    }
                },
                {
                    tooltip: this.strings.actionClone,
                    margin: '0 0 0 10px',
                    glyph: 'f24d@FontAwesome5FreeSolid',
                    handler: function (view, rowIdx, colIdx, actionCfg, evt, rec) {
                        let sdata = rec.store.data.items, maxId = rec.get('id');
                        for (var i = 0; i < sdata.length; i++) {
                            if (sdata[i].id > maxId) {
                                maxId = sdata[i].id;
                            }
                        }
                        rec.store.add(Object.assign(rec.copy().data, {id: maxId + 1}));
                    }
                }
            ]
        });

        config = Object.assign(config, {
            xtype: 'grid',
            itemId: this.getId(id),
            userCls: 't5actionColumnGrid t5noselectionGrid',
            border: 0,
            width: '100%',
            height: 'auto',
            viewConfig:{
                markDirty:false
            },
            store: {
                type: 'json',
                data: this.getStoreData(id, config.cols)
            },
            plugins: [Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1
            })]
        });
        this.filterObj.fields[name] = config;
        return holder.add(config);
    },

    getFormValues: function (vals) {
        for (const [gridId, cols] of Object.entries(this.gridCols)) {
            const grid = Ext.ComponentQuery.query('#' + gridId);
            if (grid.length > 0) {
                const fieldId = gridId.split('_').pop(),
                    records = grid[0].getStore().getRange();
                let rowIdxOut = 0;
                for (let rowIdx = 0; rowIdx < records.length; rowIdx++) {
                    let rowIsEmpty = true;
                    for (let colIdx = 0; colIdx < cols.length; colIdx++) {
                        const val = records[rowIdx].data[colIdx.toString()];
                        if (typeof val !== 'undefined' && val !== '') {
                            vals[fieldId + '.' + rowIdxOut + '.' + cols[colIdx].itemId] = val;
                            rowIsEmpty = false;
                        }
                    }
                    if (!rowIsEmpty) {
                        rowIdxOut++;
                    }
                }
                vals[fieldId + '.number.i'] = rowIdxOut;
            }
        }
        return vals;
    },

    getStoreData: function (id, cols) {
        const numRows = this.filterObj.getFieldValue(id + '_number', 0, 'integer', false);
        if (numRows === 0) {
            return [{id: 1}];
        }
        let propId, row, result = [];
        for (let rowIdx = 0; rowIdx < numRows; rowIdx++) {
            row = {id: rowIdx + 1};
            for (let colIdx = 0; colIdx < cols.length; colIdx++) {
                propId = id + '_' + rowIdx + '_' + cols[colIdx].itemId;
                if (this.filterObj.transformedData.hasOwnProperty(propId)) {
                    row[colIdx.toString()] = this.filterObj.transformedData[propId];
                }
            }
            result.push(row);
        }
        return result;
    },

    getId: function (fieldId) {
        // combined with class name, e.g. Idml_excludedStyleConfigurations
        return this.filterObj.self.getName().split('.').pop() + '_' + fieldId;
    }

});