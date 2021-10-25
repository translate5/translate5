/**
 * @class Erp.plugins.Moravia.controller.CustomerView
 * @extends Ext.app.Controller
 */
Ext.define('Erp.plugins.Moravia.controller.CustomerView', {
    extend: 'Ext.app.Controller',

    requires: [
    	'Erp.plugins.Moravia.model.Production',
        'Erp.plugins.Moravia.view.ProductionForm',
        'Erp.plugins.Moravia.view.TagFilter'
    ],

    refs: {
        productionForm: '#productionForm',
        projectForm: '#projectform',
        projectpanel:'projectpanel'
    },
    
    listen: {
    	controller: {
    		'#productionForm': {
    			activeProjectFormSave:'onActiveProjectFormSave',
    			beforeActiveProjectFormSave:'onBeforeActiveProjectFormSave',
    			projectStateChange:'onProjectStateChange'
            }
        },
        component:{
            'projectpanel':{
            	render:'onProjectPanelRender',
            	beforerender:'onProjectPanelBeforeRender'
            },
            '#btnBillCollection':{
            	click:'onBillCollectionButtonClick'
            },
            '#btnUpdateBalanceValueCheck':{
            	click:'onUpdateBalanceValueCheckClick'
            },
            '#saveLineItemButton':{
            	click:'saveAll'
            },
            '#projectGrid':{
            	filterchange:'onProjectGridFilterChange',
            	selectionchange:'onProjectGridSelectionChange'
			},
			'#projectForm':{
				projectFormRecordLoaded:'onProjectFormRecordLoaded'
			}
        },
        store:{
        	'#pmUsers':{
        		beforeload:'onPmUsersStoreBeforeLoad'
        	},
        	'#formCustomers':{
        		beforeload:'onFormCustomersBeforeLoad'
        	}
        }
    },
    
    /***
     * Internal id to customer map.
     */
    idCustomersMap:[],
    
    onBeforeActiveProjectFormSave:function(formPanel,record,saveRecordCallback){
    	var me=this,
			projectPanel=me.getProjectpanel(),
			projectPanelController=projectPanel.getController(),
			activeForm=projectPanelController.getActiveForm()
			
			
		//handle only the event comming from the production form
    	if(activeForm.getXType()!=me.getProductionForm().getXType()){
    		return true;
    	}
    	
    	//check unsaved component data (pm customer and type)
    	me.checkUnsavedData(activeForm);
    	
    	activeForm.getController().balanceValueCheck(record,saveRecordCallback);
    	return false;
    },
    
    onProjectStateChange:function(formPanel,record,component){
    	var me=this,
    		projectPanel=me.getProjectpanel(),
    		projectPanelController=projectPanel.getController(),
    		activeForm=projectPanelController.getActiveForm(),
    		newRecord=activeForm.getModelInstance();
    	
    	//handle only the event comming from the production form
    	if(activeForm.getXType()!=me.getProductionForm().getXType()){
    		return;
    	}
    	
    	//when the save and new line item button is used, load the lineitem specific record in the form
    	if(component.getItemId()!='saveLineItemButton'){
    		return;
    	}
    	
    	newRecord.data=record.data;
    	
    	//reset the lineitem specific fields (those fields need to be entered additionally for each line item separately)
    	newRecord.set('id',null);
    	newRecord.set('productionId',null);
    	newRecord.set('state',activeForm.getController().states.STATE_ORDERED);
    	newRecord.set('preliminaryWeightedWords',null);
    	newRecord.set('weightedWords',null);
    	newRecord.set('hours',null);
    	newRecord.set('submissionDate',null);
    	newRecord.set('prNumber',null);
    	newRecord.set('performanceDate',null);
    	newRecord.set('billNetValue',null);
    	newRecord.set('customerOrder',null);
    	
    	projectPanelController.add(newRecord);
    },
    
    onProjectFormRecordLoaded:function(form,record){
    	var me=this,
			projectPanel=me.getProjectpanel(),
			projectPanelController=projectPanel.getController(),
			activeForm=projectPanelController.getActiveForm(),
			formsPanel=form.up('#projectPanelEastRegion');
		
		//is production view
		if(activeForm.getXType()==me.getProductionForm().getXType()){
			formsPanel.setDisabled(false);
			return;
		}
		
    	//disable form editable for moravia customer
		formsPanel.setDisabled(record.get('isCustomerView'));
    },
    
    onActiveProjectFormSave:function(){
		this.getProjectpanel().getController().saveCommentAfterForm();
    },
    
    saveAll:function(button,e,eOpts){
    	this.getProjectpanel().getController().saveAll(button,e,eOpts);
    },
    
    cancelAll:function(button, e, eOpts){
        this.getProjectpanel().getController().cancelAll(button, e, eOpts);
    },
    
    onProjectPanelBeforeRender:function(panel){
    	var me=this,
    		projectGrid=panel.down('#projectGrid'),
    		panelViewModel=panel.getViewModel();
    		
    	//if the user has only production rights, disable the po editing
    	panelViewModel.set('isPoAllowed',Ext.Array.difference(Erp.data.app.user.roles,['production','noRights','basic']).length>0);
    	
    	//create customerId to customerMap so it can be used in the customerId renderer
    	for(var i=0;i<Erp.data.customers.customers.length;i++){
    		var single=Erp.data.customers.customers[i];
    		me.idCustomersMap[single.id]=single.name;
    	}
    	
    	var	newColumns=[
    			{
    	    		 xtype: 'datecolumn',
    	             width: 80,
    	             dataIndex: 'submissionDate',
    	             text: 'Abgabedatum',
    	             tooltip: 'Abgabedatum',
    	             filter: {
    	                 type: 'date'
    	             }
    	    	},{
    	            xtype: 'gridcolumn',
    	            width: 90,
    	            dataIndex: 'handoffNumber',
    	            text: 'HO-Nr.',
    	            tooltip: 'Hand-off-Nr.',
    	            align:'right',
    	            filter: {
    	                type: 'number'
    	            }
    	    	},{
    	            xtype: 'gridcolumn',
    	            width: 100,
    	            dataIndex: 'productionType',
    	            text: 'Type',
    	            tooltip: 'Type',
    	            filter: {
    	                type: 'string'
    	            }
    	        },{
    	        	 xtype: 'numbercolumn',
    	             width: 90,
    	             dataIndex: 'preliminaryWeightedWords',
    	             text: 'Vorläufige gewichtete Wörter',
    	             tooltip: 'Vorläufige gewichtete Wörter',
    	             align:'right', 
    	             filter: {
    	                 type: 'number'
    	             }
    	        },{
    		       	 xtype: 'numbercolumn',
    		         width: 90,
    		         dataIndex: 'weightedWords',
    		         text: 'Gewichtete Wörter',
    		         tooltip: 'Gewichtete Wörter',
    		         align:'right', 
    		         filter: {
    		             type: 'number'
    		         }
    	        },{
    		       	 xtype: 'numbercolumn',
    		         width: 90,
    		         dataIndex: 'hours',
    		         text: 'Stunden',
    		         tooltip: 'Stunden',
    		         align:'right', 
    		         renderer:function(value,metaData){
    		        	 if(value<1){
    		        		 return '0.00';
    		        	 }
    		        	 var hours = (value / 60),
    		        	 	rhours = Math.floor(hours);
    		        	 	minutes = (hours - rhours) * 60;
    		        	 	rminutes = Math.round(minutes),
    		        	 	rminutes=rminutes>9 ? rminutes : ("0"+rminutes)
    		        	 	tooltip=rhours +' Stunden und '+rminutes+' Minuten';
		        	 	metaData.tdAttr = "data-qtip='"+tooltip+"'";
		        	 	return rhours + ":" + rminutes;
    		         },
    		         filter: {
    		             type: 'number'
    		         }
    	        },{
    		       	 xtype: 'numbercolumn',
    		         width: 90,
    		         dataIndex: 'handoffValue',
    		         text: 'Handoff-Wert',
    		         tooltip: 'Handoff-Wert',
    		         align:'right', 
    		         filter: {
    		             type: 'number'
    		         }
    	        },{
    	        	xtype: 'gridcolumn',
    	            width: 100,
    	            dataIndex: 'prNumber',
    	            text: 'PR-Nr.',
    	            tooltip: 'PR-Nr.',
    	            filter: {
    	                type: 'string'
    	            }
    	        },{
    	        	xtype: 'booleancolumn',
    	            width: 60,
    	            dataIndex: 'balanceValueCheck',
    	            text: 'Abgleich-Ergebnis',
    	            tooltip: 'Abgleich-Ergebnis',
    	            renderer:function(value,metaData){
    	            	if(value==1){
    	            		return 'ok';
    	            	}
    	            	if(value==0){
    	            		return 'nicht ok';
    	            	}
    	            	metaData.tdAttr = "data-qtip='Abgleich noch nicht ausgeführt oder Feld zurückgesetzt'";
		        	 	return '';
   		         	},
    	            filter: {
    	                type: 'boolean'
    	            }
    	        },{
    	        	xtype: 'gridcolumn',
    	            width: 100,
    	            dataIndex: 'endCustomer',
    	            text: 'Endkunde',
    	            tooltip: 'Endkunde',
    	            filter: {
    	                type: 'string'
    	            }
    	        },{
    	        	xtype: 'gridcolumn',
    	            width: 100,
    	            dataIndex: 'projectNameEndCustomer',
    	            text: 'Projektname Endkunde',
    	            tooltip: 'Projektname Endkunde',
    	            filter: {
    	                type: 'string'
    	            }
    	        },{
    	        	xtype: 'gridcolumn',
    	            width: 100,
    	            dataIndex: 'pmCustomer',
    	            text: 'PM Customer',
    	            tooltip: 'PM Customer',
    	            filter: {
    	                type: 'string'
    	            }
    	        }
    		],
    		customerId=[{
    			xtype: 'gridcolumn',
                width: 80,
                dataIndex: 'customerId',
                text: 'Kunde',
                tooltip: 'Kunde',
                renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
                    if(me.idCustomersMap[value]) {
                        return me.idCustomersMap[value];
                    }
                    return '';
                },
                filter: {
					type: 'tagfilter',
					emptyText:'-- Bitte auswählen --',
					fields: {
			            in: {
			            	fieldLabel:'Enthalten',
			            	store: new Ext.data.Store({
								data: Erp.data.customers.customers
							})
			            },
			            notInList: {
			            	fieldLabel:'Ausnehmen',
			            	store: new Ext.data.Store({
								data: Erp.data.customers.customers
							})
			            }
			        }
                }
    		}];
    	
    	projectGrid.headerCt.insert(projectGrid.columns.length - 1,newColumns);
    	//insert the customerId field on old customer field place
    	projectGrid.headerCt.insert(4,customerId);
    	
    	projectGrid.getView().refresh();
    	
    	var viewConfig=panel.getController().config,
    		offerColumns=viewConfig.viewColumns['offer'],
    		projectColumns=viewConfig.viewColumns['project'],
    		billColumns=viewConfig.viewColumns['bill'];
    		
    	//hide the old customer field, and add the new one in all available views
    	if(offerColumns !==undefined){
    		offerColumns.push('customerId');
    		offerColumns=Ext.Array.remove(offerColumns,'customerName');
    	}
    	
    	if(projectColumns !==undefined){
    		projectColumns.push('customerId');
    		projectColumns=Ext.Array.remove(projectColumns,'customerName');
    	}
    	
    	if(billColumns !==undefined){
    		billColumns.push('customerId');
    		billColumns=Ext.Array.remove(billColumns,'customerName');
    	}
    	
    	
    	viewConfig.sortView['production']=[{
    		field:'id',
    		value:'DESC'
		}];
    	
    	viewConfig.viewColumns['production']=[
    		'id',
    		'name',
    		'endCustomer',
    		'pmCustomer',
    		'projectNameEndCustomer',
    		'productionType',
    		'prNumber',
    		'handoffNumber',
    		'preliminaryWeightedWords',
    		'weightedWords',
    		'hours',
    		'handoffValue',
    		'state',
    		'customerName',
    		'customerNumber',
    		'projectNameEndCustomer',
    		'productionType',
    		'submissionDate',
    		'balanceValueCheck'
		];
    	
    	var offerGridMenuToolbar=panel.down('#offerGridMenuToolbar'),
    		itemsLength=offerGridMenuToolbar.items.length;
    		
    	offerGridMenuToolbar.insert(itemsLength-1,[{
    		xtype: 'tbseparator'
    	},{
    		xtype:'button',
    		text:'Sammelrechnung',
    		itemId:'btnBillCollection',
    		disabled:true
    	},{
    		xtype: 'tbseparator'
    	},{
    		xtype:'button',
    		text:'Abgleich-Ergebnis ändern',
    		itemId:'btnUpdateBalanceValueCheck',
    		hidden:true
    	},{
    		xtype: 'tbseparator'
    	}])
    	
		var sharedViewModel=panel.getViewModel();
		
		if(sharedViewModel.isInstance) {
	        var stores = sharedViewModel.storeInfo || {};
	        stores.production = {
	        		type: 'buffered',
                    pageSize: 200,
                    autoLoad: false,
                    model: 'Erp.plugins.Moravia.model.Production',
                    listeners:{
                    	beforeload:function( store, operation, eOpts){
                            var me=this,
                            	proxy=store.getProxy(),
                            	merged = Ext.merge({}, proxy.getExtraParams(), {
                            		customerview:'production'
                            	});
                            proxy.setExtraParams(merged);
                    	},
                    	load:function(store){
                    		Ext.ComponentQuery.query('myviewport')[0].getViewModel().set('totalRows',store.totalCount)
                    	}
                    }
	        };
	        sharedViewModel.setStores(stores);
		}
	},
    
    onProjectPanelRender:function(panel){
    	panel.down('#projectPanelEastRegion').insert(1,{
			xtype:'productionForm'
		});
    },
    
    onBillCollectionButtonClick:function(){
    	var me=this,
    		projectGrid=Ext.ComponentQuery.query('#projectGrid')[0],
    		filters=projectGrid.getStore().getFilters().items,
    		mboxTitle='Sammelrechnung',
    		mboxHtml={
    			stateMessage:'',
    			customerId:'',
    			state:'',
    			additionalFilters:[],
    			dateLabel:'',
    			dateField:''
    		},
    		tpl = new Ext.XTemplate(
				'<p>',
    				'<p>{stateMessage}</p>',
    				'<p>Kunde: {customerId}</p>',
    				'<p>Status: {stateLabel}</p>',
    				'<p>Filter: ',
				    	'<tpl for="additionalFilters">',
			        		'<p><li>{name}: {value}</li><p>',
			        	'</tpl>',
	        		'</p>',
        		'</p>'
	       ),
	       getBoldHtml=function(value){
	    	   return '<b style="color: red;">'+value+'</b>'
	       };
    		
    	if(projectGrid.getStore().getCount()<1){
    		Erp.MessageBox.addInfo("Keine Ergebnisse im aktuellen Filtersatz.")
    		return;
    	}
    	
    	//collect the window template data from the filter set
    	for(var i=0;i<filters.length;i++){
    		var filter=filters[i];

    		//if the filter is the customerId, get the customer name
    		if(filter.getProperty()=='customerId'){
    			mboxHtml[filter.getProperty()]=me.idCustomersMap[filter.getValue()[0]];
    			continue;
    		}

    		var filterReadableName=projectGrid.down('gridcolumn[dataIndex="'+filter.getProperty()+'"]').text,
    			filterValue=me.getFilterRenderValue(filter);
    		
    		//collect non state filters
    		if(filter.getProperty()!='state' || filter.getValue().length!=1){
    			//collect the grid filter
    			mboxHtml['additionalFilters'].push({
    				name:getBoldHtml(filterReadableName),
    				value:getBoldHtml(filterValue)
    			});
    			continue;
    		}
    		//use the state label if exist. This is only available when single filter is selected(it is safe with [0]).
        	var stateLabel=Erp.data.project.stateLabels[filterValue] ? Erp.data.project.stateLabels[filterValue] : filterValue;;
        	
    		mboxHtml['stateLabel']=stateLabel;
    		mboxHtml['state']=filterValue;
    		
    		//collect the grid filter
    		mboxHtml['additionalFilters'].push({
    			name:filterReadableName,
    			value:stateLabel
    		});
    		
    		if(filter.getValue()[0]=='prapproved'){
    			mboxHtml['stateMessage']='"Rechnungsdatum Debitoren" für die Projekte im folgenden Filter setzen?';
    			mboxHtml['dateLabel']='Rechnungsdatum Debitoren';
    			mboxHtml['dateField']='billDate';
    		}
    		
    		if(filter.getValue()[0]=='billed'){
    			mboxHtml['stateMessage']='"Datum Rechnung bezahlt Debitoren" für die Projekte im folgenden Filter setzen?';
    			mboxHtml['dateLabel']='Datum Rechnung bezahlt Debitoren';
    			mboxHtml['dateField']='paidDate';
    		}
    	}
    	
    	//display the window
       	Ext.create('Ext.window.Window',{
			modal:true,
			width:500,
			autoScroll:true,
	        autoHeight:true,
			layout:'auto',
			title:mboxTitle,
			border:false,
			bodyPadding: 10,
			items:[{
				bodyPadding: 5,
				border:false,
				html:tpl.applyTemplate(mboxHtml)
			},{
				xtype: 'datefield',
				flex:1,
				fieldLabel: mboxHtml['dateLabel'],
				name:mboxHtml['dateField'],
				allowBlank:false,
				listeners:{
					change:function(field,newValue){
						var button=field.up('window').down('#okButton');
						button.setDisabled(!newValue || newValue=='');
					}
				}
			}],
			dockedItems: [{
		        xtype: 'toolbar',
		        dock: 'bottom',
		        items: [{
		        	xtype:'button',
		        	itemId:'okButton',
		        	text:'Datum setzen',
		        	disabled:true,
		        	handler:function(){
		        		var tmpWin=this.up('window'),
		        			dateField=tmpWin.down('datefield');
		        		me.saveBillCollection(dateField,mboxHtml['state'],projectGrid);
		        	}
		        
		        },{
		        	xtype:'button',
		        	text:'Abbrechen',
		        	handler:function(){
		        		this.up('window').destroy();
		        	}
		        }]
		    }]
    	 }).show();
    },
    
    onUpdateBalanceValueCheckClick:function(){
    	var me=this,
			projectGrid=Ext.ComponentQuery.query('#projectGrid')[0],
			selection=projectGrid.getSelection();
    	
    	if(selection.length!=1){
    		return;
    	}
    	var projectPanel=me.getProjectpanel(),
			projectPanelController=projectPanel.getController(),
			activeForm=projectPanelController.getActiveForm()
			selection=selection[0];
    	
    	//display the window
       	Ext.create('Ext.window.Window',{
			modal:true,
			width:500,
			autoScroll:true,
	        autoHeight:true,
			layout:'auto',
			title:'Abgleich-Ergebnis ändern',
			border:false,
			bodyPadding: 10,
			items:[{
				fieldLabel: 'Abgleich-Ergebnis',
				bodyPadding: 5,
				border:false,
	            xtype: 'radiogroup',
	            items: [{
	                name: 'radio',
	                inputValue: true,
	                boxLabel: 'ok',
	                checked: true
	            },{
	                name: 'radio',
	                inputValue: false,
	                boxLabel: 'nicht ok'
	            }],
			}],
			dockedItems: [{
		        xtype: 'toolbar',
		        dock: 'bottom',
		        items: [{
		        	xtype:'button',
		        	itemId:'okButton',
		        	text:'Abgleich-Ergebnis setzen',
		        	handler:function(){
		        		var tmpWin=this.up('window'),
		        			radiogroup=tmpWin.down('radiogroup');
		        		activeForm.getController().updateBalanceValueCheck(selection.get('handoffNumber'),radiogroup.getValue());
		        		tmpWin.destroy();
		        	}
		        
		        },{
		        	xtype:'button',
		        	text:'Abbrechen',
		        	handler:function(){
		        		this.up('window').destroy();
		        	}
		        }]
		    }]
    	 }).show();
    },
    
    /***
     * Save bill collection state and date.
     * The finction will update the status and date for the current grid filter.
     */
    saveBillCollection:function(field,state,grid){
    	var params = {},
        	url = Erp.data.restpath+'plugins_moravia_production/billcollection',
        	store = grid.getStore(),
        	proxy = store.getProxy();

      	params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
      	params['dateField']=field.getName();
      	params['dateValue']=field.getValue();
      	params['state']=state;
      	
      	Ext.Ajax.request({
      		url:url,
      		method:'POST',
      		params: params,
      		success: function(response){
      			var resp = Ext.util.JSON.decode(response.responseText),
      				total=resp['total'];
      			if(total==undefined || total < 1){
      				return;
      			}
      			store.load();
      			Erp.MessageBox.addInfo(Ext.String.format("Das Datum und der Status wurden für {0} Projekte aktualisiert",total));
      			field.up('window').destroy();
  			},
  			failure: function(response){
  				Erp.app.getController('ServerException').handleException(response);
			} 
	     });
    },
    
    
    onProjectGridFilterChange:function(store,filters,eOpts){
    	var me=this,
    		enableButton=0,
    		validStates=["prapproved","billed"],
    		filterToCheck=["customerId","state"],
    		validOperator=["in"],//the status and customerId(the include field) are using in operator
    		checkedFilters=[],
    		btnBillCollection=Ext.ComponentQuery.query('#btnBillCollection')[0];
    		
    	//check if the billCollection button should be enabled
    	//it is enabled only when the status is set to prapproved or billed and there is only one value selected in the customerId filter
    	for(var i=0;i<filters.length;i++){
    		var singleFilter=filters[i],
    			isFilterToCheck=Ext.Array.contains(filterToCheck,singleFilter.getProperty()),
    			isSingleValue=Ext.isBoolean(singleFilter.getValue()) || singleFilter.getValue().length==1,
    			isValidOperator=Ext.Array.contains(validOperator,singleFilter.getOperator());
    		
    		if(!isFilterToCheck || !isSingleValue || !isValidOperator){
    			continue;
    		}
    		
    		//the las check is if the is state filter and is one of the valid states
    		if(singleFilter.getProperty()=='state' && !Ext.Array.contains(validStates,singleFilter.getValue()[0])){
    			continue;
    		}
    		
    		//collect the valid filter property
    		checkedFilters.push(singleFilter.getProperty());
    	}
    	checkedFilters=Ext.Array.sort(checkedFilters);
    	//if the collected valid filter properties are the same as the filterToCheck 
    	//(only one state from the validStates list is selected and only one active customer from the include list) -> enable the button
    	btnBillCollection.setDisabled(!Ext.Array.equals(filterToCheck,checkedFilters));
	},
	
	onProjectGridSelectionChange:function(projectGrid,selected,eOpts){
    	var me=this,
			projectPanel=me.getProjectpanel(),
			projectPanelController=projectPanel.getController(),
			activeForm=projectPanelController.getActiveForm(),
			btnUpdateBalanceValueCheck=Ext.ComponentQuery.query('#btnUpdateBalanceValueCheck')[0],
    		isBalanceValueAllowed = (Ext.Array.indexOf(Erp.data.app.userRights, 'changeBalanceValueCheck') >= 0);
		
    	
    	btnUpdateBalanceValueCheck.setVisible(false);
    	
    	//handle only the event comming from the production form
    	if(activeForm.getXType()!=me.getProductionForm().getXType()){
    		return;
    	}
    	
    	//if more than one record is selected or the user has no rights to modefy the 
    	if(selected.length!=1 || !isBalanceValueAllowed){
    		return;
    	}
    	
    	btnUpdateBalanceValueCheck.setVisible(true);
	},
	
	/***
	 * Get the display value from the extjs filter.
	 * The filter operator will be translated so it is easy to understand.
	 * 
	 */
	getFilterRenderValue:function(filter){
		var operatorMap=[],
		value=[],
		filterValue=Array.isArray(filter.getValue()) ? filter.getValue().join(',') : filter.getValue();
		
		//operator and text map
		operatorMap['eq']='gleich';
		operatorMap['gt']='größer als';
		operatorMap['lt']='weniger als';
		
		if(operatorMap[filter.getOperator()]!=undefined){
			value.push(operatorMap[filter.getOperator()]);
		}
		//convert if it is date value
		if(Ext.isDate(filterValue)){
			filterValue=Ext.util.Format.date(filterValue,'d.m.Y')
		}
		value.push(filterValue);
		return value.join(':');
	},
	
	/***
	 * Check usaved data in the active form component. (pm customer and type are using separate table)
	 */
	checkUnsavedData:function(activeForm){
		var pmCustomer=activeForm.down('combo[name="pmCustomer"]'),
			productionType=activeForm.down('combo[name="productionType"]'),
			controller=activeForm.getController();
		
		controller.saveRemoteComboRecord(pmCustomer,'Erp.plugins.Moravia.model.Pmcustomers');
		controller.saveRemoteComboRecord(productionType,'Erp.plugins.Moravia.model.Type');
	},
	
	/***
	 * Before pm users store is loaded, add additional params to the request
     */
    onPmUsersStoreBeforeLoad:function( store, operation, eOpts){
        var me=this,
            proxy=store.getProxy(),
            merged = Ext.merge({}, proxy.getExtraParams(), {
                pmRoles:'pm,production'
            });
        proxy.setExtraParams(merged);
    },
    
    onFormCustomersBeforeLoad:function( store, operation, eOpts){
    	var me=this,
	    	projectPanel=me.getProjectpanel(),
			projectPanelController=projectPanel.getController(),
	        proxy=store.getProxy(),
	        merged = Ext.merge({}, proxy.getExtraParams(), {
	            customerview:projectPanelController.getViewType()
	        });
	    proxy.setExtraParams(merged);
    },
});
  