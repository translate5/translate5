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
 * @class okapiFilterGrid
 */
Ext.define('Editor.plugins.Okapi.view.filter.BConfGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.plugins.Okapi.view.filter.BConfGridController',
        'Editor.plugins.Okapi.store.BconfStore',
    ],
    alias: 'widget.okapiFilterGrid',
    plugins: ['gridfilters', 'cellediting'],
    itemId: 'okapifilterGrid',
    controller: 'bconfGridController',
    store: 'bconfStore',
    stateId: 'okapifilterGrid',
    stateful: true,
    isCustomerGrid: false,
    cls: 'okapifilterGrid',
    title: '#UT#Dateiformatkonvertierung',
    helpSection: 'useroverview',
    glyph: 'f1c9@FontAwesome5FreeSolid',
    height: '100%',
    //viewModel: 'viewportEditor',
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
            clientFilter.customer_id = newCustomer.id;
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
        bconfRequired: '#UT#Bconf required'
    },
    reference:'bconfgrid',
    viewConfig: {
        getRowClass: function(rec) {
            var cls='';
            if(rec.get('default') && (this.ownerGrid.customerDefault ? this.ownerGrid.customerDefault == rec : true)){

                //cls += 'x-tip-default-mc not-editable ';
                cls += 'chosenDefault not-editable ';
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
                    editor: 'textfield',
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
                    editor: 'textfield',
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
                        editor: 'textfield',
                        filter: {
                            type: 'string',
                        },
                        text: me.text_cols.description,
                        flex:3
                    },
                    {
                        xtype: 'checkcolumn',
                        text: me.text_cols.customerStandard,
                        width: 130,
                        dataIndex: 'isDefaultForCustomer',
                        itemId: 'customerDefaultColumn',
                        hidden: !instanceConfig.isCustomerGrid,
                        hideable: instanceConfig.isCustomerGrid,
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
                                bconfRec.set('isDefaultForCustomer', customer.getId());
                                customer.set('defaultBconfId', newDefault, {silent:true}); // set on customer
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
                        dataIndex: 'default',
                        itemId: 'globalDefaultColumn',
                        tooltip: '',
                        width: 80,
                        renderer: function(isDefault, metaData, record, rowIdx, colIdx, store, view){
                            var grid = view.ownerGrid;
                            if (isDefault && (grid.isCustomerGrid ? (grid.customerDefault && record!=grid.customerDefault) : record.get('customer_id'))){
                                arguments[0] = false;
                            }
                            return this.defaultRenderer.apply(this, arguments);
                        },
                        listeners: {
                            'beforecheckchange': function (col, recordIndex, checked, record) {
                                var view = col.getView(),
                                    grid = view.ownerGrid,
                                    store = grid.store,
                                    oldDefault;
                                if (checked) { // must uncheck old default
                                    oldDefault = store.getAt(store.findBy(({data}) => data.default && !data.customer_id));
                                    if (oldDefault && oldDefault !== record) {
                                        oldDefault.set('default', false)
                                    }
                                } else if (!checked) {
                                    return grid.isCustomerGrid; // can't unselect global default
                                }
                            }
                        }
                    },
                    {
                        xtype: 'actioncolumn',
                        stateId: 'okapiGridActionColumn',
                        align: 'center',
                        dataIndex: 'default',
                        width: 3*28+8,
                        text: me.text_cols.action,
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
                                isDisabled: 'getActionStatus'
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
                                glyph: 'f56e@FontAwesome5FreeSolid',
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
                                bind: {
                                    hidden: '{default}'
                                },
                                handler: 'showSRXChooser',
                            },
                            {
                                tooltip: me.strings.export,
                                isAllowedFor: 'bconfDelete',
                                glyph: 'f56e@FontAwesome5FreeSolid',
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
                        xtype: 'filefield',
                        name: 'bconffile',
                        buttonConfig:{
                            glyph: 'f093@FontAwesome5FreeSolid',
                            text: me.strings.uploadBconf,
                            ui: 'default-toolbar-small'
                        },
                        msgTarget: 'side',
                        width: 'auto',
                        margin: 0,
                        accept: '.bconf',
                        buttonOnly: true,
                        listeners: {
                            element: 'fileInputEl',
                            change: 'uploadBconf'
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
                        margin: '0 0 0 20px',
                        emptyText: me.strings.searchEmptyText,
                        listeners: {
                            change: 'filterByText'
                        }
                    },
                    {
                        xtype: 'component',
                        itemId: 'srxInput',
                        hidden: true,
                        autoEl: {
                            tag: 'input',
                            type: 'file',
                            accept: '.srx'
                        },
                        listeners: {
                            change: {
                                fn: function uploadSrx(e, input) {
                                    var data = new FormData()
                                    data.append('id', input.recId);
                                    data.append('srx', input.files[0]);

                                    fetch(Editor.data.restpath + 'plugins_okapi_bconf/uploadSRX', {
                                        method: 'POST',
                                        body: data
                                    }).then(function(response){
                                        debugger;
                                    })
                                    input.value = input.recId = ''; // reset file input
                                },
                                element: 'el'
                            }
                        }
                    },],
                }, ],
            };
        return me.callParent([Ext.apply(config, instanceConfig)]);
    },
});