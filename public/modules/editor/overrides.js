
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Fixing missing contains method for bufferedstores
 * needed for ext-6.0.0
 * recheck on update
 */
Ext.define('Ext.overrides.fixed.BufferedStore', {
    override: 'Ext.data.BufferedStore',
    contains: function(record) {
        return this.indexOf(record) > -1;
    }
});


/**
 * Fix for EXT6UPD-33
 * needed for ext-6.0.0
 * should be solved natively with ext-6.0.1
 */
Ext.define('Ext.overrides.fixed.PageMap', {
    override: 'Ext.data.PageMap',
    getByInternalId: function(internalId) {
        var index = this.indexMap[internalId];
        if (index != null) {
            return this.getAt(index);
        }
    }
});

/**
 * Fix for EXT6UPD-46
 * needed for ext-6.0.0
 * should be solved natively with ext-6.0.1
 */
Ext.define('Ext.overrides.fixed.ListFilter', {
    override: 'Ext.grid.filters.filter.List',
    getGridStoreListeners: function() {
        if(this.autoStore) {
            return this.callParent(arguments);
        }
        return {};
    }
});

/**
* @property {RegExp}
* @private
* Regular expression used for validating identifiers.
* !!!WARNING!!! This  and next override is made to allow ids starting with a digit. This is due to the bulk of legacy data
*/
Ext.validIdRe = /^[a-z0-9_][a-z0-9\-_]*$/i;
Ext.define('Ext.overrides.dom.Element', {
    override: 'Ext.dom.Element',
    
    constructor: function(dom) {
        this.validIdRe = Ext.validIdRe;
        this.callParent(arguments);
    }
});

/**
 * fixing for this bug: https://www.sencha.com/forum/showthread.php?288898-W-targetCls-is-missing.-This-may-mean-that-getTargetEl()-is-being-overridden-but-no/page3
 * needed for ext-6.0.0
 * recheck on update
 */
Ext.define('Ext.overrides.layout.container.Container', {
  override: 'Ext.layout.container.Container',

  notifyOwner: function() {
    this.owner.afterLayout(this);
  }
});

/**
 * enables the ability to set a optional menuOffset in menus
 * needed for ext-6.0.0
 * this override must be revalidated on extjs update
 */
Ext.override(Ext.menu.Item, {
    deferExpandMenu: function() {
        var me = this;

        if (!me.menu.rendered || !me.menu.isVisible()) {
            me.parentMenu.activeChild = me.menu;
            me.menu.parentItem = me;
            me.menu.parentMenu = me.menu.ownerCt = me.parentMenu;
            me.menu.showBy(me, me.menuAlign, me.menuOffset);
        }
    }
});

/**
 * TRANSLATE-834: Triton Theme: Tooltip on columns is missing
 * All columns should have a tooltip with the same content as the title when nothing other is configured
 */
Ext.override(Ext.grid.column.Column, {
    initConfig: function(config) {
        if(config.tooltip === undefined) {
            config.tooltip = Ext.String.htmlEncode(config.text||this.text);
        }
        return this.callParent([config]);
    }
});

/**
 * Fixing EXT6UPD-131 (fixed natively in ext-6.0.1, must be removed then!)
 */
Ext.override(Ext.grid.filters.filter.TriFilter, {
    deactivate: function () {
        var me = this,
            filters = me.filter,
            f, filter, value;

        if (!me.countActiveFilters() || me.preventFilterRemoval) {
            return;
        }

        me.preventFilterRemoval = true;

        for (f in filters) {
            filter = filters[f];

            value = filter.getValue();
            if (value || value === 0) {
                me.removeStoreFilter(filter);
            }
        }

        me.preventFilterRemoval = false;
    }
});

/**
 * Fix for EXTJS-18481
 * needed for ext-6.0.0
 * should be solved natively with ext-6.0.1
 */
Ext.define('MyApp.overrides.data.request.Ajax', {
    override: 'Ext.data.request.Ajax',
    /** * Overrideing this method as getResponse header does not work * @param xhr * @returns {{request: Egain.overrides.data.request.Ajax, requestId: *, status: *, statusText: *, getResponseHeader: (Ext.data.request.Base.privates._getHeader|Function), getAllResponseHeaders: (Ext.data.request.Base.privates._getHeaders|Function)}|*} */
    createResponse: function(xhr) {
        var me = this,
            isXdr = me.isXdr,
            headers = {},
            lines = isXdr ? [] : xhr.getAllResponseHeaders().replace(/\r\n/g, '\n').split('\n'),
            count = lines.length,
            line, index, key, response, byteArray;
        while (count--) {
            line = lines[count];
            index = line.indexOf(':');
            if (index >= 0) {
                key = line.substr(0, index).toLowerCase();
                if (line.charAt(index + 1) == ' ') {
                    ++index;
                }
                headers[key] = line.substr(index + 1);
            }
        }
        response = {
            request: me,
            requestId: me.id,
            status: xhr.status,
            statusText: xhr.statusText,
            getResponseHeader: function(name) {
                return headers[name.toLowerCase()];
            },
            getAllResponseHeaders: function() {
                return headers;
            }
        };
        if (isXdr) {
            me.processXdrResponse(response, xhr);
        }
        if (me.binary) {
            response.responseBytes = me.getByteArray(xhr);
        } else {
            // an error is thrown when trying to access responseText or responseXML 
            // on an xhr object with responseType of 'arraybuffer', so only attempt 
            // to set these properties in the response if we're not dealing with 
            // binary data
            response.responseText = xhr.responseText;
            response.responseXML = xhr.responseXML;
        }
        return response;
    },
});

