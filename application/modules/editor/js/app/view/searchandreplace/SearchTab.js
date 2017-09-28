
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.searchandreplace.SearchTab', {
    extend:'Ext.form.Panel',
    xtype:'searchTab',
    alias:'widget.searchTab',
    itemId:'searchTab',
    
    
    requires:[
        'Editor.view.searchandreplace.SearchReplaceViewModel',
    ],
    viewModel: {
        type: 'searchreplaceviewmodel'
    },
    
    closable:false,
    
    strings:{
      comboFieldLabel:'#UT#Search',
      searchInCombo:'#UT#Search in',
      matchCase:'#UT#Match case',
      towardsTop:'#UT#Search towards the top',
      useForSearch:'#UT#Use for search',
      normalSearch:'#UT#Normal" (default)',
      wildcardsSearch:'#UT#Wildcards',
      regularExpressionSearch:'#UT#Regular expressions',
    },
    
    padding:'10 10 10 10',
    
    initConfig : function(instanceConfig) {
        var me = this,
        config = {
                items:[{
                    xtype:'textfield',
                    itemId:'searchCombo',
                    name:'searchCombo',
                    focusable:true,
                    fieldLabel:me.strings.comboFieldLabel,
                },{
                    xtype:'combo',
                    itemId:'searchInCombo',
                    name:'searchInCombo',
                    fieldLabel:me.strings.searchInCombo,
                    displayField:'value',
                    valueField:'id',
                    forceSelection:true,
                },{
                    xtype:'checkbox',
                    itemId:'matchCaseChekbox',
                    name:'matchCaseChekbox',
                    boxLabel:me.strings.matchCase
                },{
                    xtype:'checkbox',
                    itemId:'searchTopChekbox',
                    name:'searchTopChekbox',
                    boxLabel:me.strings.towardsTop
                },{
                    xtype      : 'fieldcontainer',
                    fieldLabel : me.strings.useForSearch,
                    defaultType: 'radiofield',
                    defaults: {
                        flex: 1
                    },
                    layout: 'vbox',
                    items: [
                        {
                            boxLabel  : me.strings.normalSearch,
                            name      : 'searchType',
                            inputValue: 'normalSearch',
                        }, {
                            boxLabel  : me.strings.wildcardsSearch,
                            name      : 'searchType',
                            inputValue: 'wildcardsSearch',
                        }, {
                            boxLabel  : me.strings.regularExpressionSearch,
                            name      : 'searchType',
                            inputValue: 'regularExpressionSearch',
                        }
                    ]
                },{
                    xtype: 'label',
                    bind:{
                        text:'Results found: {getResultsCount}',
                        visible:'{showResultsLabel}'
                    }
                }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
    
});