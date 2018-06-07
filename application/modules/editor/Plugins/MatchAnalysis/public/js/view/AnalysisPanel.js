
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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.MatchResource.view.SearchGrid
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.plugins.MatchAnalysis.view.AnalysisPanel', {
    extend : 'Ext.panel.Panel',
    alias : 'widget.matchAnalysisPanel',
    
    requires: [
        'Editor.util.MatchResources'
    ],

    itemId:'matchAnalysisPanel',

    strings:{
      noMatch:'#UT#Keine Treffer',
      matchCount:'#UT#Trefferanzahl',
      tabTitle:"#UT#Analyse",
      exportAnalysis:'#UT#Export',
	  noAnalysis:'#UT#Keine Analyse für die aktuelle Aufgabe',
	  matchResources:'#UT#Matchressourcen',
	  analysisDate:'#UT#Datum'
    },
    
    layout:'fit',
    
    initConfig: function(instanceConfig) {
        var me = this,
            config,
            columnRenderer=function(val, meta, record) {
                return val && val.rateCount;
            },
            analysisStore=Ext.create('Editor.plugins.MatchAnalysis.store.MatchAnalysis');
            
            //load the analysis for the taskGuid
            analysisStore.load({
                params: {
                    taskGuid:instanceConfig.task.get('taskGuid')
                },
                callback: function(records, operation, success) {
                	if(records && records.length>0){
                		var rec=records[0];
                		me.down('#analysisDatum').setValue(rec.get('created'));
                	}
                }
            });
            
        config = {
            title:me.strings.tabTitle,
            items:[{
                    xtype:'grid',
                    itemId:'matchAnalysisGrid',
                    emptyText:me.strings.noAnalysis,
                    store : analysisStore,
                    columns: [{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '103',
                        cellWrap: true,
                        text: "103%",
                        tooltip:Editor.util.MatchResources.getMatchrateTooltip(103),
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '102',
                        cellWrap: true,
                        text: "102%",
                        tooltip:Editor.util.MatchResources.getMatchrateTooltip(102),
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '101',
                        cellWrap: true,
                        text: "101%",
                        tooltip:Editor.util.MatchResources.getMatchrateTooltip(101),
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '100',
                        cellWrap: true,
                        text: "100%",
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '99',
                        cellWrap: true,
                        text: "99%-90%",
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '89',
                        cellWrap: true,
                        text: "89%-80%",
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '79',
                        cellWrap: true,
                        text: "79%-70%",
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '69',
                        cellWrap: true,
                        text: "69%-60%",
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '59',
                        cellWrap: true,
                        text: "59%-51%",
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: 'noMatch',
                        cellWrap: true,
                        text: me.strings.noMatch,
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: 'wordCountTotal',
                        cellWrap: true,
                        text: me.strings.matchCount
                    }],
                    dockedItems: [{
                        xtype: 'toolbar',
                        dock: 'bottom',
                        items: [{ 
                            xtype: 'button',
                            iconCls:'icon-excel-export',
                            text:me.strings.exportAnalysis,
                            listeners:{
                                click:function(){
                                    var params = {};
                                    params["taskGuid"] = me.task.get('taskGuid');
                                    window.open(Editor.data.restpath+'plugins_matchanalysis_matchanalysis/export?'+Ext.urlEncode(params));
                                }
                            }
                        },{
                        	xtype: 'button',
                            iconCls:'icon-match-resources',
                            text:me.strings.matchResources,
                            listeners:{
                            	click:me.onMatchResourcesButtonClick
                        	}
                        }]
                    },{

                        xtype: 'toolbar',
                        dock: 'top',
                        items: [{
                            xtype: 'displayfield',
                            fieldLabel: me.strings.analysisDate,
                            itemId:'analysisDatum'
                        }]
                    
                    }]
                
                }]
        };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    /***
     * focus on match resources tab on button click
     */
    onMatchResourcesButtonClick:function(button){
    	var tabPanel=button.up('tabpanel');
    	if(!tabPanel){
    		return;
    	}
    	tabPanel.getLayout().setActiveItem('matchResourceTaskAssocPanel')
    }
});