Ext.override(Ext.util.CSS, {
    /***
     * Add a custom css to the given html page
     * FIXME replace all usages places with plain CSS files. 
     * Problem 1: the styles are added multiple times to the editor
     * Problem 2: maintaining of the CSS is a pain
     */
    createStyleSheetToWindow : function(window, cssText, id) {
        var ss,
            head = window.getElementsByTagName('head')[0],
            styleEl = window.createElement('style');

        styleEl.setAttribute('type', 'text/css');
    
        if (id) {
           styleEl.setAttribute('id', id);
        }
    
        // Feature detect old IE 
        ss = styleEl.styleSheet;
        if (ss) {
            head.appendChild(styleEl);
            ss.cssText = cssText;
        } else {
            styleEl.appendChild(window.createTextNode(cssText));
            head.appendChild(styleEl);
            ss = styleEl.sheet;
        }
    
        Ext.util.CSS.cacheStyleSheet(ss);
        return ss;
    }
})

/**
 * Workaround for TRANSLATE-1239
 * reported to sencha in: https://www.sencha.com/forum/showthread.php?470065-ExtJS-6-2-classic-Ext-data-PageMap-getPage-must-return-array-but-returns-undefined&p=1318664#post1318664
 * Currently no Feedback from Sencha, if already fixed or so.
 */
Ext.override(Ext.data.PageMap, {
    getPage: function(pageNumber) {
        return this.get(pageNumber) || [];
    },
    hasRange: function(start, end) {
        return this.callParent([start, Math.max(end, 0)]);
    }
});

/**
 * Workaround for TRANSLATE-1177
 * https://www.sencha.com/forum/showthread.php?343381-Grid-rowwidget-error-Uncaught-TypeError-Cannot-read-property-isModel-of-null
 * EXTJS-26004
 * needed for ext-6.2.0
 * seems to be still open in newer versions
 */
Ext.override(Ext.grid.CellContext, {
    setRow: function(row) {
        if(!row) {
            //the original method needs undefined for falsy row values
            return this.callParent([]);
        }
        return this.callParent([row]);
    }
});

/**
 * Workaround for TRANSLATE-1431
 * needed for ext-6.2.0
 * no info if known as ExtJS issue and if it is fixed in the future
 * must be rechecked!
 */
Ext.override(Ext.panel.Table, {
    ensureVisible: function(record, options) {
        if(record) {
            this.doEnsureVisible(record, options);
        }
    }
});

/**
 * Several Fixes for view.Table
 */
Ext.override(Ext.view.Table, {
    privates: {
        /**
         * Fix for TRANSLATE-1041 / EXTJS-24549 / https://www.sencha.com/forum/showthread.php?338435-ext-all-debug-js-206678-Uncaught-TypeError-cell-focus-is-not-a-function
         * needed for ext-6.2.0
         * should be solved natively with next version
         */
        setActionableMode: function(enabled, position) {
            var me = this,
                navModel = me.getNavigationModel(),
                activeEl,
                actionables = me.grid.actionables,
                len = actionables.length,
                i, record, column,
                isActionable = false,
                lockingPartner, cell;
            // No mode change.
            // ownerGrid's call will NOT fire mode change event upon false return.
            if (me.actionableMode === enabled) {
                // If we're not actinoable already, or (we are actionable already at that position) return false.
                // Test using mandatory passed position because we may not have an actionPosition if we are 
                // the lockingPartner of an actionable view that contained the action position.
                //
                // If we being told to go into actionable mode but at another position, we must continue.
                // This is just actionable navigation.
                if (!enabled || position.isEqual(me.actionPosition)) {
                    return false;
                }
            }
            // If this View or its lockingPartner contains the current focus position, then make the tab bumpers tabbable
            // and move them to surround the focused row.
            if (enabled) {
                if (position && (position.view === me || (position.view === (lockingPartner = me.lockingPartner) && lockingPartner.actionableMode))) {
                    isActionable = me.activateCell(position);
                }
                // Did not enter actionable mode.
                // ownerGrid's call will NOT fire mode change event upon false return.
                return isActionable;
            } else {
                // Capture before exiting from actionable mode moves focus
                activeEl = Ext.fly(Ext.Element.getActiveElement());
                // Blur the focused descendant, but do not trigger focusLeave.
                // This is so that when the focus is restored to the cell which contained
                // the active content, it will not be a FocusEnter from the universe.
                if (me.el.contains(activeEl) && !Ext.fly(activeEl).is(me.getCellSelector())) {
                    // Row to return focus to.
                    record = (me.actionPosition && me.actionPosition.record) || me.getRecord(activeEl);
                    column = me.getHeaderByCell(activeEl.findParent(me.getCellSelector()));
                    cell = position && position.getCell();
                    // Do not allow focus to fly out of the view when the actionables are deactivated
                    // (and blurred/hidden). Restore focus to the cell in which actionable mode is active.
                    // Note that the original position may no longer be valid, e.g. when the record
                    // was removed.
                    if (!position || !cell) {
                        position = new Ext.grid.CellContext(me).setPosition(record || 0, column || 0);
                        cell = position.getCell();
                    }
                    // Ext.grid.NavigationModel#onFocusMove will NOT react and navigate because the actionableMode
                    // flag is still set at this point.
                    
                    //THIS IS THE FIXED LINE:
                    cell && cell.focus();
                    //ORIGINAL: just cell.focus();
                    
                    // Let's update the activeEl after focus here
                    activeEl = Ext.fly(Ext.Element.getActiveElement());
                    // If that focus triggered handlers (eg CellEditor after edit handlers) which
                    // programatically moved focus somewhere, and the target cell has been unfocused, defer to that,
                    // null out position, so that we do not navigate to that cell below.
                    // See EXTJS-20395
                    if (!(me.el.contains(activeEl) && activeEl.is(me.getCellSelector()))) {
                        position = null;
                    }
                }
                // We are exiting actionable mode.
                // Tell all registered Actionables about this fact if they need to know.
                for (i = 0; i < len; i++) {
                    if (actionables[i].deactivate) {
                        actionables[i].deactivate();
                    }
                }
                // If we had begun action (we may be a dormant lockingPartner), make any tabbables untabbable
                if (me.actionRow) {
                    me.actionRow.saveTabbableState({
                        skipSelf: true,
                        includeSaved: false
                    });
                }
                if (me.destroyed) {
                    return false;
                }
                // These flags MUST be set before focus restoration to the owning cell.
                // so that when Ext.grid.NavigationModel#setPosition attempts to exit actionable mode, we don't recurse.
                me.actionableMode = me.ownerGrid.actionableMode = false;
                me.actionPosition = navModel.actionPosition = me.actionRow = null;
                // Push focus out to where it was requested to go.
                if (position) {
                    navModel.setPosition(position);
                }
            }
        }
    },
    /**
     * Fixing TRANSLATE-1422: Uncaught TypeError: Cannot read property 'record' of undefined
     * EXTJS-22672 fixed in 6.2.1.167.
     * https://www.sencha.com/forum/showthread.php?328802-6-2-Crash-when-clicking-grid-view-area-outside-cells
     * needed for ext-6.2.0
     */
    getDefaultFocusPosition: function(fromComponent) {
        if(fromComponent && !fromComponent.isColumn && fromComponent.isTableView && !fromComponent.lastFocused) {
            fromComponent = null;
        }
        return this.callParent([fromComponent]);
    }
});

