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

Ext.define('Editor.plugins.Okapi.view.fprm.component.Codefinder', {
    id: 'bconfCodeFinder',
    filterObj: null,
    gridId: '',
    strings: {
        useCodefinder: '#UT#Codefinder verwenden',
        add: '#UT#Hinzufügen',
        remove: '#UT#Entfernen',
        moveUp: '#UT#Hochschieben',
        moveDown: '#UT#Untenschieben',
        modify: '#UT#Ändern',
        accept: '#UT#Akzeptieren',
        discard: '#UT#Abbrechen',
        patterns: '#UT#Muster',
        testWithAllRules: '#UT#Test mit allen Regeln'
    },
    requires: [
        'Editor.plugins.Okapi.view.fprm.component.CodefinderViewModel',
        'Editor.plugins.Okapi.view.fprm.component.CodefinderViewController',
    ],

    constructor: function(filterObj){
        this.filterObj = filterObj;
    },

    addCustomFieldControl: function (data, id, name, config, holder, disabled) {
        if (data.type !== 'codefinder') {
            throw new Error('addCustomFieldControl: unknown field type "' + data.type + '"');
        }
        this.gridId = this.getId(id);
        const me = this;
        config = Object.assign(config, {
            viewModel: {
                type: 'codefinder'
            },
            border: 0,
            controller: 'codefinder',
            defaults: {
                xtype: 'container',
                flex: 1,
                margin: '0 5 0 0'
            },
            layout: 'hbox',
            items: [{
                items: [{
                    xtype: 'checkbox',
                    inputValue: 1,
                    name: 'useCodeFinder.b',
                    reference: 'useCodeFinder',
                    fieldLabel: this.strings.useCodefinder,
                    labelWidth: 150,
                    bind: {
                        value: '{finderEnabled}'
                    }
                }, {
                    xtype: 'grid',
                    itemId: this.gridId,
                    reference: 'cfGrid',
                    userCls: 't5actionColumnGrid t5noselectionGrid',
                    height: 300,
                    bind: {
                        disabled: '{!finderEnabled || finderSelectionEditing}',
                        store: '{finderStore}',
                        selection: '{finderSelection}'
                    },
                    border: 0,
                    width: '100%',
                    columns: [{
                        xtype: 'gridcolumn',
                        menuDisabled: true,
                        sortable: false,
                        dataIndex: '0',
                        renderer: function(value, metaData) {
                            return Ext.util.Format.htmlEncode(value);
                        },
                        flex: 1
                    }]
                }, {
                    border: 0,
                    items: [{
                        xtype: 'button',
                        itemId: 'add',
                        text: this.strings.add,
                        bind: {
                            disabled: '{!finderEnabled || finderSelectionEditing}'
                        },
                        width: 95,
                        margin: '0 5px 0 0'
                    }, {
                        xtype: 'button',
                        itemId: 'up',
                        text: this.strings.moveUp,
                        bind: {
                            disabled: '{moveUpDisabled}'
                        },
                        width: 115,
                        margin: '0'
                    }]
                }, {
                    border: 0,
                    items: [{
                        xtype: 'button',
                        itemId: 'remove',
                        text: this.strings.remove,
                        bind: {
                            disabled: '{!finderEnabled || !finderSelection || finderSelectionEditing}'
                        },
                        width: 95,
                        margin: '0 5px 0 0'
                    }, {
                        xtype: 'button',
                        itemId: 'down',
                        text: this.strings.moveDown,
                        bind: {
                            disabled: '{moveDownDisabled}'
                        },
                        width: 115,
                        margin: '0'
                    }]
                }]
            },{
                flex: 2,
                items: [{
                    xtype: 'textareafield',
                    itemId: 'expr',
                    bind: {
                        disabled: '{!useCodeFinder.checked || !finderSelectionEditing}',
                        value: '{cfGrid.selection.0}'
                    },
                    listeners: {
                        enable: function() {
                            this.focus();
                        }
                    },
                    width: '100%',
                    height: 125
                },{
                    layout: 'hbox',
                    border: 0,
                    items: [{
                        xtype: 'button',
                        itemId: 'modify',
                        text: this.strings.modify,
                        disabled: true,
                        bind: {
                            disabled: '{!finderEnabled || !finderSelection}',
                            hidden: '{finderSelectionEditing}'
                        },
                        width: 100,
                        margin: '0 5px 0 0'
                    }, {
                        xtype: 'button',
                        itemId: 'accept',
                        text: this.strings.accept,
                        disabled: true,
                        bind: {
                            disabled: '{!finderEnabled || !finderSelectionModified}',
                            hidden: '{!finderSelectionEditing}'
                        },
                        width: 100,
                        margin: '0 5px 0 0'
                    }, {
                        xtype: 'button',
                        itemId: 'discard',
                        text: this.strings.discard,
                        disabled: true,
                        bind: {
                            disabled: '{!finderEnabled || !finderSelectionEditing}'
                        },
                        width: 100,
                        margin: '0 5px 0 0'
                    }, {
                        xtype: 'button',
                        itemId: 'patterns',
                        text: this.strings.patterns +'...',
                        bind: {
                            disabled: '{!useCodeFinder.checked}'
                        },
                        handler: function(){
                            window.open('https://docs.oracle.com/javase/8/docs/api/java/util/regex/Pattern.html', '_blank');
                        },
                        width: 100,
                        margin: '0 15px 0 0'
                    }, {
                        xtype: 'checkbox',
                        inputValue: 1,
                        itemId: 'useAllRulesWhenTesting',
                        name: 'codeFinderRules.useAllRulesWhenTesting.b',
                        checked: this.filterObj.getFieldValue('codeFinderRules_useAllRulesWhenTesting', true, 'boolean', false),
                        bind: {
                            readOnly: '{!useCodeFinder.checked}'
                        },
                        labelWidth: 130,
                        fieldLabel: this.strings.testWithAllRules
                    }]
                },{
                    xtype: 'textareafield',
                    value: this.filterObj.getFieldValue('codeFinderRules_sample', '', 'string', true),
                    bind: {
                        readOnly: '{!useCodeFinder.checked}'
                    },
                    itemId: 'sample',
                    name: 'codeFinderRules.sample',
                    width: '100%',
                    margin: '10px 0',
                    height: 125
                },{
                    xtype: 'textareafield',
                    itemId: 'result',
                    name: 'codeFinderRules.result',
                    readOnly: true,
                    bind: {
                        disabled: '{!useCodeFinder.checked}'
                    },
                    width: '100%',
                    height: 125
                }]
            }]

        });
        this.filterObj.fields[name] = config;
        var finder = holder.add(config);
        finder.getViewModel().set('finderStoreData', this.getStoreData(id)); // _data = [{0: 'zxc'}, {0: 'qwe'}];
        finder.getViewModel().set('finderEnabled', this.filterObj.getFieldValue('useCodeFinder', false, 'boolean', false));
        return finder;
    },

    getFormValues: function (vals) {
        const fieldId = this.gridId.split('_').pop(),
            records = Ext.ComponentQuery.query('#' + this.gridId)[0].getStore().getRange();
        let rowIdxOut = 0;
        for (let rowIdx = 0; rowIdx < records.length; rowIdx++) {
            let rowIsEmpty = true;
            const val = records[rowIdx].data['0'];
            if (typeof val !== 'undefined' && val !== '') {
                vals[fieldId + '.rule' + rowIdxOut] = val;
                rowIsEmpty = false;
            }
            if (!rowIsEmpty) {
                rowIdxOut++;
            }
        }
        delete vals[fieldId + '.result'];
        if (!vals[fieldId + '.sample']) {
            vals[fieldId + '.sample'] = '';
        }
        vals[fieldId + '.count.i'] = rowIdxOut;
        vals['useCodeFinder.b'] = !!vals['useCodeFinder.b'];
        vals[fieldId + '.useAllRulesWhenTesting.b'] = !!vals['codeFinderRules.useAllRulesWhenTesting.b'];
        return vals;
    },

    getStoreData: function (id) {
        const numRows = this.filterObj.getFieldValue(id + '_count', 0, 'integer', false);
        if (numRows === 0) {
            return [];
        }
        let propId, row, result = [];
        for (let rowIdx = 0; rowIdx < numRows; rowIdx++) {
            row = {id: rowIdx + 1};
            propId = id + '_rule' + rowIdx;
            if (this.filterObj.transformedData.hasOwnProperty(propId)) {
                row['0'] = this.filterObj.transformedData[propId];
            }
            result.push(row);
        }
        return result;
    },

    getId: function (fieldId) {
        // combined with class name, e.g. Idml_codeFinderRules
        return this.filterObj.self.getName().split('.').pop() + '_' + fieldId;
    }

});