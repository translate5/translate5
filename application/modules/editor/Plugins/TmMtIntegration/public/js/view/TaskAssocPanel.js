
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
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.plugins.TmMtIntegration.view.TaskAssocPanel', {
	extend : 'Ext.panel.Panel',
	alias : 'widget.tmMtIntegrationTaskAssocPanel',
	requires: ['Editor.view.admin.TaskActionColumn'],
	cls : 'adminTaskGrid',
	title : 'Associate TMs to task',
	frame : true,
	padding : 10,
	initConfig : function(instanceConfig) {
		var me = this,
		config = {
				dockedItems : [{
	                xtype : 'toolbar',
	                dock : 'bottom',
	                ui: 'footer',
	                layout: {
	                    type: 'hbox',
	                    pack: 'start'
	                },
	                items : [{
	                    xtype: 'tbfill'
	                },{
	                	xtype : 'button',
						id : 'btnSaveChanges',
						tooltip : 'Save',
						text : 'Save TMs',
						enableToggle : true,
						pressed : true
	                }]
	            }],
			items : [ {
				xtype : 'grid',
				id : 'tmGrid',
				store : 'Editor.plugins.TmMtIntegration.store.TaskAssocStore',
				features : [ {
					id : 'group',
					ftype : 'groupingsummary',
					groupHeaderTpl : 'Service :{name}',
					hideGroupedHeader : true,
					enableGroupingMenu : false
				} ],
				columns : [ {
					xtype : 'checkcolumn',
					text : '',
					dataIndex : 'checked',
					sortable : true,
					flex : 10 / 100
				}, {
					xtype : 'gridcolumn',
					text : 'Name',
					renderer: function(value, metaData, record) {
		        		return '<div style="float: left; width: 15px; height: 15px;margin-right:5px; border: 1px solid rgba(0, 0, 0, .2);background: #'+record.get('color')+';"></div>'+value;
		        	},
					dataIndex : 'name',
					sortable : true,
					flex : 40 / 100
				}, {
					xtype : 'gridcolumn',
					text : 'Source Language',
					cls : 'source-lang',
					dataIndex : 'sourceLang',
					renderer : me.langRenderer,
					sortable : true,
					flex : 25 / 100,
				}, {
					xtype : 'gridcolumn',
					text : 'Target Language',
					cls : 'target-lang',
					dataIndex : 'targetLang',
					renderer : me.langRenderer,
					flex : 25 / 100,
					sortable : true
				} ]
			} ],// end of items
		};
		if (instanceConfig) {
			me.getConfigurator().merge(me, config, instanceConfig);
		}
		return me.callParent([ config ]);
	},
	langRenderer : function(val, md) {
		var lang = Ext.StoreMgr.get('admin.Languages').getById(val), label;
		if (lang) {
			label = lang.get('label');
			md.tdAttr = 'data-qtip="' + label + '"';
			return label;
		}
		return '';
	},
});