/**
 * TRANSLATE-1396: Remove "C:\fakepath\" from task name
 */
Ext.override(Ext.form.field.File, {
    onChange: function (newValue, oldValue) {
        this.callParent([newValue, oldValue]);
        this.inputEl.dom.value = newValue.replace("C:\\fakepath\\","");
    }
});

/**
 * TRANSLATE-1129: Missing segments on scrolling with page-down / page-up
 * The original page-down / page-up handlers can only deal with fixed segment row heights
 * Therefore this fix counts the rows from the currently focused row till the last visible row in the grid view area and scrolls about that amount  
 */
Ext.override(Ext.grid.NavigationModel, {
    getRowsVisible: function () {
        var node, 
            view = this.view,
            viewHeight = view.getHeight(),
            scrollY = view.getScrollY(),
            bodyTop = view.bufferedRenderer.bodyTop, //the hidden offset of buffered renderer
            focusedIdx = this.recordIndex || 0, //start with the focused one or zero
            rowsToScroll = 0,
            idx = focusedIdx,
            lastOffsetTop = 0,
            lastVisibleOffsetTop = false,
            lastVisibleAfterScrollIdx = false,
            nodeTop, nodeTopAndHeight,
            keyCode = this.keyNav[0] && this.keyNav[0].lastKeyEvent.keyCode,
            isPageUP = keyCode == Ext.event.Event.PAGE_UP;
        
        do { 
            node = Ext.fly(view.getRow(idx));
            if (!node) {
                continue;
            }
            //gets the view item element of the row, which has the correct offsetTop to the view container
            node = node.parent(view.itemSelector);
            nodeTop = node.dom.offsetTop + bodyTop;
            nodeTopAndHeight = node.getHeight() + nodeTop;
            
            if(isPageUP) {
                if(nodeTop < (scrollY - viewHeight)){
                    //if the current node is not completely visible anymore, the node before is the last completely visible
                    return Math.max(1, focusedIdx - idx - 2); // -/+ 1 testen!
                }
            }
            else {
                //page down 1. get last row visible in grid view
                if(lastVisibleOffsetTop === false && nodeTopAndHeight > (scrollY + viewHeight)){
                    //if the current node is not completely visible anymore, the node before is the last completely visible
                    lastVisibleOffsetTop = lastOffsetTop;
                }
                //page down 2. get the next row down from the above row, which is still visible from the above row + visible view height
                if(lastVisibleOffsetTop !== false && nodeTopAndHeight > (lastVisibleOffsetTop + viewHeight) ) {
                    //page down 3. get the row count between the focused row, and the row found in 2., return that.
                    return Math.max(1, idx - 1 - focusedIdx);
                }
            }
            
            isPageUP ? idx-- : idx++;
            lastOffsetTop = node.dom.offsetTop + bodyTop;
        } while (node);
        
        //if no node is found anymore in the rendered view table, we can not calculate the real offset, 
        // so we have to stop and jump to last available
        return isPageUP ? Math.max(1, focusedIdx - idx - 1) : Math.max(1, idx - 1 - focusedIdx);
    }
});

