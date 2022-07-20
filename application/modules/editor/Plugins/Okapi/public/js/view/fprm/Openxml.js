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

maxAttributeSize.i=4194304
bPreferenceTranslateDocProperties.b=true
bPreferenceTranslateComments.b=true
bPreferenceTranslatePowerpointNotes.b=true
bPreferenceTranslatePowerpointMasters.b=true
bPreferenceIgnorePlaceholdersInPowerpointMasters.b=false
bPreferenceTranslateWordHeadersFooters.b=true
bPreferenceTranslateWordHidden.b=false
bPreferenceTranslateWordExcludeGraphicMetaData.b=false
bPreferenceTranslatePowerpointHidden.b=true
bPreferenceTranslateExcelHidden.b=true
bPreferenceTranslateExcelExcludeColors.b=true
bPreferenceTranslateExcelExcludeColumns.b=true
bPreferenceTranslateExcelSheetNames.b=false
bPreferenceAddLineSeparatorAsCharacter.b=false
sPreferenceLineSeparatorReplacement=$0a$
bPreferenceReplaceNoBreakHyphenTag.b=false
bPreferenceIgnoreSoftHyphenTag.b=false
bPreferenceAddTabAsCharacter.b=true
bPreferenceAggressiveCleanup.b=false
bPreferenceAutomaticallyAcceptRevisions.b=true
bPreferencePowerpointIncludedSlideNumbersOnly.b=true
bPreferenceTranslateExcelDiagramData.b=true
bPreferenceTranslateExcelDrawings.b=true
subfilter=
bInExcludeMode.b=true
bInExcludeHighlightMode.b=true
bPreferenceTranslateWordExcludeColors.b=true
bReorderPowerpointNotesAndComments.b=false
bPreferenceAllowEmptyTargets.b=false
tsComplexFieldDefinitionsToExtract.i=2
cfd0=FORMTEXT
tsExcelExcludedColors.i=2
tsExcelExcludedColumns.i=6
tsExcludeWordStyles.i=2
tsWordHighlightColors.i=2
tsWordExcludedColors.i=1
tsPowerpointIncludedSlideNumbers.i=3
bExtractExternalHyperlinks.b=false
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
 */
