
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.view.LanguageResources.MatchGrid
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.view.LanguageResources.MatchGrid', {
	extend: 'Ext.grid.Panel',
	alias: 'widget.languageResourceMatchGrid',
	requires: [
	           'Editor.view.LanguageResources.MatchGridViewController',
	           'Editor.view.LanguageResources.MatchGridViewModel',
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
	           'Ext.toolbar.Toolbar',
	           'Editor.util.LanguageResources'
	],
	strings: {
        source: '#UT#Ausgangstext',
        target: '#UT#Zieltext',
        match: '#UT#Matchrate',
		ctrl: '#UT#STRG',
        tooltipMsg: '#UT#Diesen Match in das geöffnete Segment übernehmen.',
        atributeTooltipMsg: '#UT#Attribute:',
        lastEditTooltipMsg: '#UT#letzte Änderung:',
        createdTooltipMsg: '#UT#erstellt:'
    },
	stores:[
		'Editor.store.LanguageResources.TaskAssocStore'
	],
    controller: 'languageResourceMatchGrid',
    viewModel: {
        type: 'languageResourceMatchGrid'
    },
    defaultListenerScope: true,
	itemId:'matchGrid',
	cls:'matchGrid',
	assocStore: [],
	SERVER_STATUS: null,
	viewConfig: {
	    enableTextSelection: true,
	    getRowClass: function(record) {
			//same class generation in SearchGrid!
	        var me=this,
	        	result = ['segment-font-sizable', 'match-state-'+record.get('state')];
				
            if(me.lookupViewModel().get('viewmodeIsErgonomic')){
                result.push('ergonomic-font');
            }
            else {
                result.push('view-editor-font-size');
            }
            return result.join(' ');
	    }
	},
	initConfig: function(instanceConfig) {
	    var me = this,
			attrTpl = new Ext.XTemplate(
				'{title} <br/>',
				'<table class="languageresource-meta-data">',
				'<tpl for="metaData">',
				'<tr><th>{name}</th><td>{value}</td></tr>',
				'</tpl>',
				'</table>',
				'<br /> {ctrl} - {idx}: {takeMsg}'
			),
        segField = Editor.model.segment.Field,
	    config = {
	      bind: {
             store: '{editorquery}'
          },
	      columns: [{
	          xtype:'gridcolumn',
	          flex: 1,
			  hideable: false,
			  sortable: false,
	          dataIndex: 'state',
              renderer: function(val, meta, record) {
                  if(val !== me.SERVER_STATUS.SERVER_STATUS_LOADED){
                  	return "";
                  }
					meta.tdAttr = 'data-qtip="'+Ext.String.htmlEncode(attrTpl.applyTemplate({
						title: me.strings.atributeTooltipMsg,
						metaData: record.get('metaData'),
						ctrl: me.strings.ctrl,
						idx: (meta.rowIndex + 1),
						takeMsg: me.strings.tooltipMsg
					}))+'"';
					meta.tdCls  = meta.tdCls  + ' info-icon';
					return meta.rowIndex + 1;
              },
          },{
	          xtype: 'gridcolumn',
	          flex: 5,
              enableTextSelection: true,
			  hideable: false,
			  sortable: false,
	          cellWrap: true,
			  tdCls: 'x-selectable segment-tag-column source '+segField.getDirectionCls('source'),
	          dataIndex: 'source',
	          text: me.strings.source
	      },{
	          xtype: 'gridcolumn',
	          flex: 5,
              enableTextSelection: true,
			  hideable: false,
		      sortable: false,
	          cellWrap: true,
			  tdCls: 'x-selectable segment-tag-column target '+segField.getDirectionCls('target'),
	          dataIndex: 'target',
	          text: me.strings.target
	      },{
	          xtype: 'gridcolumn',
	          flex: 1,
			  hideable: false,
              sortable: false,
	          dataIndex: 'matchrate',
	          tdCls: 'matchrate',
	          renderer: function(matchrate, meta, record) {
	              var str = me.assocStore.getById(record.get('languageResourceid'));
				  
	              meta.tdAttr += 'data-qtip="'+str.get('name')+' ('+str.get('serviceName')+')'+"<br/>"+ me.getMatchrateTooltip(matchrate)+'"';

				  meta.tdCls  = meta.tdCls  + ' info-icon';
	              clr = str.get('color');
	              meta.tdAttr += 'bgcolor="' + clr + '"';
	              return "<b>"+(matchrate > 0 ? matchrate : '&nbsp;')+"</b>";
	          },
	          text: me.strings.match
	      }]
         /* ,
	      dockedItems: [{
	          xtype: 'pagingtoolbar',
	          itemId: 'pagingtoolbar',
	          dock: 'bottom',
	          displayInfo: true
	      }]
	      */
	    };
	    me.assocStore = instanceConfig.assocStore;
	    me.SERVER_STATUS=Editor.model.LanguageResources.EditorQuery.prototype;
	    if (instanceConfig) {
	        me.self.getConfigurator().merge(me, config, instanceConfig);
	    }
	    return me.callParent([config]);
	  },
	  
	  /***
	   * Get the match rate tooltip depending of the match rate percent
	   */
	  getMatchrateTooltip:function(matchrate){
		  return Editor.util.LanguageResources.getMatchrateTooltip(matchrate);
	  }
});