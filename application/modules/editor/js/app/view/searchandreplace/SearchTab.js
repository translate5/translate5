
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
        'Editor.view.searchandreplace.SearchTabViewController',
    ],
    viewModel: {
        type: 'searchreplaceviewmodel'
    },
    controller:'searchtabviewcontroller',
    
    closable:false,
    
    strings:{
      comboFieldLabel:'#UT#Suchen nach',//Search for
      searchInCombo:'#UT#Suchen in',
      matchCaseLabel:'#UT#Groß/Kleinschreibung beachten',//Match case
      towardsTop:'#UT#Nach oben suchen',//Search towards the top
      useForSearch:'#UT#Bei der Suche verwenden',//Use for search
      normalSearch:'#UT#Normal (Standard)',//Normal (default)
      wildcardsSearch:'#UT#Wildcards (* und ?)',
      regularExpressionSearch:'#UT#Regulärer Ausdruck',
      saveCurrentOpen:'#UT#Segment beim Schließen speichern',//Save segment on close
      invalidRegularExpression:'#UT#Ungültiger Regulärer Ausdruck',
      segmentMatchInfoMessage:'#UT#Segmente mit Suchtreffer:'
    },
    
    padding:'10 10 10 10',
    
    initConfig : function(instanceConfig) {
        var me = this,
        config = {
                items:[{
                    xtype:'textfield',
                    itemId:'searchCombo',
                    maxLength:1024,
                    name:'searchCombo',
                    focusable:true,
                    fieldLabel:me.strings.comboFieldLabel,
                    validator:me.validateSearchField,
                    listeners:{
                        change:'onSearchFieldTextChange'
                    }
                },{
                    xtype:'combo',
                    itemId:'searchInCombo',
                    name:'searchInCombo',
                    fieldLabel:me.strings.searchInCombo,
                    displayField:'value',
                    valueField:'id',
                    forceSelection:true,
                    listeners:{
                        select:'resetSearchParametars'
                    }
                },{
                    xtype:'checkbox',
                    itemId:'matchCase',
                    name:'matchCase',
                    boxLabel:me.strings.matchCaseLabel,
                    listeners:{
                        change:'resetSearchParametars'
                    }
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
                        flex: 1,
                        listeners:{
                            change:'resetSearchParametars'
                        }
                    },
                    layout: 'vbox',
                    items: [
                        {
                            boxLabel  : me.strings.normalSearch,
                            name      : 'searchType',
                            inputValue: 'normalSearch',
                            checked:true,
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
                        text:me.strings.segmentMatchInfoMessage+' {getResultsCount}',
                        visible:'{showResultsLabel}'
                    }
                },{
                    xtype:'checkbox',
                    itemId:'saveCurrentOpen',
                    name:'saveCurrentOpen',
                    boxLabel:me.strings.saveCurrentOpen
                }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    validateSearchField:function(val){
        var tabPanel=this.up('#searchreplacetabpanel'),
            activeTab=tabPanel.getActiveTab(),
            tabPanelviewModel=tabPanel.getViewModel(),
            activeTab=tabPanel.getActiveTab(),
            searchType=activeTab.down('radiofield').getGroupValue();
        
        if(searchType==="regularExpressionSearch"){
            try {
                var isValidRegexp=new RegExp(val);
            } catch (e) {
                tabPanelviewModel.set('disableSearchButton',true);
                return activeTab.strings.invalidRegularExpression;
            }
        }
        tabPanelviewModel.set('disableSearchButton',val===null || val==="");
        return val!=null || val!=="";
    }
    
});