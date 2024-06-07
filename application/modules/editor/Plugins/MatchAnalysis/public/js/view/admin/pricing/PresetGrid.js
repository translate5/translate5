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
 * Lists and manages the available pricing presets to choose from when creating a task
 */
Ext.define('Editor.plugins.MatchAnalysis.view.admin.pricing.PresetGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.plugins.MatchAnalysis.view.admin.pricing.PresetGridController',
        'Editor.plugins.MatchAnalysis.view.admin.pricing.PresetPricesGrid',
        'Editor.plugins.MatchAnalysis.store.admin.pricing.PresetStore',
    ],
    alias: 'widget.pricingPresetGrid',
    plugins: ['cellediting'],
    itemId: 'pricingPresets',
    controller: 'Editor.plugins.MatchAnalysis.view.admin.pricing.PresetGridController',
    store: 'pricingPresetStore',
    isCustomerGrid: false,
    userCls: 't5actionColumnGrid t5leveledGrid',
    bind: {
        title: '{l10n.MatchAnalysis.pricing.preset.title}'
    },
    glyph: 'f4c0@FontAwesome5FreeSolid',
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    listeners: {
        beforeedit: 'onBeforeEdit',
        edit: 'onPresetEdit'
    },
    config: {
        customer: null
    },
    dockedItems: [{
        xtype: 'toolbar',
        dock: 'top',
        enableOverflow: true,
        items: [
            {
                xtype: 'textfield',
                width: 300,
                minWidth: 100,
                bind: {
                    emptyText: '{l10n.MatchAnalysis.pricing.preset.search}'
                },
                triggers: {
                    clear: {
                        cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                        handler: function(field){
                            field.setValue(null);
                            field.focus();
                        },
                        hidden: true
                    }
                },
                listeners: {
                    change: 'filterByKeyword',
                    buffer: 150
                }
            },
            {
                xtype: 'button',
                glyph: 'f067@FontAwesome5FreeSolid',
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.create}'
                },
                ui: 'default-toolbar-small',
                width: 'auto',
                handler: 'createPreset'
            },
            {
                xtype: 'button',
                iconCls: 'x-fa fa-undo',
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.refresh}'
                },
                handler: function(btn){
                    btn.up('grid').getStore().getSource().reload();
                }
            },
            {
                xtype: 'tbspacer',
                flex: 1.6
            }
        ]
    }],
    viewConfig: {
        enableTextSelection: true,
        getRowClass: function(record) {
            var classes = [], isCustomerGrid = this.grid.isCustomerGrid,
                customer = isCustomerGrid ? this.grid.ownerCt.ownerCt.getViewModel().getData().list.selection : null;
            if (!isCustomerGrid || (customer && customer.get('id') === record.get('customerId'))) {
                classes.push('t5level0 pointer');
            } else {
                classes.push('t5level1');
            }
            if ((customer && customer.get('defaultPricingPresetId'))
                ? (customer.get('defaultPricingPresetId') === record.id)
                : record.get('isDefault')) {
                classes.push('t5chosenDefault');
            }
            return classes.join(' ');
        }
    },
    initConfig: function(instanceConfig){
        var me = this,
            config = {};
        config.title = me.title; //see EXT6UPD-9
        config.userCls = instanceConfig.isCustomerGrid ? 't5actionColumnGrid t5leveledGrid t5noselectionGrid' : 't5actionColumnGrid t5noselectionGrid'; // for the non-customer view, we do not need the leveled grid decorations
        config.columns = [{
                xtype: 'gridcolumn',
                dataIndex: 'id',
                text: 'Id',
                hidden: true
            },
            {
                xtype: 'gridcolumn',
                width: 260,
                dataIndex: 'name',
                stateId: 'name',
                flex: 1,
                editor: 'textfield',
                renderer: 'editableCellRenderer',
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.name}'
                }
            },
            {
                xtype: 'gridcolumn',
                width: 360,
                dataIndex: 'unitType',
                stateId: 'unitType',
                renderer: 'editableUnitTypeCellRenderer',
                flex: 1,
                editor: {
                    field: {
                        xtype: 'combobox',
                        queryMode: 'local',
                        allowBlank: false,
                        displayField: 'title',
                        valueField: 'value',
                        store: {
                            type: 'json',
                            fields: ['title', 'value'],
                            data: [
                                {title: Editor.data.l10n.MatchAnalysis.pricing.preset.unitType['word']     , value: 'word'},
                                {title: Editor.data.l10n.MatchAnalysis.pricing.preset.unitType['character'], value: 'character'}
                            ]
                        }
                    }
                },
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.unitType.text}'
                }
            },
            {
                xtype: 'gridcolumn',
                alias: 'desc',
                dataIndex: 'description',
                stateId: 'description',
                editor: {
                    field: {
                        xtype: 'textfield',
                        allowBlank: false,
                        bind: {
                            emptyText: '{l10n.MatchAnalysis.pricing.preset.desc}'
                        }
                    }
                },
                renderer: 'editableCellRenderer',
                flex: 3,
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.desc}'
                }
            },{
                xtype: 'numbercolumn',
                dataIndex: 'priceAdjustment',
                align: 'end',
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.priceAdjustment}',
                },
                width: 150,
                renderer: 'editablePriceAdjustmentCellRenderer',
                editor: {
                    xtype: 'numberfield',
                    valueToRaw: value => Ext.util.Format.number(Ext.valueFrom(value, ''), '0.00')
                }
            },{
                xtype: 'checkcolumn',
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.isCustomerDefault.text}',
                    tooltip: '{l10n.MatchAnalysis.pricing.preset.isCustomerDefault.tooltip}'
                },
                width: 90,
                itemId: 'customerDefaultColumn',
                hidden: !instanceConfig.isCustomerGrid,
                hideable: instanceConfig.isCustomerGrid,
                tdCls: 'pointer',
                // QUIRK: This is a purely synthetic column that renders based on the associated customer, so no dataIndex is set
                // This is way easier than trying to model this dynamic relation canonically
                renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                    var selection = view.grid.ownerCt.ownerCt.getViewModel().getData().list.selection,
                        customerDefaultBconfId = (selection) ? selection.get('defaultPricingPresetId') : -1;
                    isDefault = (record.id === customerDefaultBconfId); // customer is always set, else panel wouldn't be active
                    return this.defaultRenderer.apply(this, [isDefault, metaData, record, rowIdx, colIdx, store, view]);
                },
                listeners: {
                    beforecheckchange: 'onBeforeCustomerCheckChange'
                }
            },{
                xtype: 'checkcolumn',
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.isDefault.text}',
                    tooltip: '{l10n.MatchAnalysis.pricing.preset.isDefault.tooltip}'
                },
                dataIndex: 'isDefault',
                itemId: 'globalDefaultColumn',
                disabled: instanceConfig.isCustomerGrid,
                width: 70,
                renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                    if (!isDefault && !view.ownerGrid.isCustomerGrid) {
                        metaData.tdCls += ' pointer ';
                    }
                    return this.defaultRenderer.apply(this, arguments);
                },
                listeners: {
                    beforecheckchange: 'onBeforeGlobalCheckChange'
                }
            },{
                xtype: 'actioncolumn',
                align: 'center',
                itemId: 'presetPrices',
                menuDisabled: true,
                width: 60,
                setMenuText: function(menuText){
                    this.menuText = menuText;
                },
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.prices.text}',
                    tooltip: '{l10n.MatchAnalysis.pricing.preset.prices.tooltip}',
                    menuText: '{l10n.MatchAnalysis.pricing.preset.prices.text}',
                },
                items: [{
                    bind: {
                        tooltip: '{l10n.MatchAnalysis.pricing.preset.prices.tooltip}'
                    },
                    isAllowedFor: 'presetEdit',
                    glyph: 'f53d@FontAwesome5FreeSolid',
                    isDisabled: 'isEditDisabled',
                    handler: 'showPricesGrid',
                    width: 50
                }]
            },{
                xtype: 'actioncolumn',
                stateId: 'pricingGridActionColumn',
                align: 'center',
                width: 100,
                bind: {
                    text: '{l10n.MatchAnalysis.pricing.preset.actions.text}'
                },
                menuDisabled: true,
                items: [
                    {
                        bind: {
                            tooltip: '{l10n.MatchAnalysis.pricing.preset.actions.delete}'
                        },
                        tooltip: 'delete',
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        isDisabled: 'isDeleteDisabled',
                        handler: 'deletePreset'
                    },
                    {
                        bind: {
                            tooltip: '{l10n.MatchAnalysis.pricing.preset.actions.clone}'
                        },
                        tooltip: 'clone',
                        margin: '0 0 0 10px',
                        glyph: 'f24d@FontAwesome5FreeSolid',
                        handler: 'clonePreset'
                    }
                ]
            }
        ];
        return me.callParent([Ext.apply(config, instanceConfig)]);
    }
});