
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.view.admin.task.filter.AdvancedFilter', {
    extend: 'Ext.toolbar.Toolbar',
    alias: 'widget.editorAdminTaskFilterAdvancedFilter',
    itemId:'advancedFilterToolbar',
    controller:'editorAdminTaskFilterAdvancedFilter',
    viewModel: {
        type: 'editorAdminTaskFilterAdvancedFilter'
    },
    requires: [
    	'Editor.view.admin.task.filter.AdvancedFilterViewController',
    	'Editor.view.admin.task.filter.AdvancedFilterViewModel',
    	'Editor.view.admin.task.filter.FilterWindow',
    	'Editor.model.admin.task.filter.ActiveFilter'
    ],
    dock: 'top',
    strings:{
    	filterHolderLabel:'#UT#Gesetzte Filter'
    },
    hidden:true,
    bind:{
    	visible:'{isActiveFilter}'
    },
    /***
     * Filter field source mapping array. The key value array represents the filter index to filter field data source (store,array)
     * Define the key value source in the initConfig method.
     * To use the field/source mapping in the filter field renderer see the getFieldSourceValue function
     */
    filterFieldSourceMap:[],
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
        		items:[{
        			xtype:'tagfield',
        			itemId:'filterHolder',
                    name:'filterHolder',
                    dataIndex:'filterHolder',
                    valueField: 'property',
                    displayField: 'textLabel',
                    tipTpl:'<tpl for="filtergroup">{textLabel}: {operatorTranslated} <b>{dataSourceValue}</b><br/></tpl>',
                    maxWidth:700,
                    hideTrigger: true,
                    fieldLabel: me.strings.filterHolderLabel,
                    queryMode: 'local',
                    expand:function(){
                    	return false;
                    },
                    listeners:{
                    	beforedeselect:'onFilterHolderBeforeDeselect',
                    	select:'onFilterHolderSelect'
            		},
                    bind:{
                    	store:'{activeFilter}',
                    	selection:'{selectedFilters}'
                    }
        		}]
            };
        
        //the filter dataIndex to source type mapping
        //the source type mapping is used in getFieldSourceValue function for custom field value render
        me.filterFieldSourceMap['sourceLang']='language';
        me.filterFieldSourceMap['targetLang']='language';
        me.filterFieldSourceMap['relaisLangLang']='language';
        me.filterFieldSourceMap['userName']='user';
        me.filterFieldSourceMap['workflowState']='workflowState';
        me.filterFieldSourceMap['workflowUserRole']='workflowUserRole';
        me.filterFieldSourceMap['workflowStepName']='workflowStepName';
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    /***
     * return the reference of the filter grid used for the filter
     */
    getFilterGrid:function(){
    	return this.up('grid');
    },
    
    /***
     * Load the filters into the filter holder tagfield
     */
    loadFilters:function(filters){
    	var me=this,
    		filterHolder=me.down('#filterHolder'),
    		filterHolderStore=filterHolder.getStore(),
    		filtersarray=[],
    		records=[];

    	//workaround for: Cannot modify ext-empty-store
    	//on task leave the filter change is triggered and then the loadFilters call
    	if(filterHolderStore.isEmptyStore){
    		filterHolder.setStore(me.getViewModel().getStore('activeFilter'));
    		filterHolderStore=filterHolder.getStore();
    	}
    	
    	me.getViewModel().getStore('activeFilter').removeAll();
    	
		//convert all active filtes to simple array object collection
		filters.each(function(item) {
			filtersarray.push(me.getFilterModelObject(item));
		});
    	
    	//add the records to the field store
    	if(filtersarray.length>0){
    		filtersarray=me.groupActiveFiltersFiltergroup(filtersarray);
    		records=filterHolderStore.add(filtersarray);
    	}
    	
    	//disable before select event, when the value is cleared
    	filterHolder.suspendEvents('beforedeselect');
    	filterHolder.clearValue();
    	filterHolder.resumeEvents('beforedeselect');
    	
    	//update the selected filters sellection
    	me.getViewModel().set('selectedFilters',records);
    	//update the active filter count view model
    	me.getViewModel().set('activeFiltersCount',filtersarray.length>0);
    },
    
    /***
     * Get active filter model instance from given grid filter object 
     */
    getFilterModelObject:function(item){
    	var operator=item.operator || item.getOperator(),
    		property=item.property || item.getProperty(),
    		value=item.value || item.getValue();
		return Ext.create('Editor.model.admin.task.filter.ActiveFilter',{
			'operator': operator,
			'property': property,
			'value' : value,
			'dataSourceValue':this.getFieldSourceValue(property,value)
		});
    },
    
    /***
     * Create filtergroup property for all multivalued filters.
     * The value of this property is used for rendering tooltips and custom values in the advance filter component.
     * The value is also used to update the multivalued filters in the advanced filter window.
     */
    groupActiveFiltersFiltergroup:function(filtersarray){
    	var me=this,
    		singleFilter=null,
    		filterIndex=-1,
    		returnArray=[];
    	
    	for(var i=0;i<filtersarray.length;i++){
    		singleFilter=filtersarray[i];
    		filterIndex=me.isFilterInArray(returnArray,singleFilter);
    		if(!singleFilter.get('filtergroup') && filterIndex<0){
    			singleFilter.set('filtergroup',[]);
    			singleFilter.get('filtergroup').push(singleFilter.getDataCustom());
    			returnArray.push(singleFilter);
    			continue;
    		}
    		if(filterIndex<0){
    			continue;
    		}
    		returnArray[filterIndex].get('filtergroup').push(singleFilter.getDataCustom());
    	}
    	return returnArray;
    },
    
    /***
     * Check if the filter exist in the given array.
     * This will return the found array index or -1 if the field does not exist
     */
    isFilterInArray:function(filtersarray,filter){
    	for(var i=0;i<filtersarray.length;i++){
    		if(filtersarray[i].get('property')==filter.get('property')){
    			return i;
    		}
    	}
    	return -1;
    },
    
    /***
     * Get the filter render value by given field and value.
     */
    getFieldSourceValue:function(field,value){
        var me=this,
        	source=me.filterFieldSourceMap[field],
        	findValues=function(storeKey,field,label){
		  		  var store=Ext.StoreMgr.get(storeKey),
		  		  	  values=[],
		  		  	  record=null;
		  		  for(var i=0;i<value.length;i++){
		  			  record=store.findRecord(field,value[i],0,false,true,true);
		  			  if(record){
		  				  values.push(record.get(label));
		  			  }
		  		  }
		  	    return values.join(',');
        	};
    	switch(source) {
    	  case 'language':
    		  //it is langauge field, use the languages store to find the record by language id
    	    return findValues('admin.Languages','id','label')
    	  case 'user':
    		  //it is user column, use the users store to find the username
    		  return findValues('admin.UsersList','userGuid','longUserName');
    	  case 'workflowState':
    		  //it is WorkflowState filter
    		  return findValues('admin.WorkflowState','id','label');
    	  case 'workflowUserRole':
    		  //it is WorkflowUserRoles filter
    		  return findValues('admin.WorkflowUserRoles','id','label');
    	  case 'workflowStepName':
    		  //it is WorkflowUserRoles filter
    		  return findValues('admin.WorkflowSteps','id','text');
    	  default:
    	    return value;
    	}
    },
    
});
