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
 * Grid for viewing and editing the different Okapi Filter configurations of a bconf
 * @property {Editor.plugins.Okapi.model.BconfModel} bconf Config of the filtergrid, holds the bconf to which the filters belong.
 * @see Ext.grid.plugin.Editing.init
 */
Ext.define('Editor.plugins.Okapi.view.BconfFilterGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.plugins.Okapi.view.BconfFilterGridController',
        'Editor.plugins.Okapi.view.BconfFilterRowEditing',
        'Editor.plugins.Okapi.store.BconfFilterStore'
    ],
    alias: 'widget.bconffiltergrid',
    id: 'bconfFilterGrid',
    controller: 'bconffilterGridController',
    config: {
        /**
         * @method getBconf
         * @return {Editor.plugins.Okapi.model.BconfModel}
         */
        bconf: null,
    },
    plugins: [
        Ext.create('Editor.plugins.Okapi.view.BconfFilterRowEditing')
    ],
    title: {text: 'Okapi Filters', flex: 0},
    helpSection: 'useroverview',
    searchValSet: '',
    searchValCache: '',
    cls: 't5actionColumnGrid t5leveledGrid',
    text_cols: {
        name: '#UT#Name',
        extensions: '#UT#Dateitypen',
        description: '#UT#Beschreibung',
        mime: '#UT#MIME-Typ',
        identifier: '#UT#Kennung',
        actions: '#UT#Aktionen'
    },
    strings: {
        configuration: '#UT#Filter konfigurieren',
        showDefaultFilters: '#UT#Show Okapi Defaults Filters',
        refresh: '#UT#Aktualisieren',
        remove: '#UT#Löschen',
        emptySearch: '#UT#Suchen',
        uniqueName: '#UT#Eindeutiger Name'
    },
    store: {
        type: 'bconffilterStore'
    },
    initComponent: function(){
        var me = this,
            bconf = me.getBconf().getData();
        me.title.text += ` in <i data-qtip="${bconf.description}">${bconf.name}.bconf</i>`;
        me.callParent();
        me.getStore().getProxy().setBconfId(bconf.id); // for records and backend filter
    },
    viewConfig: {
        getRowClass: function(bconf){
            var classes = [];
            if(!bconf.get('editable')){
                classes.push('t5noneditable');
            }
            if(!bconf.get('isCustom')){
                classes.push('t5level1 t5default');
            }
            return classes.join(' ');
        },
        reference: 'gridview'
    },
    initConfig: function(instanceConfig){
        var me = this,
            itemFilter = function(item){ // TODO: Add authorization check
                return true;
            },
            config = {
                header: {
                    defaults: {margin: '0 10 0'},
                    items: [{
                        xtype: 'button',
                        reference: 'showDefaultsBtn',
                        enableToggle: true,
                        toggleHandler: 'toggleDefaultsFilter',
                        text: this.strings.showDefaultFilters,
                    }, {
                        xtype: 'tbspacer',
                        flex: 1
                    }, {
                        xtype: 'textfield',
                        width: 300,
                        checkChangeBuffer: 300,
                        itemId: 'search',
                        flex: 1,
                        emptyText: this.strings.emptySearch,
                        triggers: {
                            clear: {
                                cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                                handler: function(field){
                                    field.setValue(null); // || field.focus();
                                },
                                hidden: true
                            }
                        },
                    }, {
                        xtype: 'button',
                        iconCls: 'x-fa fa-undo',
                        text: me.strings.refresh,
                        handler: function(btn){
                            btn.up('grid').getStore().reload();
                        }
                    },
                    ]
                },
                columns: [{
                    xtype: 'gridcolumn',
                    dataIndex: 'name',
                    editor: {
                        xtype: 'textfield',
                        allowOnlyWhitespace: false,
                        lastValidationResult: [null, false],
                        validator: function(name){
                            if(!name){
                                return false;
                            }
                            // QUIRK: the validator runs 3x in a row when validating, so to avoid checking the store permanently, we cache the result for a value
                            if(name === this.lastValidationResult[0]){ // already validated
                                return this.lastValidationResult[1];
                            }
                            var view = this.column.getView(),
                                record = view.getStore().findUnfilteredByName(name), // finds the item in the store with the name to validate
                                nameIsUnique = (!record || record.id === view.selection?.id); // we will accept if the name is identical to the existing
                            this.lastValidationResult = [name, nameIsUnique]; // cache validation result
                            return nameIsUnique;
                        }
                    },
                    stateId: 'name',
                    width: 300,
                    filter: {
                        type: 'string'
                    },
                    text: me.text_cols.name
                }, {
                    xtype: 'gridcolumn',
                    dataIndex: 'identifier',
                    width: 300,
                    text: me.text_cols.identifier,
                    renderer: function(value, metaData, record){
                        if(value === 'NEW@FILTER'){
                            // this mimics rainbow-behaviour
                            return record.get('okapiType') + '@copy-of-' + record.get('okapiId');
                        }
                        return value;
                    }
                }, {
                    xtype: 'gridcolumn',
                    dataIndex: 'mimeType',
                    width: 300,
                    editor: {
                        xtype: 'textfield'
                    },
                    text: me.text_cols.mime,
                }, {
                    xtype: 'gridcolumn',
                    dataIndex: 'extensions',
                    width: 200,
                    stateId: 'extensions',
                    renderer: function(value){
                        return value.join(', ');
                    },
                    text: me.text_cols.extensions,
                    editor: { //TODO: add tooltip (tpl?) with current filter of extension
                        xtype: 'tagfield',
                        itemId: 'extensionMap',
                        queryMode: 'local',
                        createNewOnEnter: true,
                        createNewOnBlur: true,
                        filterPickList: true // true clears list on custom value
                     },
                }, {
                    xtype: 'gridcolumn',
                    dataIndex: 'description',
                    stateId: 'notes',
                    flex: 1,
                    editor: {
                        xtype: 'textfield'
                    },
                    text: me.text_cols.description
                }, {
                    xtype: 'actioncolumn',
                    cellFocusable: false, // prevent actionItemCLick from entering RowEditMode
                    width: 3 * 28 + 8 + 28,
                    stateId: 'okapiGridActionColumn',
                    align: 'center',
                    text: me.text_cols.actions,
                    items: Ext.Array.filter([{
                        tooltip: me.strings.configuration,
                        isAllowedFor: 'bconfEdit',
                        isDisabled: 'isEditDisabled',
                        glyph: 'f044@FontAwesome5FreeSolid',
                        handler: 'editFPRM'
                    }, {
                        tooltip: me.strings.configuration,
                        isAllowedFor: 'isFromTranslate5',
                        glyph: 'f24d@FontAwesome5FreeSolid',
                        handler: 'cloneFilter'
                    }, {
                        tooltip: me.strings.remove,
                        isAllowedFor: 'bconfDelete',
                        isDisabled: 'isDeleteDisabled',
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        handler: 'deleteFilter'
                    }], itemFilter)
                }],
            };
        return me.callParent([Ext.apply(config, instanceConfig)]);
    },
    /**
     * @returns {string}
     */
    getSearchValue: function(){
        return this.down('textfield#search').getValue();
    },
    /**
     * @param {string} newVal
     */
    setSearchValue: function(newVal){
        var searchField = this.down('textfield#search');
        this.searchValSet = newVal;
        this.searchValCache = searchField.getValue();
        searchField.setValue(newVal);
        searchField.checkChange();
    },
    unsetSearchValue: function(){
        var searchField = this.down('textfield#search');
        if(this.searchValSet && this.searchValSet === this.getSearchValue()){
            searchField.setValue(this.searchValCache);
            searchField.checkChange();
        }
        this.searchValCache = this.searchValCache = '';
    }
});