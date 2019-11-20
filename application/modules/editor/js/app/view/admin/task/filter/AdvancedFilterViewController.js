
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

Ext.define('Editor.view.admin.task.filter.AdvancedFilterViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.editorAdminTaskFilterAdvancedFilter',
    
    /***
     * On advanced filter item desellect remove the task grid filter
     */
    onFilterHolderBeforeDeselect:function(combo,record,index,eOpts){
    	var taskGrid=Ext.ComponentQuery.query('#adminTaskGrid')[0],
    		taskStore=Ext.StoreManager.get('admin.Tasks'),
    		theFilter=taskGrid.getFilter(record.get('property'));

    	//suspend the filterchange event so the load filters is not triggered
    	taskGrid.suspendEvents('filterchange');
    	//it is default filter, disable with filter setActive
    	if(theFilter){
    		theFilter.setActive(false);
    	}else{
    		taskStore.removeFilter(record.get('property'));
    	}
    	taskGrid.resumeEvents('filterchange');
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
		    Ext.Array.push(filtersarray,me.getView().getFilterRenderObject(item));
	    });
		
		//foreach advance filters, check if it is already active
		Ext.Array.each(records, function(record) {
			var tmpFilter={
					operator:record.operator,
	            	property:record.property,
	            	value:record.value
			};
			
			//find the advanced filter in the active filters
			var filteredIndex=null, 
				filtered=Ext.Array.filter(filtersarray,function(item,index){
					if(item.property==tmpFilter.property){
						filteredIndex=index;
						return true
					}
					return false;
				});
			//add the filter when the filter is not active and the new value is not empty
			if(Ext.isEmpty(filtered) && !Ext.isEmpty(tmpFilter.value)){
				filtersarray=Ext.Array.push(filtersarray,tmpFilter);
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
			filtersarray=Ext.Array.push(filtersarray,tmpFilter);
		});
		return filtersarray;
    }
});