/**
 * Override BufferedRenderer due Bugs in ExtJS 6.2.0 (TRANSLATE-1128 and TRANSLATE-1233) 
 * Bugs are fixed in the version 6.2.2, so the whole override can be removed on an extjs update 
 */
Ext.override(Ext.grid.plugin.BufferedRenderer, {
    bindStore: function (newStore) {
        var me = this,
            currentStore = me.store;
 
        // If the grid was configured with a feature such as Grouping that binds a FeatureStore (GroupStore, in its case) as 
        // the view's dataSource, we must continue to use the same Type of store. 
        // 
        // Note that reconfiguring the grid can call into here. 
        if (currentStore && currentStore.isFeatureStore) {
            return;
        }
 
        if (currentStore) {
            me.unbindStore();
        }
 
        me.storeListeners = newStore.on({
            scope: me,
            groupchange: me.onStoreGroupChange,
            clear: me.onStoreClear,
            beforeload: me.onBeforeStoreLoad,
            load: me.onStoreLoad,
            destroyable: true
        });
 
        me.store = newStore;
        
        me.setBodyTop(me.scrollTop = me.position = 0);
        
        // Delete whatever our last viewSize might have been, and fall back to the prototype's default.      
        delete me.viewSize;
        delete me.rowHeight;
        
        if (newStore.isBufferedStore) {
            newStore.setViewSize(me.viewSize);
        }
    },
    onStoreLoad: function() {
        this.isStoreLoading = true;
        this.enable();
    },
    onViewBoxReady: function(view) {
        //do nothing here!
    },
    doRefreshView: function(range, startIndex, endIndex, options) {
        var me = this,
            view = me.view,
            scroller = me.scroller,
            rows = view.all,
            previousStartIndex = rows.startIndex,
            previousEndIndex = rows.endIndex,
            previousFirstItem,
            previousLastItem,
            prevRowCount = rows.getCount(),
            calculatedTop = -1,
            viewMoved = startIndex !== rows.startIndex && !me.isStoreLoading,
            scrollIncrement,
            restoreFocus;
 
 
        // So that listeners to the itemremove events know that its because of a refresh. 
        // And so that this class's refresh listener knows to ignore it. 
        view.refreshing = me.refreshing = true;
        me.isStoreLoading = false;
 
        if (view.refreshCounter) {
 
            // Give CellEditors or other transient in-cell items a chance to get out of the way. 
            if (view.hasListeners.beforerefresh && view.fireEvent('beforerefresh', view) === false) {
                return view.refreshNeeded = view.refreshing = me.refreshing = false;
            }
 
            // If focus was in any way in the view, whether actionable or navigable, this will return 
            // a function which will restore that state. 
            restoreFocus = view.saveFocusState();
 
            view.clearViewEl(true);
            view.refreshCounter++;
            if (range.length) {
                view.doAdd(range, startIndex);
 
                if (viewMoved) {
                    // Try to find overlap between newly rendered block and old block 
                    previousFirstItem = rows.item(previousStartIndex, true);
                    previousLastItem = rows.item(previousEndIndex, true);
 
                    // Work out where to move the view top if there is overlap 
                    if (previousFirstItem) {
                        scrollIncrement = -previousFirstItem.offsetTop;
                    } else if (previousLastItem) {
                        scrollIncrement = rows.last(true).offsetTop - previousLastItem.offsetTop;
                    }
 
                    // If there was an overlap, we know exactly where to move the view 
                    if (scrollIncrement) {
                        calculatedTop = Math.max(me.bodyTop + scrollIncrement, 0);
                        me.scrollTop = calculatedTop ? me.scrollTop + scrollIncrement : 0;
                    }
                    // No overlap: calculate the a new body top and scrollTop. 
                    else {
                        calculatedTop = startIndex * me.rowHeight;
                        me.scrollTop = Math.max(calculatedTop + me.rowHeight * (calculatedTop < me.bodyTop ? me.leadingBufferZone : me.trailingBufferZone), 0);
                    }
                }
            }
 
            // Clearing the view. 
            // Ensure we jump to top. 
            // Apply empty text. 
            else {
                calculatedTop = me.scrollTop = me.position = 0;
                view.addEmptyText();
            }
 
            // Keep scroll and rendered block positions synched.  
            if (scroller && calculatedTop !== -1) {
                me.setBodyTop(calculatedTop);
                scroller.suspendEvent('scroll');
                scroller.scrollTo(null, me.position = me.scrollTop);
                scroller.resumeEvent('scroll');
            }
 
            // Correct scroll range 
            me.refreshSize();
            view.refreshSize(rows.getCount() !== prevRowCount);
            view.fireItemMutationEvent('refresh', view, range);
 
            // If focus was in any way in this view, this will restore it 
            restoreFocus();
            view.headerCt.setSortState();
        } else {
            view.refresh();
        }
        
        // If there are columns to trigger rendering, and the rendered block or not either the view size 
        // or, if store count less than view size, the store count, then try to refresh the view table
        if (view.getVisibleColumnManager().getColumns().length && rows.getCount() !== Math.min(me.store.getCount(), me.viewSize)) {
        	//see Alex comment(3.2.6) in https://jira.translate5.net/browse/TRANSLATE-1045 
        	view.refresh();
        }
        
        //TL: Additional Info here: below error raise is also triggered if there is an exception in row rendering, 
        //    for example if there is an exception in getRowClass 
        
        //<debug> 
        // If there are columns to trigger rendering, and the rendered block os not either the view size 
        // or, if store count less than view size, the store count, then there's a bug. 
        if (view.getVisibleColumnManager().getColumns().length && rows.getCount() !== Math.min(me.store.getCount(), me.viewSize)) {
            Ext.raise('rendered block refreshed at ' + rows.getCount() + ' rows while BufferedRenderer view size is ' + me.viewSize);
        }
        //</debug> 
        view.refreshNeeded = view.refreshing = me.refreshing = false;
    },
 
    renderRange: function(start, end, forceSynchronous, fromLockingPartner) {
        var me = this,
            rows = me.view.all,
            store = me.store;
 
        // Skip if we are being asked to render exactly the rows that we already have. 
        // This can happen if the viewSize has to be recalculated (due to either a data refresh or a view resize event) 
        // but the calculated size ends up the same. 
        if (!(start === rows.startIndex && end === rows.endIndex)) {
 
            // If range is available synchronously, process it now. 
            if (store.rangeCached(start, end)) {
                me.cancelLoad();
 
                if (me.synchronousRender || forceSynchronous) {
                    me.onRangeFetched(null, start, end, null, fromLockingPartner);
                } else {
                    if (!me.renderTask) {
                        me.renderTask = new Ext.util.DelayedTask(me.onRangeFetched, me);
                    }
                    // Render the new range very soon after this scroll event handler exits. 
                    // If scrolling very quickly, a few more scroll events may fire before 
                    // the render takes place. Each one will just *update* the arguments with which 
                    // the pending invocation is called. 
                    me.renderTask.delay(-1, null, null, [null, start, end, null, fromLockingPartner]);
                }
            }
 
            // Required range is not in the prefetch buffer. Ask the store to prefetch it. 
            else {
                me.attemptLoad(start, end, me.scrollTop);
            }
        }
    },
 
    onRangeFetched: function(range, start, end, options, fromLockingPartner) {
        var me = this,
            view = me.view,
            scroller = me.scroller,
            viewEl = view.el,
            rows = view.all,
            increment = 0,
            calculatedTop,
            lockingPartner = (view.lockingPartner && !fromLockingPartner && !me.doNotMirror) && view.lockingPartner.bufferedRenderer,
            variableRowHeight = me.variableRowHeight,
            activeEl, containsFocus, i, newRows, newTop, newFocus, noOverlap,
            oldStart, partnerNewRows, pos, removeCount, topAdditionSize, topBufferZone, topNode;
 
        // View may have been destroyed since the DelayedTask was kicked off. 
        if (view.destroyed) {
            return;
        }
 
        // If called as a callback from the Store, the range will be passed, if called from renderRange, it won't 
        if (range) {
            if (!fromLockingPartner) {
                // Re-cache the scrollTop if there has been an asynchronous call to the server. 
                me.scrollTop = me.scroller.getPosition().y;
            }
        } else {
            range = me.store.getRange(start, end);
 
            // Store may have been cleared since the DelayedTask was kicked off. 
            if (!range) {
                return;
            }
        }
 
        // If we contain focus now, but do not when we have rendered the new rows, we must focus the view el. 
        activeEl = Ext.fly(Ext.Element.getActiveElement());
        containsFocus = viewEl.contains(activeEl);
 
        // In case the browser does fire synchronous focus events when a focused element is derendered... 
        if (containsFocus) {
            activeEl.suspendFocusEvents();
        }
 
        // Best guess rendered block position is start row index * row height. 
        // We can use this as bodyTop if the row heights are all standard. 
        // We MUST use this as bodyTop if the scroll is a telporting scroll. 
        // If we are incrementally scrolling, we add the rows to the bottom, and 
        // remove a block of rows from the top. 
        // The bodyTop is then incremented by the height of the removed block to keep 
        // the visuals the same. 
        // 
        // We cannot always use the calculated top, and compensate by adjusting the scroll position 
        // because that would break momentum scrolling on DOM scrolling platforms, and would be 
        // immediately undone in the next frame update of a momentum scroll on touch scroll platforms. 
        calculatedTop = start * me.rowHeight;
 
        // The new range encompasses the current range. Refresh and keep the scroll position stable 
        if (start < rows.startIndex && end > rows.endIndex) {
            // How many rows will be added at top. So that we can reposition the table to maintain scroll position 
            topAdditionSize = rows.startIndex - start;
 
            // MUST use View method so that itemremove events are fired so widgets can be recycled. 
            view.clearViewEl(true);
            newRows = view.doAdd(range, start);
            view.fireItemMutationEvent('itemadd', range, start, newRows, view);
            for (i = 0; i < topAdditionSize; i++) {
                increment -= newRows[i].offsetHeight;
            }
 
            // We've just added a bunch of rows to the top of our range, so move upwards to keep the row appearance stable 
           newTop = me.bodyTop + increment;
        } 
        else {
            // No overlapping nodes; we'll need to render the whole range. 
            // teleported flag is set in getFirstVisibleRowIndex/getLastVisibleRowIndex if 
            // the table body has moved outside the viewport bounds 
            noOverlap = me.teleported || start > rows.endIndex || end < rows.startIndex;
            if (noOverlap) {
                view.clearViewEl(true);
                me.teleported = false;
            }
 
            if (!rows.getCount()) {
                newRows = view.doAdd(range, start);
                view.fireItemMutationEvent('itemadd', range, start, newRows, view);
                newTop = calculatedTop;
 
                // Adjust the bodyTop to place the data correctly around the scroll vieport 
                if (noOverlap && variableRowHeight) {
                    topBufferZone = me.scrollTop < me.position ? me.leadingBufferZone : me.trailingBufferZone;
                    topNode = rows.item(rows.startIndex + topBufferZone - 1, true);
                    newTop = Math.max(me.scrollTop - (topNode ? topNode.offsetTop : 0), 0);
                }
            }
            // Moved down the dataset (content moved up): remove rows from top, add to end 
            else if (end > rows.endIndex) {
                removeCount = Math.max(start - rows.startIndex, 0);
 
                // We only have to bump the table down by the height of removed rows if rows are not a standard size 
                if (variableRowHeight) {
                    increment = rows.item(rows.startIndex + removeCount, true).offsetTop;
                }
                newRows = rows.scroll(Ext.Array.slice(range, rows.endIndex + 1 - start), 1, removeCount);
 
                scroller.scrollTo(null, me.scrollTop);
                // We only have to bump the table down by the height of removed rows if rows are not a standard size 
                if (variableRowHeight) {
                    // Bump the table downwards by the height scraped off the top 
                    newTop = me.bodyTop + increment;
                }
                // If the rows are standard size, then the calculated top will be correct 
                else {
                    newTop = calculatedTop;
                }
            }
            // Moved up the dataset: remove rows from end, add to top 
            else {
                removeCount = Math.max(rows.endIndex - end, 0);
                oldStart = rows.startIndex;
                newRows = rows.scroll(Ext.Array.slice(range, 0, rows.startIndex - start), -1, removeCount);
 
                scroller.scrollTo(null, me.scrollTop);
                // We only have to bump the table up by the height of top-added rows if rows are not a standard size 
                if (variableRowHeight) {
                    // Bump the table upwards by the height added to the top 
                    newTop = me.bodyTop - rows.item(oldStart, true).offsetTop;
 
                    // We've arrived at row zero... 
                    if (!rows.startIndex) {
                        // But the calculated top position is out. It must be zero at this point 
                        // We adjust the scroll position to keep visual position of table the same. 
                        if (newTop) {
                            scroller.scrollTo(null, me.position = (me.scrollTop -= newTop));
                            newTop = 0;
                        }
                    }
                    // Not at zero yet, but the position has moved into negative range 
                    else if (newTop < 0) {
                        increment = rows.startIndex * me.rowHeight;
                        scroller.scrollTo(null, me.position = (me.scrollTop += increment));
                        newTop = me.bodyTop + increment;
                    }
                }
                // If the rows are standard size, then the calculated top will be correct 
                else {
                    newTop = calculatedTop;
                }
            }
            // The position property is the scrollTop value *at which the table was last correct* 
            // MUST be set at table render/adjustment time 
            me.position = me.scrollTop;
        }
 
        // We contained focus at the start, check whether activeEl has been derendered. 
        // Focus the cell's column header if so. 
        if (containsFocus) {
            // Restore active element's focus processing. 
            activeEl.resumeFocusEvents();
 
            if (!viewEl.contains(activeEl)) {
                pos = view.actionableMode ? view.actionPosition : view.lastFocused;
                if (pos && pos.column) {
                    // we set the rendering rows to true here so the actionables know 
                    // that view is forcing the onFocusLeave method here 
                    view.renderingRows = true;
                    view.onFocusLeave({});
                    view.renderingRows = false;
                    // Try to focus the contextual column header. 
                    // Failing that, look inside it for a tabbable element. 
                    // Failing that, focus the view. 
                    // Focus MUST NOT just silently die due to DOM removal 
                    if (pos.column.focusable) {
                        newFocus = pos.column;
                    } else {
                        newFocus = pos.column.el.findTabbableElements()[0];
                    }
                    if (!newFocus) {
                        newFocus = view.el;
                    }
                    newFocus.focus();
                }
            }
        }
 
        // Position the item container. 
        newTop = Math.max(Math.floor(newTop), 0);
 
        // Sync the other side to exactly the same range from the dataset. 
        // Then ensure that we are still at exactly the same scroll position. 
        if (newRows && lockingPartner && !lockingPartner.disabled) {
            // Set the pointers of the partner so that its onRangeFetched believes it is at the correct position. 
            lockingPartner.scrollTop = lockingPartner.position = me.scrollTop;
            if (lockingPartner.view.ownerCt.isVisible()) {
                partnerNewRows = lockingPartner.onRangeFetched(range, start, end, options, true);
 
                // Sync the row heights if configured to do so, or if one side has variableRowHeight but the other doesn't. 
                // variableRowHeight is just a flag for the buffered rendering to know how to measure row height and 
                // calculate firstVisibleRow and lastVisibleRow. It does not *necessarily* mean that row heights are going 
                // to be asymmetric between sides. For example grouping causes variableRowHeight. But the row heights 
                // each side will be symmetric. 
                // But if one side has variableRowHeight (eg, a cellWrap: true column), and the other does not, that 
                // means there could be asymmetric row heights. 
                if (view.ownerGrid.syncRowHeight || view.ownerGrid.syncRowHeightOnNextLayout || (lockingPartner.variableRowHeight !== variableRowHeight)) {
                    me.syncRowHeights(newRows, partnerNewRows);
                    view.ownerGrid.syncRowHeightOnNextLayout = false;
                }
            }
            if (lockingPartner.bodyTop !== newTop) {
                lockingPartner.setBodyTop(newTop, true);
            }
        }
 
        if (view.positionBody) {
            me.setBodyTop(newTop, true);
        }
 
        // If there's variableRowHeight and the scroll operation did affect that, remeasure now. 
        // We must do this because the RowExpander and RowWidget plugin might make huge differences 
        // in rowHeight, so we might scroll from a zone full of 200 pixel hight rows to a zone of 
        // all 21 pixel high rows. 
        if (me.variableRowHeight) {
            delete me.rowHeight;
            me.refreshSize();
        }
 
        // If there are columns to trigger rendering, and the rendered block or not either the view size 
        // or, if store count less than view size, the store count, then try to refresh the view table
        if (view.getVisibleColumnManager().getColumns().length && rows.getCount() !== Math.min(me.store.getCount(), me.viewSize)) {
        	//see https://jira.translate5.net/browse/TRANSLATE-1045 
        	view.refresh();
        }
        
        //<debug> 
        // If this is still the case, then there's a bug. 
        if (view.getVisibleColumnManager().getColumns().length && rows.getCount() !== Math.min(me.store.getCount(), me.viewSize)) {
            Ext.raise('rendered block refreshed at ' + rows.getCount() + ' rows while BufferedRenderer view size is ' + me.viewSize);
        }
        //</debug> 
        
        return newRows;
    },
    setBodyTop: function(bodyTop, skipStretchView) {
        var me = this,
            view = me.view,
            rows = view.all,
            store = me.store,
            body = view.body;
 
        if (!body.dom) {
            // The view may be rendered, but the body element not attached. 
            return;
        }
 
        me.translateBody(body, bodyTop);
 
        // If this is the last page, correct the scroll range to be just enough to fit. 
        if (me.variableRowHeight) {
            me.bodyHeight = body.dom.offsetHeight;
 
            // We are displaying the last row, so ensure the scroll range finishes exactly at the bottom of the view body 
            if (rows.endIndex === store.getCount() - 1) {
                me.scrollHeight = bodyTop + me.bodyHeight - 1;
            }
            // Not last row - recalculate scroll range 
            else {
                me.scrollHeight = me.getScrollHeight();
            }
            if (!skipStretchView) {
                me.stretchView(view, me.scrollHeight);
            }
        } else {
            // If we have fixed row heights, calculate rendered block height without forcing a layout 
            me.bodyHeight = rows.getCount() * me.rowHeight;
        }
    },
    /**
     * Returns the index of the last row in your table view deemed to be visible.
     * @return {Number}
     * @private
     */
    getLastVisibleRowIndex: function(startRow, endRow, viewportTop, viewportBottom) {
        var me = this,
            view = me.view,
            rows = view.all,
            elements = rows.elements,
            clientHeight = me.viewClientHeight,
            target,
            targetTop, targetBottom,
            bodyTop = me.bodyTop;
 
        // If variableRowHeight, we have to search for the first row who's bottom edge is below the bottom of the viewport 
        if (rows.getCount() && me.variableRowHeight) {
            if (!arguments.length) {
                startRow = rows.startIndex;
                endRow = rows.endIndex;
                viewportTop = me.scrollTop;
                viewportBottom = viewportTop + clientHeight;
 
                // Teleported so that body is outside viewport: Use rowHeight calculation 
                if (bodyTop > viewportBottom || bodyTop + me.bodyHeight < viewportTop) {
                    me.teleported = true;
                    return Math.floor(me.scrollTop / me.rowHeight) + Math.ceil(clientHeight / me.rowHeight);
                }
 
                // In first, non-recursive call, begin targeting the most likely last row 
                target = endRow - Math.min(me.numFromEdge + ((me.lastScrollDirection === 1) ? me.leadingBufferZone : me.trailingBufferZone), Math.floor((endRow - startRow) / 2));
            } else {
                if (startRow === endRow) {
                    return endRow;
                }
                target = startRow + Math.floor((endRow - startRow) / 2);
            }
            targetTop = bodyTop + elements[target].offsetTop;
 
            // If target is entirely below the viewport, chop upwards 
            if (targetTop > viewportBottom) {
                return me.getLastVisibleRowIndex(startRow, target - 1, viewportTop, viewportBottom);
            }
            targetBottom = targetTop + elements[target].offsetHeight;
 
            // Target is last 
            if (targetBottom >= viewportBottom) {
                return target;
            }
            // Not narrowed down to 1 yet; chop downwards 
            else if (target !== endRow) {
                return me.getLastVisibleRowIndex(target + 1, endRow, viewportTop, viewportBottom);
            }
        }
        return Math.min(rows.endIndex, me.getFirstVisibleRowIndex() + Math.ceil(clientHeight / me.rowHeight));
    }
});


