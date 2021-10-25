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

Ext.define('Erp.view.purchaseOrders.CreateWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.purchaseorderscreatewindow',

    requires: [
        'Erp.view.purchaseOrders.CreateWindowViewModel',
        'Erp.view.purchaseOrders.CreateWindowViewController',
        'Ext.form.Panel',
        'Ext.form.field.Display',
        'Ext.form.field.ComboBox',
        'Ext.form.field.Number',
        'Ext.form.field.Date',
        'Ext.button.Button',
        'Erp.view.Numberfieldcustom'
    ],
    mixins: ['Erp.view.purchaseOrders.FormFields'],
    controller: 'purchaseorderscreatewindow',
    viewModel: {
        type: 'purchaseorderscreatewindow'
    },
    layout: 'fit',
    width:'90%',
    autoScroll:true,
    title: 'Purchase Order anlegen',
    modal: true,
    defaultButton: 'saveButton',
    referenceHolder: true,
    onEsc: function() {
        this.destroy();
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                items: [
                    {
                        xtype: 'form',
                        reference: 'form',
                        bodyPadding: 10,
                        items: [
                            {
                                xtype:'fieldset',
                                itemId:'projectData',
                                title:'Projektdaten',
                                width:'100%',
                                layout: {
                                    type: 'hbox',
                                    align: 'stretch'
                                },
                                defaults: {
                                    width: '25%',
                                    padding:10
                                },
                                items:[
                                    me.getPurchaseOrdersFieldConfig('orderId'),
                                    {
                                        xtype: 'displayfield',
                                        fieldLabel: 'Auftragsname',
                                        name: 'orderName',
                                        bind: {
                                            value: '{orderName}'
                                        }
                                    },{
                                        xtype: 'datefield',
                                        allowBlank: false,
                                        hidden:true,
                                        disabled:true,
                                        fieldLabel: 'Freigabedatum',
                                        name: 'releaseDate',
                                        enableKeyEvents:true,
                                        readOnlyCls: 'x-form-readonly x-item-disabled'
                                    },{
                                        xtype: 'datefield',
                                        fieldLabel: 'Gepl. Lieferdatum',
                                        hidden:true,
                                        disabled:true,
                                        name: 'plannedDeliveryDate',
                                        enableKeyEvents:true,
                                        readOnlyCls: 'x-form-readonly x-item-disabled'
                                    }
                                ]
                            },
                            {
                                xtype:'fieldset',
                                itemId:'poData',
                                width:'100%',
                                title:'PO-Daten',                     
                                layout: {
                                    type: 'hbox',
                                    align: 'stretch'
                                },
                                defaults: {
                                    width: '25%',
                                    padding:10
                                },
                                items:[{
                                    //items 1
                                    xtype:'container',
                                    defaults: {
                                        width: '100%',
                                    },
                                    items:[me.getPurchaseOrdersFieldConfig('number',{
                                        renderer: function(value, displayField) {
                                            if(!value) {
                                                return '- wird automatisch vergeben -';
                                            }
                                        }
                                    }),
                                    {
                                        xtype:'combobox',
                                        name:'targetLang',
                                        fieldLabel:'Zielsprache',
                                        emptyText:'-- Bitte auswählen --',
                                        submitEmptyText:false,
                                        allowBlank: false,
                                        typeAhead: true,
                                        store:me.addTargetLangStore(),
                                        onFocusLeave:function(e){
                                            var me=this,
                                                val =me.getValue();
                                            if(val!=null && val!="" && !me.findRecordByValue(val)){
                                                me.setValue(null);
                                            }
                                        },
                                        listeners:{
                                            change:'onTargetLangChange'
                                        },
                                        queryMode: 'local',
                                        valueField: 'value',
                                        displayField:'text'
                                    },
                                    {
                                        xtype: 'datefield',
                                        fieldLabel: 'Erstelldatum',
                                        enableKeyEvents:true,
                                        listeners:{
                                            keyup:function(field,e,eOpts){
                                                if((!field.getRawValue() || field.getRawValue().trim()=="") && (e.keyCode == e.RIGHT ||  e.keyCode ==e.SPACE)) {
                                                    field.setValue(new Date());
                                                }
                                            }
                                        },
                                        name: 'creationDate'
                                    },
                                    me.getPurchaseOrdersFieldConfig('deliveryDate',{
                                        enableKeyEvents:true,
                                        listeners:{
                                            keyup:function(field,e,eOpts){
                                                if((!field.getRawValue() || field.getRawValue().trim()=="") && (e.keyCode == e.RIGHT ||  e.keyCode ==e.SPACE)) {
                                                    field.setValue(new Date());
                                                }
                                            }
                                        }
                                    }),{
                                        xtype: 'combobox',
                                        fieldLabel: 'Vendor',
                                        name: 'vendorId',
                                        editable:false,
                                        allowBlank: false,
                                        anyMatch: true,
                                        autoLoadOnValue: true,
                                        forceSelection: true,
                                        queryMode: 'local',
                                        valueField: 'id',
                                        bind: {
                                            store: '{vendors}'
                                        },
                                        listeners: {
                                            change: 'onVendorChange'
                                        }
                                    }]
                                },{
                                    //items 2
                                    xtype:'container',
                                    defaults: {
                                        width: '100%',
                                    },
                                    items:[
                                        me.getPurchaseOrdersFieldConfig('wordsCount',{
                                            //additional config
                                            validator:function (value) {
                                                return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                            },
                                        }),
                                        me.getPurchaseOrdersFieldConfig('perWordPrice',{
                                            //additional config
                                            validator:function (value) {
                                                return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                            },
                                            bind:{
                                                value: '{vendor.Prices.PerWord}',
                                            }
                                        }),
                                        me.getPurchaseOrdersFieldConfig('wordsDescription',{
                                            //additional config
                                            validator:function (value) {
                                                return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                            }
                                        }),
                                        me.getPurchaseOrdersFieldConfig('hoursCount',{
                                            //additional config
                                            validator:function (value) {
                                                return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                            }
                                        }),
                                        me.getPurchaseOrdersFieldConfig('perHourPrice',{
                                            //additional config
                                            validator:function (value) {
                                                return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                            },
                                            bind:{
                                                value: '{vendor.Prices.PerHour}',
                                            }
                                        }),
                                        me.getPurchaseOrdersFieldConfig('hoursDescription',{
                                            //additional config
                                            validator:function (value) {
                                                return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                            }
                                        })]
                                },{
                                    //items 3
                                    xtype:'container',
                                    defaults: {
                                        width: '100%',
                                    },
                                    items:[{
                                        xtype: 'numberfieldcustom',
                                        fieldLabel: 'Zeilenpreis',
                                        name: 'perLinePrice',
                                        step: 0,
                                        cls:'numberfieldcustom numberfieldcustomtextalign',
                                        bind:{
                                            value: '{vendor.Prices.PerLine}',
                                            visible:'{isPerLinePrice}'
                                        },
                                        validator:function(value){
                                            return true;
                                        }
                                    },
                                    me.getPurchaseOrdersFieldConfig('additionalCount',{
                                        //additional config
                                        validator:function (value) {
                                            return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                        }
                                    }),
                                    me.getPurchaseOrdersFieldConfig('additionalDescription',{
                                        //additional config
                                        validator:function (value) {
                                            return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                        }
                                    }),
                                    me.getPurchaseOrdersFieldConfig('additionalUnit',{
                                        //additional config
                                        validator:function (value) {
                                            return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                        }
                                    }),
                                    me.getPurchaseOrdersFieldConfig('perAdditionalUnitPrice',{
                                        //additional config
                                        validator:function (value) {
                                            return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                        }
                                    }),
                                    me.getPurchaseOrdersFieldConfig('additionalPrice',{
                                        //additional config
                                        validator:function (value) {
                                            return Erp.Utils.customValidation(this.getName(),this.up('form').form);
                                        },
                                        step: null,
                                        editable:false
                                    })]
                                },{
                                    //items 4
                                    xtype:'container',
                                    defaults: {
                                        width: '100%',
                                    },
                                    items:[me.getPurchaseOrdersFieldConfig('transmissionPath'),
                                    me.getPurchaseOrdersFieldConfig('additionalInfo',{
                                        height:200
                                    }),
                                    {
                                        xtype: 'numberfieldcustom',
                                        name: 'netValue',
                                        step: 10,
                                        decimalSeparator:',',
                                        validator:function(value){
                                            return parseFloat(value.replace(/,/, '.'))>0 ? true : "Dieses Feld darf nicht leer sein";
                                        },
                                        bind: {
                                            fieldLabel: 'PO-Wert {baseCurrency}'
                                        }
                                    },{
                                        xtype: 'numberfieldcustom',
                                        name: 'originalNetValue',
                                        useCustomPrecision:true,
                                        hidden:true,
                                        disabled:true,
                                        step: 10,
                                        decimalSeparator:',',
                                        validator:function(value){
                                            return parseFloat(value.replace(/,/, '.'))>0 ? true : "Dieses Feld darf nicht leer sein";
                                        },
                                        bind: {
                                            fieldLabel: 'Wert in {vendor.currency}'
                                        }
                                    }]
                                }]
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
                                        formBind: true,
                                        itemId: 'saveButton',
                                        reference: 'saveButton',
                                        margin: 5,
                                        text: 'Vorschau anzeigen',
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
                                            click: 'cancelEdit'
                                        }
                                    }
                                ]
                            }
                        ],
                        listeners: {
                            afterrender: 'onFormAfterRender',
                            beforerender:'onFormBeforeRender'
                        }
                    }
                ],
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    //add all project target langs to po targetLang combo
    addTargetLangStore:function(targetLang){
        var me=this,
            project = me.initialConfig.project,
            targetLangs = targetLang ? targetLang : project.get('targetLang'),
            store=new Ext.data.Store({
                data: Erp.data.targetLanguages,
            });
        if(!targetLangs){
            return store;
        }
        var lngs=targetLangs.split(','),
            targetStore=new Ext.data.Store({
                data: [],
                fields: ['id','value','text'],
        }),rec=null;
        //copy only target languages from project
        lngs.forEach(function(element) {
            rec = store.findRecord('value',element,0,false,true,true);
            targetStore.add(rec);
        });
        if(targetLang){
            var frm=me.down('form').form,
                tfield = frm.findField('targetLang');
            tfield.setStore(targetStore);
        }
        return targetStore;
    },
});