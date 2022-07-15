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

    listIdentifiers: {
        cfd: 'tsComplexFieldDefinitionsToExtract.i',
        ccc: 'tsExcelExcludedColors.i',
        zzz: 'tsExcelExcludedColumns.i',
        sss: 'tsExcludeWordStyles.i',
        yyy: 'tsWordExcludedColors.i',
        hlt: 'tsWordHighlightColors.i',
        sln: 'tsPowerpointIncludedSlideNumbers.i',
    },

    initConfig: function(config){
        this.items[0].layout = 'fit'
        return this.callParent(arguments);
    },

    getFieldConfig: function(name){
        var cfg = this.callParent(arguments);
        if(name.startsWith('ts') && this.listNames[name]){
            Object.assign(cfg, {
                xtype: 'tagfield',
                queryMode: 'local',
                forceSelection: false,
                createNewOnEnter: true,
                createNewOnBlur: true,
                store: []
            });
        }
        return cfg
    },


    parseFprm: function(fprm){
        const parsed = this.callParent(arguments);
        for(var listName in this.listNames){
            parsed[listName] = [] // set empty lists
        }
        for(const [parsedName, value] of Object.entries(parsed)){
            var [match, listId] = parsedName.match(/^([a-z]{3})\d/) || []
            if(listId){
                delete parsed[match]
                listName = this.listIdentifiers[listId]
                parsed[listName].push(value)
            }
        }
        return parsed;
    },

    getValues(){
        var valueObj = this.callParent(arguments),
            listId, name, value, index, entry;
        for([name, value] of Object.entries(valueObj)){
            if(this.listNames[name]){ // convert tagfield to length and individual list entries
                listId = this.listNames[name];
                valueObj[name] = value.length
                for([index, entry] of Object.entries(value)){
                    valueObj[listId + index] = entry
                }
            }
        }
        return valueObj;
    }

})