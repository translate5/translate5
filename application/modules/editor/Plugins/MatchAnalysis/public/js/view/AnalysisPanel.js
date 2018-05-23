
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
    
    //controller: '',
    //viewModel: {
    //    type: ''
    //},
    //bind: {
    //    store: '{matchAnalysis}'
    //},
    itemId:'matchAnalysisPanel',

    /*
     * 
     * 
     *     {name: 'id', type: 'int'},
    {name: 'created', type: 'date', dateFormat: Editor.DATEONLY_ISO_FORMAT },
    {name: '103', type: 'string'},
    {name: '102', type: 'string'},
    {name: '101', type: 'string'},
    {name: '100', type: 'string'},
    {name: '99', type: 'string'},//99-90
    {name: '89', type: 'string'},//89-80
    {name: '79', type: 'string'},//79-70
    {name: '69', type: 'string'},//69-60
    {name: '59', type: 'string'},//59-51
    {name: 'noMatch', type: 'string'},//50-0
    {name: 'matchCount', type: 'string'}
     */
    strings:{
      noMatch:'#UT#No matche',
      matchCount:'#UT#Match Count',
      tabTitle:"#UT#Analysis"
    },
    
    templateString: '<table>'+
                    '<tr>'+
                        '<th>103%</th>'+
                        '<th>102%</th>'+
                        '<th>101%</th>'+
                        '<th>100%</th>'+
                        '<th>99%-90%</th>'+
                        '<th>89%-80%</th>'+
                        '<th>79%-70%</th>'+
                        '<th>69%-60%</th>'+
                        '<th>59%-51%</th>'+
                        '<th>No match</th>'+
                        '<th>Match Count</th>'+
                      '</tr>'+
                      '<tr>'+
                        '<th>{103.rateCount}</th>'+
                        '<th>{102.rateCount}</th>'+
                        '<th>{101.rateCount}</th>'+
                        '<th>{100.rateCount}</th>'+
                        '<th>{99.rateCount}</th>'+
                        '<th>{89.rateCount}</th>'+
                        '<th>{79.rateCount}</th>'+
                        '<th>{69.rateCount}</th>'+
                        '<th>{59.rateCount}</th>'+
                        '<th>{noMatch}</th>'+
                        '<th>{wordCountTotal}</th>'+
                      '</tr>'+
                '</table>',
    initConfig: function(instanceConfig) {
        var me = this,
            config,
            columnRenderer=function(val, meta, record) {
                return val && val.rateCount;
            };
    
        config = {
            title:me.strings.tabTitle,
            items:[{
                xtype:'panel',
                itemId:'printArea',
                title:"printable",
                tools: [{
                    type: 'print',
                    handler: function() {
                        var analysisStore=me.down('#matchAnalysisGrid').getStore();
                        var t = new Ext.Template([
                            me.templateString
                        ]);
                        t.compile();
                        var div = document.createElement("div");
                        div.id="printableDiv";
                        
                        var theDat=analysisStore.getAt(0).getData();
                        t.append(div,theDat);
                        
                        var myWindow = window.open('', '', 'width=1000,height=800');
                        myWindow.document.write('<html>');
                        myWindow.document.write('<head></head>');
                        myWindow.document.write('<body>');
                        myWindow.document.write(div.innerHTML);
                        myWindow.document.write('</body></html>');
                        myWindow.print();
                    }
                }],
                items:[{
                    xtype:'grid',
                    itemId:'matchAnalysisGrid',
                    store : 'Editor.plugins.MatchAnalysis.store.MatchAnalysis',
                    columns: [{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '103',
                        cellWrap: true,
                        text: "103%",
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '102',
                        cellWrap: true,
                        text: "102%",
                        renderer:columnRenderer
                    },{
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '101',
                        cellWrap: true,
                        text: "101%",
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
                    }]
                
                }]
                }],
        };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});