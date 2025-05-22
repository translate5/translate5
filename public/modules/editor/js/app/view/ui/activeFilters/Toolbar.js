
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

Ext.define('Editor.view.ui.activeFilters.Toolbar', {
    extend: 'Ext.toolbar.Toolbar',
    alias: 'widget.activeFiltersToolbar',
    controller: 'activeFiltersToolbar',
    viewModel: {
        type: 'activeFiltersToolbar',
    },
    requires: [
    	'Editor.view.ui.activeFilters.ToolbarViewController',
    	'Editor.view.ui.activeFilters.ToolbarViewModel',
    	'Editor.model.ui.activeFilters.ActiveFilter'
    ],
    dock: 'top',
    hidden: true,
    bind: {
    	visible: '{atLeastOneFilterUsed}'
    },
    layout: 'anchor',
    items: [
        {
            xtype: 'tagfield',
            valueField: 'property',
            displayField: 'textLabel',
            tipTpl: '<tpl for="filtergroup">{textLabel}: {operatorTranslated} <b>{dataSourceValue}</b><br/></tpl>',
            maxWidth: 700,
            hideTrigger: true,
            queryMode: 'local',
            expand: () => false,
            bind: {
                store: '{activeFilter}',
                selection: '{selectedFilters}',
                fieldLabel: '{l10n.ui.activeFilters.tagfieldLabel}'
            }
        }
    ],

    /**
     * Filter field source mapping array. The key value array represents the filter index to filter field data source (store, array)
     * To use the field/source mapping in the filter field renderer see the getFieldSourceValue function
     */
    filterFieldSourceMap: [],

    /**
     * Load the filters into the filter holder tagfield
     */
    loadFilters: function(filtersCollection) {
    	var me = this,
            vm = me.getViewModel(),
    		tagfield = me.down('tagfield'),
    		tagfieldStore = tagfield.getStore(),
    		filtersArray = [];

        // Workaround for: 'Cannot modify ext-empty-store' error
        // which happens due to store exists but not yet bound to tagfield
        if (tagfieldStore.isEmptyStore) {
            tagfield.setStore(vm.getStore('activeFilter'));
            tagfieldStore = tagfield.getStore();
        }

		// Convert all active filters to simple array object collection
        filtersCollection.each(filter => filtersArray.push(me.getFilterModelObject(filter)));
    	
    	// Add the records to the field store
        tagfieldStore.setData(me.forceDistinctFields(filtersArray));

    	// Disable before select event, when the value is cleared
    	tagfield.suspendEvents('beforedeselect');
    	tagfield.clearValue();
    	tagfield.resumeEvents('beforedeselect');

    	// Update the selected filters selection
    	vm.set('selectedFilters', tagfieldStore.getRange());

    	// Update the active filter count view model
    	vm.set('atLeastOneFilterUsed', tagfieldStore.getCount());
    },
    
    /**
     * Get active filter model instance from given grid filter object 
     */
    getFilterModelObject:function(item){
    	var me = this,
            operator = item.operator || item.getOperator(),
    		property = item.property || item.getProperty(),
    		value = item.value || item.getValue();

        return Ext.create('Editor.model.ui.activeFilters.ActiveFilter', {
            'operator': operator,
            'property': property,
            'value' : value,
            'dataSourceValue': me.getFieldSourceValue(property, value),
            'ownerToolbarId': me.id
        });
    },
    
    /**
     * Move 1sts, 2nds and further filters having same field as 1sts into 'filtergroup'-property of 1sts,
     * and return possibly reduced array containing only 1st filters among the ones having same field.
     *
     * This is further used for rendering custom values inside the tooltip for each
     * currently used filter represetned as a tag in a tagfield.
     *
     * For tasks grid, this is also used to update the multivalued filters in the advanced filter window.
     */
    forceDistinctFields: function(filters) {
    	var filter = null,
    		found = null,
    		distinct = [];

    	// Foreach filter
    	for (filter of filters) {

    	    // Check if we already have same-property filter in 'distinct' array
    		found = distinct.find(item => item.get('property') === filter.get('property'));

    		// If yes - append data of new same-property filter to old same-property filter
    		if (found) {
                found.get('filtergroup').push(filter.getDataCustom());

            // Else if we don't have it there so far - push to 'distinct' array
            } else {
                filter.set('filtergroup', [filter.getDataCustom()]);
                distinct.push(filter);
            }
    	}

    	// Return filters for distinct fields
    	return distinct;
    },
    
    /**
     * Get the filter render value by given field and value.
     */
    getFieldSourceValue: function(field, value) {
        return value;
    }
});
