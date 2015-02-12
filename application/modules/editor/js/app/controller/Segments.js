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
 * Editor.controller.Segments kapselt die Funktionalität des Grids
 * @class Editor.controller.Segments
 * @extends Ext.app.Controller
 * 
 * SaveChain: 
 * Saving a segment triggers several asynchronous actions, which are depending on each other. 
 * To preserve the order of this actions, the complete save process is implemented as a set of functions, 
 * named as "saveChain". The next Step / Function in the save chain is either called directly or used 
 * as callback method if the previous method has used AJAX.
 * 
 * The way through the chain depends on the actual application state 
 * (for example change alike settings, segment specific, etc).
 * 
 * Starting the save chain is done calling "saveChainStart".
 * 
 * The following Methods belongs to the save chain:
 * saveChainStart (accepts config with one time bindings to events "chainEnd" and "segmentUsageFinished")
 * saveChainCheckAlikes
 * saveChainSave (fires event "afterSaveCall", returning false in the event prevents next step)
 * saveChainSaveCallback (fires event "saveComplete", returning false in the event prevents next step)
 * saveChainEnd (fires event "chainEnd")
 * 
 * additional events:
 * segmentUsageFinished: called once after change alike handling or on chainEnd, if bound by config not called in case of an error on completing the editor
 * chainEnd: called at the very end of the save process, if bound by config not called in case of an error on completing the editor
 * 
 * The ChangeAlike Controller hooks into the save chain, @see Editor.controller.ChangeAlike
 * 
 * They are called in this order.
 * Additional Infos see on each method comment. 
 */
