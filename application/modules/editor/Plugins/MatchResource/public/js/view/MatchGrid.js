
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
 * @class Editor.plugins.MatchResource.view.MatchGrid
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.plugins.MatchResource.view.MatchGrid', {
	extend : 'Ext.grid.Panel',
	alias : 'widget.tmMtIntegrationMatchGrid',
	requires: [
	           'Editor.plugins.MatchResource.view.MatchGridViewController',
	           'Editor.plugins.MatchResource.view.MatchGridViewModel',
	           'Ext.grid.filters.filter.String',
	           'Ext.grid.filters.filter.List',
	           'Ext.grid.column.Number',
	           'Ext.grid.filters.filter.Number',
	           'Ext.selection.RowModel',
	           'Ext.grid.filters.Filters',
	           'Ext.view.Table',
	           'Ext.form.Panel',
	           'Ext.form.field.ComboBox',
	           'Ext.button.Button',
	           'Ext.toolbar.Toolbar'
	],
	stores:[
		'Editor.plugins.MatchResource.store.TaskAssocStore'
	],
    controller: 'tmMtIntegrationMatchGrid',
    viewModel: {
        type: 'tmMtIntegrationMatchGrid'
    },
    defaultListenerScope: true,
	itemId:'matchGrid',
	viewConfig: {
	    enableTextSelection: true
	},
	initConfig: function(instanceConfig) {
	    var me = this,
	    config = {
	      bind: {
             store: '{editorquery}'
          },
	      columns: [{
	          xtype:'rownumberer'
	      },{
	          xtype: 'gridcolumn',
	          flex: 45/100,
	          dataIndex: 'source',
	          text:'Source'
	      },{
	          xtype: 'gridcolumn',
	          flex: 45/100,
	          dataIndex: 'target',
	          text:'Target'
	      },{
	          xtype: 'gridcolumn',
	          flex: 10/100,
	          dataIndex: 'matchrate',
	          renderer: function(matchrate, meta, record) {
	              var str =me.getViewModel().getStore('taskassoc').findRecord('id',record.get('tmmtid'));
	              clr = str.get('color');
	              meta.tdAttr = 'bgcolor="' + clr + '"';
	              return "<b>"+matchrate+"</b>";
	          },
	          text:'Match Rate'
	      }]
	    };

	    if (instanceConfig) {
	        me.getConfigurator().merge(me, config, instanceConfig);
	    }
	    return me.callParent([config]);
	  }
});