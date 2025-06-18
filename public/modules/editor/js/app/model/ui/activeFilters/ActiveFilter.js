
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

Ext.define('Editor.model.ui.activeFilters.ActiveFilter', {
    extend: 'Ext.data.Model',
    fields: [

        /**
         * Background name for the filter operator
         */
        {
    	    name: 'operator'
	    },

        /**
         * Foreground name for the filter operator
         */
        {
    	    name: 'operatorTranslated',
            convert: function (val, record) {
                var operator = record.get('operator'),
                    value = record.get('value');

                // If filter value is Date - return operator foreground name provided by ExtJS
                if (Ext.isDate(value)) {
                    return Ext.grid.filters.filter.Date.prototype.config.fields[operator].text;
                }

                // If filter value is Boolean - return empty string as no foreground name is needed
                if (Ext.isBoolean(value)) {
                    return '';
                }

                // Return predefined foreground name, if any, else return background name
                return Editor.data.app.filters.translatedOperators[operator] || operator;
            },
    	},

        /**
         * Background name for the filter property
         */
        {
            name: 'property'
        },

        /**
         * Foreground name for the filter property
         */
        {
            name: 'textLabel',
            convert: function (val, record) {
                var toolbarId = record.get('ownerToolbarId'),
                    property = record.get('property'),
                    view = Ext.getCmp(toolbarId),
                    grid = view && view.up('grid');

                // If no grid yet instantiated - return background name for the property
                if (!grid) {
                    return property;
                }

                // Else pick either from corresponding grid column text or from strings or just return background name
                return grid.down('gridcolumn[dataIndex=' + property +']')?.text
                    || grid.strings[property]
                    || property;
            }
        },

        /**
         * Convert the value based on the record type
         */
        {
            name: 'dataSourceValue',
            convert: function (val, rec) {

                // If filter value is a Date object - return formatted
                if (Ext.isDate(val)) {
                    return Ext.Date.format(val, Editor.DATEONLY_ISO_FORMAT);
                }

                // If filter value is Boolean - return native yes/no text defined by ExtJS
                if (Ext.isBoolean(rec.get('value'))) {
                    return Ext.grid.filters.filter.Boolean.prototype[
                        rec.get('value') ? 'yesText' : 'noText'
                    ];
                }

                // Get filter
                var filter = Ext.getCmp( rec.get('ownerToolbarId') )
                    .down('^ grid gridcolumn[dataIndex=' + rec.get('property') +']')
                    ?.filter;

                // If no filter, or filter type is not 'list' - return raw value
                if (filter?.type !== 'list') {
                    return val;
                }

                // Auxiliary variables
                var labels = [], option, item;

                // Foreach item within the value
                for (item of val) {

                    // Find option
                    option = filter.options.find(option => option[Ext.isArray(option) ? 0 : 'id'] === item);

                    // If found - get label
                    if (option) {
                        labels.push(
                            option[Ext.isArray(option) ? 1 : 'text']
                        );
                    }
                }

                // Return comma-separated labels
                return labels.join(', ');
            }
        },
        {
            name: 'value'
        }
    ],
	
	/**
	 * Get the model data without the tooltip property
	 */
	getDataCustom: function() {
		var data = this.getData();
		if (data) {
			delete data.tooltip;
		}
		return data;
	}
});