
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
	extend: 'Ext.grid.Panel',
	alias: 'widget.matchResourceMatchGrid',
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
	strings: {
        tooltipMsg: '#UT#Diesen Match in das geöffnete Segment übernehmen.',
        atributeTooltipMsg: '#UT#Attributes:',
        lastEditTooltipMsg: '#UT#Last Edit:',
        createdTooltipMsg: '#UT#Created:'
    },
	stores:[
		'Editor.plugins.MatchResource.store.TaskAssocStore'
	],
    controller: 'matchResourceMatchGrid',
    viewModel: {
        type: 'matchResourceMatchGrid'
    },
    defaultListenerScope: true,
	itemId:'matchGrid',
	cls:'matchGrid',
	assocStore: [],
	SERVER_STATUS: null,
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
	          xtype:'gridcolumn',
	          flex: 10/100,
	          dataIndex: 'state',
              renderer: function(val, meta, record) {
                  if(val == me.SERVER_STATUS.SERVER_STATUS_LOADED){
                      meta.tdAttr = 'data-qtip="'+me.strings.atributeTooltipMsg+' <br/> '+
                                                  me.strings.lastEditTooltipMsg+' '+record.get('lastEditor')+' '+Ext.Date.format(record.get('lastEdited'), 'd/m/Y')+' <br/> '+
                                                  me.strings.createdTooltipMsg+' '+record.get('creator')+' '+Ext.Date.format(record.get('created'), 'd/m/Y')+' <br/> '+
                                                  ' </br> STRG - '+(meta.rowIndex + 1)+': '+me.strings.tooltipMsg+'"';
                      meta.tdCls  = meta.tdCls  + ' info-icon';
                      return meta.rowIndex + 1;
                  }
                  return "";
              },
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
	              var str =me.assocStore.findRecord('id',record.get('tmmtid'));
	              if(matchrate > 0){
	                  meta.tdAttr += 'data-qtip="'+str.get('serviceName')+'"';
	                  meta.tdCls  = meta.tdCls  + ' info-icon';
	              }
	              clr = str.get('color');
	              meta.tdAttr += 'bgcolor="' + clr + '"';
	              return "<b>"+(matchrate > 0 ? matchrate : '')+"</b>";
	          },
	          text:'Match Rate'
	      }]
	    };
	    me.assocStore = instanceConfig.assocStore;
	    me.SERVER_STATUS=Editor.plugins.MatchResource.model.EditorQuery.prototype;
	    if (instanceConfig) {
	        me.getConfigurator().merge(me, config, instanceConfig);
	    }
	    return me.callParent([config]);
	  }
});