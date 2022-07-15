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
Ext.define('Editor.plugins.Okapi.view.fprm.Openxml', {
    extend: 'Editor.plugins.Okapi.view.fprm.Properties',
    requires: ['Editor.plugins.Okapi.view.fprm.gui.Openxml'],

    formItems: [{
        xtype: 'tabpanel',
        defaults: {'bodyPadding': 15, layout: 'form', scrollable: true},
        items: [
            {title: 'General Options', id: 'fprm_general', iconCls: 'x-fa fa-cog'},
            {title: 'Word Options', id: 'fprm_word', iconCls: 'x-fa fa-file-word-o'},
            {title: 'Excel Options', id: 'fprm_excel', iconCls: 'x-fa fa-file-excel-o'},
            {title: 'Powerpoint Options', id: 'fprm_pp', iconCls: 'x-fa fa-file-powerpoint-o'},
        ]
    }],

    listNames: {
        'tsComplexFieldDefinitionsToExtract.i': 'cfd',
        'tsExcelExcludedColors.i': 'ccc',
        'tsExcelExcludedColumns.i': 'zzz',
        'tsExcludeWordStyles.i': 'sss',
        'tsWordExcludedColors.i': 'yyy',
        'tsWordHighlightColors.i': 'hlt',
        'tsPowerpointIncludedSlideNumbers.i': 'sln',
    },

    /** Reverse of this.listNames */
    listIdentifiers: {
        cfd: 'tsComplexFieldDefinitionsToExtract.i',
        ccc: 'tsExcelExcludedColors.i',
        zzz: 'tsExcelExcludedColumns.i',
        sss: 'tsExcludeWordStyles.i',
        yyy: 'tsWordExcludedColors.i',
        hlt: 'tsWordHighlightColors.i',
        sln: 'tsPowerpointIncludedSlideNumbers.i',
    },

    storeData: { // from okapi/filters/openxml/src/test/resources/standardcolors.xlsx
        colors: [
            ['0070C0', 'blue'],
            ['002060', 'dark blue'],
            ['C00000', 'dark red'],
            ['00B050', 'green'],
            ['00B0F0', 'light blue'],
            ['92D050', 'light green'],
            ['FFC000', 'orange'],
            ['7030A0', 'purple'],
            ['FF0000', 'red'],
            ['FFFF00', 'yellow']
        ],
        columns: [
            "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "X", "Y", "Z",
            "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AX", "AY", "AZ",
            "BA", "BB", "BC", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BK", "BL", "BM", "BN", "BO", "BP", "BQ", "BR", "BS", "BT", "BU", "BV", "BX", "BY", "BZ",
            "CA", "CB", "CC", "CD", "CE", "CF", "CG", "CH", "CI", "CJ", "CK", "CL", "CM", "CN", "CO", "CP", "CQ", "CR", "CS", "CT", "CU", "CV", "CX", "CY", "CZ",
            "DA", "DB", "DC", "DD", "DE", "DF", "DG", "DH", "DI", "DJ", "DK", "DL", "DM", "DN", "DO", "DP", "DQ", "DR", "DS", "DT", "DU", "DV", "DX", "DY", "DZ",
            "EA", "EB", "EC", "ED", "EE", "EF", "EG", "EH", "EI", "EJ", "EK", "EL", "EM", "EN", "EO", "EP", "EQ", "ER", "ES", "ET", "EU", "EV", "EX", "EY", "EZ"
        ].map(char => ['1' + char, '2' + char, '3' + char]).flat(),
        translateableHyperlinkFields: ["HYPERLINK", "FORMTEXT", "TOC"],
        wordStyles: ["Emphasis", "ExcludeCharacterStyle", "ExcludeParagraphStyle", "Heading1", "Heading2", "Normal", "Title", "Strong", "Subtitle", "tw4winExternal"],
        numbers: [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33,
              34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66,
              67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100
        ],

},

    initConfig: function(config){
        this.items[0].layout = 'fit'
        return this.callParent(arguments);
    },

    // QUIRK This is the only Filter with lists, so list support is implemented here
    getFieldConfig: function(name, configFromDescriptionFile){
        var cfg = this.callParent(arguments);
        if(name.startsWith('ts') && this.listNames[name]){
            Object.assign(cfg, {
                xtype: 'tagfield',
                queryMode: 'local',
                createNewOnEnter: true,
                createNewOnBlur: true,
                forceSelection: true, // TODO BCONF check if this can be lifted sometimes, e.g. for colors,
                triggers: {
                    clear: {
                        cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                        handler: field => field.setValue([]) || field.focus(),
                    }
                },
                store: this.storeData[configFromDescriptionFile.storeData] || []
            });
        }
        return cfg
    },

    parseFprm: function(fprm){
        const parsed = this.callParent(arguments)
            maxLength = {};
        for(var listName in this.listNames){
            maxLength[listName] = parsed[listName]
            parsed[listName] = [] // set empty lists
        }
        for(var [parsedName, value] of Object.entries(parsed)){
            var [match, listId] = parsedName.match(/^([a-z]{3})\d/) || []
            if(listId){
                delete parsed[match] // QUIRK: Okapi keeps stale values in fprm - we delete them.
                listName = this.listIdentifiers[listId]
                if(parsed[listName].length < maxLength[listName]){
                    if(listId === 'ccc'){ value = value.replace(/^FF/, ''); } // special case
                    parsed[listName].push(value)
                }
            }
        }
        return parsed;
    },

    getValues: function(){
        var valueObj = this.callParent(arguments),
            listId, name, value, index, entry;
        for([name, value] of Object.entries(valueObj)){
            if(this.listNames[name]){ // convert tagfield to length and individual list entries
                listId = this.listNames[name];
                valueObj[name] = value.length
                for([index, entry] of Object.entries(value)){
                    switch(listId){ // special cases
                        case 'sln': // TODO BCONF Proper type support for lists (sln is the only integer one)
                            index = '' + index + '.i';
                            break;
                        case 'ccc': // prepend FF for tsExcelExcludedColors.i
                            entry = 'FF' + entry;
                    }
                    valueObj[listId + index] = entry
                }
            }
        }
        return valueObj;
    }

})