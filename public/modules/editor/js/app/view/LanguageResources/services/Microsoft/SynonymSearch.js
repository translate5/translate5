
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 */
Ext.define('Editor.view.LanguageResources.services.Microsoft.SynonymSearch', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.synonymSearch',
    controller: 'synonymSearch',
    viewModel: {
        type: 'synonymSearch'
    },
    requires:[
        'Editor.view.LanguageResources.services.Microsoft.SynonymSearchViewController',
        'Editor.view.LanguageResources.services.Microsoft.SynonymSearchViewModel'
    ],
    strings: {
        synonymSearch: '#UT#Synonym-Suche',
        resultColumn:'#UT#Übersetzung',
        partOfSpeechColumn:'#UT#Wortart',
        backTranslationsColumn:'#UT#Alternativen',
        confidenceColumn:'#UT#Wahrscheinlichkeit',
        ADJ:'#UT#Adjektiv',
        ADV:'#UT#Adverb',
        CONJ:'#UT#Verbindung',
        DET:'#UT#Bestimmer',
        MODAL:'#UT#Verb',
        NOUN:'#UT#Substantiv',
        PREP:'#UT#Präposition',
        PRON:'#UT#Pronomen',
        VERB:'#UT#Verb',
        OTHER:'#UT#Sonstiges',
        unknown:'#UT#unbekannt'
    },
    itemId:'synonymSearch',
    border: false,
    layout: 'fit',
    scrollable: true,
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.strings.synonymSearch,
                bind:{
                    store:'{translations}'
                },
                viewConfig: {
                    enableTextSelection: true,
                    getRowClass: function () {
                        return this.enableTextSelection ? 'x-selectable' : '';
                    }
                },
                columns: [{
                    xtype: 'gridcolumn',
                    enableTextSelection: true,
                    flex: 2,
                    dataIndex: 'target',
                    hideable: false,
                    sortable: false,
                    cellWrap: true,
                    text: me.strings.resultColumn
                },{
                    xtype: 'gridcolumn',
                    enableTextSelection: true,
                    flex: 2,
                    dataIndex: 'posTag',
                    renderer: function(val) {
                        return me.strings[val] !== undefined ? me.strings[val] : me.strings.unknown;
                    },
                    hideable: false,
                    sortable: false,
                    cellWrap: true,
                    text: me.strings.partOfSpeechColumn
                },{
                    xtype: 'gridcolumn',
                    flex: 1,
                    hideable: false,
                    sortable: false,
                    dataIndex: 'backTranslations',
                    renderer: function(val) {
                        if(!val){
                            return ;
                        }
                        var alternatives = [];
                        for (var i = 0; i<val.length;i++){
                            alternatives.push(val[i].displayText);
                        }
                        return alternatives.join(',');
                    },
                    text: me.strings.backTranslationsColumn
                },{
                    text: me.strings.confidenceColumn,
                    xtype: 'widgetcolumn',
                    widget: {
                        bind: '{record.confidence}',
                        xtype: 'progressbarwidget',
                        textTpl: [
                            '{percent:number("0")}%'
                        ]
                    }
                }],
                tbar: [{
                    xtype: 'textfield',
                    itemId:'textSearch',
                    checkChangeBuffer:500,
                    fieldLabel: me.strings.synonymSearch
                }]
		    };
		if (instanceConfig) {
			me.self.getConfigurator().merge(me, config, instanceConfig);
		}
		return me.callParent([ config ]);
	}
});