/**
 * Fixing the: TRANSLATE-1544 Cannot read property 'isCollapsedPlaceholder' of undefined
 * this fix is also related to TRANSLATE-1422
 */
Ext.override(Ext.grid.feature.GroupStore, {
    indexOf: function(record) {
        var ret = -1;
        if (record && !record.isCollapsedPlaceholder) {
            ret = this.data.indexOf(record);
        }
        return ret;
    }
});

/**
 * Workaround for bug in ExtJs 6.2.0.
 * Resolved in current yet unreleased version
 * Fix link: https://stackoverflow.com/questions/43236899/extjs-6-2-classic-does-not-work-with-firefox-and-a-touchscreen
 */
Ext.define('My.override.dom.Element', {
    override: 'Ext.dom.Element'
},
function(){
    var additiveEvents = this.prototype.additiveEvents,
        eventMap = this.prototype.eventMap;
    if(Ext.supports.TouchEvents && Ext.firefoxVersion >= 52 && Ext.os.is.Desktop){
        eventMap['touchstart'] = 'mousedown';
        eventMap['touchmove'] = 'mousemove';
        eventMap['touchend'] = 'mouseup';
        eventMap['touchcancel'] = 'mouseup';
        eventMap['click'] = 'click';
        eventMap['dblclick'] = 'dblclick';
        additiveEvents['mousedown'] = 'mousedown';
        additiveEvents['mousemove'] = 'mousemove';
        additiveEvents['mouseup'] = 'mouseup';
        additiveEvents['touchstart'] = 'touchstart';
        additiveEvents['touchmove'] = 'touchmove';
        additiveEvents['touchend'] = 'touchend';
        additiveEvents['touchcancel'] = 'touchcancel';

        additiveEvents['pointerdown'] = 'mousedown';
        additiveEvents['pointermove'] = 'mousemove';
        additiveEvents['pointerup'] = 'mouseup';
        additiveEvents['pointercancel'] = 'mouseup';
    }
});


