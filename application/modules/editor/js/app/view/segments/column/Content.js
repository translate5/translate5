
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
 * @class Editor.view.segments.column.Content
 * @extends Editor.view.ui.segments.column.Content
 */
Ext.define('Editor.view.segments.column.Content', {
  extend: 'Ext.grid.column.Column',
  alias: 'widget.contentColumn',
  mixins: ['Editor.view.segments.column.BaseMixin'],
  /**
   * @cfg {String} fieldName
   * The name of the segment data field, is processed automatically as dataIndex and so on.
   * **Required.**
   */
  fieldName: null,
  width: 250,
  resizable: false,
  fixed: true,
  constructor: function(conf) {
      var field = conf.fieldName;
      Ext.applyIf(conf, {
          dataIndex: field,
          itemId: field + 'Column',
          tdCls: 'segment-tag-column'+this.getTypeCls(conf.segmentField)
      });
      this.callParent(arguments);
  },
  getTypeCls: function(field) {
      return ' type-'+field.get('type');
  },
  initComponent: function() {
    var me = this;
    me.initBaseMixin();
    me.callParent(arguments);
  },
  /**
   * internal method to create a display field
   * @returns {Editor.view.segments.HtmlEditor}
   */
  getEditor: function() {
      var me = this;
      if(this.field){
          return this.field;
      }
      this.field = Ext.create('widget.displayfield',{
          name: this.dataIndex,
          fieldCls: 'x-form-display-field segment-tag-container'+me.getTypeCls(me.segmentField)
      });
      return this.field;
  } 
});