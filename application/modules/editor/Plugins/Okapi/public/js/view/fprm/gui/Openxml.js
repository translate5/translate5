Ext.define('Editor.plugins.Okapi.view.fprm.gui.Openxml', {
    singleton: true,
    fields: {
        //[ "text", "parentSelector", additionalConfig]

        //general
        maxAttributeSize: ['Maximal attribute size of xml attributes', 'general', {hidden: true}],

        bPreferenceTranslateDocProperties: ['Translate Document Properties', 'general',],
        bPreferenceTranslateComments: ['Translate Comments', 'general',],
        bPreferenceAggressiveCleanup: ['Clean Tags Aggressively', 'general',],
        bPreferenceAddTabAsCharacter: ['Treat Tab as Character', 'general',],
        bPreferenceAddLineSeparatorAsCharacter: ['Treat Line Break as Character', 'general',],

        //word
        bPreferenceTranslateWordHeadersFooters: ['Translate Headers and Footers', 'word',],
        bPreferenceTranslateWordHidden: ['Translate Hidden Text', 'word',],
        bPreferenceTranslateWordExcludeGraphicMetaData: ['Exclude Graphical Metadata', 'word',],
        bPreferenceAutomaticallyAcceptRevisions: ['Automatically Accept Revisions', 'word',],
        bPreferenceIgnoreSoftHyphenTag: ['Ignore Soft Hyphens', 'word',],
        bPreferenceReplaceNoBreakHyphenTag: ['Replace Non-Breaking Hyphen with Regular Hyphen', 'word',],
        bExtractExternalHyperlinks: ['Translate Hyperlink URLs', 'word',],
        tsComplexFieldDefinitionsToExtract: ['Translatable Fields', 'word',],
        bInExcludeMode: ['Exclude or Include Styles', 'word',],
        bInExcludeHighlightMode: ['Exclude or Include Highlights', 'word',],

        tsExcludeWordStyles: ['Styles to Exclude/Include', 'word',],
        tsWordHighlightColors: ['Highlight Colours to Exclude/Include', 'word',],
        tsWordExcludedColors: ['Text Colours to Exclude', 'word',],
        bPreferenceTranslateWordExcludeColors: ['', 'word', {hidden: true}], // depends on tsWordExcludedColors.selection.length

        //excel
        bPreferenceTranslateExcelHidden: ['Translate Hidden Rows and Columns', 'excel',],
        bPreferenceTranslatePowerpointHidden: ['', 'pp', {hidden: true}], // depends on bPreferenceTranslateExcelHidden,

        bPreferenceTranslateExcelExcludeColumns: ['Exclude Marked Columns in Each Sheet', 'excel',],
        bPreferenceTranslateExcelSheetNames: ['Translate Sheet Names', 'excel',],
        bPreferenceTranslateExcelDiagramData: ['Translate Diagram Data (e.g. Smart Art)', 'excel',],
        bPreferenceTranslateExcelDrawings: ['Translate Drawings (e.g. Text fields)', 'excel',],

        tsExcelExcludedColors: ['Colours to Exclude', 'excel',],
        bPreferenceTranslateExcelExcludeColors: ['text', 'excel', {hidden: true}], // depends on tsExcelExcludedColors.selection.length],
        subfilter: ['Name of subfilter for cell content', 'excel',],
        tsExcelExcludedColumns: ['Columns to Exclude', 'excel',],

        // powerpoint
        bPreferenceTranslatePowerpointNotes: ['Translate Notes', 'pp',],
        bPreferenceTranslatePowerpointMasters: ['Translate Masters', 'pp',],
        bPreferenceIgnorePlaceholdersInPowerpointMasters: ['Ignore Placeholder Text in Masters', 'pp',],
        bPreferencePowerpointIncludedSlideNumbersOnly: ['Translate included slide numbers only', 'pp',],

        tsPowerpointIncludedSlideNumbers: ['Included Slide Numbers', 'pp',],

        sPreferenceLineSeparatorReplacement: ['', '', {hidden: true}], // not visible in Rainbow
        bReorderPowerpointNotesAndComments: ['', '', {hidden: true}], // not visible in Rainbow
        bPreferenceAllowEmptyTargets: ['', '', {hidden: true}], // not visible in Rainbow
    }
})
