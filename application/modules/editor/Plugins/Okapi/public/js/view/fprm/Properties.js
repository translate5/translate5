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
    /**
     * DATA_TYPES:
     * - These are the data-types a property/field can have. boolean and integer are defined by the property-names (".i" for integer, ".b" for boolean)
     * - A x-properties file can only have string, boolean or integer
     * - "float" is a virtual data-type that will be sent/received as string
     * FIELD_TYPES:
     * - defaults to "field" representing a normal field generating a form-field
     * - "tab": represents a tab of the form panel. Can only be added on the highest level and if so, all toplevel-items must be tabs
     * - "fieldset": adds a fieldset to the form
     * - "boolset": special fieldset that is enabled/disabled by a checkbox. Therefore the definition must represent a "boolset" and a bool property at the same time like: "examleBoolset.b": { type: "boolset", config: {...}, children: {...}}
     * - "radio"
     */
    statics: {
        DATA_TYPES: [ 'boolean', 'integer', 'float', 'string' ],
        FIELD_TYPES: [ 'field', 'tab', 'fieldset', 'boolset', 'radio' ]
    },
    /**
     * Hashtable of our default controls
     * These will be instantiated for the given data-type
     * HINT: "valueDefault" can not be called "defaultValue" since the later has a meaning in ExtJS
     * @var {object}
     */
    fieldDefaults: {
        'boolean': { xtype: 'checkbox', inputValue: true, uncheckedValue: false, valueDefault: false },
        'integer': { xtype: 'numberfield', allowDecimals: false, allowExponential: false, valueDefault: 0 },
        'float': { xtype: 'numberfield', allowDecimals: true, allowExponential: false, valueDefault: null }, // a virtual data-type, so we do not force it to be "02 as "in reality" it's a string which supports empty values
        'string': { xtype: 'textfield', valueDefault: "" }
    },
    /**
     * Hashtable of our field definitions. These reflect the real property-names as used in the FPRM (and not the id's as used in translations etc)
     * Generally these are a variable, that pints to an object holding a config-object, which may further specify the control e.g. with "hidden" for invisible props, valueDefault, valueType or ignoreEmpty
     * See e.g. Editor.plugins.Okapi.view.fprm.Idml for how these shall look
     * @var {object}
     */
    fieldDefinitions: {},
    /**
     * All added controls that actually have data will be added to this store when creating the form (to have a flat list)
     */
    fields: {},
    /**
     * Override this function to resolve property-dependencies a la "variableX can only be filled if variableY set to true"
     * This will be called as the first step when validating the form
     */
    resolveFieldDependencies: function(){

    },
    /**
     * Creates our form by attaching the defined fieldDefinitions to the form
     * The definitions may have tabs defined on the highest level, it is expected that there is only one set of tabs in the highest level and that tabs only can be on the highest level
     */
    createForm: function(){
        var name, data, id, tab, iconClass, tabs = null;
        this.fields = {};
        for(name in this.fieldDefinitions){
            data = this.fieldDefinitions[name];
            id = this.getPropertyId(name);
            if(data.type && data.type === 'tab' && data.children){
                // create the tabs-holder if not already exists
                if(tabs === null){
                    tabs = this.createHolder('tabs', this.formPanel, null);
                }
                iconClass = data.icon ? 'x-fa ' + data.icon : null;
                tab = tabs.add({ xtype: 'panel', title: this.translations[id], iconCls: iconClass });
                this.addHolderChildren(data.children, tab, false);
                tabs.setActiveTab(0);
            } else {
                // adding a field directly to the form panel
                this.createField(id, name, data, this.formPanel, false);
            }
        }
        this.fieldDefinitions = {}; // cleanup of the hierarchical data. The "real" fields will be accessible via this.fields[propertyname]
    },
    /**
     * Overridden to disable useless API for our purposes: the form-values are applied when creating the fields ...
     */
    loadForm: function(){

    },
    /**
     * Adds a field-control, what may be a subform/fieldset or a "real" field
     * @param {string} id
     * @param {string} name
     * @param {object} data
     * @param {Ext.panel.Panel} holder
     * @param {boolean} disabled
     */
    createField: function(id, name, data, holder, disabled){
        var config = data.config || {};
        // we may have custom types defined in the config
        if(!config.hasOwnProperty('valueType')){
            config.valueType = this.getPropertyType(name);
        }
        // only multiline-textfields will be able to handle newlines
        if(!config.hasOwnProperty('canHandleNewlines')){
            config.canHandleNewlines = false;
        }
        if(!data.type || data.type === 'field'){
            this.addFieldControl(id, name, config, holder, disabled);
        } else if(data.type === 'fieldset' && data.children){
            holder = this.createHolder('fieldset', holder, (this.translations.hasOwnProperty(id) ? this.translations[id] : null));
            this.addHolderChildren(data.children, holder);
        } else if(data.type === 'boolset' && data.children && config.valueType === 'boolean'){
            var checkbox = this.addFieldControl(id, name, config, holder, disabled),
                dependentDisabled = (this.transformedData.hasOwnProperty(id) && this.transformedData[id] === false);
            checkbox.dependentControls = this.addHolderChildren(data.children, holder, dependentDisabled); // adds a prop to the checkbox with all dependent fields
            checkbox.on('change', this.onBoolsetChanged, this); // add the handler, that will enable/disable the dependent fields
        } else if(data.type === 'radio' && data.children && config.valueType === 'integer'){
            this.addRadio(id, name, config, data.children, holder);
        } else if(this.statics().FIELD_TYPES.indexOf(data.type) === -1){
            config.customFieldType = data.type; // add a marker so custom field types can be detected during validation
            this.addCustomFieldControl(data, id, name, config, holder, disabled);
        } else {
            throw new Error('createField: invalid field type "'+data.type+'", there are further configs missing');
        }
    },
    /**
     * Adds a "real" field control that handles one of the types as defined in statics.DATA_TYPES
     * @param {string} id
     * @param {string} name
     * @param {object} config
     * @param {Ext.panel.Panel} holder
     * @param {boolean} disabled
     * @returns {Ext.panel.Panel}
     */
    addFieldControl: function(id, name, config, holder, disabled){
        config = Object.assign(this.getFieldConfig(id, config.valueType, name, config), config);
        config.disabled = disabled; // some fields are disabled if booleans they depend on are not set
        this.fields[name] = config;
        return holder.add(config);
    },
    /**
     * Adds a custom field control that handles one of the types not defined in statics.DATA_TYPES
     * @param {object} data
     * @param {string} id
     * @param {string} name
     * @param {object} config
     * @param {Ext.panel.Panel} holder
     * @param {boolean} disabled
     * @returns {Ext.panel.Panel}
     */
    addCustomFieldControl: function(data, id, name, config, holder, disabled){
        throw new Error('addCustomFieldControl: unknown field type "'+data.type+'"');
    },
    /**
     * Adds a radio-button group, which adds it's selected index as integer to the main property
     * @param {string} id
     * @param {string} name
     * @param {object} config
     * @param {object} children
     * @param {Ext.panel.Panel} holder
     */
    addRadio: function(id, name, config, children, holder){
        var count = 0,
            value = this.getFieldValue(id, 0, config.valueType, config.canHandleNewlines);
        var radio = {
            xtype: 'radiogroup',
            fieldLabel: this.getFieldCaption(id, config),
            columns: 1,
            name: name,
            value: String(value),
            valueType: config.valueType,
            items: []
        };
        for(name in children){
            radio.items.push({
                boxLabel: this.getFieldCaption(this.getPropertyId(name), {}),
                inputValue: String(count),
                checked: (count === value)
            });
            count++;
        }
        return holder.add(radio);
    },
    /**
     *
     * @param {string} type
     * @param {Ext.panel.Panel}panel
     * @param {string} title
     * @returns {*}
     */
    createHolder: function(type, panel, title){
        switch(type){
            case 'tabs':
                return panel.add({
                    xtype: 'tabpanel',
                    defaults: { 'bodyPadding': 15, layout: 'form', scrollable: true }
                });
            case 'fieldset':
                return panel.add({
                    xtype: 'fieldset',
                    width: '100%',
                    title: title,
                    labelWidth: 0.45,
                    anchor: '100%',
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    bodyPadding: 10,
                    collapsible: false
                });
        }
        throw new Error('createHolder: unknown holder type "'+type+'"');
    },
    /**
     *
     * @param {object} definitions
     * @param {Ext.panel.Panel} holder
     * @param {boolean} disabled
     * @returns {string[]}
     */
    addHolderChildren: function(definitions, holder, disabled){
        var id, data, name, names = [];
        for(name in definitions){
            id = this.getPropertyId(name);
            data = definitions[name];
            this.createField(id, name, data, holder, disabled);
            names.push(name);
        }
        return names;
    },
    /**
     * Handler for the "boolset" types, which are a checkbox that enables/disables the dependant controls
     * @param checkbox
     * @param isChecked
     */
    onBoolsetChanged: function(checkbox, isChecked){
        checkbox.dependentControls.forEach(fieldName => {
            this.form.findField(fieldName).setDisabled(!isChecked);
        });
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
        var control = this.fieldDefaults[type],
            defaultValue = (config.hasOwnProperty('valueDefault')) ? config.valueDefault : control.valueDefault;
        return Object.assign({
            fieldLabel: this.getFieldCaption(id, config),
            value: this.getFieldValue(id, defaultValue, type, config.canHandleNewlines),
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
     * @param {boolean} canHandleNewlines: special config for string's: if the target field is not multiline-capable, the newlines must be escaped before setting the field-value
     * @returns {string|integer|boolean}
     */
    getFieldValue: function(id, defaultValue, type, canHandleNewlines){
        if(this.transformedData.hasOwnProperty(id)){
            if(type === 'string' && canHandleNewlines){
                return this.unescapeStringValue(this.parseTypedValue(type, this.transformedData[id]));
            } else if(type === 'string' && !canHandleNewlines){
                return this.escapeStringValue(this.parseTypedValue(type, this.transformedData[id]));
            } else {
                return this.parseTypedValue(type, this.transformedData[id]);
            }
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
     * @param {string} type
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
     * This API can be overwritten if additional logic is needed for valus derived from custom Fields
     * Also additional values can be added to the form if required
     * @param {string} name
     * @param {string} type
     * @param {string} customType
     * @param {string|boolean|integer} value
     * @param {object} formVals
     * @param {object} fieldConfig
     * @returns {string|boolean|integer}
     */
    parseCustomValue: function(name, type, customType, value, formVals, fieldConfig){
        return this.parseTypedValue(type, value); // routes back to the default behavious so extending classes are not forced to implement this
    },
    /**
     * @returns {object}
     */
    getFormValues: function(){
        var name, type, conf, vals = this.form.getValues();
        for(name in vals){
            // we may have a custom type set
            conf = (this.fields.hasOwnProperty(name)) ? this.fields[name] : null; // we may have a programmatical field
            type = (conf && conf.valueType) ? conf.valueType : this.getPropertyType(name);
            // we have to remove optional fields that shall be removed if empty
            if(conf && conf.ignoreEmpty && (vals[name] === '' || vals[name] === null)){
                delete vals[name];
            } else if(conf && conf.customFieldType) {
                vals[name] = this.parseCustomValue(name, type, conf.customFieldType, vals[name], vals, conf);
            } else {
                vals[name] = this.parseTypedValue(type, vals[name]);
            }
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
            if(this.getPropertyType(name) === 'string'){
                // Crucial: \r and \n van not be transfered, so we must escape them as defined in OKAPIs x-properties format
                // Although the Okapi-Spec enables \r, it is encoded as $0d$ we remove it to get consistent settings between windows & linux
                lines.push(this.createRawResultLine(name, this.escapeStringValue(values[name])));
            } else {
                lines.push(this.createRawResultLine(name, values[name]));
            }
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
        this.resolveFieldDependencies();
        if(this.form.isValid()){
            var data, name, val, id, typeName, errors = [];
            for(name in this.fields){
                data = this.fields[name];
                if(data.valueType && data.valueType !== 'boolean' && data.valueType !== 'integer' && data.valueType !== 'string'){
                    val = this.form.findField(name).getValue();
                    // custom data-types need a special validation as long as they shall not be ignored
                    if(!(data.ignoreEmpty && (val === '' || val === null))){
                        id = this.getPropertyId(name);
                        if(!this.validateValue(val, data.valueType)){
                            typeName = this.strings.hasOwnProperty(data.valueType) ? this.strings[data.valueType] : data.valueType;
                            errors.push(this.strings.invalidField.split('{0}').join(this.translations[id]).split('{1}').join(typeName));
                        }
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
     * @param {string} type
     * @returns {boolean}
     */
    validateValue: function(value, type){
        switch(type){
            case 'boolean':
                return (value === 'true' || value === 'false' || value === true || value === false);
            case 'integer':
                return (String(value).match(/^\-?[0-9]+$/) !== null);
            case 'float':
                return (String(value).split(',').join('.').match(/^\-?[0-9]*\.?[0-9]+$/) !== null);
            case 'string':
            default:
                return true;
        }
    },
    /**
     * Escapes a string value, x-properties use a special whitespace encoding for strings. See net.sf.okapi.common.ParametersString
     * QUIRK: to enable a consistent editing between LINUX and WINDOWS, we remove \r\n
     * @param {string} value
     * @returns {string}
     */
    escapeStringValue: function(value){
        return value.split('\r\n').join('\n').split('\r').join('$0d$').split('\n').join('$0a$');
    },
    /**
     * Unescapes a string value, x-properties use a special whitespace encoding for strings. See net.sf.okapi.common.ParametersString
     * @param {string} value
     * @returns {string}
     */
    unescapeStringValue: function(value){
        return value.split('$0a$').join('\n').split('$0d$').join('\r').split('\r\n').join('\n');
    },
    /**
     * Removes our fields-cache ...
     */
    doDestroy: function() {
        this.fields = {};
        this.callParent();
    }
});