Ext.define('Editor.plugins.Okapi.view.fprm.gui.Openxml', {
    singleton: true,
    fields: {
        //[ "text", "parentSelector", additionalConfig]

        //general
        'maxAttributeSize.i': ['Maximal attribute size of xml attributes', 'general', {hidden: true}],

        'bPreferenceTranslateDocProperties.b': ['Translate Document Properties', 'general',],
        'bPreferenceTranslateComments.b': ['Translate Comments', 'general',],
        'bPreferenceAggressiveCleanup.b': ['Clean Tags Aggressively', 'general',],
        'bPreferenceAddTabAsCharacter.b': ['Treat Tab as Character', 'general',],
        'bPreferenceAddLineSeparatorAsCharacter.b': ['Treat Line Break as Character', 'general',],

        //word
        'bPreferenceTranslateWordHeadersFooters.b': ['Translate Headers and Footers', 'word',],
        'bPreferenceTranslateWordHidden.b': ['Translate Hidden Text', 'word',],
        'bPreferenceTranslateWordExcludeGraphicMetaData.b': ['Exclude Graphical Metadata', 'word',],
        'bPreferenceAutomaticallyAcceptRevisions.b': ['Automatically Accept Revisions', 'word',],
        'bPreferenceIgnoreSoftHyphenTag.b': ['Ignore Soft Hyphens', 'word',],
        'bPreferenceReplaceNoBreakHyphenTag.b': ['Replace Non-Breaking with Regular Hyphens', 'word',],
        'bExtractExternalHyperlinks.b': ['Translate Hyperlink URLs', 'word',],
        'tsComplexFieldDefinitionsToExtract.i': ['Translatable Fields', 'word',],
        'bInExcludeMode.b': ['Exclude or Include Styles', 'word',],
        'bInExcludeHighlightMode.b': ['Exclude or Include Highlights', 'word',],

        'tsExcludeWordStyles.i': ['Styles to Exclude/Include', 'word',],
        'tsWordHighlightColors.i': ['Highlight Colours to Exclude/Include', 'word',],
        'tsWordExcludedColors.i': ['Text Colours to Exclude', 'word',],
        'bPreferenceTranslateWordExcludeColors.b': ['', 'word', {hidden: true}], // depends on tsWordExcludedColors.selection.length

        //excel
        'bPreferenceTranslateExcelHidden.b': ['Translate Hidden Rows and Columns', 'excel',],
        'bPreferenceTranslatePowerpointHidden.b': ['', 'pp', {hidden: true}], // depends on bPreferenceTranslateExcelHidden,

        'bPreferenceTranslateExcelExcludeColumns.b': ['Exclude Marked Columns in Each Sheet', 'excel',],
        'bPreferenceTranslateExcelSheetNames.b': ['Translate Sheet Names', 'excel',],
        'bPreferenceTranslateExcelDiagramData.b': ['Translate Diagram Data (e.g. Smart Art)', 'excel',],
        'bPreferenceTranslateExcelDrawings.b': ['Translate Drawings (e.g. Text fields)', 'excel',],

        'tsExcelExcludedColors.i': ['Colours to Exclude', 'excel',],
        'bPreferenceTranslateExcelExcludeColors.b': ['text', 'excel', {hidden: true}], // depends on tsExcelExcludedColors.selection.length],
        'subfilter': ['Name of subfilter for cell content', 'excel',],
        'tsExcelExcludedColumns.i': ['Columns to Exclude', 'excel',],

        // powerpoint
        'bPreferenceTranslatePowerpointNotes.b': ['Translate Notes', 'pp',],
        'bPreferenceTranslatePowerpointMasters.b': ['Translate Masters', 'pp',],
        'bPreferenceIgnorePlaceholdersInPowerpointMasters.b': ['Ignore Placeholder Text in Masters', 'pp',],
        'bPreferencePowerpointIncludedSlideNumbersOnly.b': ['Translate included slide numbers only', 'pp',],

        'tsPowerpointIncludedSlideNumbers.i': ['Included Slide Numbers', 'pp',],

        'sPreferenceLineSeparatorReplacement': ['', '', {hidden: true}], // not visible in Rainbow
        'bReorderPowerpointNotesAndComments.b': ['', '', {hidden: true}], // not visible in Rainbow
        'bPreferenceAllowEmptyTargets': ['', '', {hidden: true}], // not visible in Rainbow
    }
})
