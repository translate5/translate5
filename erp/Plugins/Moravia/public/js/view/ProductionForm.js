Ext.define('Erp.plugins.Moravia.view.ProductionForm', {
    extend: 'Erp.view.project.Form',
    alias: 'widget.productionForm',
    
    
    requires: [
        'Erp.plugins.Moravia.store.Pmcustomers',
        'Erp.plugins.Moravia.store.Type',
        'Erp.plugins.Moravia.view.ProductionViewController',
        'Erp.view.Numberfieldcustom'
    ],

    controller: 'productionForm',
    itemId:'productionForm',
    scrollable: true,
    bodyPadding: 10,
    fieldDefaults: {
        anchor: '100%'
    },
    trackResetOnLoad: true,
    defaultButton: 'saveButton',
    layout: {
        type: 'vbox',
        align: 'stretch'
    },
    cls:'x-selectable',//selectable display fields
    
    SOURCE_LANG_RFC_DEFAULT:'en-US',
    TARGET_LANG_RFC_DEFAULT:'de-DE',
    
    formConfig:[{
        name:'offerNetValue',
        visible:false,
        required:false
    },{
        name:'offerDate',
        visible:false,
        required:false
    },{
        name:'debitNumber',
        visible:false,
        required:false
    },{
        name:'offerMargin',
        visible:false,
        required:false
    },{
        name:'billTaxValue',
        visible:false,
        required:false
    },{
        name:'billGrossValue',
        visible:false,
        required:false
    },{
        name:'checked',
        visible:false,
        required:false
    },{
        name:'checkerName',
        visible:false,
        required:false
    },{
        name:'conversionMonth',
        visible:false,
        required:false
    },{
        name:'conversionYear',
        visible:false,
        required:false
    },{
        name:'plannedDeliveryDate',
        visible:false,
        required:false
    },{
        name:'paidDate',
        visible:false
    },{
        name:'billMargin',
        visible:false,
        required:false
    },{
        name:'keyAccount',
        visible:false,
        required:false
    },{
        name:'customerId',
        visible:false,
        required:true
    },{
        name:'customerNumber',
        visible:false,
        required:true
    },{
        name:'taxPercent',
        visible:false,
        required:false
    },{
        name:'customerName',
        visible:false,
        required:false
    },{
        name:'sourceLang',
        editable:false,
        visible:false
    },{
        name:'targetLang',
        editable:false,
        visible:false
    },{
    	name:'releaseDate'
    },{
    	name:'billDate',
    	visible:false
    },{
    	name:'pmCustomer',
    	required:true
    },{
    	name:'preliminaryWeightedWords',
    	visible:false
    },{
    	name:'endCustomer',
    	required:true
    },{
    	name:'customerOrder',
    	visible:false
    }],
    
    initComponent:function(){
    	var me=this;
    	me.callParent();
    	me.loadCustomConfig(me.formConfig);
    },
    
    loadRecord: function(record) {
    	var me=this,
    		projectPanelEastRegion=me.up('#projectPanelEastRegion'),
    		controller=me.getController();

    	me.callParent(arguments);
    	
    	//check if the east region (the form panel holder) is disabled, if yes, enable the panel
    	if(projectPanelEastRegion.isDisabled()){
        	projectPanelEastRegion.setDisabled(false);
        }
        
    	controller.setCustomerPrices(record.get('endCustomer'));
    	controller.loadPreliminaryWeightedWordsConfig(record.get('endCustomer'));
    	controller.customFieldValidator();
        
    },
    
    loadCustomConfig:function(customConfig){
        var me=this,
            form=me.getForm(),
            orderFieldset=me.down('#orderFieldset'),
            customerFieldset=me.down('#customerFieldset'),
            invoiceFieldset=me.down('#invoiceFieldset'),
            checkedFieldset=me.down('#checkedFieldset'),
            invoiceEntryFieldset=me.down('#invoiceEntryFieldset'),
            numberFieldValidator=function(value){
        		Erp.Utils.checkNegativeNumber(this,value);
        		return true;
        	};
        
        
    	invoiceFieldset.add({
            xtype: 'textfield',
            fieldLabel: 'PR-Nr.',
            name: 'prNumber',
            listeners:{
            	change:'onPrNumberChange'
            }
        });
    	
    	invoiceEntryFieldset.setHidden(true);
    	
    	//remove the field from the invoice fieldset. This field will be added into the order fieldset.
        invoiceFieldset.remove(invoiceFieldset.down('numberfield[name="billNetValue"]'));
        
        orderFieldset.add([{
            xtype: 'displayfield',
            fieldLabel: 'Hand-off-Nr.',
            name: 'handoffNumber',
            editable:false,
            allowBlank:false
        },{
            xtype: 'combo',
            fieldLabel: 'Type',
            name: 'productionType',
            typeAhead: true,
            queryMode:'local',
            displayField: 'name',
        	valueField: 'name',
            store:Ext.create('Erp.plugins.Moravia.store.Type'),
            allowBlank:false,
            enableKeyEvents:true,
            listeners:{
            	keyup:'onTypeKeyPress'
            }
        },{
            xtype: 'datefield',
            fieldLabel: 'Abgabedatum',
            name: 'submissionDate',
            allowBlank:false
        },{
            xtype: 'numberfieldcustom',
            fieldLabel: 'Vorläufige gewichtete Wörter',
            decimalPrecision:4,
            useCustomPrecision:true,
            allowDecimals:true,
            name: 'preliminaryWeightedWords',
            cls:'numberfieldcustom numberfieldcustomtextalign',
            enableKeyEvents:true,
            listeners:{
            	keyup:'onPreliminaryWeightedWordsChange'
            },
            validator:numberFieldValidator
        },{
            xtype: 'numberfieldcustom',
            fieldLabel: 'Gewichtete Wörter',
            decimalPrecision:4,
            useCustomPrecision:true,
            name: 'weightedWords',
            cls:'numberfieldcustom numberfieldcustomtextalign',
            allowDecimals:true,
            enableKeyEvents:true,
            listeners:{
            	keyup:'onWeightedWordsChange'
            },
            validator:numberFieldValidator
        },{
            xtype: 'numberfieldcustom',
            fieldLabel: 'Stunden (Eingabe: Minuten)',
            name: 'hours',
            minValue:1,
            cls:'numberfieldcustom numberfieldcustomtextalign',
            enableKeyEvents:true,
            listeners:{
            	keyup:'onHoursChange'
            },
            validator:numberFieldValidator
        },{
            xtype: 'numberfieldcustom',
            fieldLabel: 'Handoff-Wert',
            name: 'handoffValue',
            cls:'numberfieldcustom numberfieldcustomtextalign',
            decimalPrecision:2,
            useCustomPrecision:true,
            validator:numberFieldValidator
        },{
        	xtype: 'numberfieldcustom',
            decimalPrecision:2,
            useCustomPrecision:true,
        	mouseWheelEnabled:false,
            validator: function(value) {
                var res = this.lookupViewModel().get('stateresult');
                //this here will add red color to the text if the value is negative
                Erp.Utils.checkNegativeNumber(this,value);
                if(!value && res && res.isOrdered) {
                    return this.blankText;
                }
                return true;
            },
            fieldLabel: 'Rechn.-betrag netto €',
            name: 'billNetValue',
            readOnlyCls: 'x-form-readonly x-item-disabled',
            step: 10,
            cls:'numberfieldcustom numberfieldcustomtextalign',
            bind: {
                readOnly: '{stateresult.isBilled}'
            },
            listeners: {
                change: 'onBillNetChange'
            }
        }]);
        
        checkedFieldset.add({
        	xtype: 'displayfield',
            fieldLabel: 'Abgleich-Ergebnis',
            name: 'balanceValueCheck',
            valueToRaw: function(value) {
            	if(value==1){
            		return 'ok';
            	}
            	if(value==0){
            		return 'nicht ok';
            	}
            	return 'Abgleich noch nicht ausgeführt oder Feld zurückgesetzt';
            }
        });
        
        customerFieldset.add([{
        	xtype:'combo',
        	fieldLabel: 'Endkunde',
        	name:'endCustomer',
        	typeAhead: true,
        	queryMode:'local',
        	store:Erp.data.plugins.Moravia.endCustomers,
        	allowBlank:false,
        	forceSelection:true,
        	enableKeyEvents:true,
        	bind:{
        		disabled:'{isNewRecord}'
        	},
        	listeners:{
        		change:'onEndCustomerChange',
        		enable:'onEndCustomerEnable'
        	}
        },{
            xtype: 'textfield',
            fieldLabel: 'Projektname Endkunde',
            name: 'projectNameEndCustomer',
            allowBlank:false
        },{
        	xtype:'combo',
            name: 'pmCustomer',
            fieldLabel: 'PM Customer',
            typeAhead: true,
            queryMode:'local',
            blankText: "Dieses Feld darf nicht leer sein",
            displayField: 'name',
        	valueField: 'name',
            store:Ext.create('Erp.plugins.Moravia.store.Pmcustomers'),
            enableKeyEvents:true,
            listeners:{
            	keyup:'onPmCustomerKeyPress'
            }
        }]);
        
        /*
        customerFieldset.down('#customerId').setStore({
        	store:Ext.create('Ext.data.Store', {
        	     model: 'Erp.model.Customer',
        	     data : [Erp.data.plugins.Moravia.customer]
        	 })
        })
        */
        //add the line items save button to the form
        me.getDockedItems('container[dock="bottom"]')[0].add([{
            xtype: 'button',
            flex: 1,
            formBind: true,
            itemId: 'saveLineItemButton',
            reference: 'saveLineItemButton',
            margin: 5,
            text: 'Speichern & neues line-item'
        }]);
        
        me.applyCustomConfig(customConfig);
        
        //readOnlyCls: 'x-form-readonly x-item-disabled'
        var statusComboButton=me.down('#statusComboButton'),
        	menu=statusComboButton.getMenu();
        	
        menu.add({
        	xtype: 'menuitem',
            text: 'PR created',
            listeners: {
                click: 'onPrCreatedClick'
            }
        });
        
        menu.add({
        	xtype: 'menuitem',
            text: 'PR approved',
            listeners: {
                click: 'onPrApprovedClick'
            }
        });
        
        form.isValid();
    },
    
    /***
     * Return initialized model instance.This model is loaded on form init 
     */
    getModelInstance:function(){
    	var me=this,
    		sourceLang=Ext.Array.filter(Erp.data.sourceLanguages,function(item){
    			return item.value.toLowerCase()==me.SOURCE_LANG_RFC_DEFAULT.toLowerCase();
    		}),
    		targetLang=Ext.Array.filter(Erp.data.targetLanguages,function(item){
    			return item.value.toLowerCase()==me.TARGET_LANG_RFC_DEFAULT.toLowerCase();
    		});
    	
    	return Ext.create('Erp.plugins.Moravia.model.Production', {
            id: null,
            sourceLang:sourceLang[0].value,
            targetLang:targetLang[0].value,
            name: "",
            offerMargin: null,
            offerDate: new Date(),
            debitNumber: null,
            state: this.getController().states.STATE_ORDERED,
            billDate:null,
            paidDate:null,
            releaseDate:new Date(),
            modifiedDate:new Date(),
            editorId: Erp.data.app.user.id,
            pmId: Erp.data.app.user.id,
            editorName: Erp.data.app.user.userName,
            conversionMonth:null,
            conversionYear:null,
            taxPercent: null,
            offerNetValue: 0,
            billNetValue:0,
            billTaxValue:null,
            billGrossValue:0,
            billMargin:null,
            customerId:Erp.data.plugins.Moravia.customer.id,
            customerName:Erp.data.plugins.Moravia.customer.name,
            customerNumber:Erp.data.plugins.Moravia.customer.number,
            weightedWords:null,
            hours:null,
            isCustomerView:true
        });
    },
});