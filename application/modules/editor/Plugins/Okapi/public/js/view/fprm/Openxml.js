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
            'tsComplexFieldDefinitionsToExtract.i': { config: { guiData: 'translateableHyperlinkFields' }},
            'bInExcludeMode.b': {},
            'bInExcludeHighlightMode.b': {},
            'tsExcludeWordStyles.i': { config: { guiData: 'wordStyles' }},
            'tsWordHighlightColors.i': { config: { guiData: 'colorNames' }},
            'tsWordExcludedColors.i': { config: { guiData: 'colors' }},
            'bPreferenceTranslateWordExcludeColors.b': { config: { hidden: true }}
        }},
        /* Excel Options */
        'tabExcelOptions': { type: 'tab', icon: 'fa-file-excel-o', children: {
            'bPreferenceTranslateExcelHidden.b': {},
            'bPreferenceTranslateExcelSheetNames.b': {},
            'bPreferenceTranslateExcelDiagramData.b': {},
            'bPreferenceTranslateExcelDrawings.b': {},
            'tsExcelExcludedColors.i': { config: { guiData: 'colors', dataPrefix: 'FF' }},
            'bPreferenceTranslateExcelExcludeColors.b': { config: { hidden: true }},
            'subfilter': { config: {}},
            'bPreferenceTranslateExcelExcludeColumns.b': { type: 'boolset', children: { // if not true, tsExcelExcludedColumns, tsExcelExcludedColumnsSheetN will not be processed
                'tsExcelExcludedColumnsSheet1.i': { config: { guiData: 'columns', dataPrefix: '1' }}, // this is a "virtual" field that does not show up in the data
                'tsExcelExcludedColumnsSheet2.i': { config: { guiData: 'columns', dataPrefix: '2' }}, // this is a "virtual" field that does not show up in the data
                'tsExcelExcludedColumnsSheet3.i': { config: { guiData: 'columns', dataPrefix: '3' }} // this is a "virtual" field that does not show up in the data
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
            'tsPowerpointIncludedSlideNumbers.i': { config: { guiData: 'numbers' }}
        }}
    },
    /**
     * Which variables become tagfields
     */
    listNames: {
        'tsComplexFieldDefinitionsToExtract.i': 'cfd',
        'tsExcelExcludedColors.i': 'ccc',
        'tsExcelExcludedColumnsSheet1.i': 'zzz',
        'tsExcelExcludedColumnsSheet2.i': 'zzz',
        'tsExcelExcludedColumnsSheet3.i': 'zzz',
        'tsExcludeWordStyles.i': 'sss',
        'tsWordExcludedColors.i': 'yyy',
        'tsWordHighlightColors.i': 'hlt',
        'tsPowerpointIncludedSlideNumbers.i': 'sln'
    },
    /**
     * where the number of items of the specified identifier is saved in
     */
    listTargets: {
        cfd: 'tsComplexFieldDefinitionsToExtract.i',
        ccc: 'tsExcelExcludedColors.i',
        zzz: 'tsExcelExcludedColumns.i',
        sss: 'tsExcludeWordStyles.i',
        yyy: 'tsWordExcludedColors.i',
        hlt: 'tsWordHighlightColors.i',
        sln: 'tsPowerpointIncludedSlideNumbers.i'
    },
    initConfig: function(config){
        config.minHeight = 765;
        return this.callParent([config]);
    },

    // QUIRK This is the only GUI with lists, so list support is implemented here
    getFieldConfig: function(id, type, name, config){
        var identifier, control = this.callParent(arguments);
        if(this.listNames.hasOwnProperty(name)){
            identifier = this.listNames[name];
            Object.assign(control, {
                xtype: 'tagfield',
                queryMode: 'local',
                createNewOnEnter: true,
                createNewOnBlur: true,
                forceSelection: true,
                triggers: {
                    clear: {
                        cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                        handler: field => field.setValue([]) || field.focus(),
                    }
                },
                value: this.getTagfieldValue(id, identifier, config.dataPrefix),
                store: this.getTagfieldStore(id, config.guiData, config.dataPrefix)
            });
        }
        return control;
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
            numVals = this.getFieldValue('tsExcelExcludedColumns', 0, 'integer');
            //  excel excluded columns only when bPreferenceTranslateExcelExcludeColumns.b is true !
            if(numVals > 0 && this.getFieldValue('bPreferenceTranslateExcelExcludeColumns', false, 'boolean') === true){
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
            numVals = this.getFieldValue(id, 0, 'integer');
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
     * Deconstructs the multivalue-tagfield values to the ugly "numbered lists with strange names"
     * type-checks all other vals
     * @returns {*}
     */
    getFormValues: function(){
        var i, name, vals, identifier, target, type,
            formvals = this.form.getValues(),
            result = {},
            zzzVals = [];
        for(name in this.fields){
            if(this.listNames.hasOwnProperty(name)){
                vals = formvals[name];
                identifier = this.listNames[name];
                target = this.listTargets[identifier];
                result[target] = 0;
                if(vals && Array.isArray(vals) && vals.length > 0){
                    // only the powerpoint-slidenums are integers and thus need a type-suffix
                    if(name.includes('tsExcelExcludedColumnsSheet')){
                        // all 3 tsExcelExcludedColumnsSheetN fields will be sent with the same identifier
                        zzzVals = zzzVals.concat(vals);
                    } else {
                        type = (name === 'tsPowerpointIncludedSlideNumbers') ? '.i' : '';
                        for(i = 0; i < vals.length; i++){
                            result[identifier + String(i) + type] = (type === '.i') ? parseInt(vals[i]) : vals[i];
                        }
                        result[target] = vals.length;
                    }
                }
            } else {
                result[name] = this.parseTypedValue(this.getPropertyType(name), formvals[name]);
            }
        }
        // last step: add entries of combined excluded excel-columns - only if the general option is set
        if(zzzVals.length > 0 && result['bPreferenceTranslateExcelExcludeColumns.b'] === true){
            target = this.listTargets.zzz;
            result[target] = zzzVals.length;
            for(i = 0; i < zzzVals.length; i++){
                result['zzz' + i] = zzzVals[i];
            }
        }
        return result;
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