
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

Ext.define('Editor.model.admin.task.filter.ActiveFilter', {
    extend: 'Ext.data.Model',
    alias: 'model.taskFilterActiveFilter',
    fields:[{
    	name: 'operator'
	},{
    	name: 'operatorTranslated',
    	/***
         * Convert the operator to userfrendly text
         */
    	convert:function (val, record) {
			var operator=record.get('operator'),
				translated=Editor.data.app.filters.translatedOperators;
	        if(Ext.isDate(record.get('value'))){
        		return Ext.grid.filters.filter.Date.prototype.config.fields[operator].text;
	        }
	        //for the boolean filter the value is listed without operator
	        if(Ext.isBoolean(record.get('value'))){
	        	return '';
	        }
			return translated[operator] ? translated[operator] : operator;
	    },
	},{
		name: 'property'
	},{
		name: 'textLabel',
		/***
	     * Convert the text label to userfrendly text (the filter property is used as key)
	     */
    	convert:function(val, record){
			var view=Ext.ComponentQuery.query('#advancedFilterToolbar')[0],
				grid=view && view.getFilterGrid(),
				itemProperty=record.get('property');
			if(!grid){
				return itemProperty;
			}
			return grid.text_cols[itemProperty]? grid.text_cols[itemProperty] : itemProperty;
    	}
	},{
		name: 'dataSourceValue',
	    /***
	     * Convert the value based on the record type
	     */
		convert:function(val,record){
	    	if(Ext.isDate(val)){
	    		return Ext.Date.format(val,Editor.DATEONLY_ISO_FORMAT);
	    	}
	    	if(Ext.isBoolean(record.get('value'))){
	        	return record.get('value')==true ? Ext.grid.filters.filter.Boolean.prototype.yesText : Ext.grid.filters.filter.Boolean.prototype.noText;
	        }
	    	return val;
	    }
	},{
		name: 'value'
	}],
	
	/***
	 * Get the model data without the tooltip property
	 */
	getDataCustom:function(){
		var data=this.getData(arguments);
		if(data){
			delete data.tooltip;
		}
		return data;
	}
});