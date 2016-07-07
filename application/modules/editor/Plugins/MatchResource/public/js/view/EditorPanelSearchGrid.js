
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
 * @class Editor.plugins.pluginFeasibilityTest.view.Panel
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.plugins.MatchResource.view.EditorPanelSearchGrid', {
	extend : 'Ext.grid.Panel',
	alias : 'widget.tmMtIntegrationEditorPanelSearchGrid',
	itemId:'editorPanelSearchGrid',
	assocStore : [],
	strings: {
	    source: '#UT#Quelltext',
	    target: '#UT#Zieltext',
	    match: '#UT#Matchrate'
	},
	initConfig: function(instanceConfig) {
	    var me = this,
	    config = {
	      columns: [{
	          xtype: 'gridcolumn',
	          flex: 33/100,
	          dataIndex: 'source',
	          text: me.strings.source
	      },{
	          xtype: 'gridcolumn',
	          flex: 33/100,
	          dataIndex: 'target',
	          text: me.strings.target
	      },{
	          xtype: 'gridcolumn',
	          flex: 33/100,
	          dataIndex: 'matchrate',
	          text: me.strings.match
	      }]
	    };
	    me.assocStore = instanceConfig.assocStore;
	    if (instanceConfig) {
	        me.getConfigurator().merge(me, config, instanceConfig);
	    }
	    return me.callParent([config]);
	  }
});