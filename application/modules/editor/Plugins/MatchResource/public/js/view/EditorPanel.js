
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * @class Editor.plugins.pluginFeasibilityTest.view.EditorPanel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.plugins.MatchResource.view.EditorPanel', {
	extend : 'Ext.tab.Panel',
	alias : 'widget.tmMtIntegrationTmMtEditorPanel',
	requires:['Editor.plugins.MatchResource.view.EditorPanelSearchGrid',
          'Editor.plugins.MatchResource.view.MatchGrid'],
    strings: {
        searchTitle: '#UT#Konkordanzsuche',
        matchTitle:'#UT#Match Ergebnisse',
        sourceEmptyText:'#UT#Quelltextsuche',
        targetEmptyText:'#UT#Zieltextsuche'
    },
    activeTab: 0,
    height:350,
    plain: true,
    collapsible :true,
	initConfig : function(instanceConfig) {
		var me = this,
		config = {
			items : []// end of items
		};
		me.isAllowedMatchQuery(config);
		me.isAllowedSearchQuery(config);
		if (instanceConfig) {
			me.getConfigurator().merge(me, config, instanceConfig);
		}
		return me.callParent([ config ]);
	},
	isAllowedMatchQuery:function(config){
		if(Editor.app.authenticatedUser.isAllowed('pluginMatchResourceMatchQuery')) {
			config.items.push({
	             title: this.strings.matchTitle,
	             xtype:'tmMtIntegrationMatchGrid'
	         });
		}
	},
	isAllowedSearchQuery:function(config){
		if(Editor.app.authenticatedUser.isAllowed('pluginMatchResourceSearchQuery')) {
			config.items.push({
	             title: this.strings.searchTitle,
	             items:[{
	            	 xtype: 'container',
	                 anchor: '100%',
	                 layout:'column',
	                 items:[{
	                	 xtype:'textfield',
	                	 dataIndex:'sourceSearch',
	                	 emptyText:this.strings.sourceEmptyText,
	                	 padding:'10 10 10 10',
	                 },{
	                	 xtype:'textfield',
		            	 dataIndex:'targetSearch',
		            	 emptyText:this.strings.targetEmptyText,
		            	 padding:'10 10 10 10',
	                 }]
	             },{
	            	 xtype:'tmMtIntegrationEditorPanelSearchGrid'
	             }]
	         });
		}
	},	
});