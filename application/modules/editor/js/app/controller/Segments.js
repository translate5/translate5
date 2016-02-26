
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
  //views: ['segments.Scroller', 'segments.RowEditing', 'segments.HtmlEditor', 'segments.GridFilter'],
  views: ['segments.RowEditing', 'segments.HtmlEditor'],
  messages: {
    segmentSaved: 'Das Segment wurde gespeichert!',
    sortCleared: 'Die gewählte Sortierung der Segmente wurde zurückgesetzt!',
    segmentNotSaved: '#UT# Das zuletzt geöffnete Segment (Nr. {0}) konnte nicht gespeichert werden!',
    noSegmentToFilter: 'Kein Segment dieser Datei entspricht den Filterkriterien',
    otherFiltersActive: '#UT#ACHTUNG: Ein weiterer Filter ist gesetzt. Es ist daher möglich, dass nicht alle Segmente der Merkliste sichtbar sind'
  },
  /**
   * Cache der Zuordnung fileId => Grid Index des ersten Segments der Datei.
   */
  filemap: {},
  loadingMaskRequests: 0,
  saveChainMutex: false,
  changeAlikeOperation: null,
  defaultRowHeight: 15,
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
  },{
      ref : 'watchListFilterBtn',
      selector : '#watchListFilterBtn'
  }],
  listen: {
      controller: {
          '#Editor.$application': {
              editorViewportClosed: 'clearSegments'
          },
          '#editorcontroller': {
              saveSegment: 'saveChainStart',
              watchlistRemoved: 'handleWatchlistRemoved'
          },
          '#changealikecontroller': {
              //called after currently loaded segment data is not used anymore by the save chain / change alike handling
              segmentUsageFinished: 'onSegmentUsageFinished'
          },
          '#fileordercontroller': {
              itemsaved: 'handleFileSaved'
          }
      },
      component: {
          '#segmentgrid' : {
              afterrender: function(grid) {
                  var me = this,
                      ro = Editor.data.task && Editor.data.task.isReadOnly();
                  grid.setTitle(ro ? grid.title_readonly : grid.title);
                  me.styleResetFilterButton(grid.store.filters);
                  grid.store.on('load', me.afterStoreLoad, me);
                  grid.store.on('filterchange', me.handleFilterChange, me);
                  grid.store.on('sort', me.handleFilterChange, me);
                  me.reloadFilemap();
              },
              selectionchange: 'handleSegmentSelectionChange',
              columnhide: 'handleColumnVisibility',
              columnshow: 'handleColumnVisibility'
          },
          '#fileorderTree': {
              itemclick: 'handleFileClick'
          },
          '#clearSortAndFilterBtn': {
              click: 'clearSortAndFilter'
          },
          '#watchListFilterBtn': {
              click: 'watchListFilter'
          }
      },
      store: {
          '#AlikeSegments': {
              //called after load of change alikes to a segment
              beforeload: 'onFetchChangeAlikes'
          }
      }
  },
  /**
   * Should be called on leaving a task, to ensure that on next task store is empty.
   */
  clearSegments: function() {
      var store = this.getSegmentsStore();
      store.removeAll(false);
  },
  /**
   * handler if segment store was loaded
   */
  afterStoreLoad: function() {
      var me = this,
          store = me.getSegmentsStore(),
          newTotal = store.totalCount,
          cls = 'activated',
          btn = me.getResetFilterBtn(),
          btnWatchList = me.getWatchListFilterBtn(),
          filters = store.filters;
    
      me.updateFilteredCountDisplay(newTotal);
    
    
      if(filters.length > 0){
          btn.addCls(cls);
      }
      else {
          btn.removeCls(cls);
          btnWatchList.removeCls(cls);
    }
  },
  /**
   * Displays / Updates the segment count in the reset button
   * @param {Integer} new segment count to be displayed
   */
  updateFilteredCountDisplay: function(newTotal) {
    var btn_text = this.getSegmentGrid().item_clearSortAndFilterBtn;
    btn_text = Ext.String.format('{0} ({1})', btn_text, newTotal);
    this.getResetFilterBtn().setText(btn_text);
  },
  /**
   * FIXME check this method, should be obsolete, since RowEditor save is triggered internally by ARIA crap
   * opened segments are saved on segment selection change in grid
   */
  handleSegmentSelectionChange: function() {
      return; //FIXME deaktivert, da durch das speichern bei selection change das offene Segment auch beim Klick im Filetree gespeichert wird.
      //SEE EXT6UPD-44, must be converted to double click. This should already work, by ARIA crap
      console.log("handleSegmentSelectionChange");
      var ed = this.getSegmentGrid().editingPlugin;
      if(ed && ed.editor && ed.editing && ! ed.disableEditBySelect){
        this.saveChainStart();
      }
  },
  /**
   * FIXME check me
   * maintains the visibility of the editor on showing/hiding columns
   * @param {Ext.grid.header.Container} head
   * @param {Editor.view.segments.column.Content} col
   */
  handleColumnVisibility: function(head, col) {
      console.log("handleColumnVisibility");
      var ed = this.getSegmentGrid().editingPlugin;
      if(ed && ed.editor && ed.editor.columnToEdit == col.dataIndex) {
          ed.editor.toggleMainEditor(col.isVisible());
      }
  },
  /**
   * reset grid filter and sort, grid will be reloaded and scrolled to top
   */
  clearSortAndFilter: function() {
    var me = this,
        store = me.getSegmentsStore(),
        btn = me.getWatchListFilterBtn(),
        filters = me.getSegmentGrid().filters;
    me.clearSegmentSort();
    store.removeAll();
    if(store.getFilters().length > 0){
      //reloading of the store is caused by clearFilter call
      filters.clearFilters();
    }
    else {
      store.reload();
    }
  },
  /**
   * Toggle filtering by watch list.
   */
  watchListFilter: function() {
    var me = this,
        grid = me.getSegmentGrid(),
        gridFilters = grid.filters,
        filters = gridFilters.store.filters,
        btn = me.getWatchListFilterBtn(),
        found = false,
        otherFound = false,
        column;

    filters.each(function(filter, index, len){
        var isWatched = filter.getProperty() == 'isWatched';
        found = found || isWatched;
        otherFound = otherFound || !isWatched && filter.getDisabled() === false;
    });
    //remove watchlist filter
    if (found) {
        column = grid.columnManager.getHeaderByDataIndex('isWatched');
        if (column && column.filter && column.filter.isGridFilter) {
            column.filter.setActive(false);
        }
        return;
    } 
    //add watchlist filter
    gridFilters.addFilter({
        dataIndex: 'isWatched',
        type: 'boolean',
        value: true,
        disabled: false
    });
    // currently enabled at least one more filter:
    if (otherFound) {
        Editor.MessageBox.addSuccess(me.messages.otherFiltersActive);
    }
  },
  /**
   * removes the segment from the grid if removed from the watchlist and watchlist filter is set
   */
  handleWatchlistRemoved: function(rec) {
      var me = this, 
          btn = me.getWatchListFilterBtn();
          store = me.getSegmentsStore();
      if(!btn.pressed) {
          return;
      }
  },
  /**
   * handles the click on a file in the filetree
   * resets the sorting and jumps to the first segment of the file.
   * Shows an errormessage if no segment to the file can be shown, caused by filtering.
   * 
   * @param {Ext.tree.Panel} panel
   * @param {Editor.model.File} fileRecord
   * @param {HTMLNode} node
   */
  handleFileClick: function(panel, fileRecord, node) {
      var me = this;
      if(!fileRecord || !fileRecord.isLeaf()) {
          return;
      }

      if(me.filemap[fileRecord.get('id')] !== undefined){
          console.dir(me.filemap);
          me.resetSegmentSortForFileClick(me.filemap[fileRecord.get('id')]);
      }
      else{
          Editor.MessageBox.addSuccess(me.messages.noSegmentToFilter);
      }
  },
  /**
   * behandelt die Änderung der Grid Filter:
   * nach einem ändern der Filter muss das mapping zwischen Datei und Startsegmenten neu geladen werden.
   * @return void
   */
  handleFilterChange: function() {
      console.log("handleFilterChange", arguments);
      var me = this,
          grid = me.getSegmentGrid(),
          gridFilters = grid.filters,
          store = gridFilters.store,
          filters = store.filters,
          proxy = store.getProxy(),
          btn = me.getWatchListFilterBtn(),
          found = false,
          params = {};
          
      filters.each(function(filter, index, len){
         found = found || (filter.getProperty() == 'isWatched');
      });
      btn.toggle(found);

      params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);

      filters && me.styleResetFilterButton(filters);
      me.reloadFilemap(params);
  },
  /**
   * reloads the filemap with the given sort and filter parameters
   */
  reloadFilemap: function(params) {
      var me = this;
      params = params || {};
      console.log("reloadFilemap");
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
              me.filemap = json.rows;
          }
      });
  },
  /**
   * updates the style of the reset sort and filter button
   * @param {Ext.util.FilterCollection} filters
   * @param {Boolean} forced
   */
  styleResetFilterButton: function(filters){
      this.updateFilteredCountDisplay('...');
  },
  /**
   * resets the gird sort, jumps to the first segment of the clicked file,
   * shows a notice to the user that sorting was resetted
   * @param {Integer} rowindex
   */
  resetSegmentSortForFileClick: function(idxToScrollTo) {
      console.log("resetSegmentSortForFileClick");
      var me = this,
          grid = me.getSegmentGrid(),
          store = me.getSegmentsStore();
      
      if(! me.clearSegmentSort()){
          //no sort to reset, scroll directly
          grid.scrollTo(idxToScrollTo);
          return;
      }
      store.on('load', function(){
          grid.scrollTo(idxToScrollTo);
      }, me,{single:true});
      store.reload();
      Editor.MessageBox.addSuccess(me.messages.sortCleared);
  },
  /**
   * Helper: resets only the sorters, does no reload and so on
   * returns false if there are no sorters, true otherwise
   * @return {Boolean}
   */
  clearSegmentSort: function() {
      var me = this, 
          sorters = me.getSegmentsStore().sorters;
    if(sorters.length == 0){
      return false;
    }
    sorters.clear();
    return true;
  },
  
  /**
   * Reloads the segment store and resets the segment sorting after updating the file tree order
   */
  handleFileSaved: function(movedItem){
      this.clearSegmentSort();
      this.getSegmentsStore().reload();
      this.handleFilterChange();
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
   * binds the change alike load operation to the save chain
   * @param {Editor.store.AlikeSegments} store
   * @param {Ext.data.Operation} op
   */
  onFetchChangeAlikes: function(store, op) {
      this.changeAlikeOperation = op;
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
    console.log("saveChainStart");
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
      
      //FIXME check if this callback is called!
      //add a callback to complete this completeEdit call after successfull load of the alike segments
      op.handleReadAfterSave = function(){
          console.log("called op.handleReadAfterSave");
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
          grid = me.getSegmentGrid(),
          store = grid.store,
          ed = grid.editingPlugin,
          record = ed.context.record,
          recordindex = store.indexOf(record);
      
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
