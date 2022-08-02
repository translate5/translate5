/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/*
    For easy reference: This is what the tagfield creating props and their hilarious "volatile" data looks like in the saved fprm/properties.
    Note we have 3 different color-formats !
    Also note that the data-name "zzz" applies to 3 controls e,g. "zzz1=2AB" means: second sent value, references selector "2", value "AB". it can hardly become crazier ...
    ...
    bPreferenceTranslateExcelExcludeColumns.b=true
    ...
    tsComplexFieldDefinitionsToExtract.i=2
    cfd0=FORMTEXT
    tsExcelExcludedColors.i=2
    tsExcelExcludedColumns.i=6
    tsExcludeWordStyles.i=2
    tsWordHighlightColors.i=2
    tsWordExcludedColors.i=1
    ...
    tsPowerpointIncludedSlideNumbers.i=3
    cfd1=HYPERLINK
    ccc0=FF002060
    ccc1=FF00B0F0
    sss0=ExcludeCharacterStyle
    sss1=ExcludeParagraphStyle
    hlt0=blue
    hlt1=green
    yyy0=002060
    sln0.i=1
    sln1.i=2
    sln2.i=3
    zzz0=1AC
    zzz1=1AD
    zzz2=2A
    zzz3=2F
    zzz4=3B
    zzz5=3C

    see /okapi-ui/swt/filters/openxml-ui/src/main/java/net/sf/okapi/filters/openxml/ui/Editor.java
    see /okapi/filters/openxml/src/main/java/net/sf/okapi/filters/openxml/ConditionalParameters.java
 */

/**
 * MS Office Filter: this is a more complicated frontend with tabs and especially because of the crazy way, list-data is saved in the fprm
 */
