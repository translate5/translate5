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
        defaults: {'bodyPadding': 15, layout: 'form'},
        items: [
            {title: 'General Options', id: 'fprm_general'},
            {title: 'Word Options', id: 'fprm_word'},
            {title: 'Excel Options', id: 'fprm_excel'},
            {title: 'Powerpoint Options', id: 'fprm_pp'},
        ]
    }],

    listNames: {
        tsComplexFieldDefinitionsToExtract: 'cfd',
        tsExcelExcludedColors: 'ccc',
        tsExcelExcludedColumns: 'zzz',
        tsExcludeWordStyles: 'sss',
        tsWordExcludedColors: 'yyy',
        tsWordHighlightColors: 'hlt',
        tsPowerpointIncludedSlideNumbers: 'sln',
    },

    listIdentifiers: {
        cfd: 'tsComplexFieldDefinitionsToExtract',
        ccc: 'tsExcelExcludedColors',
        zzz: 'tsExcelExcludedColumns',
        sss: 'tsExcludeWordStyles',
        yyy: 'tsWordExcludedColors',
        hlt: 'tsWordHighlightColors',
        sln: 'tsPowerpointIncludedSlideNumbers',
    },

    initConfig: function(config){
        this.items[0].layout = 'fit'
        return this.callParent(arguments);
    },


    parseFprm: function(fprm){
        const parsed = this.callParent(arguments);

        for(var listName in this.listNames){
            parsed[listName] = [] // set empty lists
            delete parsed[listName+'.i'] // length of list
        }
        for(const [parsedName, value] of Object.entries(parsed)){
            var [match, listId] = parsedName.match(/([a-z]{3})\d$/) || []
            if(listId){
                delete parsed[match]
                listName = this.listIdentifiers[listId]
                parsed[listName].push(value)
            }
        }
        return parsed;
    },

    getFieldConfig: function(name){
        var cfg = this.callParent(arguments);
        if(cfg.id.startsWith('ts') && this.listNames[name]){
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

})