/**
 * In Ext.data.reader.Reader::extractRecord the call readAssociated reads out the hasMany associations and processes them.
 * This works perfectly for Model.load() since internally a Model is used as record variable in extractRecord. 
 * For Model.save() record extractRecord contains just the Object with the received data from the PUT request, 
 *  therefore readAssociated is never called and no associations are initialized or updated.
 * The following override calls readAssociated if necessary in the save callback.
 */
Ext.override(Ext.data.Model, {
    save: function(options) {
        options = Ext.apply({}, options);
        var me = this,
            includes = me.schema.hasAssociations(me),
            scope  = options.scope || me,
            callback,
            readAssoc = function(record) {
                //basicly this is the same code as in readAssociated to loop through the associations
                var roles = record.associations,
                    key, role,assocStore;
                for (key in roles) {
                    if (roles.hasOwnProperty(key)) {
                        role = roles[key];
                        // The class for the other role may not have loaded yet
                        if (role.cls) {
                        	assocStore=record[role.getterName]();
                        	//update the assocStore if exist
                        	if(assocStore){
                        		//update the assoc store too                            
                        		assocStore.loadRawData(role.reader.getRoot(record.data));
                        		delete record.data[role.role];
                        	}
                        }
                    }
                }

            };

        //if we have includes, then we can read the associations
        if(includes) {
            //if there is already an success handler, we have to call both
            if(options.success) {
                callback = options.success;
                options.success = function(rec, operation) {
                    readAssoc(rec);
                    Ext.callback(callback, scope, [rec, operation]);
                };
            }
            else {
                options.success = readAssoc;
            }
        }
        this.callParent([options]);
    }
});