Ext.define('Editor.controller.Segments', {
  extend : 'Ext.app.Controller',
  stores: ['Segments'],
  views: ['segments.Scroller', 'segments.RowEditing', 'segments.HtmlEditor', 'segments.GridFilter'],
  messages: {
    segmentSaved: 'Das Segment wurde gespeichert!',
    sortCleared: 'Die gewählte Sortierung der Segmente wurde zurückgesetzt!',
    segmentNotSaved: '#UT# Das zuletzt geöffnete Segment (Nr. {0}) konnte nicht gespeichert werden!',
    noSegmentToFilter: 'Kein Segment dieser Datei entspricht den Filterkriterien'
  },
  /**
   * Cache der Zuordnung fileId => Grid Index des ersten Segments der Datei.
   */
  filemap: {},
  loadingMaskRequests: 0,
  saveChainMutex: false,
  changeAlikeOperation: null,
  refs : [{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  },{
    ref : 'segmentPager',
    selector : '#segmentgrid editorgridscroller'
  },{
    ref : 'fileTree',
    selector : '#fileorderTree'
  },{
    ref : 'viewport',
    selector : 'viewport'
  },{
      ref : 'resetFilterBtn',
      selector : '#clearSortAndFilterBtn'
  }],
  init : function() {
      var me = this, 
          mpCtrl = me.application.getController('MetaPanel'),
          caCtrl = me.application.getController('ChangeAlike');
      mpCtrl.on('saveSegment', me.saveChainStart, me);
      //called after load of cahnge alikes to a segment
      caCtrl.on('fetchChangeAlikes', me.onFetchChangeAlikes, me);
      //called after currently loaded segment data is not used anymore by the save chain / change alike handling
      caCtrl.on('segmentUsageFinished', me.onSegmentUsageFinished, me);
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following event bindings 
      me.getStore('Files').on('write', me.reloadGrid, me);
      Editor.app.on('editorViewportClosed', me.clearSegments, me);

      me.control({
      '#segmentgrid .headercontainer' : {
        sortchange: me.scrollGridToTop
      },
      '#segmentgrid .gridview' : {
        beforerefresh: me.editorDomCleanUp
      },
      '#segmentgrid' : {
          afterrender: function(grid) {
              var me = this,
                  ro = Editor.data.task && Editor.data.task.isReadOnly();
              grid.setTitle(ro ? grid.title_readonly : grid.title);
              me.styleResetFilterButton(grid.filters);
              
              //moved the store handler into after grid render, because of 
              //the fluent reconfiguration of the model and late instanciation of the store.
              //@todo should be replaced with Event Domains after update to ExtJS >4.2
              me.getSegmentsStore().on('load', me.invalidatePager, me);
              me.getSegmentsStore().on('load', me.refreshGridView, me);
          },
        selectionchange: me.handleSegmentSelectionChange,
        columnhide: me.handleColumnVisibility,
        columnshow: me.handleColumnVisibility,
        filterupdate: me.handleFilterChange
      },
      '#fileorderTree': {
        selectionchange: me.handleFileSelectionChange
      },
      '#clearSortAndFilterBtn': {
        click: me.clearSortAndFilter
      }
    });
  },
  loadSegments: function() {
      this.handleFilterChange(); //load filemap
      //initiales Laden des Stores:
      this.getSegmentsStore().guaranteeRange(0, 199);
  },
  clearSegments: function() {
      var store = this.getSegmentsStore();
      store.prefetchData.clear();
      delete store.totalCount;
      delete store.guaranteedStart;
      delete store.guaranteedEnd;
      store.removeAll();
  },
  refreshGridView: function() {
    this.getSegmentGrid().getView().refresh();
  },  
  /**
   * geöffnete Segmente werden bei der Wahl eines anderen Segments gespeichert
   */
  handleSegmentSelectionChange: function() {
      var ed = this.getSegmentGrid().editingPlugin;
      if(ed && ed.editor && ed.openedRecord && ! ed.disableEditBySelect){
        this.saveChainStart();
      }
  },
  /**
   * maintains the visibility of the editor on showing/hiding columns
   * @param {Ext.grid.header.Container} head
   * @param {Editor.view.segments.column.Content} col
   */
  handleColumnVisibility: function(head, col) {
      var ed = this.getSegmentGrid().editingPlugin;
      if(ed && ed.editor && ed.editor.columnToEdit == col.dataIndex) {
          ed.editor.toggleMainEditor(col.isVisible());
      }
  },
  /**
   * zurücksetzten der Filter und Sortierungen, Grid wird neu geladen, und zum ersten Segment gescrollt.
   */
  clearSortAndFilter: function() {
    var me = this, 
    filters = this.getSegmentGrid().filters;
    me.resetSegmentSortIntern();
    me.getSegmentsStore().prefetchData.clear();
    if(filters.getFilterData().length > 0){
      //das Neuladen des Stores erfolgt hier durch den clearFilter.
      me.getSegmentGrid().filters.clearFilters();
    }
    else {
      me.getSegmentsStore().loadPage(1);
    }
    me.scrollGridToTop();
  },
  /**
   * behandelt die Selektion von Dateien im Dateibaum
   * setzt die Sortierung zurück, springt zum ersten Segment der Datei. Zeigt Fehlermeldung wenn aufgrund des Filters kein passendes Segment vorhanden ist.
   * @param {Ext.selection.Model} sm
   * @param {Editor.model.Segment[]} selectedRecords
   */
  handleFileSelectionChange: function(sm, selectedRecords) {
    if(selectedRecords.length == 0 || !selectedRecords[0].isLeaf()) {
      return;
    }
    var me = this,
    selectedFile = selectedRecords[0];
    if(selectedFile && me.filemap[selectedFile.get('id')] !== undefined){
      this.resetSegmentSortForFileClick(me.filemap[selectedFile.get('id')]);
    }
    else{
      Editor.MessageBox.addSuccess(me.messages.noSegmentToFilter);
    }
  },
  /**
   * behandelt die Änderung der Grid Filter:
   * nach einem ändern der Filter muss das mapping zwischen Datei und Startsegmenten neu geladen werden.
   * @param {Ext.ux.grid.FiltersFeature} filterFeature
   * @return void
   */
  handleFilterChange: function(filterFeature) {
      var me = this,
          params = filterFeature ? filterFeature.buildQuery(filterFeature.getFilterData()) : '';
          
      me.getSegmentsStore().prefetchData.clear();
      me.invalidatePagerOnNextLoad();
      filterFeature && me.styleResetFilterButton(filterFeature);
      Ext.Ajax.request({
          url: Editor.data.pathToRunDir+'/editor/segment/filemap',
          method: 'get',
          params: params,
          scope: me,
          success: function(response){
              if(response.status != 200){
                  return;
              }
              var json = Ext.decode(response.responseText);
              this.filemap = json.rows;
          }
      });
  },
  /**
   * updates the style of the reset sort and filter button
   * @param {Editor.view.segments.GridFilter} filterFeature
   * @param {Boolean} forced
   */
  styleResetFilterButton: function(filterFeature){
      var cls = 'activated',
          btn = this.getResetFilterBtn(),
          initialActive = filterFeature.filters.length == 0 && filterFeature.initialActive.length > 0;
      if(initialActive || filterFeature.getFilterData().length > 0){
          btn.addCls(cls);
      }
      else {
          btn.removeCls(cls);
      }
      btn.ownerCt.doLayout();
  },
  invalidatePagerOnNextLoad: function() {
    this._invalidatePagerOnNextLoad = true;
  },
  /**
   * berechnet die Höhe des Scrollers neu
   */
  invalidatePager: function() {
    if(this._invalidatePagerOnNextLoad && this.getSegmentPager()){
      this._invalidatePagerOnNextLoad = false;
      this.getSegmentPager().invalidate();
    }
  },
  /**
   * setzt die Sortierung zurück, springt zum ersten Segment der Datei. Informiert den Benutzer, dass Sortierung zurückgesetzt wurde
   * @param {Integer} rowindex
   */
  resetSegmentSortForFileClick: function(rowindex) {
    if(! this.resetSegmentSortIntern()){
      //keine Sortierung war gesetzt, springe direkt
    	this.scrollOrFocus(rowindex);
      return;
    }
    this.getSegmentsStore().prefetchData.clear();
    //anspringen des Zielsegments über einmaligen load Handler:
      this.getSegmentsStore().on('load', function(){
          this.scrollOrFocus(rowindex);
      }, this,{single:true});
    this.getSegmentsStore().loadPage(1);
    this.scrollGridToTop();
    Editor.MessageBox.addSuccess(this.messages.sortCleared);
  },
  scrollOrFocus: function(rowindex) {
    if(this.getSegmentPager()) {
      this.getSegmentPager().jumpToSegmentRowIndexAndSelect(rowindex);
      return;
    }
    this.getSegmentGrid().selectOrFocus(rowindex);
  },
  /**
   * Hilfsfunktion: setzt lediglich den Sorter zurück, führt kein reload etc. pp. aus.
   * gibt false zurück wenn es keine Sorter zum zurücksetzen gibt, true andernfalls
   * @return boolean
   */
  resetSegmentSortIntern: function() {
    if(this.getSegmentsStore().sorters.length == 0){
      return false;
    }
    this.getSegmentGrid().headerCt.clearOtherSortStates(null, true);
    this.getSegmentsStore().sorters.clear();
    return true;
  },
  scrollGridToTop: function() {
    this.getSegmentPager() && this.getSegmentPager().setScrollTop(0);
  },
  reloadGrid: function(){
    this.resetSegmentSortIntern();
    this.scrollGridToTop();
    this.getSegmentsStore().loadPage(1);
    this.handleFilterChange(this.getSegmentGrid().filters);
  },
  /**
   * Hilfsfunktion um beim Schließen des Browserfensters das letzte Segment anzuzeigen
   * @returns boolean|string
   */
  getLastSegmentShortInfo: function() {
    var grid = this.getSegmentGrid();
    if(grid && grid.editingPlugin.editor) {
      return this.getSegmentGrid().editingPlugin.editor.lastSegmentShortInfo;
    }
    return false;
  },
  /**
   * Fix für den Editor Bug, bei dem die inneren Editor Styles verschwinden. Die DOM Struktur des Editors wird zurückgesetzt, wenn sich die DOM Struktur des Grids verändert.
   */
  editorDomCleanUp: function() {
    if(this.getSegmentGrid().editingPlugin){
      this.getSegmentGrid().editingPlugin.editorDomCleanUp();
    }
  },
  /**
   * binds the change alike load operation to the save chain
   * @param {Ext.data.Operation} operation
   */
  onFetchChangeAlikes: function(operation) {
      this.changeAlikeOperation = operation;
  },
  /**
   * Method is called on chain end / and by change alikes
   * fires event segmentUsageFinished
   */
  onSegmentUsageFinished: function() {
      this.fireEvent('segmentUsageFinished', this);
  },
  /**
   * starts the save process (general Infos in class comment)
   * blocks with a loading mask if another savechain is running
   * next step in chain: saveChainCheckAlikes
   * @param {Object} config possible values are: 
   *    chainEnd: callback method called ONCE after finishing the save chain
   *    segmentUsageFinished: callback method called ONCE after finishing the usage of the currently loaded segment
   *    scope: the scope of the given callbacks 
   */
  saveChainStart: function(config) {
      var me = this,
          ed = me.getSegmentGrid().editingPlugin;
      config = config || {};
          
      // No Editor was started.
      if(! ed.context){
          return;
      }

      //register / add given callbacks
      if(me.saveChainMutex) {
          me.addLoadMask();
          return;
      }
      
      me.saveChainMutex = true;
      ed.completeEdit();
      //if completeEdit fails, the plugin remains editing and the record is not dirty.
      if(ed.editing && !ed.context.record.dirty) {
          //TODO the below by config bound handlers can also be bound elsewhere and get no information about success or failed chainend!
          me.saveChainEnd(); 
          return;
      }

      //the following handlers should only be bound if no 
      if(config.chainEnd && Ext.isFunction(config.chainEnd)) {
          me.on('chainEnd', config.chainEnd, (config.scope || me), {single: true});
      }
      if(config.segmentUsageFinished && Ext.isFunction(config.segmentUsageFinished)) {
          me.on('segmentUsageFinished', config.segmentUsageFinished, (config.scope || me), {single: true});
      }
      me.saveChainCheckAlikes(); //NEXT step in save chain
  },
  /**
   * checks if changeAlikes are already fetched, 
   * if GET alikes call is still in progress, bind next step to the running AJAX Operation (handleReadAfterSave)
   * @see Editor.controller.ChangeAlike 
   * 
   * next step: saveChainSave
   */
  saveChainCheckAlikes: function() {
      var me = this,
      op = me.changeAlikeOperation;
      if(!op || !op.isRunning()) {
          me.saveChainSave(); //NEXT step in save chain
          return;
      }
      me.addLoadMask();
      
      //add a callback to complete this completeEdit call after successfull load of the alike segments
      op.handleReadAfterSave = function(){
          me.saveChainSave();   //NEXT step in save chain
          me.delLoadMask();
      };
  },
  /**
   * saves the edited segment to the server
   * next step if nothing to save: saveChainEnd
   * next step after save: saveChainSaveCallback
   * 
   * fires the "afterSaveCall" event, the final step saveChainEnd is provided to the event as parameter.
   */
  saveChainSave: function() {
      var me = this,
          ed = me.getSegmentGrid().editingPlugin,
          record = ed.context.record;
      
      //its possible that the editor is already destroyed by editorDomCleanUp, then the save process wouldn't work.
      if(!ed || !ed.editor){
          Editor.MessageBox.addError(Ext.String.format(me.messages.segmentNotSaved,record.get('segmentNrInTask')));
          me.saveChainEnd();
          return;
      }
      
      //this check also prevents saving if RowEditor.completeEdit was returning false!
      if(! record.dirty) {
          me.saveChainEnd();
          return;
      }
      
      record.save({
          scope: me,
          callback: me.saveChainSaveCallback //NEXT step in save chain
      });
      me.saveIsRunning = true;
      //fire event to process things after save call is started, like change alike handling
      //parameter is the callback to the final save chain call, for later usage in ChangeAlike Handling
      me.fireEvent('afterSaveCall', function(){
          me.saveChainEnd();
      });
  },
  /**
   * callback of saving a segment record
   * next step: saveChainEnd
   * 
   * fires event "saveComplete". Returning false in the event prevents calling next step.
   */
  saveChainSaveCallback: function(records, operation, success) {
      var me = this,
          errorHandler;
      me.saveIsRunning = false;
      if(!operation.success){
          errorHandler = Editor.app.getController('ServerException');
          errorHandler.handleCallback.apply(errorHandler, arguments);
          me.saveChainEnd();
          return;
      }
      //show other messages on the segment:
      Editor.MessageBox.addByOperation(operation);
      //show save segment success message 
      Editor.MessageBox.addSuccess(me.messages.segmentSaved);
      //invoking change alike handling:
      if(me.fireEvent('saveComplete')){
          me.saveChainEnd(); //NEXT step in save chain
      }
  },
  /**
   * End of the save chain.
   * fires event "chainEnd".
   */
  saveChainEnd: function() {
      var me = this;
      me.delLoadMask();
      me.saveChainMutex = false;
      me.onSegmentUsageFinished();
      me.fireEvent('chainEnd', me);
  },
  addLoadMask: function() {
      var me = this;
      if(me.loadingMaskRequests == 0) {
          me.getViewport().setLoading(true);
      }
      me.loadingMaskRequests++;
  },
  delLoadMask: function() {
      var me = this;
      if(me.loadingMaskRequests > 0) {
          me.loadingMaskRequests--;
      }
      if(me.loadingMaskRequests == 0) {
          me.getViewport().setLoading(false);
      }
  } 
});
