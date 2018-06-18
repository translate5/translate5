
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
      matchCount:'#UT#Gesamtzahl der Wörter',
      tabTitle:"#UT#Analyse",
      exportAnalysis:'#UT#Export',
	  noAnalysis:'#UT#Keine Analyse für die aktuelle Aufgabe',
	  matchResources:'#UT#Matchressourcen',
	  analysisDate:'#UT#Datum',
	  matchResourceName: '#UT#Name'
    },
    
    layout:'fit',
    
    initConfig: function(instanceConfig) {
        var me = this,
            config,
            columnRenderer=function(val, meta, record) {
        		if(val && val.wordCount && val.wordCount>0){
        			return val.wordCount;
        		}
                return "";
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
                	}else{
                		me.down('#exportExcel').setDisabled(true);
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
                    features: [{
                        ftype: 'summary'
                    }],
                    columns: [{
                        xtype: 'gridcolumn',
                        text: me.strings.matchResourceName,
                        renderer: function(value, metaData, record) {
                            return '<div style="float: left; width: 15px; height: 15px;margin-right:5px; border: 1px solid rgba(0, 0, 0, .2);background: #'+record.get('resourceColor')+';"></div>'+value;
                        },
                        dataIndex : 'resourceName',
                        sortable : true
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '103',
                        cellWrap: true,
                        text: "103%",
                        tooltip:Editor.util.MatchResources.getMatchrateTooltip(103),
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                            return me.calculateRowSum(103,analysisStore);
                        },
                        renderer:columnRenderer,
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '102',
                        cellWrap: true,
                        text: "102%",
                        tooltip:Editor.util.MatchResources.getMatchrateTooltip(102),
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                        	return me.calculateRowSum(102,analysisStore); 
                        },
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '101',
                        cellWrap: true,
                        text: "101%",
                        tooltip:Editor.util.MatchResources.getMatchrateTooltip(101),
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                        	return me.calculateRowSum(101,analysisStore);
                        },
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '100',
                        cellWrap: true,
                        text: "100%",
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                        	return me.calculateRowSum(100,analysisStore);
                        },
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '99',
                        cellWrap: true,
                        text: "99%-90%",
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                        	return me.calculateRowSum(99,analysisStore);
                        },
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '89',
                        cellWrap: true,
                        text: "89%-80%",
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                        	return me.calculateRowSum(89,analysisStore);
                        },
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '79',
                        cellWrap: true,
                        text: "79%-70%",
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                        	return me.calculateRowSum(79,analysisStore);
                        },
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '69',
                        cellWrap: true,
                        text: "69%-60%",
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                        	return me.calculateRowSum(69,analysisStore);
                        },
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '59',
                        cellWrap: true,
                        text: "59%-51%",
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                        	return me.calculateRowSum(59,analysisStore);
                        },
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: 'noMatch',
                        cellWrap: true,
                        text: me.strings.noMatch,
                        //summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                        	return me.calculateRowSum("noMatch",analysisStore);
                        },
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: 'wordCountTotal',
                        cellWrap: true,
                        summaryType: 'sum',
                        summaryRenderer: function(value, summaryData, dataIndex) {
                            return value; 
                        },
                        text: me.strings.matchCount
                    }],
                    dockedItems: [{
                        xtype: 'toolbar',
                        dock: 'bottom',
                        items: [{ 
                            xtype: 'button',
                            iconCls:'icon-excel-export',
                            itemId:'exportExcel',
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
    },
    
    /***
     * Calculate row total by group
     */
    calculateRowSum:function(group,store){
    	var totalSum=0;
    	store.each(function(record){
    		if(record.get(group)){
    			totalSum+=record.get(group).wordCount;
    		}
    	});
    	return totalSum>0 ? totalSum : "";
    }
});