Ext.define('Editor.plugins.Okapi.view.fprm.Openxml', {
    extend: 'Editor.plugins.Okapi.view.fprm.Properties',
    width: 800,
    helpPage: 'http://okapiframework.org/wiki/index.php?title=OpenOffice_Filter',
    fieldDefinitions: {
        //general
        'maxAttributeSize.i': { parent: 'general', config: { hidden: true }},
        'bPreferenceTranslateDocProperties.b': { parent: 'general', config: null },
        'bPreferenceTranslateComments.b': { parent: 'general', config: null },
        'bPreferenceAggressiveCleanup.b': { parent: 'general', config: null },
        'bPreferenceAddTabAsCharacter.b': { parent: 'general', config: null },
        'bPreferenceAddLineSeparatorAsCharacter.b': { parent: 'general', config: null },
        //word
        'bPreferenceTranslateWordHeadersFooters.b': { parent: 'word', config: null },
        'bPreferenceTranslateWordHidden.b': { parent: 'word', config: null },
        'bPreferenceTranslateWordExcludeGraphicMetaData.b': { parent: 'word', config: null },
        'bPreferenceAutomaticallyAcceptRevisions.b': { parent: 'word', config: null },
        'bPreferenceIgnoreSoftHyphenTag.b': { parent: 'word', config: null },
        'bPreferenceReplaceNoBreakHyphenTag.b': { parent: 'word', config: null },
        'bExtractExternalHyperlinks.b': { parent: 'word', config: null },
        'tsComplexFieldDefinitionsToExtract.i': { parent: 'word', config: { guiData: 'translateableHyperlinkFields' }},
        'bInExcludeMode.b': { parent: 'word', config: null },
        'bInExcludeHighlightMode.b': { parent: 'word', config: null },
        'tsExcludeWordStyles.i': { parent: 'word', config: { guiData: 'wordStyles' }},
        'tsWordHighlightColors.i': { parent: 'word', config: { guiData: 'colorNames' }},
        'tsWordExcludedColors.i': { parent: 'word', config: { guiData: 'colors' }},
        'bPreferenceTranslateWordExcludeColors.b': { parent: 'word', config: { hidden: true }}, // depends on tsWordExcludedColors.selection.length
        //excel
        'bPreferenceTranslateExcelHidden.b': { parent: 'excel', config: null },
        'bPreferenceTranslatePowerpointHidden.b': { parent: 'powerpoint', config: { hidden: true }}, // depends on bPreferenceTranslateExcelHidden,
        'bPreferenceTranslateExcelExcludeColumns.b': { parent: 'excel', config: null },
        'bPreferenceTranslateExcelSheetNames.b': { parent: 'excel', config: null },
        'bPreferenceTranslateExcelDiagramData.b': { parent: 'excel', config: null },
        'bPreferenceTranslateExcelDrawings.b': { parent: 'excel', config: null },
        'tsExcelExcludedColors.i': { parent: 'excel', config: { guiData: 'colors', dataPrefix: 'FF' }},
        'bPreferenceTranslateExcelExcludeColors.b': { parent: 'excel', config: { hidden: true }}, // depends on tsExcelExcludedColors.selection.length },
        'subfilter': { parent: 'excel', config: null },
        'tsExcelExcludedColumns.i': { parent: 'excel', config: { hidden: true }}, // this provides the data for the following 3 fields
        'tsExcelExcludedColumnsSheet1.i': { parent: 'excel', config: { guiData: 'columns', dataPrefix: '1' }}, // this is a "virtual" field that does not show up in the data
        'tsExcelExcludedColumnsSheet2.i': { parent: 'excel', config: { guiData: 'columns', dataPrefix: '2' }}, // this is a "virtual" field that does not show up in the data
        'tsExcelExcludedColumnsSheet3.i': { parent: 'excel', config: { guiData: 'columns', dataPrefix: '3' }}, // this is a "virtual" field that does not show up in the data

        // powerpoint
        'bPreferenceTranslatePowerpointNotes.b': { parent: 'powerpoint', config: null },
        'bPreferenceTranslatePowerpointMasters.b': { parent: 'powerpoint', config: null },
        'bPreferenceIgnorePlaceholdersInPowerpointMasters.b': { parent: 'powerpoint', config: null },
        'bPreferencePowerpointIncludedSlideNumbersOnly.b': { parent: 'powerpoint', config: null },
        'tsPowerpointIncludedSlideNumbers.i': { parent: 'powerpoint', config: { guiData: 'numbers' }},
        'sPreferenceLineSeparatorReplacement': { parent: null, config: { hidden: true }}, // not visible in Rainbow
        'bReorderPowerpointNotesAndComments.b': { parent: null, config: { hidden: true }}, // not visible in Rainbow
        'bPreferenceAllowEmptyTargets.b': { parent: null, config: { hidden: true }} // not visible in Rainbow
    },

    formItems: [{
        xtype: 'tabpanel',
        defaults: { 'bodyPadding': 15, layout: 'form', scrollable: true },
        items: [
            { title: 'General Options', id: 'fprmh_general', iconCls: 'x-fa fa-cog' },
            { title: 'Word Options', id: 'fprmh_word', iconCls: 'x-fa fa-file-word-o' },
            { title: 'Excel Options', id: 'fprmh_excel', iconCls: 'x-fa fa-file-excel-o' },
            { title: 'Powerpoint Options', id: 'fprmh_powerpoint', iconCls: 'x-fa fa-file-powerpoint-o' },
        ]
    }],

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
        this.items[0].layout = 'fit';
        return this.callParent(arguments);
    },

    fprmDataLoaded: function(height){
        // since the tab-strip is generated on instantiation we have to dynamically apply translations
        this.down('#fprmh_general').setTitle(this.translations.captionGeneralOptions);
        this.down('#fprmh_word').setTitle(this.translations.captionWordOptions);
        this.down('#fprmh_excel').setTitle(this.translations.captionExcelOptions);
        this.down('#fprmh_powerpoint').setTitle(this.translations.captionPowerpointOptions);
        this.callParent(arguments);
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
     *
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
        for(name in this.fieldDefinitions){
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
    }
});