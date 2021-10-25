/*
 */
Ext.define('Erp.plugins.Moravia.view.ProductionViewController', {
    extend: 'Erp.view.project.FormViewController',
    alias: 'controller.productionForm',
    id: 'productionForm',
    
    setState: function(state) {
        var me = this,
            vm = me.getViewModel();
        
        me.handlePrApprovedPrCreatedStateChange(state);
        
        switch(state){
            case me.states.STATE_POPUBLISHED:
            case me.states.STATE_PRAPPROVED:
            case me.states.STATE_PRCREATED:
                break;
            default:
                me.callParent([state]);
                return;
        }
        vm.set('state', state);
        me.getView().getForm().isValid();
    },
    
    /***
     * Leistungsdatum and Pr number are required when STATE_PRAPPROVED or STATE_PRCREATED state is set
     */
    handlePrApprovedPrCreatedStateChange:function(state){
    	var me=this,
    		required=state==me.states.STATE_PRAPPROVED || state==me.states.STATE_PRCREATED
    	me.getView().applyCustomConfig([{
    		name:"performanceDate",
    		required:required,
    		editable:true
    	},{
    		name:"prNumber",
    		required:required,
    		editable:true
    	}]);
    },
    
	onPmCustomerKeyPress:function(combo,event,eOpts){
        if (event.getKey() != event.ENTER){
        	return;
        }
        this.saveRemoteComboRecord(combo,'Erp.plugins.Moravia.model.Pmcustomers');
	},
	
	onTypeKeyPress:function(combo,event,eOpts){
        if (event.getKey() != event.ENTER){
        	return;
        }
        this.saveRemoteComboRecord(combo,'Erp.plugins.Moravia.model.Type');
	},
	
	onEndCustomerChange:function(combo,newValue,oldValue,eOpts){
		var me=this;
		me.setCustomerPrices(newValue);
		me.loadPreliminaryWeightedWordsConfig(newValue);
		me.customFieldValidator();
	},
	
	onEndCustomerEnable:function(combo){
		//reapply the allowblank since it is removed after isNewRecord bind
		this.getView().applyCustomConfig([{
			name:'endCustomer',
			required:true
		}]);
	},
	
	onPreliminaryWeightedWordsChange:function(field,e,eOpts){
		var me=this,
			newValue=field.getValue(),
			vm=me.getViewModel(),
			record=vm.get('record'),
			form=me.getView().getForm(),
			isNewRecord=record && record.get('handoffNumber')<1;
		
		if(isNewRecord){
			form.findField('handoffValue').setValue(Erp.Utils.roundNumber(vm.get('perWordPrice')*newValue,2));
			form.findField('hours').setValue(null);
		}
		
		me.customFieldValidator();
	},
	
	onWeightedWordsChange:function(field,e,eOpts){
		var me=this,
			newValue=field.getValue(),
			vm=me.getViewModel(),
			record=vm.get('record'),
			form=me.getView().getForm(),
			isNewRecord=record && record.get('handoffNumber')<1,
			preliminaryWeightedWords=form.findField('preliminaryWeightedWords'),
			newResult=Erp.Utils.roundNumber(vm.get('perWordPrice')*newValue,2);
		
		if(isNewRecord && preliminaryWeightedWords.isHidden()){
			form.findField('handoffValue').setValue(newResult);
		}
		
		form.findField('hours').setValue(null);
		form.findField('billNetValue').setValue(newResult);
		
		me.customFieldValidator();
	},
	
	onHoursChange:function(field,e,eOpts){
		var me=this,
			newValue=field.getValue(),
			vm=me.getViewModel(),
			record=vm.get('record'),
			form=me.getView().getForm(),
			isNewRecord=record && record.get('handoffNumber')<1,
			valueInMinutes=(vm.get('perHourPrice')/60)*newValue;
		

		if(isNewRecord){
			form.findField('handoffValue').setValue(valueInMinutes);
			form.findField('preliminaryWeightedWords').setValue(null);
		}

		form.findField('weightedWords').setValue(null);
		form.findField('billNetValue').setValue(valueInMinutes);
		me.customFieldValidator();
	},
	
	onPrCreatedClick:function(){
		this.setState(this.states.STATE_PRCREATED);
	},
	
	onPrApprovedClick:function(){
		this.setState(this.states.STATE_PRAPPROVED);
	},
	
	onPrNumberChange:function(field,newValue,oldValue,eOpts){
		var me=this,
			vm=me.getViewModel(),
			state=vm.get('state');
		
		if(state == me.states.STATE_ORDERED && newValue!==""){
			this.setState(this.states.STATE_POPUBLISHED);
		}
		
		//if the the value is removed, set the initial state
		if((newValue=="" || newValue==null) && state==this.states.STATE_POPUBLISHED){
			this.setState(this.states.STATE_ORDERED);
		}
	},
	
	/***
	 * Calculate the handoff value or billNet value based on vm multiplier price and the changedValue.
	 */
	calculateHandoffBillNetValue:function(multiplier,changedValue){
		var me=this,
			vm=me.getViewModel(),
			record=vm.get('record'),
			form=me.getView().getForm();
		
		//if the changed value is <0 or the multiplier is not set or the value is < 1 do not calculate
		if((!changedValue || changedValue<1) || (!vm.get(multiplier) || vm.get(multiplier)<1)){
			return false;
		}
		
		var updateField='handoffValue';
		//update the billNetValue when is not a new record
		if(record.get('handoffNumber') && record.get('handoffNumber')>0){
			updateField='billNetValue';
		}
		
		
		form.findField(updateField).setValue(Erp.Utils.roundNumber(vm.get(multiplier)*changedValue,2));
		return true;
	},
	
	/***
	 * Save or set the current record in the given component
	 */
	saveRemoteComboRecord:function(combo,modelClass){
		var me=this,
	    	store=combo.getStore(),
	    	value=combo.inputEl.dom.value;
	    
	    if(value==''){
	    	return;
	    }
	    var storeRec=store.findRecord('name',value,0,false,true,true);
	    //the record exist, set the value
	    if(storeRec){
	    	combo.setValue(storeRec);
	    	return;
	    }
	    //the record does not exist, insert in db
		var model=Ext.create(modelClass);
	    model.set('name',value);
	    model.set('id',null);
	    model.save({
	        success: function(record) {
	        	combo.setValue(record);
	        	//reload the store
	        	store.load();
	        }
	    });
	},
	
	/***
	 * Load preliminaryWeightedWords field config for given customer name.
	 * The config is defined in zf config table.
	 */
	loadPreliminaryWeightedWordsConfig:function(endCustomerName){
		var me=this,
			vm=me.getViewModel(),
			endCustomerConfig=Erp.data.plugins.Moravia.preliminaryWeightedWordsEndCustomerConfig[endCustomerName],
			defaultConfig={
				name:"preliminaryWeightedWords",
				visible:false,
				required:false
			};
		//the selected end customer is not configured in the config, use the default config
		if(endCustomerConfig==undefined){
			me.getView().applyCustomConfig([defaultConfig]);
			return;
		}
		me.getView().applyCustomConfig([endCustomerConfig]);
	},
	
	/***
	 * Set the required property for hours and weightedWords fields.
	 * The fields are required when required is true.
	 * Only one of the fields can be empty when required is true.
	 */
	setHoursAndWeightedWordsRequired:function(required){
		var me=this;
		me.conditionalRequireField(required,'weightedWords','hours');
		me.getView().getForm().isValid();
	},
	
	/***
	 * Set if field1 and field2 are required based on the required flag and there value.
	 */
	conditionalRequireField:function(required,field1,field2){
		var me=this,
			form=me.getView().getForm(),
			field1=form.findField(field1),
			field2=form.findField(field2);
		
		if(!required){
			field1.allowBlank=true;
			field2.allowBlank=true;
			form.isValid();
			return;
		}
		
		field1.allowBlank = field2.getValue()>0;
		field2.allowBlank = field1.getValue()>0;
	},
	
	/***
	 * Custom validator for the weightedWords,hours and preliminaryWeightedWords fields.
	 * 
	 */
	customFieldValidator:function(){
		var me=this,
			state=me.getViewModel().get('state'),
			form=me.getView().getForm(),
			preliminaryWeightedWords=form.findField('preliminaryWeightedWords');
		
		//reset before calculation
		me.getView().applyCustomConfig([{
			name:"weightedWords",
			required:false
		},{
			name:"hours",
			required:false
		},{
			name:"preliminaryWeightedWords",
			required:false
		}]);
		
		//when the preliminaryWeightedWords is not visible -> hours or weightedWords required
		if(preliminaryWeightedWords.isHidden()){
			me.setHoursAndWeightedWordsRequired(true);
			form.isValid();
			return;
		}
		
		if(Ext.Array.contains([me.states.STATE_POPUBLISHED,me.states.STATE_PRAPPROVED,me.states.STATE_PRCREATED],state)){
			//wenn vorläufige gewichtete Wörter ausgefüllt, 
			//muss zusätzlich gewichtete Wörter ausgefüllt werden (durch den Benutzer - nicht automatisch). 
			//Wenn vorläufige gewichtete Wörter und gewichtete Wörter leer, muss Stunden ausgefüllt sein
			me.conditionalRequireField(true,'hours','weightedWords');
			me.conditionalRequireField(true,'hours','preliminaryWeightedWords');
		}
		
		if(state==me.states.STATE_ORDERED){
			//im Status beauftragt: Entweder vorläufige gewichtete Wörter ausgefüllt oder Stunden
			me.conditionalRequireField(true,'hours','preliminaryWeightedWords');
		}
		
		form.isValid();
	},
	
	/***
	 * Request a balanceValueCheck to the server.
	 */
	balanceValueCheck:function(formRecord,saveRecordCallback){
        var me = this,
        	projectGrid=Ext.ComponentQuery.query('#projectGrid')[0];
        
	    Ext.Ajax.request({
            url:Erp.data.restpath+'plugins_moravia_production/balancevaluecheck',
                method: "GET",
                params:{
            		handoffNumber:formRecord.get('handoffNumber'),
            		record:Ext.JSON.encode(formRecord.getData())
                },
                scope: me,
                success: function(response){
                	var resp = Ext.util.JSON.decode(response.responseText),
      					result=resp['rows'],
      					message=resp['message'];
                	
                	if(message!==undefined){
                		//the balance value check is not valid and there is no mesage from the server
                		if(message==''){
                			formRecord.set('balanceValueCheck',null);
                        	saveRecordCallback();
                			return;
                		}
                		
                		Ext.MessageBox.show({
                            title: 'Abgleich-Check fehlgeschlagen',
                            msg: message,
                            buttons: Ext.MessageBox.YES
                        });
                		return;
                	}
                	
                	if(result==undefined || result.length<1){
                		Ext.MessageBox.show({
                            title: 'Abgleich-Check fehlgeschlagen',
                            msg: 'Der Abgleich-Check ist fehlgeschlagen. Trotzdem speichern?',
                            buttons: Ext.MessageBox.YESNO,
                            buttonText:{ 
                                yes: "Ja", 
                                no: "Nein" 
                            },
                            fn:function(button){
                            	if(button!="yes"){
                            		return;
                            	}
                            	
                            	formRecord.set('balanceValueCheck',false);
                            	
                            	//update the balance value check to false
                            	me.updateBalanceValueCheck(formRecord.get('handoffNumber'),false);
                            	
                            	//call the save record callback
                            	saveRecordCallback();
                            }
                        });
                		return;
	      			}
                	
                	formRecord.set('balanceValueCheck',true);
                	saveRecordCallback();
                }, 
                failure: function(response){
                	Erp.app.getController('ServerException').handleException(response);
                }
        });
	},
	
	/***
	 * Update the given balanceValueCheck value for the fiven handoff. The function will also reload the project store.
	 */
	updateBalanceValueCheck:function(handoffNumber,balanceValueCheck){
		var me = this,
    		projectGrid=Ext.ComponentQuery.query('#projectGrid')[0];
		
		Ext.Ajax.request({
            url:Erp.data.restpath+'plugins_moravia_production/balancevaluecheck',
                method: "GET",
                params:{
                	handoffNumber:handoffNumber,
                	balanceValueCheck:balanceValueCheck
                },
                scope: me,
                success: function(response){
                	Erp.MessageBox.addSuccess('Abgleich-Check erfolgreich!');
                	projectGrid.getStore().load();
                }, 
                failure: function(response){
                	Erp.app.getController('ServerException').handleException(response);
                	projectGrid.getStore().load();
                }
        })
	},
	
	/***
	 * Set hour and word price view model values from givend end customer and the form record language combination
	 */
	setCustomerPrices:function(endCustomer){
		var me=this,
			vm=me.getViewModel(),
			customerConfig=Erp.data.plugins.Moravia.handoffNumberCustomerConfig[Erp.data.plugins.Moravia.customer["number"]],
			perHourPrice=null,
			perWordPrice=null;
		
		//check if there is valid config for the moravia customer
		if(customerConfig==undefined){
			vm.set('perHourPrice',perHourPrice);
			vm.set('perWordPrice',perWordPrice);
			return;
		}
		
		customerConfig=customerConfig[endCustomer];
		//check if there is valid config for the end customer
		if(customerConfig==undefined){
			vm.set('perHourPrice',perHourPrice);
			vm.set('perWordPrice',perWordPrice);
			return;
		}
		
		var record=vm.get('record'),
			sourceLang=record && record.get('sourceLang'),
			targetLang=record && record.get('targetLang');
		
		if(!sourceLang){
			sourceLang=me.getView().SOURCE_LANG_RFC_DEFAULT;
		}
		
		if(!targetLang){
			targetLang=me.getView().TARGET_LANG_RFC_DEFAULT;
		}
		
		sourceLang=sourceLang.toLowerCase();
		targetLang=targetLang.toLowerCase();
		
		for(var i=0;i<customerConfig.length;i++){
			if(customerConfig[i].source.toLowerCase()==sourceLang && customerConfig[i].target.toLowerCase()==targetLang){
				perHourPrice=customerConfig[i].hourprice;
				perWordPrice=customerConfig[i].wordprice;
				break;
			}
		}
		
		vm.set('perHourPrice',perHourPrice);
		vm.set('perWordPrice',perWordPrice);
	}
});
