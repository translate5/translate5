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
    listeners: {
        beforeedit: 'handleBeforeedit',
        show: 'loadOkapiFilters'
    },
    config: {
        customer: null
    },
    text_cols: {
        name: '#UT#Name',
        extensions: '#UT#Erweiterungen',
        description: '#UT#Beschreibung',
        action: '#UT#Aktionen',
        uploadBconf: '#UT#Bconf hochladen',
        fileSuffix: '#UT#-Datei',
        fileDlTemplate: '#UT#{0}{1} herunterladen',
        fileUpTemplate: '#UT#{0}{1} hochladen',
        filters: '#UT#Filter',
        srx: '#UT#SRX-Datei',
        sourceSrx: '#UT#Source SRX',
        targetSrx: '#UT#Target SRX',
        pipeline: '#UT#Pipeline',
        standard: '#UT#Standard',
        customerStandard: '#UT#Kundenstandard'
    },
    strings: {
        addBconf: '#UT#Bconf hinzufügen',
        bconfRequired: '#UT#wird benötigt',
        browse: '#UT#Durchsuchen...',
        confirmDeleteMessage: '#UT#Möchten Sie diese Bconf-Datei wirklich löschen?',
        confirmDeleteTitle: '#UT#Bconf löschen',
        copy: '#UT#Kopieren',
        deleteSuccess: '#UT#Bconf-Datei gelöscht',
        edit: '#UT#Bearbeiten',
        editBconf: '#UT#Bconf-Datei bearbeiten',
        export: '#UT#Exportieren',
        fileUploaded: '#UT#{0}-Datei erfolgreich hochgeladen.',
        invalidMsg: '#UT#Die hochgeladene Datei ist keine gültige {0}-Datei.',
        invalidTitle: '#UT#Ungültige {0}-Datei',
        name: '#UT#Name',
        nameExists: '#UT#Dieser Name ist schon vergeben',
        newBconf: '#UT#Neue Bconf-Datei',
        refresh: '#UT#Aktualisieren',
        remove: '#UT#Löschen',
        searchEmptyText: '#UT#Suchen',
        uniqueName: '#UT#Eindeutiger Name',
        upload: '#UT#Upload',
        uploadBconf: '#UT#Bconf hochladen',
        configureFilters: '#UT#Filter konfigurieren'
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
                text: me.text_cols.extensions
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
            },
            {
                xtype: 'checkcolumn',
                text: me.text_cols.customerStandard,
                width: 90,
                itemId: 'customerDefaultColumn',
                hidden: !instanceConfig.isCustomerGrid,
                hideable: instanceConfig.isCustomerGrid,
                tdCls: 'pointer',
                tooltip: '',  // QUIRK: needed to work
                // QUIRK: This is a purely synthetic column that renders based on the associated customer, so no dataIndex is set
                // This is way easier than trying to model this dynamic relation canonically
                renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                    const customer = view.grid.ownerCt.ownerCt.getViewModel().getData().list.selection || {};
                    arguments[0] = (record.id === customer.data.defaultBconfId); // customer is always set, else panel wouldn't be active
                    return this.defaultRenderer.apply(this, arguments);
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
                        var view = col.getView(),
                            store = view.getStore(),
                            customer = view.grid.getCustomer(),
                            oldDefaultId = customer.get('defaultBconfId'),
                            isSelect = oldDefaultId !== clicked.id, // find-params: ... startIndex, anyMatch, caseSensitive, exactMatch
                            id2Refresh = (isSelect && oldDefaultId) ? customer.get('defaultBconfId') : Editor.data.plugins.Okapi.systemDefaultBconfId,
                            value = isSelect ? clicked.id : null;
                        view.select(clicked)
                        customer.set('defaultBconfId', value); // TODO: why doesn't {commit:true} trigger save but even prevent it?!
                        Ext.Ajax.request({
                            url: Editor.data.restpath + 'customermeta',
                            method: 'PUT',
                            params: {
                                id: customer.id,
                                data: Ext.encode({
                                    defaultBconfId: value
                                })
                            },
                            failure: function(response){
                                Editor.app.getController('ServerException').handleException(response);
                            }
                        })
                        view.refresh(clicked);
                        if(id2Refresh !== clicked.id){
                            var oldDefaultRec = store.getById(id2Refresh);
                            if(oldDefaultRec){
                                view.refreshNode(oldDefaultRec);
                            }
                        }
                        return false; // checked state handled manually via view.refresh
                    }
                }
            },
            {
                xtype: 'checkcolumn',
                text: me.text_cols.standard,
                dataIndex: 'isDefault',
                itemId: 'globalDefaultColumn',
                tooltip: '', // QUIRK: needed to work
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
                        view.select(record)
                        if(grid.isCustomerGrid || !checked){ // Cannot set in customerGrid, cannot deselect global default
                            return false;
                        } else if(checked){ // must uncheck old default
                            oldDefault = store.getAt(store.findBy(({data}) => data.isDefault && !data.customerId));
                            if(oldDefault && oldDefault !== record){
                                oldDefault.set('isDefault', {commit: false}); // QUIRK: prevent saving twice this way
                                oldDefault.commit();
                            }
                        }
                        Editor.data.plugins.Okapi.systemDefaultBconfId = record.id;
                    }
                }
            },
            {
                xtype: 'actioncolumn',
                align: 'center',
                itemId: 'bconfFilters',
                menuDisabled: true,
                width: 60,
                text: me.text_cols.filters,
                menuText: me.text_cols.filters,
                items: [{
                    tooltip: me.strings.configureFilters,
                    isAllowedFor: 'bconfEdit',
                    glyph: 'f0b0@FontAwesome5FreeSolid',
                    isDisabled: 'isEditDisabled',
                    handler: 'showFilterGrid',
                    width: 50
                }]
            },
            {
                xtype: 'actioncolumn',
                stateId: 'okapiGridActionColumn',
                align: 'center',
                width: 100,
                text: me.text_cols.action,
                menuText: me.text_cols.action,
                menuDisabled: true,
                items: [
                    {
                        tooltip: me.strings.remove,
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        isDisabled: 'isDeleteDisabled',
                        handler: 'deleteBconf'
                    },
                    {
                        tooltip: me.strings.copy,
                        isAllowedFor: 'bconfCopy',
                        margin: '0 0 0 10px',
                        glyph: 'f24d@FontAwesome5FreeSolid',
                        handler: 'cloneBconf'
                    },
                    {
                        tooltip: me.strings.export,
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        handler: 'downloadBconf'
                    }
                ]
            },
            {
                xtype: 'actioncolumn',
                align: 'center',
                text: me.text_cols.sourceSrx,
                menuText: me.text_cols.sourceSrx,
                width: 90,
                menuDisabled: true,
                items: [
                    {
                        isAllowedFor: 'bconfEdit',
                        glyph: 'f093@FontAwesome5FreeSolid',
                        isDisabled: 'isEditDisabled',
                        purpose: 'source',
                        tooltip: new Ext.Template(me.text_cols.fileUpTemplate)
                            .apply(['Source SRX', me.text_cols.fileSuffix]),
                        handler: 'showSRXChooser',
                    },
                    {
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        purpose: 'source',
                        tooltip: new Ext.Template(me.text_cols.fileDlTemplate)
                            .apply(['Source SRX', me.text_cols.fileSuffix]),
                        handler: 'downloadSRX'
                    },
                ]
            },
            {
                xtype: 'actioncolumn',
                align: 'center',
                text: me.text_cols.targetSrx,
                menuText: me.text_cols.targetSrx,
                width: 90,
                menuDisabled: true,
                items: [
                    {
                        isAllowedFor: 'bconfEdit',
                        glyph: 'f093@FontAwesome5FreeSolid',
                        isDisabled: 'isEditDisabled',
                        purpose: 'target',
                        tooltip: new Ext.Template(me.text_cols.fileUpTemplate)
                            .apply(['Target SRX', me.text_cols.fileSuffix]),
                        handler: 'showSRXChooser',
                    },
                    {
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        purpose: 'target',
                        tooltip: new Ext.Template(me.text_cols.fileDlTemplate)
                            .apply(['Target SRX', me.text_cols.fileSuffix]),
                        handler: 'downloadSRX'
                    }
                ]
            },

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
                    xtype: 'tbspacer',
                    flex: 1.6,
                },
            ],
        }];
        return me.callParent([Ext.apply(config, instanceConfig)]);
    },
});