
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
    extend: 'Ext.tab.Panel',
    alias: 'widget.matchResourceEditorPanel',
    controller: 'matchResourceEditorPanel',
    requires:[
        'Editor.plugins.MatchResource.view.EditorPanelViewController',
        'Editor.plugins.MatchResource.view.SearchGrid',
        'Editor.plugins.MatchResource.view.MatchGrid'
    ],
    strings: {
        searchTitle: '#UT#Konkordanzsuche',
        matchTitle:'#UT#Matches',
        sourceEmptyText:'#UT#Quelltextsuche',
        targetEmptyText:'#UT#Zieltextsuche',
        panelTitle:'#UT#Matches und Konkordanz-Suche'
    },
    bind: {
        hidden: '{taskIsReadonly}'
    },
    itemId:'matchResourceEditorPanel',
    activeTab: 0,
    header: {
        hidden: true
    },
    layout: 'fit',
    plain: false,
    cls: 'plugin-match-resource-result-panel',
    initConfig: function(instanceConfig) {
        var me = this,
        config = {
                items: []// end of items
		};
        me.title=me.strings.panelTitle;
		me.isAllowedMatchQuery(config,instanceConfig);
		me.isAllowedSearchQuery(config,instanceConfig);
		if (instanceConfig) {
			me.self.getConfigurator().merge(me, config, instanceConfig);
		}
		return me.callParent([ config ]);
	},
	isAllowedMatchQuery:function(config,instanceConfig){
		if(Editor.app.authenticatedUser.isAllowed('pluginMatchResourceMatchQuery')) {
			config.items.push({
	             title: this.strings.matchTitle,
	             xtype:'matchResourceMatchGrid',
	             assocStore:instanceConfig.assocStore,
	         });
		}
	},
	isAllowedSearchQuery:function(config,instanceConfig){
		var showSearch = instanceConfig.assocStore.find('searchable', true) >= 0;
		if(showSearch && Editor.app.authenticatedUser.isAllowed('pluginMatchResourceSearchQuery')) {
			config.items.push({
	             title: this.strings.searchTitle,
            	 xtype:'matchResourceSearchGrid',
            	 assocStore:instanceConfig.assocStore
	         });
		}
	},	
});