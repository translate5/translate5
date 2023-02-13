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
 * Lists and manages the available Input Bconfs to choose from when creating a task
 * On import the chosen one is sent together with the input files to Okapi Longhorn for Segmentation
 */
Ext.define('Editor.plugins.Okapi.view.BconfGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.plugins.Okapi.view.BconfGridController',
        'Editor.plugins.Okapi.view.BconfFilterGrid',
        'Editor.plugins.Okapi.store.BconfStore',
    ],
    alias: 'widget.okapiBconfGrid',
    plugins: ['cellediting'],
    itemId: 'bconf',
    controller: 'Editor.plugins.Okapi.view.BconfGridController',
    store: 'bconfStore',
    isCustomerGrid: false,
    userCls: 't5actionColumnGrid t5leveledGrid',
    title: '#UT#Dateiformatkonvertierung',
    glyph: 'f1c9@FontAwesome5FreeSolid',
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    helpLink: 'http://www.okapiframework.org/wiki/index.php?title=Filters#Supported_File_Formats',
    listeners: {
        beforeedit: 'handleBeforeedit',
        show: 'loadOkapiFilters'
    },
    config: {
        customer: null
    },
    text_cols: {
        name: '#UT#Name',
        extensions: '#UT#Geänderte Filter',
        extensionsTooltip: '#UT#Dateitypen, für die Filter angepasst wurden',
        description: '#UT#Beschreibung',
        standard: '#UT#Standard',
        standardTooltip: '#UT#Standardmäßig bei der Projekterstellung ausgewähltes Filterset, wenn nicht auf Kundenebene überschrieben',
        customerStandard: '#UT#Kundenstandard',
        customerStandardTooltip: '#UT#Standardmäßig bei der Projekterstellung ausgewähltes Filterset für diesen Kunden',
        filters: '#UT#Filter',
        filtersTooltip: '#UT#Anpassen von Dateifiltern in diesem Filterset',
        action: '#UT#Aktionen',
        actionDelete: '#UT#Filterset löschen',
        actionClone: '#UT#Filterset klonen',
        actionDownload: 'Filterset als bconf-Datei herunterladen',
        sourceSrx: '#UT#Source SRX',
        sourceSrxTooltip: '#UT#LSRX-Datei, die die Segmentierungsregeln für die Quelle enthält',
        sourceSrxUpTooltip: '#UT#Quell SRX-Datei hochladen und SRX-Regeln dieses Filtersatzes ersetzen',
        sourceSrxDownTooltip: '#UT#Laden Sie die Quell SRX-Datei herunter',
        targetSrx: '#UT#Target SRX',
        targetSrxTooltip: '#UT#SRX-Datei, die Segmentierungsregeln enthält, die zur weiteren Segmentierung des targets von xliff-Dateien verwendet werden können',
        targetSrxUpTooltip: '#UT#Hochladen der target-SRX-Datei',
        targetSrxDownTooltip: '#UT#Herunterladen der target-SRX-Datei',
        uploadBconf: '#UT#Bconf hochladen',
        fileSuffix: '#UT#-Datei',
        srx: '#UT#SRX-Datei',
        pipeline: '#UT#Pipeline'
    },
    strings: {
        addBconf: '#UT#Bconf hinzufügen',
        bconfRequired: '#UT#wird benötigt',
        browse: '#UT#Durchsuchen...',
        supprtedFormats: '#UT#Unterstützte Dateiformate',
        confirmDeleteMessage: '#UT#Möchten Sie diese Bconf-Datei wirklich löschen?',
        confirmDeleteTitle: '#UT#Bconf löschen',
        deleteSuccess: '#UT#Bconf-Datei gelöscht',
        edit: '#UT#Bearbeiten',
        editBconf: '#UT#Bconf-Datei bearbeiten',
        fileUploaded: '#UT#{0}-Datei erfolgreich hochgeladen.',
        invalidMsg: '#UT#Die hochgeladene Datei ist keine gültige {0}-Datei.',
        invalidTitle: '#UT#Ungültige {0}-Datei',
        name: '#UT#Name',
        nameExists: '#UT#Dieser Name ist schon vergeben',
        newBconf: '#UT#Neue Bconf-Datei',
        refresh: '#UT#Aktualisieren',
        searchEmptyText: '#UT#Suchen',
        uniqueName: '#UT#Eindeutiger Name',
        upload: '#UT#Upload',
        uploadBconf: '#UT#Bconf hochladen',
        supportetFormats: '#UT#Unterstützte Dateiformate'
    },
    viewConfig: {
        enableTextSelection: true, // neccessary for pointer class to have effect on whole row
        getRowClass: function(record){
            var classes = [],
                customer = this.grid.isCustomerGrid ? this.grid.ownerCt.ownerCt.getViewModel().getData().list.selection : null;
            if(!this.grid.isCustomerGrid || (customer && customer.get('id') === record.get('customerId'))){
                classes.push('t5level0 pointer');
            } else {
                classes.push('t5level1');
            }
            if((customer && customer.get('defaultBconfId')) ? (customer.get('defaultBconfId') === record.id) : record.get('isDefault')){
                classes.push('t5chosenDefault');
            }
            return classes.join(' ');
        }
    },
    initConfig: function(instanceConfig){
        var me = this,
            config = {};
        config.title = me.title; //see EXT6UPD-9
        config.userCls = instanceConfig.isCustomerGrid ? 't5actionColumnGrid t5leveledGrid' : 't5actionColumnGrid'; // for the non-customer view, we do not need the leveled grid decorations
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
                text: me.text_cols.name
            },
            {
                xtype: 'gridcolumn',
                width: 110,
                dataIndex: 'customExtensions',
                stateId: 'customExtensions',
                renderer: function(value){
                    return value.join(', ');
                },
                text: me.text_cols.extensions,
                tooltip: me.text_cols.extensionsTooltip
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
                        emptyText: me.text_cols.description
                    }
                },
                text: me.text_cols.description,
                flex: 3
            },{
                xtype: 'checkcolumn',
                text: me.text_cols.customerStandard,
                tooltip: me.text_cols.customerStandardTooltip,  // QUIRK: needed to work
                width: 90,
                itemId: 'customerDefaultColumn',
                hidden: !instanceConfig.isCustomerGrid,
                hideable: instanceConfig.isCustomerGrid,
                tdCls: 'pointer',
                // QUIRK: This is a purely synthetic column that renders based on the associated customer, so no dataIndex is set
                // This is way easier than trying to model this dynamic relation canonically
                renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                    var selection = view.grid.ownerCt.ownerCt.getViewModel().getData().list.selection,
                        customerDefaultBconfId = (selection) ? selection.get('defaultBconfId') : -1;
                    isDefault = (record.id === customerDefaultBconfId); // customer is always set, else panel wouldn't be active
                    return this.defaultRenderer.apply(this, [isDefault, metaData, record, rowIdx, colIdx, store, view]);
                },
                listeners: {
                    /** @method
                     * There is always a row to be highlighted and one to be unhighlighted
                     * The exception is, when system default is (de)selected as customer default - then don't refresh old
                     * @param col
                     * @param recordIndex
                     * @param {boolean} checked the status of the checkbox
                     * @param clicked the record whose row was clicked
                     * @returns {boolean}
                     */
                    'beforecheckchange': function(col, recordIndex, checked, clicked){
                        // at times extJs fires this event without record what in theory must not happen
                        if(!clicked){
                            return;
                        }
                        var gridView = col.getView(),
                            customer = gridView.grid.getCustomer(),
                            customerId = customer.id,
                            oldBconfId = customer.get('defaultBconfId'),
                            newChecked = (oldBconfId !== clicked.id), // find-params: ... startIndex, anyMatch, caseSensitive, exactMatch
                            newBconfId = newChecked ? clicked.id : null;
                        gridView.select(clicked);
                        Ext.Ajax.request({
                            url: Editor.data.restpath + 'customermeta',
                            method: 'PUT',
                            params: {
                                id: customerId,
                                data: Ext.encode({
                                    defaultBconfId: newBconfId
                                })
                            },
                            success: function() {
                                var storeId, store, sCustomer;
                                // unfortunately there are two customer stores, which both act as source for the TaskImport customer selector, so we have to update them both
                                for(storeId of ['customersStore', 'userCustomers']){
                                    store = Ext.getStore(storeId);
                                    sCustomer = (store) ? store.getById(customerId) : null;
                                    if(store && sCustomer){
                                        sCustomer.set('defaultBconfId', newBconfId, { commit: true, silent: true });
                                    }
                                }
                                // refresh the grid for the changed record
                                gridView.refresh(clicked);
                                if(oldBconfId !== null){
                                    // if there was a old record, refresh the grid for the old record
                                    var bconf = gridView.getStore().getById(oldBconfId);
                                    if(bconf){
                                        gridView.refreshNode(bconf);
                                    }
                                }
                            },
                            failure: function(response){
                                Editor.app.getController('ServerException').handleException(response);
                            }
                        });
                        return false; // checked state handled manually via view.refresh
                    }
                }
            },{
                xtype: 'checkcolumn',
                text: me.text_cols.standard,
                tooltip: me.text_cols.standardTooltip,
                dataIndex: 'isDefault',
                itemId: 'globalDefaultColumn',
                disabled: instanceConfig.isCustomerGrid,
                width: 60,
                renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                    var grid = view.ownerGrid;
                    if(!isDefault && !grid.isCustomerGrid){
                        metaData.tdCls += ' pointer ';
                    }
                    return this.defaultRenderer.apply(this, arguments);
                },
                listeners: {
                    'beforecheckchange': function(col, recordIndex, checked, record){
                        var view = col.getView(),
                            grid = view.ownerGrid,
                            store = grid.store,
                            oldDefault;
                        view.select(record);
                        if(grid.isCustomerGrid || !checked){ // Cannot set in customerGrid, cannot deselect global default
                            return false;
                        } else if(checked){ // must uncheck old default
                            oldDefault = store.getAt(store.findBy(({data}) => data.isDefault && !data.customerId));
                            if(oldDefault && oldDefault !== record){
                                oldDefault.set('isDefault', { commit: false }); // QUIRK: prevent saving twice this way
                                oldDefault.commit();
                            }
                        }
                        Editor.data.plugins.Okapi.systemDefaultBconfId = record.id; // Crucial: the default-id is a global that must be updated!
                    }
                }
            },{
                xtype: 'actioncolumn',
                align: 'center',
                itemId: 'bconfFilters',
                menuDisabled: true,
                width: 60,
                text: me.text_cols.filters,
                tooltip: me.text_cols.filtersTooltip,
                menuText: me.text_cols.filters,
                items: [{
                    tooltip: me.text_cols.filtersTooltip,
                    isAllowedFor: 'bconfEdit',
                    glyph: 'f0b0@FontAwesome5FreeSolid',
                    isDisabled: 'isEditDisabled',
                    handler: 'showFilterGrid',
                    width: 50
                }]
            },{
                xtype: 'actioncolumn',
                stateId: 'okapiGridActionColumn',
                align: 'center',
                width: 100,
                text: me.text_cols.action,
                menuText: me.text_cols.action,
                menuDisabled: true,
                items: [
                    {
                        tooltip: me.text_cols.actionDelete,
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        isDisabled: 'isDeleteDisabled',
                        handler: 'deleteBconf'
                    },
                    {
                        tooltip: me.text_cols.actionClone,
                        isAllowedFor: 'bconfCopy',
                        margin: '0 0 0 10px',
                        glyph: 'f24d@FontAwesome5FreeSolid',
                        handler: 'cloneBconf'
                    },
                    {
                        tooltip: me.text_cols.actionDownload,
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        handler: 'downloadBconf'
                    }
                ]
            },{
                xtype: 'actioncolumn',
                align: 'center',
                text: me.text_cols.sourceSrx,
                tooltip: me.text_cols.sourceSrxTooltip,
                menuText: me.text_cols.sourceSrx,
                width: 90,
                menuDisabled: true,
                items: [
                    {
                        isAllowedFor: 'bconfEdit',
                        glyph: 'f093@FontAwesome5FreeSolid',
                        isDisabled: 'isEditDisabled',
                        purpose: 'source',
                        tooltip: me.text_cols.sourceSrxUpTooltip,
                        handler: 'showSRXChooser',
                    },
                    {
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        purpose: 'source',
                        tooltip: me.text_cols.sourceSrxDownTooltip,
                        handler: 'downloadSRX'
                    },
                ]
            },{
                xtype: 'actioncolumn',
                align: 'center',
                text: me.text_cols.targetSrx,
                tooltip: me.text_cols.targetSrxTooltip,
                menuText: me.text_cols.targetSrx,
                width: 90,
                menuDisabled: true,
                items: [
                    {
                        isAllowedFor: 'bconfEdit',
                        glyph: 'f093@FontAwesome5FreeSolid',
                        isDisabled: 'isEditDisabled',
                        purpose: 'target',
                        tooltip: me.text_cols.targetSrxUpTooltip,
                        handler: 'showSRXChooser',
                    },
                    {
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        purpose: 'target',
                        tooltip: me.text_cols.targetSrxDownTooltip,
                        handler: 'downloadSRX'
                    }
                ]
            }
        ];
        config.dockedItems = [{
            xtype: 'toolbar',
            dock: 'top',
            items: [
                {
                    xtype: 'button',
                    glyph: 'f093@FontAwesome5FreeSolid',
                    text: me.strings.uploadBconf,
                    ui: 'default-toolbar-small',
                    width: 'auto',
                    handler: function(btn){
                        Editor.util.Util.chooseFile('.bconf')
                            .then(files => btn.up('grid').getController().uploadBconf(files[0]));
                    }
                },
                {
                    xtype: 'button',
                    iconCls: 'x-fa fa-undo',
                    text: me.strings.refresh,
                    handler: function(btn){
                        btn.up('grid').getStore().getSource().reload();
                    }
                },
                {
                    xtype: 'textfield',
                    width: 300,
                    flex: 1,
                    emptyText: me.strings.searchEmptyText,
                    triggers: {
                        clear: {
                            cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                            handler: field => field.setValue(null) || field.focus(),
                            hidden: true
                        }
                    },
                    listeners: {
                        change: 'filterByText',
                        buffer: 150
                    }
                },
                {
                    xtype: 'button',
                    iconCls: 'x-fa fa-link',
                    text: me.strings.supprtedFormats,
                    handler: function(btn){
                        window.open(btn.up('grid').helpLink, '_blank');
                    }
                },
                {
                    xtype: 'tbspacer',
                    flex: 1.6,
                },
            ],
        }];
        return me.callParent([Ext.apply(config, instanceConfig)]);
    },
});