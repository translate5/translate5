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
 * Grid for viewing and editing the prices-record of a preset
 * @property {Editor.plugins.MatchAnalysis.model.admin.pricing.PresetModel}
 * @see Ext.grid.plugin.Editing.init
 */
Ext.define('Editor.plugins.MatchAnalysis.view.admin.pricing.PresetPricesGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.plugins.MatchAnalysis.view.admin.pricing.PresetPricesGridController',
        'Editor.plugins.MatchAnalysis.store.admin.pricing.PresetPricesStore'
    ],
    alias: 'widget.presetpricesgrid',
    id: 'presetPricesGrid',
    controller: 'pricingpresetpricesGridController',
    minWidth: 800,
    maxWidth: 1280,
    config: {
        /**
         * @method getPreset
         * @return {Editor.plugins.MatchAnalysis.model.admin.pricing.PresetModel}
         */
        preset: null,
    },
    selModel: 'rowmodel',
    plugins: {
        ptype: 'rowediting',
        clicksToEdit: 3
    },
    title: {
        text: '',
        flex: 0
    },
    iconCls: 'x-fa fa-money-check-dollar',
    helpSection: 'useroverview',
    searchValSet: '',
    searchValCache: '',
    isNewRecordSearchSet: false,
    cls: 't5actionColumnGrid',
    store: {
        type: 'pricingpresetpricesStore'
    },
    header: {
        defaults: {margin: '0 10 0'},
        items: [{
            xtype: 'button',
            bind: {
                text: '{l10n.MatchAnalysis.pricing.prices.create}'
            },
            handler: 'createPrice'
        }, {
            xtype: 'tbfill',
            flex: 1
        }, {
            xtype: 'textfield',
            width: 300,
            checkChangeBuffer: 300,
            itemId: 'search',
            flex: 1,
            bind: {
                emptyText: '{l10n.MatchAnalysis.pricing.prices.search}',
            },
            triggers: {
                clear: {
                    cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                    handler: function(field){
                        field.setValue(null) || field.focus();
                    },
                    hidden: true
                }
            },
        }, {
            xtype: 'button',
            iconCls: 'x-fa fa-undo',
            bind: {
                text: '{l10n.MatchAnalysis.pricing.prices.refresh}',
            },
            handler: function(btn){
                btn.up('grid').getStore().reload();
            }
        },
        ]
    },
    tbar: [{
        xtype: 'button',
        bind: {
            text: '{l10n.MatchAnalysis.pricing.range.create}'
        },
        handler: 'openRangePrompt'
    }, {
        xtype: 'button',
        bind: {
            text: '{l10n.MatchAnalysis.pricing.range.delete}'
        },
        handler: 'deleteRange'
    },{
        xtype: 'tbtext',
        bind: {
            text: '{l10n.MatchAnalysis.pricing.range.hint}',
        }
    }],
    columns: [{
        dataIndex: 'id',
        hidden: true,
        text: 'Id',
    }, {
        xtype: 'gridcolumn',
        dataIndex: 'sourceLanguageId',
        renderer: 'languageIdRenderer',
        minWidth: 100,
        flex: 1,
        menuDisabled: true,
        bind: {
            text: '{l10n.MatchAnalysis.pricing.prices.sourceLang}',
            tooltip: '{l10n.MatchAnalysis.pricing.prices.sourceLang}'
        }
    }, {
        xtype: 'gridcolumn',
        dataIndex: 'targetLanguageId',
        renderer: 'languageIdRenderer',
        minWidth: 100,
        flex: 1,
        menuDisabled: true,
        bind: {
            text: '{l10n.MatchAnalysis.pricing.prices.targetLang}',
            tooltip: '{l10n.MatchAnalysis.pricing.prices.targetLang}'
        }
    }, {
        xtype: 'gridcolumn',
        dataIndex: 'currency',
        width: 100,
        menuDisabled: true,
        bind: {
            text: '{l10n.MatchAnalysis.pricing.prices.currency}'
        },
        editor: {
            xtype: 'textfield',
            allowBlank: false,
            maxLength: 3,
            regex: /[a-zA-Z0-9\$€£¥]{1,3}/
        }
    }, {
        xtype: 'numbercolumn',
        sortable: false,
        align: 'end',
        format: '0,000.0000',
        bind: {
            text: '{l10n.MatchAnalysis.pricing.prices.noMatch}'
        },
        dataIndex: 'noMatch',
        menuDisabled: true,
        editor: {
            xtype: 'numberfield',
            decimalPrecision: 4,
            minValue: 0,
            step: 0.0001,
            hideTrigger: true,
            valueToRaw: value => Ext.util.Format.number(Ext.valueFrom(value, ''), '0.0000')
        }
    }, {
        xtype: 'actioncolumn',
        cellFocusable: false, // prevent actionItemCLick from entering RowEditMode
        width: 120,
        align: 'center',
        bind: {
            text: '{l10n.MatchAnalysis.pricing.prices.actions.header}'
        },
        editRenderer: Ext.emptyFn,
        menuDisabled: true,
        items: [{
            glyph: 'f303@FontAwesome5FreeSolid',
            handler: 'editPrice',
            bind: {
                tooltip: '{l10n.MatchAnalysis.pricing.prices.actions.edit}'
            }
        }, {
            glyph: 'f24d@FontAwesome5FreeSolid',
            handler: 'clonePrice',
            bind: {
                tooltip: '{l10n.MatchAnalysis.pricing.prices.actions.clone}'
            }
        }, {
            glyph: 'f2ed@FontAwesome5FreeSolid',
            handler: 'deletePrice',
            bind: {
                tooltip: '{l10n.MatchAnalysis.pricing.prices.actions.delete}'
            }
        }]
    }],
    viewConfig: {
        reference: 'gridview'
    },

    initComponent: function(){
        var me = this,
            preset = me.getPreset(),
            name = preset.get('name').split('"').join('');
        if(name.length > 50){
            name = name.substring(0, 47) + ' ...';
        }
        me.title.text = Editor.data.l10n.MatchAnalysis.pricing.prices.title + ' <i>“'+name+'”</i>';
        me.callParent();
        me.getStore().getProxy().setPresetId(preset.id); // for records and backend filter
    },

    /**
     * @returns {string}
     */
    getSearchValue: function(){
        return this.down('textfield#search').getValue();
    },

    /**
     *
     * @param {string} newVal
     * @param {boolean} isNewRecordSearch
     */
    setSearchValue: function(newVal, isNewRecordSearch){
        var searchField = this.down('textfield#search');
        this.searchValSet = newVal;
        this.searchValCache = searchField.getValue();
        searchField.setValue(newVal);
        searchField.checkChange();
        this.isNewRecordSearchSet = isNewRecordSearch;
    },

    /**
     * @param {boolean} isNewRecordSearch
     */
    unsetSearchValue: function(isNewRecordSearch){
        // we do not always set a search-value when cloning records, so we must prevent removing user-seraches
        if(isNewRecordSearch && !this.isNewRecordSearchSet){
            return;
        }
        var searchField = this.down('textfield#search');
        if(this.searchValSet && this.searchValSet === this.getSearchValue()){
            searchField.setValue(this.searchValCache);
            searchField.checkChange();
        }
        this.searchValCache = this.searchValCache = '';
        this.isNewRecordSearchSet = false;
    }
});