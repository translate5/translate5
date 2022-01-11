
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
 * saveChainStart (accepts config with one time bindings to events "segmentEditSaved" and "segmentUsageFinished")
 * saveChainCheckAlikes
 * saveChainSave (fires event "afterSaveCall", returning false in the event prevents next step)
 * saveChainSaveCallback (fires event "saveComplete", returning false in the event prevents next step)
 * saveChainEnd (fires event "segmentEditSaved")
 * 
 * additional events:
 * segmentUsageFinished: called once after change alike handling or on segmentEditSaved, if bound by config not called in case of an error on completing the editor
 * segmentEditSaved: called at the very end of the save process, if bound by config not called in case of an error on completing the editor
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
  views: ['segments.RowEditing', 'segments.HtmlEditor', 'ToolTip'],
  messages: {
    sortCleared: 'Die gewählte Sortierung der Segmente wurde zurückgesetzt!',
    segmentNotSaved: '#UT# Das zuletzt geöffnete Segment (Nr. {0}) konnte nicht gespeichert werden!',
    noSegmentToFilter: 'Kein Segment dieser Datei entspricht den Filterkriterien',
    otherFiltersActive: '#UT#ACHTUNG: Ein weiterer Filter ist gesetzt. Es ist daher möglich, dass nicht alle Segmente der Lesezeichenliste sichtbar sind'
  },
  /**
   * Cache der Zuordnung fileId => Grid Index des ersten Segments der Datei.
   */
  filemap: {},
  lastFileMapParams: null,
  loadingMaskRequests: 0,
  saveChainMutex: false,
  changeAlikeOperation: null,
  isQualityFiltered: false,
  defaultRowHeight: 15,
  refs : [{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
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
  },{
      ref: 'filterBtnRepeated',
      selector: '#filterBtnRepeated'
  },{
	  ref:'segmentsToolbar',
	  selector:'segmentsToolbar'
  },{
      ref:'qualityFilterPanel',
      selector:'qualityFilterPanel'
  }],
  listen: {
      controller: {
          '#Editor.$application': {
              editorViewportClosed: 'clearSegments',
        	  editorViewportOpened: 'onOpenEditorViewport'
          },
          '#Editor': {
              saveSegment: 'saveChainStart',
              watchlistRemoved: 'handleWatchlistRemoved'
          },
          '#ChangeAlike': {
              //called after currently loaded segment data is not used anymore by the save chain / change alike handling
              segmentUsageFinished: 'onSegmentUsageFinished',
              afterUpdateChangeAlike: 'updateSiblingsMetaCache',
              alikesSaveSuccess:'onAlikesSaveSuccessHandler'
          },
          '#Fileorder': {
              itemsaved: 'handleFileSaved'
          },
          'qualityFilterPanel': {
              qualityFilterChanged: 'onQualityFilterChanged'
          }
      },
      component: {
          '#segmentgrid' : {
              afterrender: 'gridAfterRender',
              columnhide: 'handleColumnVisibility',
              columnshow: 'handleColumnVisibility',
              canceledit:'handleCancelEdit'
          },
          '#fileorderTree': {
              itemclick: 'handleFileClick'
          },
          '#clearSortAndFilterBtn': {
              click: 'clearSortAndFilter'
          },
          'segmentsToolbar #watchListFilterBtn': {
              click: 'watchListFilter'
          },
          'segmentsToolbar #filterBtnRepeated': {
              click: 'repeatedFilter'
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
      this.fileMap = {};
      this.lastFileMapParams = null;
  },
  
  onOpenEditorViewport: function(app, task) {
      this.updateSegmentFinishCountViewModel(task);
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
          filters = store.filters;
    
      me.updateFilteredCountDisplay(newTotal);

      if(filters.length > 0 || me.isQualityFiltered){
          btn.addCls(cls);
      } else {
          btn.removeCls(cls);
      }
  },
  /**
   * Displays / Updates the segment count in the reset button
   * @param {Integer} new segment count to be displayed
   */
  updateFilteredCountDisplay: function(newTotal) {
      var btn_text = this.getSegmentsToolbar().item_clearSortAndFilterBtn;
      btn_text = Ext.String.format('{0} ({1})', btn_text, newTotal);
      this.getResetFilterBtn().setText(btn_text);
  },
  /**
   * 
   */
  gridAfterRender: function(grid) {
      var me = this,
          task = Editor.data.task,
          title = Ext.String.ellipsis(task.get('taskName'), 60),
          store = grid.store,
          repeated = grid.down('isRepeatedColumn'),
          initialGridFilters = Editor.data.initialGridFilters;
      
      grid.getHeader().getTitle().getEl().set({
          'data-qtip': task.getTaskName()
      });
      
      if(grid.lookupViewModel().get('taskIsReadonly')) {
          title = title + grid.title_readonly;
      }

      if(task.isUnconfirmed()) {
          title = title + grid.title_addition_unconfirmed;
      }
      
      if(!task.get('defaultSegmentLayout')) {
          repeated && repeated.hide();
      }
      
      initialGridFilters = initialGridFilters && initialGridFilters.segmentgrid;

      grid.setTitle(title);
      me.styleResetFilterButton(grid.store.filters);
      grid.store.on('load', me.afterStoreLoad, me);
      grid.store.on('filterchange', me.handleFilterChange, me);
      grid.store.on('sort', me.handleFilterChange, me);

      // add initial filters for this grid
      if(initialGridFilters && Ext.isArray(initialGridFilters)) {
          Ext.Array.each(initialGridFilters, function(item){
              grid.down('gridcolumn[dataIndex="'+item.dataIndex+'"]').show();
          });
          grid.filters.addFilters(initialGridFilters);
          me.reloadFilemap(store.getFilterParams());
      }
      else {
        //reset suppressNextFilter to reenable normal filtering (suppressNextFilter needed for initialGridFilters)
        store.suppressNextFilter = false;
        store.load();
        me.reloadFilemap();
      }
  },
  /**
   * maintains the visibility of the editor on showing/hiding columns
   * @param {Ext.grid.header.Container} head
   * @param {Editor.view.segments.column.Content} col
   */
  handleColumnVisibility: function(head, col) {
      var grid = this.getSegmentGrid(),
          ed = grid && grid.editingPlugin;
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
        grid = me.getSegmentGrid(),
        filters = me.getSegmentGrid().filters,
        qualityPanel = me.getQualityFilterPanel();
    grid.selModel.deselectAll();
    me.clearSegmentSort();
    // reset the quality filter and uncheck any checked qualities
    store.setQualityFilter('');
    me.isQualityFiltered = false;
    if(qualityPanel){
        qualityPanel.uncheckAll();
    }
    store.removeAll();
    if(store.getFilters().length > 0){
      //reloading of the store is caused by clearFilter call
      filters.clearFilters();
    } else {
      store.reload();
    }
  },
  /**
   * Toggle filtering by watch list.
   */
  watchListFilter: function (btn) {
      var me = this,
          grid = me.getSegmentGrid(),
          gridFilters = grid.filters,
          filters = gridFilters.store.filters,
          otherFound = false,
          column = grid.down('[dataIndex=isWatched]');
          
      filters.each(function (filter, index, len) {
          var isWatched = filter.getProperty() == 'isWatched';
          otherFound = otherFound || !isWatched && filter.getDisabled() === false;
      });
      
      if(column && column.filter) {
          if(btn.pressed) {
              column.filter.filter.setValue(true);
              column.filter.setActive(true);
          }
          else {
              column.filter.setActive(false);
          }
      }
      
      // currently enabled at least one more filter:
      if (btn.pressed && otherFound) {
          Editor.MessageBox.addSuccess(me.messages.otherFiltersActive);
      }
  },
  
  repeatedFilter: function (btn) {
      var me = this,
          grid = me.getSegmentGrid(),
          column = grid.down('[dataIndex=isRepeated]');
      
      if(column && column.filter) {
          if(btn.pressed) {
              column.filter.setActive(true);
              //1,2,3 contain in filter source only (1), target only (2), and segments repeatead in both (3)
              column.filter.filter.setValue([1,2,3]);
          }
          else {
              column.filter.setActive(false);
          }
      }
  },
  /**
   * removes the segment from the grid if removed from the watchlist and watchlist filter is set
   */
  handleWatchlistRemoved: function(rec) {
      var me = this, 
          btn = me.getWatchListFilterBtn();
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
  handleFilterChange: function () {
      var me = this,
          grid = me.getSegmentGrid(),
          gridFilters = grid.filters,
          store = gridFilters.store,
          filters = store.filters,
          proxy = store.getProxy(),
          tbar = me.getSegmentsToolbar(),
          watchListFound = false,
          isRepeatedFound = false,
          params = {};

      filters.each(function (filter, index, len) {
          watchListFound = watchListFound || filter.getProperty() == 'isWatched';
          isRepeatedFound = isRepeatedFound || filter.getProperty() == 'isRepeated';
      });
      
      tbar.down('#watchListFilterBtn').toggle(watchListFound, false);
      tbar.down('#filterBtnRepeated').toggle(isRepeatedFound, false);

      params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);

      filters && me.styleResetFilterButton(filters);
      me.reloadFilemap(params);
  },
  /**
   * reloads the filemap with the given sort and filter parameters
   */
  reloadFilemap: function(params) {
      var me = this,
          encoded;
      params = params || {filter: "[]"};
      encoded = Ext.Object.toQueryString(params);
      if(me.lastFileMapParams === encoded) {
        //we don't need to fetch the filemap again, since filters did not change
        return;
      }
      me.lastFileMapParams = encoded;
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
      var me = this,
          store = me.getSegmentsStore();
      me.clearSegmentSort();
      store.removeAll();
      store.reload();
      me.lastFileMapParams = null; //set to null to force reload
      me.handleFilterChange();
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
   *    segmentEditSaved: callback method called ONCE after finishing the save chain
   *    segmentUsageFinished: callback method called ONCE after finishing the usage of the currently loaded segment
   *    scope: the scope of the given callbacks 
   */
  saveChainStart: function(config) {
      var me = this,
          ed = me.getSegmentGrid().editingPlugin,
          record;
      
      config = config || {};
          
      // No Editor was started.
      if(!ed.editing || ! ed.context){
          return;
      }

      //register / add given callbacks
      if(me.saveChainMutex) {
          me.addLoadMask();
          return;
      }
      
      me.saveChainMutex = true;
      record = ed.context.record;
      ed.completeEdit();
      //if completeEdit fails, the plugin remains editing
      if(ed.editing) {
          //TODO the below by config bound handlers can also be bound elsewhere and get no information about success or failed chainend!
          me.saveChainEnd(record); 
          return;
      }
      
      //update the sibling segments metaCache.siblingData from the currentSegment
      //same again after segment was saved successfully

      //the following handlers should only be bound if no 
      if(config.segmentEditSaved && Ext.isFunction(config.segmentEditSaved)) {
          me.on('segmentEditSaved', config.segmentEditSaved, (config.scope || me), { single: true });
      }
      if(config.segmentUsageFinished && Ext.isFunction(config.segmentUsageFinished)) {
          me.on('segmentUsageFinished', config.segmentUsageFinished, (config.scope || me), {single: true});
      }
      me.saveChainCheckAlikes(record); //NEXT step in save chain
  },
  /**
   * checks if changeAlikes are already fetched, 
   * if GET alikes call is still in progress, bind next step to the running AJAX Operation (handleReadAfterSave)
   * @see Editor.controller.ChangeAlike 
   * 
   * next step: saveChainSave
   * 
   * @param {Editor.model.Segment} record record to be saved
   */
  saveChainCheckAlikes: function(record) {
      var me = this,
      op = me.changeAlikeOperation;
      if(!op || !op.isRunning()) {
          me.saveChainSave(record); //NEXT step in save chain
          return;
      }
      me.addLoadMask();
      
      //add a callback to complete this completeEdit call after successfull load of the alike segments
      op.handleReadAfterSave = function(){
          me.saveChainSave(record);   //NEXT step in save chain
          me.delLoadMask();
      };
  },
  /**
   * saves the edited segment to the server
   * next step if nothing to save: saveChainEnd
   * next step after save: saveChainSaveCallback
   * 
   * fires the "afterSaveCall" event, the final step saveChainEnd and the record are provided to the event as parameter.
   * 
   * @param {Editor.model.Segment} record record to be saved
   */
  saveChainSave: function(record) {
      var me = this,
          grid = me.getSegmentGrid(),
          ed = grid.editingPlugin;
      
      //its possible that the editor is already destroyed by editorDomCleanUp, then the save process wouldn't work.
      if(!ed || !ed.editor){
          Editor.MessageBox.addError(Ext.String.format(me.messages.segmentNotSaved, record.get('segmentNrInTask')));
          me.saveChainEnd(record);
          return;
      }
      
      //this check also prevents saving if RowEditor.completeEdit was returning false!
      if(! record.dirty) {
          me.saveChainEnd(record);
          return;
      }
      
      record.save({
          scope: me,
          //prevent default ServerException handling
          preventDefaultHandler: true,
          //callback: me.saveChainSaveCallback //NEXT step in save chain
          callback: function(record, operation, success){

              // Call initial callback function
              me.saveChainSaveCallback(record, operation, success);

              // update length stuff in siblings of store
              me.updateSiblingsMetaCache(record);

              //fire event to process things after save call is started, like change alike handling
              //parameters are the callback to the final save chain call,
              //for later usage in ChangeAlike Handling and the saved record
              me.fireEvent('afterSaveCall', function(){
                  me.saveChainEnd(record);
              }, record);
          }
      });
      me.saveIsRunning = true;
      
      // update length stuff in siblings of store
      /*me.updateSiblingsMetaCache(record);

      //fire event to process things after save call is started, like change alike handling
      //parameters are the callback to the final save chain call,
      //for later usage in ChangeAlike Handling and the saved record
      me.fireEvent('afterSaveCall', function(){
          me.saveChainEnd(record);
      }, record);*/
  },
  /**
   * callback of saving a segment record
   * next step: saveChainEnd
   * 
   * fires event "saveComplete". Returning false in the event prevents calling next step.
   */
  saveChainSaveCallback: function(record, operation, success) {
      var me = this,
          errorHandler;
      me.saveIsRunning = false;
      if(!operation.success){
          errorHandler = Editor.app.getController('ServerException');
          errorHandler.handleCallback.apply(errorHandler, arguments);
          me.saveChainEnd(record);
          return;
      }
      me.updateSiblingsMetaCache(record);
      
      //show other messages on the segment:
      Editor.MessageBox.addByOperation(operation);
      
      //FIXME 
      //this event is triggered because we are not able to listen(use) the 'saveComplete' event
      //the 'saveComplete' event is subscribed in 'ChangeAlike' controller, and it is disabled if the manual processing is disabled
      //we are not able to use the event listener priority because of the extjs bug : https://www.sencha.com/forum/showthread.php?305085-Observable-listener-priority-does-not-work
      //this bug also exist in extjs 6.2.0
      me.fireEvent('beforeSaveCall', record);
      
      //get the segmentFinishCount parameter from the response
      var response=operation.getResponse(),
      	  decoded=response.responseText && Ext.JSON.decode(response.responseText),
		  segmentFinishCount=decoded && decoded.segmentFinishCount;
      
      //TODO: this should be implemented with websokets(when ready)
      me.updateSegmentFinishCountViewModel(Ext.Number.from(segmentFinishCount,0));
      
      //invoking change alike handling:
      if(me.fireEvent('saveComplete')){
          me.saveChainEnd(record); //NEXT step in save chain
      }
  },
  /**
   * Updates the siblings metaCache of the given record
   * @param {Editor.models.Segment} records
   */
  updateSiblingsMetaCache: function(records) {
      if(!Ext.isArray(records)) {
          records = [records];
      }
      Ext.Array.each(records, function(rec){
          var sourceId = rec.get('id'), 
              meta = rec.get('metaCache');
          if(!meta || !meta.siblingData || !meta.siblingData[sourceId]) {
              return;
          }
          //clone the sources data inside the target record:
          Ext.Object.each(meta.siblingData, function(targetId, data) {
              targetId = parseInt(targetId); //targetId is coming from the object key, which is string.
              if(targetId == sourceId) {
                  //don't update myself again
                  return;
              }
              var targetRec = rec.store.getById(targetId),
                  targetMeta = targetRec && targetRec.get('metaCache');
              if(!targetMeta || !targetMeta.siblingData || !targetMeta.siblingData[sourceId]) {
                  return;
              }
              targetMeta.siblingData[sourceId] = Ext.clone(meta.siblingData[sourceId]);
              targetRec.set('metaCache', targetMeta);
              targetRec.commit();
          });
      });
  },
  
  /***
   * On save alike sucess handler
   */
  onAlikesSaveSuccessHandler:function(data){
	  var value=Ext.Number.from(data.segmentFinishCount,0);
	  this.updateSegmentFinishCountViewModel(value);
  },
  
  /**
   * End of the save chain.
   * fires event "segmentEditSaved".
   */
  saveChainEnd: function(record) {
      var me = this;
      me.delLoadMask();
      me.saveChainMutex = false;
      me.onSegmentUsageFinished();
      // crucial: reset the trigger flag indicating a original target update when save chain ended
      record.wasOriginalTargetUpdated = false;
      me.fireEvent('segmentEditSaved', me, record);
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
  },
  
  /**
   * Update the segmentFinishCount segments grid view model.
   */
  updateSegmentFinishCountViewModel:function(record){
	  var me=this,
	  	grid = me.getSegmentGrid(),
	  	vm = grid.getViewModel(),
	  	value = Ext.isNumber(record) ? record : record.get('segmentFinishCount');
	  vm.set('segmentFinishCount',value);
  },
  /**
   * Handles the cancel edit of the segment grid
   * Some View Controllers like qualities FilterPanelController can not listen to the #segmentgrid directly but can listen to this controller (Why???) so we forward the event
   * Generally it would be great to have all events regarding segment editing being dispatched from one source which centralizes them
   */
  handleCancelEdit(){
      this.fireEvent('segmentEditCanceled', this);
  },
  /**
   * Listens to the filter panel controller and delegates it to our store and changes the view if the stored filter changed
   */
  onQualityFilterChanged: function(filter){
      var store = this.getSegmentsStore();
      // the store checks if the filter actually changed and we adjut the view only if requested
      if(store.setQualityFilter(filter)){
          this.isQualityFiltered = (filter && filter != '');
          store.removeAll();
          store.reload();
      }
  }
});
