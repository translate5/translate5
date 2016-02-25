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

/**
 * @class Editor.view.segments.RowEditor
 * @extends Ext.grid.RowEditor
 * 
 * @TODO rename in RowEditor
 * This is the RowEditor part of this multi class component.
 * The original RowEditor is overriden for custom needs (no buttons, our own positioning, no wrapEl)
 */
Ext.define('Editor.view.segments.RowEditor', {
    extend: 'Ext.grid.RowEditor',
    alias: 'widget.segmentroweditor',
    requires: [
        'Editor.view.segments.RowEditorColumnParts'
    ],
    itemId: 'roweditor',
    liveDrag: true,
    rowToEditOrigHeight: 0,
    editorExtraHeight: 20,
    editorLocalTop: 0,
    /**
     * If set to true, rowEditor remains on its position on startEdit and grid scrolls instead
     */
    isScrollUnderMoveMode: false,
    
    columns: null,

    initComponent: function() {
        var me = this,
            grid = me.editingPlugin.grid,
            Container = Ext.container.Container,
            form, normalCt, lockedCt;
           
        me.columns = new Ext.util.HashMap();

        me.cls = Ext.baseCSSPrefix + 'grid-editor ' + Ext.baseCSSPrefix + 'grid-row-editor';

        me.layout = {
            type: 'hbox',
            align: 'middle'
        };

        me.lockable = grid.lockable;

        // initialize a scroller instance for maintaining horizontal scroll position
        me.setScrollable({
            x: false,
            y: false
        });

        me.lockedColumnContainer = me.normalColumnContainer = me;

        me.callParent();

        if (me.fields) {
            me.addFieldsForColumn(me.fields, true);
            me.insertColumnEditor(me.fields);
            delete me.fields;
        }

        me.mon(Ext.GlobalEvents, {
            scope: me,
            show: me.repositionIfVisible
        });
        
        form = me.getForm();
        form.trackResetOnLoad = true;
        form.on('errorchange', me.onErrorChange, me);
    },
    /**
     * cancels the editing process
     */
    cancelEdit: function() {
        var me = this;
        me.restoreEditingRowHeight();
        me.callParent(arguments);
    },
    completeEdit: function() {
        var me = this;
        me.restoreEditingRowHeight();
        me.hide();
    },
    //
    // Grid listener added when this is rendered.
    // Keep our containing element sized correctly
    // @override
    //
    onGridResize: function() {
        var me = this,
            clientWidth = me.getClientWidth();
        //FIXME update clipping width
    },
    
    updateButton: function(valid) {
        //do nothing since we don't use buttons!
    },
    
    /**
     * implements mouse dragging of the row editor
     */
    onBoxReady: function() {
        var me = this,
            grid = me.editingPlugin.grid,
            ddConfig = {
                el: me.el,
                constrain: true,
                constrainDelegate: true,
                listeners: {
                    dragend: function() {
                        console.log("DRAGEND", me.getOffsetsTo(grid.body)[1], me.lastScrollTop, me.getOffsetsTo(grid.body)[1] - me.lastScrollTop);
                        me.editorLocalTop = me.getOffsetsTo(grid.body)[1];
                    }
                },
                constrainTo: grid.body,
                //constrainTo: me.view.body//,
                delegate: '.x-form-display-field-body'
            };

        me.dd = new Ext.util.ComponentDragger(me, ddConfig);
        //override onDrag so that component can only be moved vertically. 
        //This would not be needed if we use view body as constrainTo
        //but we must use grid.body, since view.body will have the height of the available segments,
        //which is to less if only 2 segments are available by filter for example
        me.dd.onDrag = function(e) {
            var me = this,
                comp = (me.proxy && !me.comp.liveDrag) ? me.proxy : me.comp,
                offset = me.getOffset(me.constrain || me.constrainDelegate ? 'dragTarget' : null);
    
            comp.setPagePosition(me.startPosition[0], me.startPosition[1] + offset[1]);
        }.bind(me.dd);
        
        me.relayEvents(me.dd, ['dragstart', 'drag', 'dragend']);
    },
    
    /**
     * @override
     */
    onViewScroll: function(){
        var me = this,
            viewEl = me.editingPlugin.view.el,
            scrollingView = me.scrollingView,
            scrollTop  = scrollingView.getScrollY(),
            scrollLeft = scrollingView.getScrollX(),
            scrollTopChanged = scrollTop !== me.lastScrollTop;
            scrollLeftChanged = scrollLeft !== me.lastScrollLeft;

        me.lastScrollTop  = scrollTop;
        me.lastScrollLeft = scrollLeft;
        if (scrollLeftChanged) {
            me.reposition();
        }
    },

    insertColumnEditor: function(column) {
        var me = this;
        me.callParent(arguments);
        if(column.getEditor) {
            me.columns.add(column.getEditor().id, column);
        }
    },

    /**
     * Doing nothing with the tab key, since navigation is done by our own keys
     * @param {} e
     */
    onFieldTab: function(e) {
    },

    getFloatingButtons: function() {
        throw "getFloatingButtons must not be used!";
    },
    
    getRefItems: function(deep) {
        var me = this;
        //using the panels getRefItems method instead the roweditor one
        return me.superclass.superclass.getRefItems.apply(me, arguments);
    },

    /**
     * ensures that the roweditor stays at the initial opened position
     * @param {} animateConfig
     * @param {} fromScrollHandler
     */
    reposition: function(animateConfig, fromScrollHandler) {
        var me = this;
        me.el.setLocalXY(-me.lastScrollLeft, me.editorLocalTop);
        //TODO if overlapping the scrollbar is a problem, we must rebuild/refactor the syncEditorClip method
        //perhaps it is easier, roweditor width must be "view size" + scrollLeft
        //me.syncEditorClip(); 
    },
    
    /**
     * overriding to remain editor open on view refresh
     * @param {} view
     */
    onViewRefresh: function(view) {
        var me = this,
            context = me.context,
            row;
        // Recover our row node after a view refresh
        if (context && (row = view.getRow(context.record))) {
            context.row = row;
            me.reposition();
            if (me.tooltip && me.tooltip.isVisible()) {
                me.tooltip.setTarget(context.row);
            }
        }
    },
    
    /**
     * overriding
     */
    onViewItemRemove: function() {
        //do nothing here, since we want to keep the editor also on scrolling through different ranges 
    },
    
    /**
     * sets the initial position of the roweditor after opening a segment
     * @param {} animateConfig
     * @param {} fromScrollHandler
     */
    initialPositioning: function(animateConfig, fromScrollHandler){
        var me = this,
            context = me.context,
            grid = me.editingPlugin.grid,
            row = context && context.row,
            rowIdx = context && context.rowIdx,
            rowTop,
            moveEditor;

        // Position this editor if the context row is rendered (buffered rendering may mean that it's not in the DOM at all)
        if(!row || !Ext.isElement(row)) {
            return;
        }
        moveEditor = function() {
            // Get the y position of the row relative to its top-most static parent.
            // offsetTop will be relative to the table, and is incorrect
            // when mixed with certain grid features (e.g., grouping).
            rowTop = Ext.fly(row).getOffsetsTo(grid.body)[1] - grid.el.getBorderWidth('t') + me.lastScrollTop;
            me.editorLocalTop = me.calculateEditorTop(rowTop);
            me.reposition();
        };
        if (me.isScrollUnderMoveMode) {
            console.log("INIT TO MOVE", context);
            //giving the finalScroller as fallback handler to the scroll command
            grid.scrollTo(rowIdx, 'editor', moveEditor);
        }
        else {
            console.log("INIT");
            moveEditor();
        }
    },

    /**
     * same as original, expect the button height.
     * @return {Number} the scroll delta. Zero if scrolling is not required.
     */
    getScrollDelta: function() {
        var me = this,
            scrollingViewDom = me.scrollingViewEl.dom,
            context = me.context,
            body = me.body,
            deltaY = 0;

        if (context) {
            deltaY = Ext.fly(context.row).getOffsetsTo(scrollingViewDom)[1];
            if (deltaY < 0) {
                deltaY -= body.getBorderPadding().beforeY;
            }
            else if (deltaY > 0) {
                deltaY = Math.max(deltaY + me.getHeight() -
                    scrollingViewDom.clientHeight - body.getBorderWidth('b'), 0);
                if (deltaY > 0) {
                    deltaY -= body.getBorderPadding().afterY;
                }
            }
        }
        return deltaY;
    },

    //
    // Calculates the top pixel position of the passed row within the view's scroll space.
    // So in a large, scrolled grid, this could be several thousand pixels.
    //
    XcalculateLocalRowTop: function(row) {
        var grid = this.editingPlugin.grid;
        return Ext.fly(row).getOffsetsTo(grid)[1] - grid.el.getBorderWidth('t') + this.lastScrollTop;
    },

    // Given the top pixel position of a row in the scroll space,
    // calculate the editor top position in the view's encapsulating element.
    // This will only ever be in the visible range of the view's element.
    XcalculateEditorTop: function(rowTop) {
        var me = this,
            context = me.context,
            row = Ext.get(context.row),
            grid = me.editingPlugin.grid,
            viewHeight = grid.getHeight();
            
        return (viewHeight / 2);
    },
    
    //FIXME unklar warum dieses if column dazu.
    XrenderColumnData: function(field, record, activeColumn) {
        var me = this,
            grid = me.editingPlugin.grid,
            headerCt = grid.headerCt,
            view = me.scrollingView,
            store = view.dataSource,
            column = activeColumn || field.column,
            value,
            renderer,
            metaData,
            rowIdx,
            colIdx,
            columns,
            i,
            scope;
            
        if (!column)
        {
            columns = grid.columns;
            for (i = 0; i < columns.length; i++)
            {
                if (field.id == columns[i].field.id)
                {
                    column = field.column = columns[i];
                    break;
                }
            }
        }
        
        value = record.get(column.dataIndex);
        renderer = column.editRenderer || column.renderer;
        scope = (column.usingDefaultRenderer && !column.scope) ? column : column.scope;
        

        // honor our column's renderer (TemplateHeader sets renderer for us!)
        if (renderer) {
            metaData = { tdCls: '', style: '' };
            rowIdx = store.indexOf(record);
            colIdx = headerCt.getHeaderIndex(column);

            value = renderer.call(
                scope || headerCt.ownerCt,
                value,
                metaData,
                record,
                rowIdx,
                colIdx,
                store,
                view
            );
        }

        field.setRawValue(value);
    },

    /**
     * Start editing the specified grid at the specified position.
     * @param {Ext.data.Model} record The Store data record which backs the row to be edited.
     * @param {Ext.data.Model} columnHeader The Column object defining the column to be focused
     */
    startEdit: function(record, columnHeader) {
        var me = this,
            editingPlugin = me.editingPlugin,
            grid = editingPlugin.grid,
            context = me.context = editingPlugin.context,
            alreadyVisible = me.isVisible(),
            wrapEl = me.wrapEl;

        // Ensure that the render operation does not lay out
        // The show call will update the layout
        Ext.suspendLayouts();

        if (!me.rendered) {
            if (!me.rendered)
            {
                me.width = me.getClientWidth();
                me.render(grid.el, grid.el.dom.firstChild);
            }
            // On first show we need to ensure that we have the scroll positions cached
            me.onViewScroll();
        }
        
        // Select at the clicked position.
        context.grid.getSelectionModel().selectByPosition({
            row: record,
            column: columnHeader
        });

        // Make sure the container el is correctly sized.
        me.onGridResize();

        // Reload the record data
        me.loadRecord(record);

        // Layout the form with the new content if we are already visible.
        // Otherwise, just allow resumption, and the show will update the layout.
        Ext.resumeLayouts(alreadyVisible);
        if (alreadyVisible) {
            me.setEditorHeight();
            me.setEditorWidth();
            me.reposition(true);
        } else {
            me.show();
        }
    },
    /**
     * Start editing the specified grid at the specified position.
     * The valid modes: 
     * 0: for default positioning
     * 1: for scroll grid instead move editor
     * => The values are defined as constants in the RowEditor Plugin
     * @param {Integer} mode 
     */
    setMode: function(mode) {
        this.isScrollUnderMoveMode = (mode === 1);
    },
    /**
     * just returns the given delta, since buttons are disabled
     * @param {} delta
     * @return {}
     */
    syncButtonPosition: function(delta) {
        return delta;
    },
    setEditorHeight: function() {
        console.log("setEditorHeight called");
        var me = this,
            context = me.context,
            row = Ext.get(context.row),
            rowHeight = row.getHeight(),
            editorHeight = rowHeight + me.editorExtraHeight,
            moveEditor = (me.editorLocalTop + editorHeight) - me.scrollingView.getHeight();
        
        me.rowToEditOrigHeight = rowHeight;
        row.setHeight(editorHeight);
        //low border of editor is outside of the visible area, then we have to move the editor additionaly
        if(moveEditor > 0) {
            me.editorLocalTop -= moveEditor;
            me.editorLocalTop = Math.max(me.editorLocalTop, 0);
            me.reposition();
        }
        me.setHeight(editorHeight);
    },
    restoreEditingRowHeight: function() {
        var me = this,
            context = me.context,
            row = Ext.get(context.row);

        row.setHeight(me.rowToEditOrigHeight);
        me.rowToEditOrigHeight = 0;
    },
    setEditorWidth: function() {
        var me = this,
            editingPlugin = me.editingPlugin,
            grid = editingPlugin.grid,
            i, columnsWidth = 0;
            
        for (i = 0; i < grid.columns.length; i++)
        {
            if (grid.columns[i].isVisible())
            {
                columnsWidth += grid.columns[i].getWidth();
            }
        }
        
        me.setWidth(columnsWidth);
    },
    /**
     * overriden for wrapEl disabling and initial positioning
     */
    onShow: function() {
        var me = this;

        //me.wrapEl.show();
        me.superclass.superclass.onShow.apply(me, arguments);
        if (me.needsSyncFieldWidths) {
            me.suspendLayouts();
            me.syncAllFieldWidths();
            me.resumeLayouts(true);
        }
        delete me.needsSyncFieldWidths;

        me.setEditorHeight();
        me.setEditorWidth();
        me.initialPositioning();
    },

    /**
     * overriden for wrapEl disabling
     * @return {}
     */
    onHide: function() {
        var me = this,
            column,
            focusContext;

            // Try to push focus into the cell below the active field
        if (me.activeField) {
            column = me.activeField.column;
            focusContext = new Ext.grid.CellContext(column.getView()).setPosition(me.context.record, column);
            focusContext.view.getNavigationModel().setPosition(focusContext);
            me.activeField = null;
        }
        me.superclass.superclass.onHide.apply(me, arguments);
        if (me.tooltip) {
            me.hideToolTip();
        }
    },

    onResize: function(width, height) {
        //FIXME resize element instead of wrapEl?
        //this.wrapEl.setSize(width, height);
    },

    beforeDestroy: function(){
        Ext.destroy(this.tooltip);
        this.callParent();
    }
});
