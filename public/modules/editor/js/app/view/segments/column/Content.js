
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
 * @class Editor.view.segments.column.Content
 * @extends Editor.view.ui.segments.column.Content
 */
Ext.define('Editor.view.segments.column.Content', {
  extend: 'Ext.grid.column.Column',
  alias: 'widget.contentColumn',
  mixins: ['Editor.view.segments.column.BaseMixin'],
  filter: {
      type: 'string'
  },
  /**
   * @cfg {String} fieldName
   * The name of the segment data field, is processed automatically as dataIndex and so on.
   * **Required.**
   */
  fieldName: null,
  minWidth: 250,
  width: 250,
  //resizable: false,
  //fixed: true,
  isContentColumn: true,
  variableRowHeight: true,
  strings: {
      missingSource: '#UT#ACHTUNG: QUELLSPRACHLICHER INHALT IN VORHERIGEM ODER NACHFOLGENDEM SEGMENT',
      missingTarget: '#UT#ACHTUNG: ZIELSPRACHLICHER INHALT IN VORHERIGEM ODER NACHFOLGENDEM SEGMENT'
  },
  constructor: function(conf) {
      var field = conf.fieldName;
      Ext.applyIf(conf, {
          dataIndex: field,
          itemId: field + 'Column',
          stateId:field + 'Column',
          tdCls: 'segment-tag-column'+this.getTypeCls(conf.segmentField)
      });
      this.callParent(arguments);
      
  },
  getTypeCls: function(field) {
      var segField = Editor.model.segment.Field,
          type = field.get('type');
      return ' x-selectable type-'+type+' '+segField.getDirectionCls(type);
  },
  initComponent: function() {
    var me = this;
    this.scope = this; //so that renderer can access this object instead the whole grid.
    me.initBaseMixin();
    me.callParent(arguments);
  },
  initConfig: function(instanceConfig) {
      var me = this,
      config = {
          enableTextSelection: true
      };
      if (instanceConfig) {
          me.self.getConfigurator().merge(me, config, instanceConfig);
      }
      return me.callParent([config]);
  },
  /**
   * internal method to create a display field
   * @returns {Editor.view.segments.HtmlEditor}
   */
  getEditor: function(rec, conf) {
      var me = this,
          plug = me.grid.editingPlugin,
          config = {
              xtype: 'displayfield',
              isContentColumn: true,
              // Override Field's implementation so that the default display fields will not return values. This is done because
              // the display field will pick up column renderers from the grid.
              getModelData: function() {
                  return null;
              },
              name: this.dataIndex,
              cls: 'segment-content',
              fieldCls: 'x-form-display-field segment-tag-container'+me.getTypeCls(me.segmentField)
          };
      return plug.getColumnField(me, config);
  },
  renderer: function(v, meta, rec) {
      var type = rec.get('matchRateType'),
          fieldType = this.segmentField.get('type'),
          isSource = fieldType === this.segmentField.TYPE_SOURCE,
          isTarget = fieldType === this.segmentField.TYPE_TARGET;
      
      if(isSource && /missing-source-mrk/.test(type)) {
          meta.tdCls += "missing-source-segment";
          return this.strings.missingSource;
      }
      if(isTarget && /missing-target-mrk/.test(type)) {
          meta.tdCls += "missing-target-segment";
          return this.strings.missingTarget;
      }
      return v;
  }
});