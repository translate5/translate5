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
        "integer": { xtype: 'numberfield', allowDecimals: false, allowExponential: false, defaultValue: 0 },
        "float": { xtype: 'numberfield', allowDecimals: true, allowExponential: false, defaultValue: null }, // a virtual data-type, so we do not force it to be "02 as "in reality" it's a string which supports empty values
        "string": { xtype: 'textfield', defaultValue: "" },
    },
    /**
     * overridden
     */
    fprmDataLoaded: function(height){
        this.createForm();
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
            // we may have custom types defined in the config
            if(!data.config.valueType){
                data.config.valueType = type;
            } else {
                type = data.config.valueType;
            }
            config = Object.assign(this.getFieldConfig(id, type, name, data.config), data.config);
            this.getFieldTarget(data).add(config);
        }
    },
    /**
     * Retrieves the target component for a form-field
     * @param {object} config
     * @returns {Ext.Component}
     */
    getFieldTarget(data){
        return this.formPanel;
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
            fieldLabel: this.getFieldCaption(id, config),
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
     * @param {object} config
     * @returns {string}
     */
    getFieldCaption: function(id, config){
        // Tooltips can be configured in the fieldDefinitions and expect a translation with the same id + 'Tooltip'
        if(this.translations.hasOwnProperty(id)){
            if(config.hasTooltip && this.translations.hasOwnProperty(id + 'Tooltip')){
                return '<span data-qtip="' + this.translations[id + 'Tooltip'] + '">' + this.translations[id] + '</span>';
            }
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
     * @returns {string|boolean|integer}
     */
    parseTypedValue: function(type, value){
        switch(type){
            case 'boolean':
                return (value === true) ? true : false;
            case 'integer':
                return Number.isInteger(value) ? value : parseInt(value);
            case 'float':
                return (typeof value === 'number') ? value : parseFloat(String(value).split(',').join('.'));
            case 'string':
            default:
                return (typeof value === 'string') ? value : String(value);
        }
    },
    /**
     * @returns {object}
     */
    getFormValues: function(){
        var name, type, vals = this.form.getValues();
        for(name in vals){
            // we may have a custom type set
            type = (this.fieldDefinitions.hasOwnProperty(name)) ? this.fieldDefinitions[name].config.valueType : null;
            if(!type || type === 'boolean' && type === 'integer' && type === 'string'){
                type = this.getPropertyType(name);
            }
            vals[name] = this.parseTypedValue(type, vals[name]);
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
    },
    /**
     *
     * @returns {boolean|string}
     */
    validate: function(){
        this.resolvePropertyDependencies();
        if(this.form.isValid()){
            var data, name, val, id, type, errors = [];
            for(name in this.fieldDefinitions){
                data = this.fieldDefinitions[name];
                if(data.valueType && data.valueType !== 'boolean' && data.valueType !== 'integer' && data.valueType !== 'string'){
                    val = this.form.findField(name).getValue();
                    id = this.getPropertyId(name);
                    if(!this.validateValue(val, data.valueType)){
                        type = this.strings.hasOwnProperty(data.valueType) ? this.strings[data.valueType] : data.valueType;
                        errors.push(this.strings.invalidField.split('{0}').join(this.translations[id]).split('{0}').join(type));
                    }
                }
            }
            if(errors.length > 0){
                return errors.join('<br/>');
            }
            return true;
        }
        return false;
    },
    /**
     * Validates a single value
     * @param {string|number|boolean} value
     * @param {strong} type
     * @returns {boolean}
     */
    validateValue: function(value, type){
        switch(type){
            case 'boolean':
                return (value === 'true' || value === 'false' || value === true || value === false);
            case 'integer':
                return (String(value).match(/^\-?[0-9]+$/) !== null);
            case 'float':
                return (String(value).split(',').join('.').match(/^\-?0?\.[0-9]+$/) !== null);
            case 'string':
            default:
                return true;
        }
    },
    /**
     * Override this function to resolve property-dependencies a la "variableX can only be filled if variableY set to true"
     */
    resolvePropertyDependencies: function(){

    },
    /**
     * Cleanup
     */
    closeWindow: function(){
        this.fieldDefinitions = {};
        this.callParent(arguments);
    }
});