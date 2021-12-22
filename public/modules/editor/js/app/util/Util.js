
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

/**
 * Useful extjs functions
 * @class Editor.util.Util
 */
Ext.define('Editor.util.Util', {
    
    statics:{
        
        /***
        *
        * @param {Date} date The date to modify
        * @param {Number} days The amount to add to the current date. If decimal provided, it will be converted to hours
        * @return {Date} The new Date instance.
        */
        addBusinessDays:function(date,days){
            // if it is float number, calculate the hours from the floating point number.
            var hours = days - parseInt(days);
            if(hours > 0){
                hours = 24 * hours;
                date = Ext.Date.add(date, Ext.Date.HOUR, hours);
            }
            for(var i=1;i<=days;){
                date = Ext.Date.add(date, Ext.Date.DAY, 1);
                if(!Ext.Date.isWeekend(date)){
                    i++;
                }
            }
            return date;
        },
        /**
         * Creates an CSS-Selector from props
         * @param nodeName {String} the relevant node-name of the serched elements
         * @param classNames {Array,String} like [class1, ..., classN] OR String the relevant class/classes of the searched elements
         * @param dataProps {Array} like [{ name:'name1', value:'val1' }, ..., { name:'nameN', value:'valN' }] the relevant data-properties of the searched elements
         * @return String
         */
        createSelectorFromProps(nodeName, classNames, dataProps){
            var selector = (nodeName) ? nodeName : '';
                if(classNames){
                selector += (Array.isArray(classNames)) ? ('.' + classNames.join('.')) : ('.' + classNames.split(' ').join('.'));
            }
            if(dataProps && Array.isArray(dataProps)){
                dataProps.forEach(function(prop){
                    if(prop.name && prop.value){
                        selector += ("[data-" + prop.name + "='" + prop.value + "']");
                    }
                });
            }
            return selector;
        },

        /**
         * renders the value of the language columns
         * @param {String} val
         * @returns {String}
         */
        gridColumnLanguageRenderer: function(val, md) {
            var lang = Ext.StoreMgr.get('admin.Languages').getById(val),
                label;
            if(lang){
                label = lang.get('label');
                md.tdAttr = 'data-qtip="' + label + '"';
                return label;
            }
            return '';
        },

        /***
         * Return the translated workflowStep name
         */
        getWorkflowStepNameTranslated:function(stepName){
            if(!stepName){
                return "";
            }
            var store=Ext.StoreManager.get('admin.WorkflowSteps'),
                rec=store.getById(stepName);
            if(rec){
                stepName=rec.get('text');
            }
            return stepName;
        },

        /***
         * Get the file xtension from the file name
         * @returns {any|string}
         */
        getFileExtension:function (name){
            return name ? name.split('.').pop() : '';
        }
    }
    
});