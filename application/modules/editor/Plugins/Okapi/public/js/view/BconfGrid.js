/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
    plugins: ['gridfilters', 'cellediting'],
    itemId: 'okapiBconfGrid',
    SYSTEM_BCONF_NAME: 'Translate5-Standard',
    controller: 'bconfGridController',
    store: 'bconfStore',
    stateId: 'okapiBconfGrid',
    stateful: true,
    isCustomerGrid: false,
    userCls: 'actionColGrid',
    title: '#UT#Dateiformatkonvertierung',
    helpSection: 'useroverview',
    glyph: 'f1c9@FontAwesome5FreeSolid',
    height: '100%',
    config: {
        customer: null,
        customerDefault: null
    },
    updateCustomer:function(newCustomer){
        if(!newCustomer){
            return;
        }
        var storeFilters = this.getStore().getFilters(),
            clientFilter = storeFilters.getByKey('clientFilter');
        if(clientFilter){
            clientFilter.customerId = newCustomer.id;
            storeFilters.notify('endupdate');
        }
    },
    layout: {
        type: 'fit',
    },
    text_cols: {
        name: '#UT#Name',
        extensions: '#UT#Extensions',
        description: '#UT#Description',
        action: '#UT#Actions',
        upload: '#UT#upload',
        srx: '#UT#SRX',
        pipeline: '#UT#Pipeline',
        customerStandard: '#UT#Kundenstandard'
    },
    strings: {
        edit: '#UT#Edit',
        remove: '#UT#Remove',
        copy: '#UT#Copy',
        upload: '#UT#Upload',
        addBconf: '#UT#Add Bconf',
        uploadBconf: '#UT#Upload Bconf',
        searchText: '#UT#Search',
        searchEmptyText: '#UT#Search Bconf',
        export: '#UT#Export',
        browse: '#UT#Browse',
        bconfRequired: '#UT#Bconf required',
        confirmDeleteTitle:'#UT#Bconf löschen',
        confirmDeleteMessage:'#UT#Möchten Sie diese Bconf-Datei wirklich löschen?',
        deleteSuccess:'#UT#Bconf-Datei gelöscht',
        invalidSrxTitle:"#UT#Ungültige SRX-Datei",
        invalidSrxMsg:"#UT#Die hochgeladene Datei ist keine gültige SRX-Datei.",
    },
    reference:'bconfgrid',
    viewConfig: {
        getRowClass: function({data:bconf}) {
            var cls='', customer = (this.ownerGrid.customer||{}).data || {};
                if(bconf.customerId != customer.id || bconf.name === this.grid.SYSTEM_BCONF_NAME) {
                    cls += 'not-editable ';
                }
                if(bconf.isDefault || customer.defaultBconfId == bconf.id){
                    cls += 'chosenDefault '
                }
            return cls;
        },
    },

    initConfig: function(instanceConfig) {
        var me = this,
            itemFilter = function(item) {
                return true;
            },
            config = {
                columns: [{
                    xtype: 'gridcolumn',
                    width: 200,
                    dataIndex: 'id',
                    filter: {
                        type: 'string',
                    },
                    text: 'Id',
                    hidden: true,
                },
                {
                    xtype: 'gridcolumn',
                    width: 200,
                    dataIndex: 'name',
                    stateId: 'name',
                    flex: 1,
                    filter: {
                        type: 'string',
                    },
                    //editor: 'textfield',
                    text: me.text_cols.name,
                },
                   /* {
                        xtype: 'gridcolumn',
                        width: 200,
                        dataIndex: 'extensions',
                        stateId: 'extensions',
                        filter: {
                            type: 'string',
                        },
                        text: me.text_cols.extensions,
                    },*/
                    {
                        xtype: 'gridcolumn',
                        width: 300,
                        alias:'desc',
                        dataIndex: 'description',
                        stateId: 'description',
                        editor: {
                            field: {
                                xtype: 'textfield',
                                allowBlank: false,
                                emptyText: me.text_cols.description
                            }
                        },
                        filter: {
                            type: 'string',
                        },
                        tdCls: 'pointer',
                        text: me.text_cols.description,
                        flex:3
                    },
                    {
                        xtype: 'checkcolumn',
                        text: me.text_cols.customerStandard,
                        width: 150,
                        dataIndex: 'isDefaultForCustomer',
                        itemId: 'customerDefaultColumn',
                        hidden: !instanceConfig.isCustomerGrid,
                        hideable: instanceConfig.isCustomerGrid,
                        tdCls: 'pointer',
                        tooltip: '',
                        renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                            var customer = view.ownerGrid.getCustomer();
                            arguments[0] = customer && record.id == customer.get('defaultBconfId');
                            return this.defaultRenderer.apply(this, arguments);
                        },
                        listeners: {
                            'beforecheckchange': function (col, recordIndex, checked, bconfRec) {
                                var view = col.getView(),
                                    grid = view.ownerGrid,
                                    customer = grid.getCustomer(),
                                    newDefault = bconfRec.getId(),
                                    oldDefault = customer.get('defaultBconfId');
                                if(newDefault == oldDefault){ // deselect customer default
                                    newDefault = null;
                                }
                                bconfRec.set('isDefaultForCustomer', customer.getId());
                                customer.set('defaultBconfId', newDefault, {silent: true}); // set on customer
                                if(oldDefault){ // unselect old
                                    var oldDefaultRec = view.getStore().getById(oldDefault);
                                    oldDefaultRec.set('isDefaultForCustomer', false, newDefault==oldDefault ? {} : {silent:true, dirty:false});
                                    view.refreshNode(oldDefaultRec);
                                }
                            return false;
                            }
                        }

                    },
                    /** @name checkCol */
                    {
                        xtype: 'checkcolumn',
                        text: me.text_cols.standard,
                        dataIndex: 'isDefault',
                        itemId: 'globalDefaultColumn',
                        tooltip: '',
                        disabled: instanceConfig.isCustomerGrid,
                        width: 95,
                        renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                            var grid = view.ownerGrid;
                            if (!isDefault && !grid.isCustomerGrid){
                                metaData.tdCls += ' pointer ';
                            }
                            return this.defaultRenderer.apply(this, arguments);
                        },
                        listeners: {
                            'beforecheckchange': function (col, recordIndex, checked, record) {
                                var view = col.getView(),
                                    grid = view.ownerGrid,
                                    store = grid.store,
                                    oldDefault;
                                if(grid.isCustomerGrid || !checked){ // Cannot set in customerGrid, cannot deselect global default
                                    return false;
                                } else if (checked) { // must uncheck old default
                                    oldDefault = store.getAt(store.findBy(({data}) => data.isDefault && !data.customerId));
                                    if (oldDefault && oldDefault !== record) {
                                        oldDefault.set('isDefault', false)
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
                        width: 3*28+8,
                        text: me.text_cols.action,
                        iconCls: 'margin5', // applies to all items
                        items: [/*{
                                tooltip: me.strings.edit,
                                isAllowedFor: 'bconfEdit',
                                glyph: 'f044@FontAwesome5FreeSolid',
                                handler: 'editbconf',
                                isDisabled: 'getActionStatus',
                            },*/
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
                                handler: 'exportbconf',
                            },
                        ],
                    },
                    {
                        xtype: 'actioncolumn',
                        align: 'center',
                        text: me.text_cols.srx,
                        width: 2*28+8+28,
                        items: [{
                                tooltip: me.strings.upload,
                                isAllowedFor: 'bconfEdit',
                                glyph: 'f093@FontAwesome5FreeSolid',
                                isDisabled: 'isSRXUploadDisabled',
                                handler: 'showSRXChooser',
                            },
                            {
                                tooltip: me.strings.export,
                                isAllowedFor: 'bconfDelete',
                                glyph: 'f019@FontAwesome5FreeSolid',
                                handler: 'downloadSRX'
                            },
                        ]
                    },
                   /* {
                        xtype: 'actioncolumn',
                        align: 'center',
                        width: 150,
                        text: me.text_cols.pipeline,
                        items: Ext.Array.filter(
                            [{
                                    tooltip: me.strings.upload,
                                    isAllowedFor: 'bconfEdit',
                                    glyph: 'f093@FontAwesome5FreeSolid',
                                },
                                {
                                    tooltip: me.strings.export,
                                    isAllowedFor: 'bconfDelete',
                                    glyph: 'f56e@FontAwesome5FreeSolid',
                                },
                            ],
                            itemFilter
                        ),
                    },*/
                ],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [{
                        xtype: 'button',
                        glyph: 'f093@FontAwesome5FreeSolid',
                        text: me.strings.uploadBconf,
                        ui: 'default-toolbar-small',
                        width: 'auto',
                        handler: function(btn){
                            Editor.util.Util.chooseFile('.bconf')
                                .then(files => btn.up('grid').getController().uploadBconf(files[0]))
                        }
                    },
                    {
                        xtype: 'button',
                        iconCls: 'x-fa fa-undo',
                        text: 'Reload',
                        handler: function(btn) {
                            btn.up('grid').getStore().getSource().reload();
                        }
                    },
                    {
                        xtype: 'textfield',
                        width: 300,
                        flex: 2,
                        margin: '0 0 0 20px',
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
                        xtype: 'tbseparator',
                        flex: 3,
                    },
                    ],
                }, ],
            };
        return me.callParent([Ext.apply(config, instanceConfig)]);
    },
});