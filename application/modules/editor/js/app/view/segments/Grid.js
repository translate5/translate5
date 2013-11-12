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
 * @class Editor.view.segments.Grid
 * @extends Editor.view.ui.segments.Grid
 * @initalGenerated
 */
Ext.define('Editor.view.segments.Grid', {
  requires: ['Editor.view.segments.GridFilter'],
  extend: 'Editor.view.ui.segments.Grid',
  alias: 'widget.segments.grid',
  store: 'Segments',
  stateful: false,
  columnMap:{},
  stateData: {},
  qualityData: {},
  features: [{
    ftype: 'editorGridFilter'
  }],
  //eigener X-Type für den Scroller
  verticalScrollerType: 'editorgridscroller',
  invalidateScrollerOnRefresh: false,
  //Einbindung des eigenen Editor Plugins
  plugins: [
    Ext.create('Editor.view.segments.RowEditing', {
      clicksToMoveEditor: 1,
      autoCancel: false
    })
  ],
  /**
   * Config Parameter für die {Ext.grid.View} des Grids
   */
  viewConfig: {
    blockRefresh: true,
    getRowClass: function(record, rowIndex, rowParams, store){
      if(record.get('editable')){
        return "";
      }
      return "editing-disabled";
    }
  },
  initComponent: function() {
    var me = this;
    
    //befülle interne Hash Map mit QM und Status Werten:
    Ext.each(Editor.data.segments.stateFlags, function(item){
      me.stateData[item.id] = item.label;
    });
    Ext.each(Editor.data.segments.qualityFlags, function(item){
      me.qualityData[item.id] = item.label;
    });
    me.callParent(arguments);
  },
  selectOrFocus: function(localRowIndex) {
    var sm = this.getSelectionModel();
    if(sm.isSelected(localRowIndex)){
      this.getView().focusRow(localRowIndex);
    }
    else {
      sm.select(localRowIndex);
    }
  }
});