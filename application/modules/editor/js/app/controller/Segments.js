
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
  enableSelectOrFocus: false,
  lastRequestedRowIndex: -1,
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
      global: {
          editorViewportClosed: 'clearSegments'
      },
      controller: {
          '#metapanelcontroller': {
              watchlistRemoved: 'handleWatchlistRemoved'
          },
          '#editorcontroller': {
              saveSegment: 'saveChainStart'
          },
          '#changealikecontroller': {
              //called after currently loaded segment data is not used anymore by the save chain / change alike handling
              segmentUsageFinished: 'onSegmentUsageFinished'
          }
      },
      component: {
          '#segmentgrid headercontainer' : {
              sortchange: 'scrollGridToTop'
          },
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
              selectionchange: 'handleFileSelectionChange'
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
          },
          '#Files': {
              write: 'reloadGrid'
          }
      }
  },
  /**
   * FIXME check this method
   */
  clearSegments: function() {
      var store = this.getSegmentsStore();
      store.prefetchData.clear();
      delete store.totalCount;
      delete store.guaranteedStart;
      delete store.guaranteedEnd;
      store.removeAll();
  },
  /**
   * handler if segment store was loaded
   */
  afterStoreLoad: function() {
    var newTotal = this.getSegmentsStore().totalCount;
    this.updateFilteredCountDisplay(newTotal);
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
   * FIXME check this method
   * opened segments are saved on segment selection change in grid
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
   * reset grid filter and sort, grid will be reloaded and scrolled to top
   */
  clearSortAndFilter: function() {
    var me = this,
        store = me.getSegmentsStore();
    filters = me.getSegmentGrid().filters;
    me.resetSegmentSortIntern();
    store.removeAll();
    if(store.getFilters().length > 0){
      //reloading of the store is caused by clearFilter call
      filters.clearFilters();
    }
    else {
      store.loadPage(1);
    }
    me.scrollGridToTop();
  },
  /**
   * Toggle filtering by watch list.
   */
  watchListFilter: function() {
    var me = this, 
        filters = me.getSegmentGrid().filters.filters.items,
        filtersData = this.getSegmentGrid().filters.getFilterData(),
        btn = me.getWatchListFilterBtn();

    for (var i = 0; i < filters.length; i++)
    {
        if (filters[i].dataIndex != 'isWatched')
        {
            continue;
        }
        if (filters[i].active === true)
        {
            filters[i].setActive(false);
            btn.toggle(false);
            continue;
        }
        if (filtersData.length > 0)
        {
            Editor.MessageBox.addSuccess(me.messages.otherFiltersActive);
        }
        filters[i].setActive(true);
        filters[i].setValue(true);
        btn.toggle(true);
    }
    me.scrollGridToTop();
  },
  /**
   * removes the segment from the grid if removed from the watchlist and watchlist filter is set
   */
  handleWatchlistRemoved: function(rec) {
      var me = this, 
          btn = me.getWatchListFilterBtn();
          store = me.getSegmentsStore();
      if(!btn.pressed) {
          return
      }
      store.remove(rec);
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
   * @param {Ext.data.Store} store The store.
   * @param {Ext.util.Filter[]} filters The array of Filter objects.
   * @return void
   */
  handleFilterChange: function(store, filters) {
      var proxy = store.getProxy(),
          params = {};

      params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
      params[proxy.getSortParam()] = proxy.encodeSorters(store.getSorters().items);

      //filterFeature && me.styleResetFilterButton(filterFeature);
      this.reloadFilemap(params);
  },
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
              this.filemap = json.rows;
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
      var cls = 'activated',
          btn = this.getResetFilterBtn(),
          btnWatchList = this.getWatchListFilterBtn();
      if(filters.length > 0){
          btn.addCls(cls);
      }
      else {
          btn.removeCls(cls);
          btnWatchList.removeCls(cls);
      }
      //btn.ownerCt.doLayout();
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
      this.jumpToSegmentRowIndexAndSelect(rowindex);
  },
  /**
    * Springt ein Segment an und selektiert es, rowindex bezeichnet die
    * Position in der kompletten gefilterten und sortierten Ergebnissmenge.
    * @param {Integer} rowindex
    */
  jumpToSegmentRowIndexAndSelect: function(rowindex) {
        var me = this,
        grid = me.getSegmentGrid(),
        segment = me.getRecordByTotalIndex(rowindex);
        me.enableSelectOrFocus = true;

        me.lastRequestedRowIndex = rowindex;
        me.jumpToRecordIndex(rowindex);
        if(segment){
            me.centerSegmentInGrid(segment);
            me.selectOrFocus(segment);
        }
  },
  calcRowTop: function(row) {
        var me = this,
            grid = me.getSegmentGrid(),
            scrollingView = grid.lockable ? grid.normalGrid.view : grid.view,
            scrollTop = scrollingView.getScrollY();
            
        return Ext.fly(row).getOffsetsTo(grid)[1] - grid.el.getBorderWidth('t') + scrollTop;
  },
  centerSegmentInGrid: function(segment) {
      var me = this,
          grid = me.getSegmentGrid(),
          scrollingView = grid.lockable ? grid.normalGrid.view : grid.view,
          scrollingViewEl = scrollingView.el,
          row = scrollingView.getRow(segment),
          scrollTop = scrollingView.getScrollY(),
          viewHeight = grid.getHeight(),
          editorTop = (viewHeight / 2) + scrollTop,
          rowTop = me.calcRowTop(row),
          scrollDelta = Math.abs(editorTop - rowTop);

        if (scrollDelta != 0) {
            scrollingViewEl.scrollBy(0, scrollDelta, false);
        }
  },
  /**
   * Gibt den Segment Record zum gewünschten index in der gesamten Ergebnismenge
   * @param {Integer} rowindex
   */
  getRecordByTotalIndex: function(rowindex) {
      var me = this,
          grid = me.getSegmentGrid(),
          store = grid.store,
          range = store.getRange(rowindex, (rowindex+1));
      
      if (range && range[0])
      {
          return range[0];
      }
      return null;
  },
  /**
   * springt durch verschieben des Scrollers zum gewünschten Segment. 
   * recordindex bezeichnet den Index des Segments in der gesamten gefilterten
   *  und sortierten Ergebnissmenge (also nicht nur im aktuellen Range)
   * @param {Integer} recordindex 
   */
  jumpToRecordIndex: function(recordindex){
      var me = this,
          grid = me.getSegmentGrid(),
          table = grid.getView(),
          options = {focus: true};

      table.bufferedRenderer.scrollTo(recordindex);
  },
  /**
     * Selektiert und fokusiert das gewünschte Segment, localRowIndex bezeichnet
     * den Index des Segments im aktuell garantierten Range
     * @param {Integer} localRowIndex
     */
    selectOrFocus: function(localRowIndex){
      if(!this.enableSelectOrFocus){
        return;
      }
      this.enableSelectOrFocus = false;
      this.getSegmentGrid().selectOrFocus(localRowIndex);
  },
  /**
   * Helper: resets only the sorters, does no reload and so on
   * returns false if there are no sorters, true otherwise
   * @return {Boolean}
   */
  resetSegmentSortIntern: function() {
    if(this.getSegmentsStore().sorters.length == 0){
      return false;
    }
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
          me.scrollOrFocus(recordindex);
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
