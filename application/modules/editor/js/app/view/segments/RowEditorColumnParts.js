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
 * @class Editor.view.segments.Translate5RowEditor
 * @extends Ext.form.Panel
 * 
 * erweitert den Orginal Ext Editor um eigene Funktionalität
 */
Ext.define('Editor.view.segments.Translate5RowEditor', {
    extend: 'Ext.grid.RowEditor',
    alias: 'widget.translate5roweditor',

    // from Ext4 begin
    itemId: 'roweditor',
    
    liveDrag: true,
    
    //beinhaltet den gekürzten Inhalt des letzten geöffneteten Segments
    lastSegmentShortInfo: '',
    columnToEdit: null,
    
    rowToEditOrigHeight: 0,
    editorExtraHeight: 20,
    editorFieldExtraHeight: 10,
    editorLocalTop: 0,
    fieldToEdit: null,
    
    previousRecord: null,
    timeTrackingData: null,
    columns: null,
    messages: {
        segmentNotSavedUserMessage: 'Das Segment konnte nicht gespeichert werden. Bitte schließen Sie das Segment ggf. durch Klick auf "Abbrechen" und öffnen, bearbeiten und speichern Sie es erneut. Vielen Dank!',
        cantSaveEmptySegment: '#UT#Das Segment kann nicht ohne Inhalt gespeichert werden!'
    },
    // from Ext4 end

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
           
        // From Ext4 begin
        me.on('render', function(p) {
            p.body.on('dblclick', me.changeColumnByClick, me);
        });
        me.mainEditor = me.add(new Editor.view.segments.HtmlEditor());
        // From Ext4 end
    },
    
    // From Ext4 begin
    /**
     * handles clicking on the displayfields of the roweditor to change the editor position
     * @param {Ext.Event} ev
     * @param {DOMNode} target
     */
    changeColumnByClick: function(ev, target) {
        var me = this, 
            cmp = null;
 
        //FIXME use fields instead of columns here? Then we can remove the columns filling
        //bubble up to the dom element which is the el of the Component
        while (target && target.nodeType === 1) {
            if(/displayfield-[0-9]+/.test(target.id)) {
                cmp = me.columns.get(target.id);
                if (cmp) {
                    break;
                }
            }
            target = target.parentNode;
        }
        if(cmp) {
            me.changeColumnToEdit(cmp);
        }
    },
    /**
     * changes the maineditor to the given column
     * @param {Editor.view.segments.column.ContentEditable} column
     */
    changeColumnToEdit: function(column) {
        var me = this,
            oldIdx = me.columnToEdit,
            rec = me.context.record,
            oldField = me.query('displayfield[name="'+oldIdx+'"]');
        if(oldIdx == column.dataIndex) {
            //column did not change
            return;
        }
        if(!me.saveMainEditorContent(rec)) {
            return; //errors on saving, also do not change
        }
        //sync content back to the displayfield
        if(oldField && oldField.length > 0) {
            oldField[0].setValue(rec.get(oldIdx));
        }
        if(me.setColumnToEdit(column)) {
            me.mainEditor.setValueAndMarkup(rec.get(me.columnToEdit), rec.get('id'), me.columnToEdit);
            me.setLastSegmentShortInfo(me.mainEditor.lastSegmentContentWithoutTags.join(''));
        }
        me.focusContextCell();
    },
    /**
     * Method Implements that we can have multiple editable columns, but only one HtmlEditor Instance
     * This is done by swaping the position of the different field editors
     *
     * @param {Ext.grid.column.Column} col
     * @return {Boolean} returns true if column has changed, false otherwise
     */
    setColumnToEdit: function(col) {
        var me = this,
            firstTarget = Editor.view.segments.column.ContentEditable.firstTarget, //is the dataIndex
            toEdit = col.dataIndex,
            hasToSwap = false,
            linkedDisplayField = null;
                    
        if(col.segmentField) {
            me.mainEditor.fieldTypeToEdit = col.segmentField.get('type');
        }
        //if user clicked on a not content column open default dataindex (also if it is a content column but not editable)
        if(!col.segmentField || !col.segmentField.get('editable')) {
            toEdit = firstTarget;
            me.mainEditor.fieldTypeToEdit = Editor.model.segment.Field.prototype.TYPE_TARGET;
        }
        //if its the readonly column take the edit one
        else if(col.isContentColumn && !col.isEditableContentColumn) {
            toEdit = col.dataIndex+'Edit';
        }
        //no swap if last edited column was the same
        hasToSwap = me.columnToEdit !== toEdit;
        if(hasToSwap && me.columnToEdit){
            me.stopTimeTrack(me.columnToEdit);
        }
        me.startTimeTrack(me.toEdit);
        
        me.items.each(function(field){
            if(!me.columns.containsKey(field.id)) {
                return; //ignore the editor itself, which has no col mapping
            }
            var vis = me.columns.get(field.id).isVisible();
            if(field.name == toEdit) {
                linkedDisplayField = field;
                return;
            }
        });
        
        //all editor fields disabled
        //FIXME test the alle fields disabled case (open task readonly?)
        if(!linkedDisplayField || !hasToSwap) {
            if(!linkedDisplayField) {
                me.linkedDisplayField = false;
            }
            return false;
        }
        me.columnToEdit = toEdit;
        me.columnClicked = col.dataIndex;
        me.mainEditor.dataIndex = col.dataIndex;
        
        //if isset linkedDisplayField the cols get changed in focusContextCell
        me.linkedDisplayField = linkedDisplayField;
        return true;
    },
    /**
     * overrides original focusing with our repositioning of the editor
     */
    focusContextCell: function() {
        console.log("focusContextCell");
        console.trace();
        var me = this, 
            toDis = me.linkedDisplayField,
            pos;
   
        if(! toDis) {
            me.mainEditor.deferFocus();
            return;
        }

        pos = toDis.getPosition(true);
       
        //disable editor if column was also disabled
        me.mainEditor.setWidth(toDis.width);
        //swap position
        me.repositionMainEditor(pos[0]);
        me.scrollMainEditorHorizontallyInView();
        me.mainEditor.deferFocus();
        me.fireEvent('afterEditorMoved', me.columnToEdit, me);
        return true;
    },
    /**
     * repositions the grid view so that, the mainEditor is visible after change the editing column
     */
    scrollMainEditorHorizontallyInView: function () {
        var me = this,
            view = me.editingPlugin.grid.getView(),
            gridReg = view.getEl().getRegion(),
            offset,
            edReg = me.mainEditor.getEl().getRegion();
        
        if(gridReg.contains(edReg)) {
            return;
        }
        
        if(edReg.right > gridReg.right) {
            offset = -1 * gridReg.getOutOfBoundOffsetX(edReg.right) + 10;
            view.scrollBy(offset, 0, true);
        }
        else {
            offset = -1 * gridReg.getOutOfBoundOffsetX(edReg.x) - 10;
            view.scrollBy(offset, 0, true);
        }
    },
    /**
     * reusable method to get info which field is edited by opened editor
     * returns the dataIndex of the field
     * @return {String}
     */
    getEditedField: function() {
        return this.columnToEdit;
    },

    /**
     * shows / hides the main editor, used as show hide column handler
     */
    toggleMainEditor: function(show) {
        this.mainEditor.setVisible(show);
    },
    
    /**
     * erweitert die Orginal Methode
     * @returns {Boolean}
     */
    completeEdit: function() {
        console.trace();
        var me = this,
            rec = me.context.record;

        if(!me.saveMainEditorContent(rec)) {
            return false;
        }
        
        me.stopTimeTrack(me.columnToEdit);
        //we have to provide the durations to the change alike handler, 
        //since the record is available there, we put them into a tmp field,
        //the setted durations field is overwritten / cleared by successfull PUT
        rec.set('durations', me.getTimeTrackingData());

        me.restoreEditingRowHeight();
        //me.hide();
        me.previousRecord = me.editingPlugin.openedRecord;
        me.editingPlugin.openedRecord = null;
        return true;
    },
    /**
     * saves the Editor Content into the loaded record
     * @returns {Boolean}
     */
    saveMainEditorContent: function(record) {
        var me = this,
            //der replace aufruf entfernt vom Editor automatisch hinzugefügte unsichtbare Zeichen, 
            //und verhindert so, dass der Record nicht als modified markiert wird, wenn am Inhalt eigentlich nichts verändert wurde
            //newValue = Ext.String.trim(me.mainEditor.getValueAndUnMarkup()).replace(/\u200B/g, '');
            newValue = me.mainEditor.getValueAndUnMarkup().replace(/\u200B/g, '');
            
        //check, if the context delivers really the correct record, because through some issues in reallive data 
        //rose the idea, that there might exist special race conditions, where
        //the context.record is not the record of the newValue
        if(me.editingPlugin.openedRecord === null || me.editingPlugin.openedRecord.internalId != record.internalId ){
            Editor.MessageBox.addError(me.messages.segmentNotSavedUserMessage + newValue,' Das Segment konnte nicht gespeichert werden. Im folgenden der Debug-Werte: ' + newValue + 'me.editingPlugin.openedRecord.internalId: ' + me.editingPlugin.openedRecord.internalId + ' record.internalId: ' + record.internalId);
            me.editingPlugin.openedRecord = null;
            return false;
        }
        
        if(newValue.length == 0 && record.get(me.columnToEdit).length > 0) {
            Editor.MessageBox.addError(me.messages.cantSaveEmptySegment);
            return false;
        }
        
        if(me.mainEditor.hasAndDisplayErrors()) {
            return false;
        }
        me.setLastSegmentShortInfo(me.mainEditor.lastSegmentContentWithoutTags.join(''));
        record.beginEdit();
        record.set(me.columnToEdit, newValue);
        record.set('autoStateId', 999);
        record.endEdit(true); //silent = true → dont notify the store. if notifiying the store we get a "grid jumps to top problem" with left right navi
        return true;
    },
    /**
     * cancels the editing process
     */
    cancelEdit: function() {
        var me = this;
      
        me.context.record.reject();
        me.getTimeTrackingData();
        me.editingPlugin.openedRecord = null;
        me.restoreEditingRowHeight();
        me.callParent(arguments);
    },
    /**
     * setzt den gekürzten Inhalt des letzten Segments. Muss mit dem "gemarkupten" Content aufgerufen werden um alle Tags zu entfernen. 
     * @param segmentText
     */
    setLastSegmentShortInfo: function (segmentText) {
      this.lastSegmentShortInfo = Ext.String.ellipsis(Ext.util.Format.stripTags(segmentText), 60, true);
    },
    /**
     * starts tracking the editing time for the given field
     * @param {String} field
     */
    startTimeTrack: function(field) {
        if(!this.timeTrackingData) {
            this.timeTrackingData = {};
        }
        this.timeTrackingData._start = new Date();
    },
    /**
     * stops and saves the elapsed milliseconds since last startTimeTrack call for the given field
     * @param {String} field
     */
    stopTimeTrack: function(field) {
        var end = new Date(), 
            data = this.timeTrackingData,
            value = data[field],
            duration;
        if(!data._start) {
            return;
        }
        duration = end - data._start;
        delete(data._start);
        if(value) {
            duration += value;
        }
        data[field] = duration;
    },
    /**
     * resets the tracking information and returns them as a object
     * @return {Object}
     */
    getTimeTrackingData: function() {
        var result = this.timeTrackingData;
        delete(result._start);
        this.timeTrackingData = {};
        return result;
    },
    // From Ext4 end

    //
    // Grid listener added when this is rendered.
    // Keep our containing element sized correctly
    // @override
    //
    onGridResize: function() {
        var me = this,
            clientWidth = me.getClientWidth();

        me.setWidth(clientWidth);
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
                constrainTo: grid.body
                //constrainTo: me.view.body//,
                //delegate: '#' + me.header.id
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
        if (scrollTopChanged || scrollLeftChanged)
        {
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
        console.log("REPO", -me.lastScrollLeft, me.editorLocalTop);
        console.trace();
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
     * sets the initial position of the roweditor after opening a segment
     * @param {} animateConfig
     * @param {} fromScrollHandler
     */
    initialPositioning: function(animateConfig, fromScrollHandler){
        var me = this,
            context = me.context,
            grid = me.editingPlugin.grid,
            row = context && context.row,
            rowTop,
            afterPosition;

        // Position this editor if the context row is rendered (buffered rendering may mean that it's not in the DOM at all)
        if (row && Ext.isElement(row)) 
        {
            rowTop = Ext.fly(row).getOffsetsTo(grid.body)[1] - grid.el.getBorderWidth('t') + me.lastScrollTop;
            me.editorLocalTop = me.calculateEditorTop(rowTop);

            // Get the y position of the row relative to its top-most static parent.
            // offsetTop will be relative to the table, and is incorrect
            // when mixed with certain grid features (e.g., grouping).
            console.log("INIT");
            me.reposition();
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
    
    loadRecord: function(record) {
        var me = this;
        me.callParent(arguments);
        
        me.setColumnToEdit(me.context.column);
        me.mainEditor.setValueAndMarkup(record.get(me.columnToEdit), record.get('id'), me.columnToEdit);
        me.setLastSegmentShortInfo(me.mainEditor.lastSegmentContentWithoutTags.join(''));
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

//        if (!me.rendered || !wrapEl) {
        if (!me.rendered) {
            if (!me.rendered)
            {
                me.width = me.getClientWidth();
                me.render(grid.el, grid.el.dom.firstChild);
            }
            // The wrapEl is a container for the editor.
//            wrapEl = me.wrapEl = me.el.wrap();
            // Change the visibilityMode to offsets so that we get accurate measurements
            // when the roweditor is hidden for laying out things like a TriggerField.
//            wrapEl.setVisibilityMode(3);

//            wrapEl.addCls(me._wrapCls);
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
        me.focusContextCell();
    },
    /**
     * just returns the given delta, since buttons are disabled
     * @param {} delta
     * @return {}
     */
    syncButtonPosition: function(delta) {
        return delta;
    },
    /**
     * place the HtmlEditor/MainEditor in the rowEditor over the desired displayfield
     */
    repositionMainEditor: function(newX) {
        var me = this;

        if(newX || newX === 0) {
            me.editorNewXPosition = newX;
        }
        else {
            newX = me.editorNewXPosition;
        }
        me.mainEditor.setPosition(newX, 0);
    },
    setEditorHeight: function() {
        var me = this,
            context = me.context,
            row = Ext.get(context.row),
            rowHeight = row.getHeight(),
            editorHeight = rowHeight + me.editorExtraHeight,
            editorFieldHeight = rowHeight + me.editorFieldExtraHeight;
        
        me.rowToEditOrigHeight = rowHeight;
        row.setHeight(editorHeight);
        me.setHeight(editorHeight);
        me.mainEditor.setHeight(editorFieldHeight);
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
