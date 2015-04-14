
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