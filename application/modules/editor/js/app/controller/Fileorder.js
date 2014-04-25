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
 * Controller für den Dateibaum
 * @class Editor.controller.Fileorder
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Fileorder', {
  extend : 'Ext.app.Controller',
  stores: ['Files','ReferenceFiles'],
  views: ['fileorder.Tree','fileorder.ReferenceTree'],
  refs : [{
    ref : 'fileTree',
    selector : '#fileorderTree'
  },{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  }],
  init : function() {
      var me = this;
      //@todo on updating ExtJS to >4.2 use Event Domains and me.listen for the following event bindings 
      Editor.app.on('editorViewportClosed', me.clearStores, me);
      me.getStore('Files').on('write', me.releaseFileSelection, me);
      me.control({
          '#segmentgrid' : {
              selectionchange: me.handleSegmentSelectionChange
          },
          '#fileorderTree': {
              itemmove: me.handleItemMoved
          }
      });
  },
  loadFileTree: function() {
      this.getFilesStore().load({
          manualLoaded: true,
          callback: function() {
              this.getRootNode().expand();
          }
      });
      this.getReferenceFilesStore().load({
          manualLoaded: true,
          callback: function() {
              this.getRootNode().expand();
          }
      });
  },
  clearStores: function() {
      this.getFilesStore().getRootNode().removeAll();
      this.getFilesStore().removed = [];
      this.getReferenceFilesStore().getRootNode().removeAll();
      this.getReferenceFilesStore().removed = [];
  },
  handleItemMoved: function() {
    this.getFilesStore().sync();
  },
  releaseFileSelection: function() {
    this.getFileTree().getSelectionModel().deselectAll();
  },
  /**
   * Handler für das Segment Grid selectionchange event
   * @param {Ext.selection.Model} sm aktuelle SelectionModel Instanz des Grids
   * @param {Array} selectedRecords 
   */
  handleSegmentSelectionChange: function(sm, selectedRecords) {
    if(selectedRecords.length == 0) {
      return;
    }
    var selected = selectedRecords[0];
    var fileNodeToSelectedSegment = this.getFilesStore().getNodeById(selected.get('fileId'));
    if(fileNodeToSelectedSegment) {
      //Baum an gewünschtem Element ausklappen
      fileNodeToSelectedSegment.bubble(function(node){node.expand();});
      //gewünschtes Element selektieren
      this.getFileTree().getSelectionModel().select(fileNodeToSelectedSegment, false, true);
    }
  }
});