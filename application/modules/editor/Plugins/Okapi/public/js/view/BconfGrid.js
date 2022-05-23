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
 * @class okapiBconfGrid
 */
Ext.define('Editor.plugins.Okapi.view.BconfGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.plugins.Okapi.view.BconfGridController',
        'Editor.plugins.Okapi.store.BconfStore',
    ],
    alias: 'widget.okapiBconfGrid',
    plugins: ['cellediting'],
    itemId: 'okapiBconfGrid',
    controller: 'bconfGridController',
    store: 'bconfStore',
    isCustomerGrid: false,
    userCls: 'actionColGrid',
    title: '#UT#Dateiformatkonvertierung',
    glyph: 'f1c9@FontAwesome5FreeSolid',
    listeners: {
        beforeedit: 'handleBeforeedit'
    },
    config: {
        customer: null
    },
    text_cols: {
        name: '#UT#Name',
        extensions: '#UT#Extensions',
        description: '#UT#Description',
        action: '#UT#Actions',
        uploadBconf: '#UT#upload',
        _file: '#UT#-Datei',
        fileDlTemplate: '#UT#{0}{1} herunterladen',
        fileUpTemplate: '#UT#{0}{1} hochladen',
        srx: '#UT#SRX',
        sourceSrx: '#UT#SourceSRX',
        targetSrx: '#UT#TargetSRX',
        pipeline: '#UT#Pipeline',
        customerStandard: '#UT#Kundenstandard'
    },
    strings: {
        addBconf: '#UT#Add Bconf',
        bconfRequired: '#UT#Bconf required',
        browse: '#UT#Browse',
        confirmDeleteMessage: '#UT#Möchten Sie diese Bconf-Datei wirklich löschen?',
        confirmDeleteTitle: '#UT#Bconf löschen',
        copy: '#UT#Copy',
        deleteSuccess: '#UT#Bconf-Datei gelöscht',
        edit: '#UT#Edit',
        editBconf: '#UT#Bconf-Datei bearbeiten',
        export: '#UT#Export',
        fileUploaded: '#UT#{}-Datei erfolgreich hochgeladen.',
        invalidMsg: '#UT#Die hochgeladene Datei ist keine gültige {}-Datei.',
        invalidTitle: '#UT#Ungültige {}-Datei',
        name: '#UT#Name',
        nameExists: '#UT#Dieser Name ist schon vergeben',
        newBconf: '#UT#Neue Bconf-Datei',
        refresh: '#UT#Aktualisieren',
        remove: '#UT#Remove',
        searchEmptyText: '#UT#Search Bconf',
        searchText: '#UT#Search',
        uniqueName: '#UT#Eindeutiger Name',
        upload: '#UT#Upload',
        uploadBconf: '#UT#Upload Bconf',
    },
    viewConfig: {
        enableTextSelection: true, // neccessary for pointer class to have effect on whole row
        getRowClass: function({data: bconf}){
            var cls = '',
                customer = this.grid.customer;
            if(customer && customer.get('id') === bconf.customerId){
                cls += ' pointer ';
            } //else not editable
            if((customer && customer.get('defaultBconfId')) ? (customer.get('defaultBconfId') === bconf.id) : bconf.isDefault){
                cls += ' chosenDefault ';
            }
            return cls;
        }
    },
    initConfig: function(instanceConfig){
        var me = this,
            config = {};
        config.columns = [
            {
                xtype: 'gridcolumn',
                width: 50,
                dataIndex: 'id',
                text: 'Id',
                hidden: true,
            },
            {
                xtype: 'gridcolumn',
                width: 200,
                dataIndex: 'name',
                stateId: 'name',
                flex: 1,
                editor: 'textfield',
                text: me.text_cols.name,
            },
            {
                xtype: 'gridcolumn',
                width: 300,
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
                width: 150,
                itemId: 'customerDefaultColumn',
                hidden: !instanceConfig.isCustomerGrid,
                hideable: instanceConfig.isCustomerGrid,
                tdCls: 'pointer',
                tooltip: '',  // QUIRK: needed to work
                // QUIRK: This is a purely synthetic column that renders based on the associated customer, so no dataIndex is set
                // This is way easier than trying to model this dynamic relation canonically
                renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                    this.disabled = view.grid.customer.get('isDefaultCustomer');
                    arguments[0] = (record.id === view.grid.customer.get('defaultBconfId')); // customer is always set, else panel wouldn't be active
                    return this.defaultRenderer.apply(this, arguments);
                },
                listeners: {
                    /**
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
                            id2Refresh = (isSelect && oldDefaultId) ? customer.get('defaultBconfId') : Editor.data.plugins.Okapi.systemDefaultBconfId;
                        customer.set('defaultBconfId', isSelect ? clicked.id : 0); // TODO: why doesn't {commit:true} trigger save but even prevent it?!
                        customer.save();
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
                width: 95,
                renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                    var grid = view.ownerGrid;
                    if(!isDefault && !grid.isCustomerGrid){
                        metaData.tdCls += ' pointer ';
                    }
                    return this.defaultRenderer.apply(this, arguments);
                },
                listeners: {
                    'beforecheckchange':
                        function(col, recordIndex, checked, record){
                            var view = col.getView(),
                                grid = view.ownerGrid,
                                store = grid.store,
                                oldDefault;
                            if(grid.isCustomerGrid || !checked){ // Cannot set in customerGrid, cannot deselect global default
                                return false;
                            } else if(checked){ // must uncheck old default
                                oldDefault = store.getAt(store.findBy(({data}) => data.isDefault && !data.customerId));
                                if(oldDefault && oldDefault !== record){
                                    oldDefault.set('isDefault', false);
                                }
                            }
                        }
                }
            },
            {
                xtype: 'actioncolumn',
                stateId: 'okapiGridActionColumn',
                align: 'center',
                dataIndex: 'isDefault',
                width: 3 * 28 + 8 + 28,
                text: me.text_cols.action,
                menuText: me.text_cols.action,
                menuDisabled: true,
                items: [
                    {
                        tooltip: me.strings.remove,
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        handler: 'deletebconf',
                        isDisabled: 'isDeleteDisabled'
                    },
                    {
                        tooltip: me.strings.copy,
                        isAllowedFor: 'bconfCopy',
                        margin: '0 0 0 10px',
                        glyph: 'f24d@FontAwesome5FreeSolid',
                        handler: 'clonebconf',
                    },
                    {
                        tooltip: me.strings.export,
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        handler: 'downloadBconf',
                    },
                ],
            },
            {
                xtype: 'actioncolumn',
                align: 'center',
                text: me.text_cols.sourceSrx,
                menuText: me.text_cols.sourceSrx,
                width: 2 * 28 + 8 + 28,
                menuDisabled: true,
                items: [
                    {
                        isAllowedFor: 'bconfEdit',
                        glyph: 'f093@FontAwesome5FreeSolid',
                        isDisabled: 'isSRXUploadDisabled',
                        purpose: 'source',
                        tooltip: new Ext.Template(me.text_cols.fileUpTemplate)
                            .apply(['SourceSRX', me.text_cols._file]),
                        handler: 'showSRXChooser',
                    },
                    {
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        purpose: 'source',
                        tooltip: new Ext.Template(me.text_cols.fileDlTemplate)
                            .apply(['SourceSRX', me.text_cols._file]),
                        handler: 'downloadSRX'
                    },
                ]
            },
            {
                xtype: 'actioncolumn',
                align: 'center',
                text: me.text_cols.targetSrx,
                menuText: me.text_cols.targetSrx,
                width: 2 * 28 + 8 + 28,
                menuDisabled: true,
                items: [
                    {
                        isAllowedFor: 'bconfEdit',
                        glyph: 'f093@FontAwesome5FreeSolid',
                        isDisabled: 'isSRXUploadDisabled',
                        purpose: 'target',
                        tooltip: new Ext.Template(me.text_cols.fileUpTemplate)
                            .apply(['TargetSRX', me.text_cols._file]),
                        handler: 'showSRXChooser',
                    },
                    {
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f019@FontAwesome5FreeSolid',
                        purpose: 'target',
                        tooltip: new Ext.Template(me.text_cols.fileDlTemplate)
                            .apply(['TargetSRX', me.text_cols._file]),
                        handler: 'downloadSRX'
                    },
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
        },
        ];
        return me.callParent([Ext.apply(config, instanceConfig)]);
    },
});