/*
 * File: app/view/purchaseOrders/CreateWindow.js
 *
 * This file was generated by Sencha Architect
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 5.1.x library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 5.1.x. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('Erp.view.purchaseOrders.VendorPdf.VendorPdfWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.vendorpdfwindow',

    requires: [
        'Ext.form.Panel',
        'Ext.form.field.Display',
        'Ext.form.field.ComboBox',
        'Ext.form.field.Number',
        'Ext.form.field.Date',
        'Ext.button.Button',
        'Erp.view.Numberfieldcustom',
        'Erp.view.purchaseOrders.VendorPdf.VendorPdfWindowViewController'
    ],
    controller: 'vendorpdfwindow',
    minWidth: 800,
    layout: 'fit',
    alwaysOnTop:true,
    pdfFilename:'',
    vendorData:null,
    purchaseOrderData:null,
    dirtyFields:null,
    modal: true,
    onEsc: function() {
        this.destroy();
    },
    listeners:{
        render:'onWindowRender',
        afterrender:'onWindowAfterRender',

    },
    initConfig: function(instanceConfig) {
        var me = this;
            me.purchaseOrderData=Ext.util.JSON.encode(instanceConfig.purchaseOrder.getData()),
            me.vendorData = Ext.util.JSON.encode(instanceConfig.vendor),
            me.dirtyFields = Ext.util.JSON.encode(instanceConfig.dirtyFields);
            if(!instanceConfig.vendor.IsCompany){
                me.title = "Purchase Order für "+instanceConfig.vendor.LastName+" "+instanceConfig.vendor.FirstName;    
            }else{
                me.title = "Purchase Order für "+instanceConfig.vendor.Company;
            }
            config = {
                    items:[{
                        xtype:'form',
                        url:'erp/purchaseorder/preview.pdf',
                        baseParams:{
                            data:me.purchaseOrderData,
                            vendor:me.vendorData,
                            dirtyFields:me.dirtyFields
                        },
                        standardSubmit:true,
                        hidden:true,
                    },{
                        xtype: 'container',
                        html: '<iframe type="application/pdf" id="pdfit" scrolling="auto"  name="pdfit" frameborder="0" height="100%" width="100%"></iframe>' 
                    }
                    ],
                    dockedItems: [
                            {
                                xtype: 'container',
                                dock: 'bottom',
                                padding: 10,
                                layout: {
                                    type: 'hbox',
                                    align: 'middle',
                                    pack: 'center'
                                },
                                items: [
                                    {
                                        xtype: 'button',
                                        flex: 1,
                                        itemId: 'saveButton',
                                        reference: 'saveButton',
                                        margin: 5,
                                        text: 'PO an Vendor senden',
                                        listeners: {
                                            click: 'save'
                                        }
                                    },
                                    {
                                        xtype: 'button',
                                        flex: 1,
                                        itemId: 'cancelButton',
                                        margin: 5,
                                        text: 'Abbrechen',
                                        listeners: {
                                            click: 'cancel'
                                        }
                                    }
                                ]
                            }
                        ]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});