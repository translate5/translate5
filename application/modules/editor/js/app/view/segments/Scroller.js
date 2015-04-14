
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
 * @class Editor.view.segments.Scroller
 * @extends Ext.grid.PagingScroller
 * 
 * Erweitert bzw. passt den Orginal Endless Scroller an die lokalen Gegebenheiten an:
 * - Das Orginal kann nur mit Zeilen gleicher Höhe umgehen
 * - 
 */
Ext.define('Editor.view.segments.Scroller', {
    extend: 'Ext.grid.PagingScroller',
    alias: 'widget.editorgridscroller',
    lastRequestedRowIndex: -1,
    percentageFromEdge: 0.4,
    scrollToLoadBuffer: 200,
    chunkSize: 200,
    //rowHeight wird als fester Wert gesetzt
    rowHeight: 15,
    snapIncrement: 10,
    visibleStart: 1,
    syncScroll: false,
    enableSelectOrFocus: false,
    notPreFetched: true,
    initComponent: function() {
      this.on({
        beforerender: this.hijackFocusRow,
        single: true,
        scope: this
      });
      this.callParent(arguments);
    },
    /**
     * hijackFocusRow ersetzt die focusRow Methode des Grid Table Views.
     * Die Methode kann nicht mit Ext Mitteln überschrieben werden,
     * sondern muss direkt im JS ersetzt werden.
     * Die Orginal focusRow berechnet das Screll Delta für die lokalen
     * Anforderungen falsch.
     */
    hijackFocusRow: function() {
      var me = this,
      view = me.getPanel().getView();
      // hijack the view's focusRow method
      view.focusRow = Ext.Function.bind(me.focusRow, view);
    },
  //Overriding the ExtJS Original
    /**
     * Handler für Scrollevents der Scrollbar, ist zuständig für das nachladen
     * der Daten im Store, synchronisiert bei Bedarf die Position der Segmente
     * im View Bereich zum per Scroller eingestellten Segment.
     * überschreibt das Orginal auf Grund der lokalen Anforderungen
     * @param {Ext.EventObjectImpl} e
     * @param {DOMNode} t
     */
    onElScroll: function(e, t) {
      var me = this,
          panel = me.getPanel(),
          store = panel.store,
          pageSize = store.pageSize,
          guaranteedStart = store.guaranteedStart,
          guaranteedEnd = store.guaranteedEnd,
          totalCount = store.getTotalCount(),
          numFromEdge = Math.ceil(me.percentageFromEdge * pageSize),
          position = t.scrollTop,
          visibleStart = Math.floor(position / me.rowHeight),
          view = panel.down('tableview'),
          viewEl = view.el,
          visibleHeight = viewEl.getHeight(),
          visibleAhead = Math.ceil(visibleHeight / me.rowHeight),
          visibleEnd = visibleStart + visibleAhead,
          prevPage = Math.floor(visibleStart / pageSize),
          nextPage = Math.floor(visibleEnd / pageSize) + 2,
          lastPage = Math.ceil(totalCount / pageSize),
          //Ab hier Änderungen gegenüber dem Orginal
          requestStart = Math.max(0, Math.floor(visibleStart / me.snapIncrement) * me.snapIncrement - me.snapIncrement),
          requestEnd = requestStart + pageSize - 1,
          activePrefetch = me.activePrefetch;
          //Ende der Änderungen

      if(me.isDisabled()) {
          return;
      }
      
      me.visibleStart = visibleStart;
      me.visibleEnd = visibleEnd;

      me.syncScroll = true;
      //Ab hier diverse Änderungen zum Orginal
      if (totalCount >= pageSize) {
          if (totalCount < (this.chunkSize * 2) && nextPage <= lastPage &&
            activePrefetch && visibleEnd > (guaranteedEnd - numFromEdge) && me.notPreFetched) {
              store.prefetchPage(nextPage);
              me.notPreFetched = false;
          }
          // end of request was past what the total is, grab from the end back a pageSize
          else if (requestEnd > totalCount - 1) {
              panel.editingPlugin.beforeRangeChange();
              this.cancelLoad();
              if (store.rangeSatisfied(totalCount - pageSize, totalCount - 1)) {
                  me.syncScroll = true;
              }
              store.guaranteeRange(totalCount - pageSize, totalCount - 1);
          // Out of range, need to reset the current data set
          } else if ((visibleStart >= 0 && visibleStart <= guaranteedStart) || visibleEnd > guaranteedEnd) {
              panel.editingPlugin.beforeRangeChange();
              if (store.rangeSatisfied(requestStart, requestEnd)) {
                  this.cancelLoad();
                  store.guaranteeRange(requestStart, requestEnd);
              } else {
                  store.mask();
                  me.attemptLoad(requestStart, requestEnd);
              }
              // if guaranteedRange !== requestedRange, dont sync the scroll view immediately, sync after the range has been guaranteed
              me.syncScroll = (requestStart === guaranteedStart && requestEnd === guaranteedEnd);
          } else if (activePrefetch && visibleStart < (guaranteedStart + numFromEdge) && prevPage > 0) {
              me.syncScroll = true;
              store.prefetchPage(prevPage);
          } else if (activePrefetch && visibleEnd > (guaranteedEnd - numFromEdge) && nextPage < lastPage) {
              me.syncScroll = true;
              store.prefetchPage(nextPage);
          }
      }

      if (me.syncScroll) {
          me.syncTo();
      }
    },
    /**
     * Syncronisiert die TableView Scroll Position zur Scrollbar
     * überschreibt das orginal aufgrund der lokalen Anforderungen
     */
    syncTo: function() {
        var me            = this,
            pnl           = me.getPanel(),
            store         = pnl.store,
            scrollerElDom = this.scrollEl.dom,
            rowOffset     = me.visibleStart - store.guaranteedStart,
            scrollHeight  = scrollerElDom.scrollHeight,
            clientHeight  = this.getScrollerClientHeight(),
            scrollTop     = scrollerElDom.scrollTop,
            viewTop       = pnl.getView().getEl().getTop(),
            targetRow     = pnl.getView().getEl().down('tr.x-grid-row'),
            scrollBy      = 0,
            i             = 0,
            useMaximum;

        for(;i < rowOffset && targetRow;i++){
            scrollBy = scrollBy + targetRow.getHeight();
            targetRow = targetRow.next();
        }

        scrollBy = Math.max(0, scrollBy - me.rowHeight); // selected rows kleben nicht oben am Viewport, sondern etwas darunter

        // This should always be zero or greater than zero but staying
        // safe and less than 0 we'll scroll to the bottom.
        useMaximum = (scrollHeight - clientHeight - scrollTop <= 0);
        this.setViewScrollTop(scrollBy, useMaximum);
    },
    /**
     * gibt die aktuelle Höhe des Scroller Elements zurück
     * @return Integer
     */
    getScrollerClientHeight: function() {
      var scrollerElDom = this.scrollEl.dom;
      // BrowserBug: clientHeight reports 0 in IE9 StrictMode
      // Instead we are using offsetHeight and hardcoding borders
      if (Ext.isIE9 && Ext.isStrict) {
          return scrollerElDom.offsetHeight + 2;
      }
      return scrollerElDom.clientHeight;
    },
    /**
     * springt durch verschieben des Scrollers zum gewünschten Segment. 
     * recordindex bezeichnet den Index des Segments in der gesamten gefilterten
     *  und sortierten Ergebnissmenge (also nicht nur im aktuellen Range)
     * @param {Integer} recordindex 
     */
    jumpToRecordIndex: function(recordindex){
      this.setScrollTop(recordindex * this.rowHeight);
    },

    /**
     *Handler wenn der Store einen garantierten Bereich geladen hat
     * - synchronisiert bei Bedarf die View Positionen
     * - selektiert bei Bedarf ein Segment (File Klick)
     * - überschreibt das Orginal.
     */
    onGuaranteedRange: function(range, start, end) {
      //start orginal onGuaranteedRange
      var me = this,
      ds = me.store;
      // this should never happen
      if (range.length && me.visibleStart < range[0].index) {
          return;
      }

      ds.loadRecords(range);
      //Fix für BugID 7 / Segmente sind nicht mehr synchron zum Scroller bzw. Dateibaum:
      if(me.rendered){
        me.getPanel().down('tableview').refresh();
      }

      if (!me.firstLoad) {
          if (me.rendered) {
              me.invalidate();
          } else {
              me.on('afterrender', me.invalidate, me, {single: true});
          }
          me.firstLoad = true;
      } else {
          // adjust to visible
          // only sync if there is a paging scrollbar element and it has a scroll height (meaning it's currently in the DOM)
          if (me.scrollEl && me.scrollEl.dom && me.scrollEl.dom.scrollHeight) {
              me.syncTo();
          }
      }
      //end orginal onGuaranteedRange

      //ab hier wird der Code fürs anspringen benötigt
      var lastidx = me.lastRequestedRowIndex,
      segment = me.getRecordByTotalIndex(lastidx);
      me.lastRequestedRowIndex = -1;
      if(me.isInRange(start, end, lastidx) && segment){
        me.enableSelectOrFocus = true;
        me.selectOrFocus(segment);
      }
    },
    /**
     * Springt ein Segment an und selektiert es, rowindex bezeichnet die
     * Position in der kompletten gefilterten und sortierten Ergebnissmenge.
     * @param {Integer} rowindex
     */
    jumpToSegmentRowIndexAndSelect: function(rowindex) {
      var me = this,
      segment = me.getRecordByTotalIndex(rowindex);
      me.enableSelectOrFocus = true;

      me.lastRequestedRowIndex = rowindex;
      me.jumpToRecordIndex(rowindex);
      if(segment){
        me.selectOrFocus(segment);
      }
    },
    /**
     * Gibt den Segment Record zum gewünschten index in der gesamten Ergebnismenge
     * @param {Integer} rowindex
     */
    getRecordByTotalIndex: function(rowindex) {
      return this.store.getAt(this.store.findBy(function(rec){
        return (rec.index == rowindex);
      }));
    },
    /**
     * Selektiert und fokusiert das gewünschte Segment, localRowIndex bezeichnet
     * den Index des Segments im aktuell garantierten Range
     * @param {Integer} localRowIndex
     */
    selectOrFocus: function(localRowIndex){
      if(! this.enableSelectOrFocus){
        return;
      }
      this.enableSelectOrFocus = false;
      this.getPanel().selectOrFocus();
    },
    /**
     * gibt zurück ob start <= value <= end
     * @param {Integer} start
     * @param {Integer} end
     * @param {Integer} value
     * @return boolean
     */
    isInRange: function(start, end, value) {
      return (start <= value && value <= end);
    },
    /**
     * Gibt die Anzahl der Segmente in der Ergebnismenge zurück
     * @return Integer
     */
    getRowCount: function() {
      var store = this.store;
      // If the Store is *locally* filtered, use the filtered count from getCount.
      return store[(!store.remoteFilter && store.isFiltered()) ? 'getCount' : 'getTotalCount']();
    },
    /**
     * berechnet die Höhe des Scroller Elements, um einen adäquaten
     * Scrollbalken anzuzeigen.
     * Überschreibt das Orginal.
     * @return Object
     */
    getSizeCalculation: function() {
        var height = 1;
        height = this.getRowCount() * this.rowHeight + this.getScrollerClientHeight() - this.rowHeight;
        if (isNaN(height)) {
            height = 1;
        }
        return {
            width: 1,
            height: height
        };
    },
    setScrollTop: function(scrollTop) {
        if(!this.isDisabled()) {
            this.callParent(arguments);
        }
    },
    /**
     * Setzt die Position des View Elements des Segment Grids
     * überschreibt das Orginal
     * @param {Integer} scrollTop
     * @param {Boolean} useMax Bei True springt der Scroller zum Ende
     */
    setViewScrollTop: function(scrollTop, useMax) {
      var owner = this.getPanel(),
          items = owner.query('tableview'),
          i = 0,
          len = items.length,
          center,
          centerEl,
          calcScrollTop,
          maxScrollTop,
          scrollerElDom = this.el.dom;

      owner.virtualScrollTop = scrollTop;

      center = items[1] || items[0];
      centerEl = center.el.dom;

      if(owner.getView().getEl().down('table.x-grid-table')){
        maxScrollTop = (owner.getView().getEl().down('table.x-grid-table').getHeight() - centerEl.clientHeight);
      }
      else {
        maxScrollTop = centerEl.clientHeight;
      }
      calcScrollTop = scrollTop;
      if (useMax || calcScrollTop > maxScrollTop) {
          calcScrollTop = maxScrollTop;
      }
      for (; i < len; i++) {
          items[i].el.dom.scrollTop = calcScrollTop;
      }
  },
  setRowHeight: function(height){
      this.rowHeight = height;
  },
  getRowHeight: function(){
      return this.rowHeight;
  },
  /**
   * ersetzt die Methode Ext.view.Table.focusRow
   * fokusiert das Segment mit dem lokalen Row Index
   * @param {Integer} rowIdx
   */
  focusRow: function(rowIdx){
    var me         = this,
    row        = me.getNode(rowIdx),
    el         = me.el,
    adjustment = 0,
    panel      = me.ownerCt,
    rowRegion,
    elRegion,
    record;

    if (row && el) {
      elRegion  = el.getRegion();
      rowRegion = Ext.fly(row).getRegion();
      // row is above
      if (rowRegion.top < elRegion.top) {
          adjustment = rowRegion.top - elRegion.top;
      // row is below
      } else if (rowRegion.bottom > elRegion.bottom) {
          adjustment = rowRegion.bottom - elRegion.bottom;
      }
      record = me.getRecord(row);
      rowIdx = me.store.indexOf(record);

      if (adjustment) {
//ExtJS Original: panel.scrollByDeltaY(adjustment);
          panel.getVerticalScroller().jumpToRecordIndex(record.index);
      }
      me.fireEvent('rowfocus', record, row, rowIdx);
    }
  }
});
