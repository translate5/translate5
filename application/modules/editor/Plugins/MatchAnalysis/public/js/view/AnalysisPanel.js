
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
    extend : 'Ext.grid.Panel',
    alias : 'widget.matchAnalysisPanel',
    //controller: '',
    //viewModel: {
    //    type: ''
    //},
    //bind: {
    //    store: '{}'
    //},
    itemId:'matchAnalysisPanel',

    initConfig: function(instanceConfig) {
        var me = this,
            config;
    
        config = {
            title:"Analysis",
            items:[
                {
                    xtype:'matchAnalysisGrid',
                    itemId:'matchAnalysisGrid',
                    columns: [{
                        xtype: 'gridcolumn',
                        hideable: false,
                        sortable: false,
                        flex: 2,
                        dataIndex: '103',
                        cellWrap: true,
                        text: "103"
                    },{
                        xtype: 'gridcolumn',
                        hideable: false,
                        sortable: false,
                        flex: 2,
                        dataIndex: '103',
                        cellWrap: true,
                        text: "101"
                    },{
                        xtype: 'gridcolumn',
                        hideable: false,
                        sortable: false,
                        flex: 2,
                        dataIndex: '101',
                        cellWrap: true,
                        text: "101"
                    }]
                }
                
            ],
        };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});