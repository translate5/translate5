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
    minWidth: 800,
    maxWidth: 1280,
    config: {
        /**
         * @method getBconf
         * @return {Editor.plugins.Okapi.model.BconfModel}
         */
        bconf: null,
    },
    plugins: [
        'bconffilterrowediting'
    ],
    title: {
        text: 'Filter in',
        flex: 0
    },
    iconCls: 'x-fa fa-filter',
    helpSection: 'useroverview',
    searchValSet: '',
    searchValCache: '',
    isNewRecordSearchSet: false,
    cls: 't5actionColumnGrid t5leveledGrid t5noselectionGrid',
    text_cols: {
        name: '#UT#Name',
        extensions: '#UT#Dateitypen',
        description: '#UT#Beschreibung',
        mime: '#UT#MIME-Typ',
        identifier: '#UT#Kennung',
        actions: '#UT#Aktionen'
    },
    strings: {
        title: '#UT#Filter in',
        configuration: '#UT#Filter konfigurieren',
        showDefaultFilters: '#UT#Standard Filter anzeigen',
        hideDefaultFilters: '#UT#Standard Filter verstecken',
        editTexts: '#UT#Filternamen, zugehörige Dateierweiterungen, MIME-Typ und Beschreibung bearbeiten',
        editTextsRowTip: '#UT#Doppelklicken, um Filternamen, zugehörige Dateierweiterungen, MIME-Typ und Beschreibung zu bearbeiten',
        clone: '#UT#Filter klonen',
        download: '#UT#Filter/FPRM als Datei herunterladen',
        refresh: '#UT#Aktualisieren',
        remove: '#UT#Löschen',
        emptySearch: '#UT#Filter suchen',
        fileUploaded: '#UT#{0}-Datei erfolgreich hochgeladen.',
        invalidMsg: '#UT#Die hochgeladene Datei ist keine gültige {0}-Datei.',
        invalidTitle: '#UT#Ungültige {0}-Datei',
        upload: '#UT#Filter/FPRM aus Datei hochladen',
        uniqueName: '#UT#Eindeutiger Name'
    },
    store: {
        type: 'bconffilterStore'
    },
    initComponent: function(){
        var me = this,
            bconf = me.getBconf(),
            name = bconf.get('name').split('"').join('');
        if(name.length > 50){
            name = name.substring(0, 47) + ' ...';
        }
        me.title.text = me.strings.title + ' <i>“'+Ext.String.htmlEncode(name)+'”</i>';
        me.callParent();
        me.getStore().getProxy().setBconfId(bconf.id); // for records and backend filter
    },
    viewConfig: {
        getRowClass: function(record){
            if(record.get('isCustom')){
                return 't5level0';
            } else {
                return 't5level1 t5default';
            }
        },
        reference: 'gridview'
    },
    initConfig: function(instanceConfig){
        var me = this,
            itemFilter = function(item){ // TODO BCONF: Add authorization check
                return true;
            },
            config = {
                header: {
                    defaults: {margin: '0 10 0'},
                    items: [{
                        xtype: 'button',
                        width: 200,
                        iconCls: 'x-fa fa-eye',
                        reference: 'showDefaultsBtn',
                        enableToggle: true,
                        toggleHandler: 'toggleDefaultsFilter',
                        text: me.strings.showDefaultFilters,
                    }, {
                        xtype: 'tbspacer',
                        flex: 1
                    }, {
                        xtype: 'textfield',
                        width: 300,
                        checkChangeBuffer: 300,
                        itemId: 'search',
                        flex: 1,
                        emptyText: me.strings.emptySearch,
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
                    dataIndex: 'id',
                    hidden: true,
                    text: 'Id',
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'name',
                    renderer: me.editableColumnRenderer,
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
                    width: 275,
                    filter: {
                        type: 'string'
                    },
                    text: me.text_cols.name
                }, {
                    xtype: 'gridcolumn',
                    dataIndex: 'identifier',
                    renderer: me.identifierColumnRenderer,
                    width: 275,
                    text: me.text_cols.identifier
                }, {
                    xtype: 'gridcolumn',
                    dataIndex: 'mimeType',
                    renderer: me.editableColumnRenderer,
                    width: 200,
                    editor: {
                        xtype: 'textfield'
                    },
                    text: me.text_cols.mime,
                }, {
                    xtype: 'gridcolumn',
                    dataIndex: 'extensions',
                    width: 175,
                    stateId: 'extensions',
                    renderer: v => Ext.String.htmlEncode(v.join(', ')),
                    text: me.text_cols.extensions,
                    editor: {
                        xtype: 'tagfield',
                        itemId: 'extensionMap',
                        queryMode: 'local',
                        createNewOnEnter: true,
                        createNewOnBlur: true,
                        filterPickList: true, // true clears list on custom value
                    },
                }, {
                    xtype: 'gridcolumn',
                    tooltip: me.strings.editTextsRowTip,
                    dataIndex: 'description',
                    renderer: me.tooltippedColumnRenderer,
                    stateId: 'notes',
                    flex: 1,
                    editor: {
                        xtype: 'textfield',
                        margin: '0, -115, 0, 0'
                    },
                    text: me.text_cols.description
                }, {
                    xtype: 'actioncolumn',
                    cellFocusable: false, // prevent actionItemCLick from entering RowEditMode
                    width: 180,
                    stateId: 'okapiGridActionColumn',
                    align: 'center',
                    text: me.text_cols.actions,
                    hideMode: 'visibility',
                    editRenderer: function(){ // disables showing the action-column in the RowEditor
                        return '';
                    },
                    items: Ext.Array.filter([{
                        tooltip: me.strings.configuration,
                        isAllowedFor: 'bconfEdit',
                        isDisabled: 'isEditDisabled',
                        glyph: 'f044@FontAwesome5FreeSolid',
                        handler: 'editFPRM'
                    }, {
                        tooltip: me.strings.editTexts,
                        isAllowedFor: 'bconfEdit',
                        isDisabled: 'isWriteDisabled',
                        glyph: 'f303@FontAwesome5FreeSolid',
                        handler: 'editTexts'
                    }, {
                        tooltip: me.strings.clone,
                        isAllowedFor: 'isFromTranslate5',
                        isDisabled: 'isCloneDisabled',
                        glyph: 'f24d@FontAwesome5FreeSolid',
                        handler: 'cloneFilter'
                    }, {
                        tooltip: me.strings.upload,
                        isAllowedFor: 'bconfEdit',
                        glyph: 'f093@FontAwesome5FreeSolid',
                        isDisabled: 'isWriteDisabled',
                        handler: 'showFPRMChooser'
                    },
                    {
                        tooltip: me.strings.download,
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        isDisabled: 'isDeleteDisabled',
                        handler: 'downloadFPRM'
                    },
                    {
                        tooltip: me.strings.remove,
                        isAllowedFor: 'bconfDelete',
                        isDisabled: 'isDeleteDisabled',
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        handler: 'deleteFilter'
                    }], itemFilter)
                }]
            };
        return me.callParent([Ext.apply(config, instanceConfig)]);
    },

    /**
     *
     * @param {String} value
     * @param {Object} metadata
     * @returns {String}
     */
    editableColumnRenderer: function(value, metadata) {
        metadata.tdAttr = 'data-qtip="' + this.strings.editTextsRowTip + '"';
        return Ext.String.htmlEncode(value);
    },
    /**
     *
     * @param {String} value
     * @param {Object} metadata
     * @returns {String}
     */
    tooltippedColumnRenderer: function(value, metadata) {
        if (value) {
            value = value.replace('"', '“').replace("'", '‘');
            value = Ext.String.htmlEncode(value);

            metadata.tdAttr = 'data-qtip="' + Ext.String.htmlEncode(value) + '"';

            return value;
        }

        return '';
    },
    /**
     *
     * @param {String} value
     * @param {Object} metadata
     * @param {Object} record
     * @returns {String}
     */
    identifierColumnRenderer: function(value, metadata, record) {
        metadata.tdAttr = 'data-qtip="' + Ext.String.htmlEncode(Ext.String.htmlEncode(value)) + '"';
        if(value === 'NEW@FILTER'){
            // this mimics rainbow-behaviour
            return record.get('okapiType') + '@copy-of-' + record.get('okapiId');
        }
        return Ext.String.htmlEncode(value);
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