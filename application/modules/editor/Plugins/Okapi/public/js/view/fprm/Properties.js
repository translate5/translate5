/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following 700information
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
 * @extend Editor.plugins.Okapi.view.FprmEditor
 */
Ext.define('Editor.plugins.Okapi.view.fprm.Properties', {
    extend: 'Editor.plugins.Okapi.view.FprmEditor',
    fieldDefinitions: {},
    fieldDefaults: {
        "boolean": { xtype: 'checkbox', inputValue: true, uncheckedValue: false, defaultValue: false },
        "integer": { xtype: 'numberfield', defaultValue: 0 },
        "string": { xtype: 'textfield', defaultValue: "" },
    },
    /**
     * overridden
     */
    fprmDataLoaded: function(height){
        this.createForm();
        this.down('button#save').enable();
    },
    /**
     * Creates our forms & attaches them to our view
     */
    createForm: function(){
        var name, config, parent, data, id, type;
        for(name in this.fieldDefinitions){
            data = this.fieldDefinitions[name];
            id = this.getPropertyId(name);
            type = this.getPropertyType(name);
            config = Object.assign(this.getFieldConfig(id, type, name, data.config), data.config);
            config.parentSelector = (data.parent) ? ('fprmh_' + data.parent) : null;
            parent = (config.parentSelector) ? (Ext.getCmp(config.parentSelector) || this.formPanel) : this.formPanel;
            parent.add(config);
        }
    },
    /**
     *
     * @param {string} id
     * @param {string} type
     * @param {string} name
     * @param {object} config
     * @returns {object}
     */
    getFieldConfig: function(id, type, name, config){
        var control = this.fieldDefaults[type];
        return Object.assign({
            fieldLabel: this.getFieldCaption(id),
            value: this.getFieldValue(id, control.defaultValue, type),
            labelWidth: 'auto',
            labelClsExtra: 'x-selectable',
            valueType: type,
            name: name
        }, control);
    },
    /**
     *
     * @param {string} id
     * @returns {string}
     */
    getFieldCaption: function(id){
        if(this.translations.hasOwnProperty(id)){
            return this.translations[id];
        }
        return 'TRANSLATION MISSING';
    },
    /**
     *
     * @param {string} id
     * @param {string|integer|boolean} defaultValue
     * @param {string} type
     * @returns {string|integer|boolean}
     */
    getFieldValue: function(id, defaultValue, type){
        if(this.transformedData.hasOwnProperty(id)){
            return this.parseTypedValue(type, this.transformedData[id]);
        }
        return defaultValue;
    },
    /**
     * Retrieves the type defining character of an variable/property
     * @param propertyName
     * @returns {string}
     */
    getPropertyTypeSuffix: function(propertyName){
        var idx = propertyName.lastIndexOf('.');
        if(idx > -1){
            return propertyName.substring(idx + 1);
        }
        return '';
    },
    /**
     * Retrieves the type of an variable/property
     * @param propertyName
     * @returns {string}
     */
    getPropertyType: function(propertyName){
        switch(this.getPropertyTypeSuffix(propertyName)){
            case 'b':
                return 'boolean';
            case 'i':
                return 'integer';
            default:
                return 'string';
        }
    },
    /**
     * Creates a usable ID out of a property name
     * @param propertyName
     * @returns {string}
     */
    getPropertyId: function(propertyName){
        if(propertyName.length > 2 && (propertyName.substr(-2, 2) === '.b' || propertyName.substr(-2, 2) === '.i')){
            propertyName = propertyName.substr(0, (propertyName.length - 2));
        }
        return propertyName.split('.').join('_');
    },
    /**
     *
     * @param {string}type
     * @param {string|boolean|integer} value
     * @returns {{string|boolean|integer}
     */
    parseTypedValue: function(type, value){
        switch(type){
            case 'boolean':
                return (value === true) ? true : false;
            case 'integer':
                return Number.isInteger(value) ? value : parseInt(value);
            case 'string':
            default:
                return (typeof value === 'string') ? value : String(value);
        }
    },

    getFormValues: function(){
        var name, vals = this.form.getValues();
        for(name in vals){
            vals[name] = this.parseTypedValue(this.getPropertyType(name), vals[name]);
        }
        return vals;
    },
    /**
     * Creates the data sent back to the backend
     * @returns {string}
     */
    getRawResult: function(){
        var name,
            values = this.getFormValues(),
            lines = ['#v1'];
        for(name in values){
            lines.push(this.createRawResultLine(name, values[name]));
        }
        return lines.join("\n");
    },
    /**
     *
     * @param {string} name
     * @param {boolean|integer|string} value
     * @returns {string}
     */
    createRawResultLine: function(name, value){
        switch(this.getPropertyType(name)){
            case 'boolean':
                return name + '=' + ((value === true) ? 'true' : 'false');
            case 'integer':
            case 'string':
            default:
                return name + '=' + value;
        }
    }
});