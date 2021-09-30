
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
  listen: {
      controller: {
          '#Editor.$application': {
              editorViewportClosed: 'clearStores'
          }
      },
      component: {
          '#fileorderTree': {
              itemmove: 'handleItemMoved'
          },
          '#segmentgrid': {
              selectionchange: 'handleSegmentSelectionChange'
          }
      },
      store: {
          '#Files': {
              write: 'releaseFileSelection'
          }
      }
  },
  loadFileTree: function() {
      var me = this,
          fileStore = me.getFilesStore(),
          refStore = me.getReferenceFilesStore();
      fileStore.load({
          manualLoaded: true,
          scope: fileStore,
          callback: function() {
              this.getRootNode().expand();
          }
      });
      
      refStore.load({
          manualLoaded: true,
          scope: refStore,
          callback: function() {
              this.getRootNode().expand();
          }
      });
  },
  clearStores: function() {
      this.getFilesStore().getRootNode().removeAll(false);
      this.getReferenceFilesStore().getRootNode().removeAll(false);
  },
  handleItemMoved: function(nodeItem) {
      nodeItem.save({
          success: this.handleItemSaved,
          scope: this
      });
  },
  handleItemSaved: function(record, operation) {
      this.fireEvent('itemsaved', record, operation);
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
    var selected = selectedRecords[0],
        fileNodeToSelectedSegment = this.getFilesStore().getNodeById(selected.get('fileId'));
    if(fileNodeToSelectedSegment) {
      //Baum an gewünschtem Element ausklappen
      fileNodeToSelectedSegment.bubble(function(node){node.expand();});
      //gewünschtes Element selektieren
      this.getFileTree().getSelectionModel().select(fileNodeToSelectedSegment, false, true);
    }
  }
});