/**
 * needed for ext-6.2.0
 * no info if known as ExtJS issue and if it is fixed in the future
 * must be rechecked (just modify a tasks usertacking on the server, make a task.load and see if task.userTracking() contains the updated data)!
 * Problem: On using model associations (for example the task model with the associated userTracking), 
 *          the assoc stores (for example userTracking) are not updated when reloading the task model (with load method or on save)
 *          AND when the assoc store should get its data from task raw data (and not as a separate load request)
 *          Here it seems that this is a bug, since in the responsible getAssociatedStore method, the case 
 *          if a store already exists and new data is given, is just not handled. This is done right now by the below override
 *           
 */
Ext.override(Ext.data.schema.Role, {
    getAssociatedStore: function(inverseRecord, options, scope, records, allowInfer) {
        var me = this,
            storeName = me.getStoreName(),
            store = inverseRecord[storeName],
            load = options && options.reload;
        
        if(store && !options && !load && records) {
            //this case is never handled in the original getAssociatedStore, but it should since otherwise the data is not loaded
            store.loadRawData(records);
            return store;
        }
        else {
            return this.callParent([inverseRecord, options, scope, records, allowInfer]);
        }
    }
});
/***
 * Set the default dismis delay of the tooltip to 15 sec. In translate5 there are some larger tooltip texts
 * and 5 sec (default dismis delay before override) is to short
 */
Ext.override(Ext.tip.ToolTip, {
    dismissDelay:15000
});


/***
 * Check for column lenght throws an exception when not columns are defined.
 * Check if the columns object exist before lenght check
 */
Ext.override(Ext.panel.Table, {
    buildColumnHash: function(columns) {
        var len = columns &&  columns.length,
            columnState, i, result;
        // Create a useable state lookup hash from which each column
        // may look up its state based upon its stateId
        // {
        //      col_name: {
        //          index: 0,
        //          width: 100,
        //          locked: true
        //      },
        //      col_details: {
        //          index: 1,
        //          width: 200,
        //          columns: {
        //              col_details_1: {
        //                  index: 0,
        //                  width: 100
        //              },
        //              col_details_2: {
        //                  index: 1,
        //                  width: 100
        //              }
        //          }
        //      },
        // }
        if (columns) {
            result = {};
            for (i = 0 , len = columns.length; i < len; i++) {
                columnState = columns[i];
                columnState.index = i;
                if (columnState.columns) {
                    columnState.columns = this.buildColumnHash(columnState.columns);
                }
                result[columnState.id] = columnState;
            }
            return result;
        }
    }
});