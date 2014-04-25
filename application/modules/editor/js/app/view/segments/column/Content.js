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
  editor: {
      xtype: 'displayfield',
      fieldCls: 'x-form-display-field segment-tag-container'
  },
  constructor: function(conf) {
      var field = conf.fieldName;
      conf.width = 250; //needed, otherwise the Columns are overwritten with 100. Why ever...
      Ext.applyIf(conf, {
          dataIndex: field,
          itemId: field + 'Column',
          tdCls: 'segment-tag-column'
      });
      this.callParent(arguments);
  },
  initComponent: function() {
    var me = this;
    me.initBaseMixin();
    me.callParent(arguments);
  }
});