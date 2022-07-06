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
 * @property {Ext.grid.plugin.Editing} editingPlugin Undocumented property used for shorthand access (for this.findPlugin('rowediting'))
 * @property {Editor.plugins.Okapi.model.BconfModel} bconf Config of the filtergrid, holds the bconf to which the filters belong.
 * @see Ext.grid.plugin.Editing.init
 */
Ext.define('Editor.plugins.Okapi.view.BconfFilterGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.plugins.Okapi.view.BconfFilterGridController',
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
    editingPlugin: null, // placeholder for undocumented property, see JSDoc
    plugins: [{
        ptype: 'rowediting',
        clicksToEdit: 3, // QUIRK: 1 not possible, triggers on actioncolumns TODO: limit to non actionCols, add pointerCls
        removeUnmodified: true,
    }],
    title: {text: 'Okapi Filters', flex: 0},
    initComponent: function(){
        var me = this,
            bconf = me.getBconf().getData();
        me.title.text += ` in <i data-qtip="${bconf.description}">${bconf.name}.bconf</i>`;
        me.callParent();
        me.getStore().getProxy().setBconfId(bconf.id); // for records and backend filter
    },
    helpSection: 'useroverview',
    cls: 'actionColGrid',
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
        remove: '#UT#Remove',
        copy: '#UT#Copy',
        upload: '#UT#Upload',
        addBconf: '#UT#Add Bconf',
        showDefaultFilter: '#UT#Show Okapi Defaults Filters',
        customizeFilter: '#UT#Customize Filter',
        in: '#in',
    },
    store: {
        type: 'bconffilterStore'
    },
    viewConfig: {
        getRowClass: function(bconf){
            var classes = [];
            if(!bconf.get('editable')){
                classes.push('t5noneditable');
            }
            if(bconf.get('isCustom')){
                classes.push('t5default');
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
                        text: '#UT#Show Okapi Defaults Filters',
                    }, {
                        xtype: 'tbspacer',
                        flex: 1
                    }, {
                        xtype: 'textfield',
                        width: 300,
                        checkChangeBuffer: 300,
                        itemId: 'search',
                        flex: 1,
                        emptyText: Editor.plugins.Okapi.view.BconfGrid.prototype.strings.searchEmptyText,
                        triggers: {
                            clear: {
                                cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                                handler: field => field.setValue(null) || field.focus(),
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
                        lastVal: [],
                        validator: function(name){
                            if(name === this.lastVal[0]){ // already validated
                                return this.lastVal[1];
                            }
                            var view = this.column.getView(),
                                records = view.getStore().getData().getSource().items,
                                nameIsUnique = !records.some(rec => rec.data.name === name && rec.id !== view.selection?.id)
                                    || Editor.plugins.Okapi.view.BconfGrid.prototype.strings.nameExists; // errormessage
                            this.lastVal = [name, nameIsUnique]; // cache validation result
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
                    text: me.text_cols.identifier
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
                    filter: {
                        type: 'string'
                    },
                    text: me.text_cols.extensions,
                    /**
                     * @property {object} changelog Saves changes for rollback
                     * @see BconfFilterGridController.prepareFilterEdit
                     */
                    editor: { //TODO: add tooltip (tpl?) with current filter of extension
                        xtype: 'tagfield',
                        itemId: 'extensionMap',
                        queryMode: 'local',
                        createNewOnEnter: true,
                        createNewOnBlur: true,
                        filterPickList: true, // ture clears list on custom value
                        changelog: null,
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
                        handler: 'copy'
                    }, {
                        tooltip: me.strings.remove,
                        isAllowedFor: 'bconfDelete',
                        isDisabled: 'isDeleteDisabled',
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        handler: 'delete'
                    }], itemFilter)
                }],
            };
        return me.callParent([Ext.apply(config, instanceConfig)]);
    },

});