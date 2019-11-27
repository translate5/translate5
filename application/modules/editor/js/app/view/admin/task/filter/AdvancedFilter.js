
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
    	'Editor.view.admin.task.filter.FilterWindow'
    ],
    dock: 'top',
    strings:{
    	filterHolderLabel:'#UT#Gesetzte Filter'
    },
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
                    hideTrigger: true,
                    fieldLabel: me.strings.filterHolderLabel,
                    listeners:{
                    	beforedeselect:'onFilterHolderBeforeDeselect',
            		},
                    bind:{
                    	store:'{activeFilter}'
                    }
        		}]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    /***
     * Load the filters into the filter holder tagfield
     */
    loadFilters:function(filters){
    	var me=this,
    		filterHolder=me.down('#filterHolder'),
    		filtersarray=[],
    		records=[];
    	
		for(var i=0;i<filters.length;i++){
			Ext.Array.push(filtersarray,me.getFilterRenderObject(filters[i]));
		}
    	//add the records to the field store
    	if(filtersarray.length>0){
    		records=filterHolder.getStore().add(filtersarray);
    	}
    	//disable before select event, when the value is cleared
    	filterHolder.suspendEvents('beforedeselect');
    	filterHolder.clearValue();
    	filterHolder.resumeEvents('beforedeselect');
    	
    	//set the new sellection
    	filterHolder.setSelection(records);
    },
    /***
     * Get the filter render object for the filterHolder tagfield
     */
    getFilterRenderObject:function(item){
    	var textLabel=item.textLabel,
    		itemProperty=item.property ||item.getProperty(),
    		taskGrid=Ext.ComponentQuery.query('#adminTaskGrid')[0],
    		isGridFilter=item instanceof Ext.util.Filter;

    	//if it is a default(grid column) filter, try to find the label from the grid
    	if(!textLabel){
    		textLabel=taskGrid.text_cols[itemProperty] ? taskGrid.text_cols[itemProperty] : itemProperty;
    	}
    	return {
			'operator': !isGridFilter ? item.operator : item.getOperator(),
			'property': itemProperty,
			'value' : !isGridFilter ? item.value : item.getValue(),
			'textLabel':textLabel
		};
    }
});
