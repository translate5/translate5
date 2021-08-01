
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

Ext.define('Editor.view.admin.task.filter.AdvancedFilterViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.editorAdminTaskFilterAdvancedFilter',
    
    /***
     * On advanced filter item desellect remove the task grid filter
     */
    onFilterHolderBeforeDeselect:function(combo,record,index,eOpts){
    	var me=this,
    		taskGrid=me.getView().getFilterGrid(),
    		theFilter=taskGrid.getColumnFilter(record.get('property'));

    	//it is default filter, disable with filter setActive
    	if(theFilter){
    		theFilter.setActive(false);
    		return;
    	}
    	//the filter is one of the advanced filters
		me.removeAdvancedFilters(record.get('property'));
		//reset the value also in the filter window
		me.resetAdvancedFilterFieldValue(record);
    },
    
    /***
     * On advanced filter holder select event handler
     */
    onFilterHolderSelect:function(combo,value){
    	//the filter is visible if there is at least 1 active filter
    	this.getViewModel().set('activeFiltersCount',combo.selection!==null);
    },

    /***
     * Merge the advanced filters into the active filters list.
     */
    filterActiveFilters:function(records){
    	var me=this,
    		taskStore=Ext.StoreManager.get('admin.Tasks'),
			activefilters = taskStore.getFilters(false),
			filtersarray = [];
		
    	//convert all active filtes to simple array object collection
		activefilters.each(function(item) {
		    Ext.Array.push(filtersarray,me.getView().getFilterModelObject(item));
	    });
		
		//foreach advance filters, check if it is already active
		Ext.Array.each(records, function(record) {
			var tmpFilter={
					operator:record.operator,
	            	property:record.property,
	            	value:record.value,
	            	type:record.type
			};
			
			//find the advanced filter in the active filters
			var filteredIndex=null, 
				filtered=Ext.Array.filter(filtersarray,function(item,index){
					if((item.get('property')+item.get('operator'))==(tmpFilter.property+tmpFilter.operator)){
						filteredIndex=index;
						return true
					}
					return false;
				});
			//add the filter when the filter is not active and the new value is not empty
			if(Ext.isEmpty(filtered) && !Ext.isEmpty(tmpFilter.value)){
				filtersarray=Ext.Array.push(filtersarray,me.getView().getFilterModelObject(tmpFilter));
				return true;
			}
			//when remove index is found, remove the found active filter
			if(filteredIndex!==null){
				filtersarray=Ext.Array.removeAt(filtersarray,filteredIndex);
			}
			//when the new value is empty do nothing(the filter is removed)
			if(Ext.isEmpty(tmpFilter.value)){
				return true;
			}
			//add the new filter in the active filters array
			filtersarray=Ext.Array.push(filtersarray,me.getView().getFilterModelObject(tmpFilter));
		});
		return filtersarray;
    },
    
    /***
     * Remove the active advanced filter in the task grid by filter property
     */
    removeAdvancedFilters:function(property){
    	var me=this,
    		taskGrid=me.getView().getFilterGrid(),
    		tagField=me.getView().down('#filterHolder'),
    		records=tagField.getStore().query('property',property),//all active tagfield records for the property
    		taskStore=Ext.StoreManager.get('admin.Tasks');
    	
    	records.each(function(rec){
    		//get all active filters for the property
    		var filters=taskGrid.getActiveFilter(rec.get('property'));
    		Ext.each(filters, function(f) {
    			taskStore.removeFilter(f);
            });
    	});
    },
    
    /***
     * Reset the advanced filter field value for given record.
     */
    resetAdvancedFilterFieldValue:function(record){
    	var me=this,
    		filterGroup=record.get('filtergroup') ? record.get('filtergroup') : [],
			win=Ext.ComponentQuery.query('#editorAdminTaskFilterFilterWindow')[0];
		
		if(!win){
			return;
		}

		//set values to null and update the window
		for(var i=0;i<filterGroup.length;i++){
			filterGroup[i].value=null;
		}
		record.set('value',null);
		win.loadRecord([record]);
    }
});