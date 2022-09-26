
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
/*
 * File: app/view/ui/changealike/Grid.js
 *
 * This file was generated by Ext Designer version 1.2.3.
 * http://www.sencha.com/products/designer/
 *
 * This file will be auto-generated each and everytime you export.
 *
 * Do NOT hand edit this file.
 */

Ext.define('Editor.view.ui.changealike.Grid', {
  extend: 'Ext.grid.Panel',

  item_segmentNrInTaskColumn: 'Nr.', 
  item_sourceColumn: 'Quelle', 
  item_targetColumn: 'Ziel', 
  item_filterColumn: 'In aktueller Filterung enthalten', 
  item_sourceMatchColumn: 'Quell-Treffer', 
  item_targetMatchColumn: 'Ziel-Treffer',
  item_sameContextColumn: 'Kontext-Treffer',

  requires: [
      'Editor.view.segments.column.Matchrate',
      'Editor.view.segments.column.AutoState'
  ],
  rowBodyTpl: new Ext.XTemplate([
      '<span class="x-grid-cell-inner">{type}</span>',
      '<span class="x-grid-cell-inner">{source}</span>',
      '<span class="x-grid-cell-inner"></span>',
      '<span class="x-grid-cell-inner">{target}</span>'
  ]),
  initConfig: function(instanceConfig) {
    var me = this,
    segField = Editor.model.segment.Field,
    config;
    config = {
      columns: [
        {
          dataIndex: 'segmentNrInTask',
          filter: {
              type: 'numeric'
          },
          text: me.item_segmentNrInTaskColumn,
          width: 50
        },
        {
          xtype: 'autoStateColumn',
          userCls: 't5auto-state',
          width: 36,
          text: ''
        },
        {
          xtype: 'gridcolumn',
          dataIndex: 'source',
          flex: 1,
          filter: {
              type: 'string'
          },
          tdCls: 'alike-source-field segment-tag-column '+segField.getDirectionCls('source'),
          width: 250, 
          renderer: function(value, metaData, record) {
            if(record.get('sourceMatch')) {
              metaData.style = 'font-style: italic;';
            }
            return value;
          },
          text: me.item_sourceColumn
        },
        {
          xtype: 'booleancolumn',
          dataIndex: 'sourceMatch',
          userCls: 't5source-match',
          filter: {
            type: 'boolean'
          },
          width: 49,
          tooltip: me.item_sourceMatchColumn
        },
        {
          xtype: 'gridcolumn',
          dataIndex: 'target',
          isAlikeTarget: true,
          flex: 1,
          filter: {
              type: 'string'
          },
          tdCls: 'alike-target-field segment-tag-column '+segField.getDirectionCls('target'),
          width: 250,
          renderer: function(value, metaData, record) {
            if(record.get('targetMatch')) {
              metaData.style = 'font-style: italic;';
            }
            return value;
          },
          text: me.item_targetColumn
        },
        {
          xtype: 'booleancolumn',
          dataIndex: 'targetMatch',
          userCls: 't5target-match',
          filter: {
            type: 'boolean'
          },
          width: 49,
          tooltip: me.item_targetMatchColumn
        },
        {
          xtype: 'booleancolumn',
          dataIndex: 'infilter',
          filter: {
              type: 'boolean'
          },
          width: 41,
          tooltip: me.item_filterColumn,
          text: '<span class="fa fa-filter"></span>'
        },
        {
          xtype: 'booleancolumn',
          dataIndex: 'contextMatch',
          filter: {
              type: 'boolean'
          },
          width: 41,
          tooltip: me.item_sameContextColumn,
          text: '<span class="fa fa-table-list"></span>',
        }, {
        	xtype:'matchrateColumn',
            width: 43,
            text: '<span class="fa fa-percent"></span>',
            tooltip: 'Matchrate'
        }
      ],
      userCls: 't5alikeGrid',
      selModel: Ext.create('Ext.selection.CheckboxModel', {
        injectCheckbox: 0
      }),
      features: [{
        ftype: 'rowbody',
        bodyBefore: true,
        getAdditionalData: (data, idx, record) => {
          return {
            rowBody: me.rowBodyTpl.apply({
              type: record.get('context').data[0].type,
              source: record.get('context').data[0].source,
              target: record.get('context').data[0].target
            }),
            rowBodyCls: 'segment-tag-column'
          }
        }
      }, {
        ftype: 'rowbody',
        getAdditionalData: (data, idx, record) => {
          return {
            rowBody: me.rowBodyTpl.apply({
              type: record.get('context').data[1].type,
              source: record.get('context').data[1].source,
              target: record.get('context').data[1].target
            }),
            rowBodyCls: 'segment-tag-column'
          }
        }
      }]
    };

    if (instanceConfig) {
        me.self.getConfigurator().merge(me, config, instanceConfig);
    }
    return me.callParent([config]);
  }
});