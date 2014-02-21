/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.segments.column.Editor
 * @extends Editor.view.ui.segments.column.Editor
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.ContentEditable', {
  extend: 'Ext.grid.column.Column',
  alias: 'widget.contentEditableColumn',
  mixins: ['Editor.view.segments.column.BaseMixin'],
  //how is this defined for alternative columns:
  isErgonomicVisible: true,
  //how is this defined for alternative columns:
  isErgonomicSetWidth: true,
  
  /**
   * @cfg {String} fieldName
   * The name of the segment data field, is processed automatically as dataIndex and so on.
   * **Required.**
   */
  fieldName: null,
  width: 250,
  resizable: false,
  fixed: true,
  
  filter: {
    type: 'string'
  },
  constructor: function(conf) {
      var field = conf.fieldName;
      conf.width = 250; //needed, otherwise the Columns are overwritten with 100. Why ever...
      Ext.applyIf(conf, {
          dataIndex: field + 'Edit',
          itemId: field + 'EditColumn',
          tdCls: 'editable segment-tag-column'
      });
      this.callParent(arguments);
  },
  initComponent: function() {
  //disable ergo mode on source column
    var me = this;
    me.initBaseMixin();
    me.callParent(arguments);
  },
  /**
   * interne Methode, wird zur Erzeugung der Editor Instanz einer Spalte verwendet
   * @returns {Editor.view.segments.HtmlEditor}
   */
  getEditor: function() {
      var me = this;
      if(this.field){
          return this.field;
      }
      //ensure that we have only one Editor in the application, created for the first target column
      if(me.segmentField.isTarget() && !me.self.firstTarget || (me.self.firstTarget == me.dataIndex)) {
          me.self.firstTarget = me.dataIndex;
      }
      this.field = Ext.create('widget.displayfield',{
          name: this.dataIndex,
          fieldCls: 'x-form-display-field segment-tag-container'
      });
      return this.field;
  } 
});