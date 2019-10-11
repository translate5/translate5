
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

Ext.define('Editor.view.searchandreplace.TabPanel', {
    extend:'Ext.tab.Panel',
    xtype:'searchreplacetabpanel',
    alias:'widget.searchreplacetabpanel',
    itemId:'searchreplacetabpanel',
    
    requires:[
        'Editor.view.searchandreplace.TabPanelViewController',
        'Editor.view.searchandreplace.TabPanelViewModel',
        'Editor.view.searchandreplace.SearchTab',
        'Editor.view.searchandreplace.ReplaceTab',
    ],
    controller: 'tabpanelviewcontroller',
    viewModel: {
        type: 'tabpanelviewmodel'
    },
    
    listeners:{
        tabchange:'onTabPanelTabChange'
    },
    
    strings:{
        searchTabTitle:'#UT#Suchen',
        replaceTabTitle:'#UT#Ersetzen',
        closeButton:'#UT#Schließen',
        replaceAllButton:'#UT#Alles ersetzen',
        searchButton:'#UT#Suchen',
        replaceButton:'#UT#Ersetzen',
        mqmNotSupporterTooltip:'#Alle ersetzen wird für Aufgaben mit Segmenten mit MQM-Tags nicht unterstützt'
    },
    
    initConfig : function(instanceConfig) {
        var me = this,
        config = {
                items:[{
                    xtype:'searchTab',
                    title:me.strings.searchTabTitle
                },{
                    xtype:'replaceTab',
                    title:me.strings.replaceTabTitle
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    items: [{ 
                        xtype: 'button',
                        itemId:'searchButton',
                        bind:{
                            disabled:'{isDisableSearchButton}',
                        },
                        text: me.strings.searchButton 
                    },{ 
                        xtype: 'button',
                        itemId:'replaceButton',
                        bind:{
                            visible:'{!isSearchView}'
                        },
                        text: me.strings.replaceButton 
                    },{ 
                        xtype: 'button',
                        itemId:'replaceAllButton',
                        hidden:true,
                        style: {
                            //https://www.sencha.com/forum/showthread.php?310184-Show-Tooltip-on-disabled-Button
                            pointerEvents: 'all'
                        },
                        bind:{
                            visible:'{!isSearchView}',
                            disabled:'{isDisableReplaceButton}',
                            tooltip:'{isMqmTooltip}'
                        },
                        text: me.strings.replaceAllButton
                    },{ 
                        xtype: 'button',
                        text: me.strings.closeButton,
                        handler:'onCloseButtonClick'
                    },]
                }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});