Ext.define('Editor.plugins.Okapi.view.fprm.Openxml', {
    extend: 'Editor.plugins.Okapi.view.fprm.Properties',
    width: 900,
    formPanelLayout: 'fit',
    formPanelPadding: 2,
    fieldDefinitions: {
        /* General Options */
        'tabGeneralOptions': { type: 'tab', icon: 'fa-cog', children: {
            'maxAttributeSize.i': { config: { hidden: true, valueDefault: 4194304 }},  // not visible in Rainbow, default value = 4kB as defined in rainbow-code
            'sPreferenceLineSeparatorReplacement': { config: { hidden: true, valueDefault: '\n' }}, // not visible in Rainbow, default value as defined in default-fprm
            'bPreferenceAllowEmptyTargets.b': { config: { hidden: true, valueDefault: false }}, // not visible in Rainbow, default value as defined in default-fprm
            'bPreferenceTranslateDocProperties.b': {},
            'bPreferenceTranslateComments.b': {},
            'bPreferenceAggressiveCleanup.b': {},
            'bPreferenceAddTabAsCharacter.b': {},
            'bPreferenceAddLineSeparatorAsCharacter.b': {}

        }},
        /* Word Options */
        'tabWordOptions': { type: 'tab', icon: 'fa-file-word-o', children: {
            'bPreferenceTranslateWordHeadersFooters.b': {},
            'bPreferenceTranslateWordHidden.b': {},
            'bPreferenceTranslateWordExcludeGraphicMetaData.b': {},
            'bPreferenceAutomaticallyAcceptRevisions.b': {},
            'bPreferenceIgnoreSoftHyphenTag.b': {},
            'bPreferenceReplaceNoBreakHyphenTag.b': {},
            'bExtractExternalHyperlinks.b': {},
            'tsComplexFieldDefinitionsToExtract.i': { type: 'tagfield', identifier: 'cfd', guiData: 'translateableHyperlinkFields' },
            'bInExcludeMode.b': {},
            'bInExcludeHighlightMode.b': {},
            'tsExcludeWordStyles.i': { type: 'tagfield', identifier: 'sss', guiData: 'wordStyles' },
            'tsWordHighlightColors.i': { type: 'tagfield', identifier: 'hlt', guiData: 'colorNames' },
            'tsWordExcludedColors.i': { type: 'tagfield', identifier: 'yyy', guiData: 'colors' },
            'bPreferenceTranslateWordExcludeColors.b': { config: { hidden: true }}
        }},
        /* Excel Options */
        'tabExcelOptions': { type: 'tab', icon: 'fa-file-excel-o', children: {
            'bPreferenceTranslateExcelHidden.b': {},
            'bPreferenceTranslateExcelSheetNames.b': {},
            'bPreferenceTranslateExcelDiagramData.b': {},
            'bPreferenceTranslateExcelDrawings.b': {},
            'tsExcelExcludedColors.i': { type: 'tagfield', identifier: 'ccc', guiData: 'colors', dataPrefix: 'FF' },
            'bPreferenceTranslateExcelExcludeColors.b': { config: { hidden: true }},
            'subfilter': { config: {}},
            'bPreferenceTranslateExcelExcludeColumns.b': { type: 'boolset', children: { // if not true, tsExcelExcludedColumns, tsExcelExcludedColumnsSheetN will not be processed
                'tsExcelExcludedColumnsSheet1.i': { type: 'tagfield', identifier: 'zzz', guiData: 'columns', dataPrefix: '1' }, // this is a "virtual" field that does not show up in the data
                'tsExcelExcludedColumnsSheet2.i': { type: 'tagfield', identifier: 'zzz', guiData: 'columns', dataPrefix: '2' }, // this is a "virtual" field that does not show up in the data
                'tsExcelExcludedColumnsSheet3.i': { type: 'tagfield', identifier: 'zzz', guiData: 'columns', dataPrefix: '3' } // this is a "virtual" field that does not show up in the data
            }},
            'tsExcelExcludedColumns.i': { config: { hidden: true }} // this stores the number of values for the 3 fields in bPreferenceTranslateExcelExcludeColumns.b

        }},
        /* PowerPoint Options */
        'tabPowerpointOptions': { type: 'tab', icon: 'fa-file-powerpoint-o', children: {
            'bPreferenceTranslatePowerpointHidden.b': { config: { hidden: true }},
            'bReorderPowerpointNotesAndComments.b': { config: { hidden: true }}, // not visible in Rainbow
            'bPreferenceTranslatePowerpointNotes.b': {},
            'bPreferenceTranslatePowerpointMasters.b': {},
            'bPreferenceIgnorePlaceholdersInPowerpointMasters.b': {},
            'bPreferencePowerpointIncludedSlideNumbersOnly.b': {},
            'tsPowerpointIncludedSlideNumbers.i': { type: 'tagfield', identifier: 'sln', guiData: 'numbers' }
        }}
    },
    initConfig: function(config){
        config.minHeight = 765;
        return this.callParent([config]);
    },

    /**
     * Overwritten to create tagfield-controls
     * @param {object} data
     * @param {string} id
     * @param {string} name
     * @param {object} config
     * @param {Ext.panel.Panel} holder
     * @param {boolean} disabled
     * @returns {Ext.panel.Panel}
     */
    addCustomFieldControl: function(data, id, name, config, holder, disabled){
        if(data.type === 'tagfield'){
            config = Object.assign(config, {
                xtype: 'tagfield',
                fieldLabel: this.getFieldCaption(id, config),
                labelClsExtra: 'x-selectable',
                labelWidth: 'auto',
                queryMode: 'local',
                createNewOnEnter: true,
                createNewOnBlur: true,
                forceSelection: true,
                disabled: disabled,
                valueType: config.valueType,
                name: name,
                fieldDataIdentifier: data.identifier,
                triggers: {
                    clear: {
                        cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                        handler: field => field.setValue([]) || field.focus(),
                    }
                },
                value: this.getTagfieldValue(id, data.identifier, data.dataPrefix),
                store: this.getTagfieldStore(id, data.guiData, data.dataPrefix)
            });
            this.fields[name] = config;
            return holder.add(config);
        } else {
            throw new Error('addCustomFieldControl: unknown field type "'+data.type+'"');
        }
    },

    /**
     *
     * @param {string} id
     * @param {string} identifier
     * @param {string} prefix
     * @returns {array}
     */
    getTagfieldValue: function(id, identifier, prefix){
        var i, val, numVals, vals = [];
        // very special coding for the 3 tsExcelExcludedColumnsSheet. they save their data combined as zzz0 ... zzzN
        if(id.includes('tsExcelExcludedColumnsSheet')){
            numVals = this.getFieldValue('tsExcelExcludedColumns', 0, 'integer', false);
            //  excel excluded columns only when bPreferenceTranslateExcelExcludeColumns.b is true !
            if(numVals > 0 && this.getFieldValue('bPreferenceTranslateExcelExcludeColumns', false, 'boolean', false) === true){
                for(i = 0; i < numVals; i++){
                    if(this.transformedData.hasOwnProperty(identifier + i)){
                        // data has a crazy structure like "zzz1=1AD" meaning: tsExcelExcludedColumnsSheet1 has value "AD"
                        val = this.transformedData[identifier + i];
                        if(val.slice(0, 1) === prefix){
                            vals.push(val); // the stores of the tsExcelExcludedColumnsSheetX will already contain the prefix as value
                        }
                    }
                }
            }
        } else {
            // the "normal" case: collect by identifier
            numVals = this.getFieldValue(id, 0, 'integer', false);
            if(numVals > 0){
                for(i = 0; i < numVals; i++){
                    if(this.transformedData.hasOwnProperty(identifier + i)){
                        vals.push(this.transformedData[identifier + i]);
                    }
                }
            }
        }
        vals.sort();
        return vals;
    },
    /**
     * Creates the stores for the tagfields. A prefix can be applied to generate the "crazy" formats for "tsExcelExcludedColumnsSheetN" (=> e.g. "2AB") or the color-formats ("002060" vs "FF002060")
     * @param {string} id
     * @param {string} name
     * @param {string} prefix
     * @returns {array}
     */
    getTagfieldStore: function(id, name, prefix){
        var vals = this.guiData[name] || [], result = [];
        if(prefix){
            for(var i=0; i < vals.length; i++){
                if(Array.isArray(vals[i])){
                    result[i] = [];
                    result[i][0] = '' + prefix + vals[i][0];
                    result[i][1] = vals[i][1];
                } else {
                    result[i] = ['' + prefix + vals[i], vals[i]];
                }
            }
        } else {
            result = vals;
        }
        return result;
    },
    /**
     * Overwritten to process tagfield-values
     * @param {string} name
     * @param {string} type
     * @param {string} customType
     * @param {string|boolean|integer} value
     * @param {object} formVals
     * @param {object} fieldConfig
     * @returns {string|boolean|integer}
     */
    parseCustomValue: function(name, type, customType, value, formVals, fieldConfig){
        if(customType === 'tagfield'){
            // init additional values cache
            if(!this.hasOwnProperty('additionalFormVals')){
                this.additionalFormVals = {};
                this.additionalFormVals.zzz = [];
            }
            var identifier = fieldConfig.fieldDataIdentifier;
            if(value && Array.isArray(value) && value.length > 0){
                // all 3 tsExcelExcludedColumnsSheetN fields will be sent with the same identifier and need further processing
                if(name.includes('tsExcelExcludedColumnsSheet')){
                    this.additionalFormVals.zzz = this.additionalFormVals.zzz.concat(value);
                } else {
                    // only the powerpoint-slidenumbers are integers and thus need a type-suffix
                    type = (name === 'tsPowerpointIncludedSlideNumbers') ? '.i' : '';
                    for(var i = 0; i < value.length; i++){
                        this.additionalFormVals[identifier + String(i) + type] = (type === '.i') ? parseInt(value[i]) : value[i];
                    }
                    return value.length;
                }
            }
            return 0;
        }
        return this.parseTypedValue(type, value);
    },
    /**
     * Overwritten
     * Deconstructs the multivalue-tagfield values to the ugly "numbered lists with strange names"
     */
    getFormValues: function(){
        var vals = this.callParent();
        // adding the tagfield-contents cached in additionalFormVals the crazy way (especially for "zzz")
        if(this.additionalFormVals){
            for(var name in this.additionalFormVals){
                if(name === 'zzz'){
                    if(this.additionalFormVals.zzz.length > 0 && vals['bPreferenceTranslateExcelExcludeColumns.b'] === true){
                        vals['tsExcelExcludedColumns.i'] = this.additionalFormVals.zzz.length; // holds the number of "zzz" values
                        for(var i = 0; i < this.additionalFormVals.zzz.length; i++){
                            vals['zzz' + i] = this.additionalFormVals.zzz[i];
                        }
                    } else {
                        vals['tsExcelExcludedColumns.i'] = 0;
                    }
                } else {
                    vals[name] = this.additionalFormVals[name];
                }
            }
            delete this.additionalFormVals;
        }
        // cleanup virtual fields
        delete vals['tsExcelExcludedColumnsSheet1.i'];
        delete vals['tsExcelExcludedColumnsSheet2.i'];
        delete vals['tsExcelExcludedColumnsSheet3.i'];
        return vals;
    },
    /**
     * Overridden to resolve our dependencies
     */
    resolveFieldDependencies: function(){
        // if the ckeckbox is not set we need to remove the Column-Choices
        if(this.form.findField('bPreferenceTranslateExcelExcludeColumns.b').getValue() === false){
            this.form.findField('tsExcelExcludedColumnsSheet1.i').setRawValue([]);
            this.form.findField('tsExcelExcludedColumnsSheet2.i').setRawValue([]);
            this.form.findField('tsExcelExcludedColumnsSheet3.i').setRawValue([]);
        }
    }
});