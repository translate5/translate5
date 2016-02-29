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
    extend: 'Ext.form.Panel',
    alias: 'widget.translate5roweditor',
    requires: [
        'Ext.tip.ToolTip',
        'Ext.util.KeyNav'
    ],

    lastScrollLeft: 0,
    lastScrollTop: 0,

    border: false,

    _wrapCls: Ext.baseCSSPrefix + 'grid-row-editor-wrap',

    errorCls: Ext.baseCSSPrefix + 'grid-row-editor-errors-item',

    // Change the hideMode to offsets so that we get accurate measurements when
    // the roweditor is hidden for laying out things like a TriggerField.
    hideMode: 'offsets',
    
    // from Ext4 begin
    itemId: 'roweditor',
    
    //beinhaltet den gekürzten Inhalt des letzten geöffneteten Segments
    lastSegmentShortInfo: '',
    columnToEdit: null,
    rowToEditOrigHeight: 0,
    editorExtraHeight: 20,
    editorFieldExtraHeight: 10,
    editorShadowsExtraHeight: 4, // see css/main.css class .x-grid-row-editor-wrap
    fieldToEdit: null,
    previousRecord: null,
    timeTrackingData: null,
    columns: null,
    messages: {
        segmentNotSavedUserMessage: 'Das Segment konnte nicht gespeichert werden. Bitte schließen Sie das Segment ggf. durch Klick auf "Abbrechen" und öffnen, bearbeiten und speichern Sie es erneut. Vielen Dank!',
        cantSaveEmptySegment: '#UT#Das Segment kann nicht ohne Inhalt gespeichert werden!',
        cantEditContents: '#UT#You cannot edit segment content! Please use (Ctrl+Z) to undo changes or cancel editing.'
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

        // Create field containing structure for when editing a lockable grid.
        if (me.lockable) {
            me.items = [
                // Locked columns container shrinkwraps the fields
                lockedCt = me.lockedColumnContainer = new Container({
                    id: grid.id + '-locked-editor-cells',
                    scrollable: {
                        x: false,
                        y: false
                    },
                    layout: {
                        type: 'hbox',
                        align: 'middle'
                    },
                    // Locked grid has a border, we must be exactly the same width
                    margin: '0 1 0 0'
                }),

                // Normal columns container flexes the remaining RowEditor width
                normalCt = me.normalColumnContainer = new Container({
                    // not user scrollable, but needs a Scroller instance for syncing with view
                    scrollable: {
                        x: false,
                        y: false
                    },
                    flex: 1,
                    id: grid.id + '-normal-editor-cells',
                    layout: {
                        type: 'hbox',
                        align: 'middle'
                    }
                })
            ];

            // keep horizontal position of fields in sync with view's horizontal scroll position
            //FIXME disabling the addPartner calls, since they are leading to inconsistent scrolling positions.
            //We have to test the RowEditor if scrolling works well, if yes then we can delete the addPartner calls:
            //lockedCt.getScrollable().addPartner(grid.lockedGrid.view.getScrollable(), 'x');
            //normalCt.getScrollable().addPartner(grid.normalGrid.view.getScrollable(), 'x');
        } else {
            // initialize a scroller instance for maintaining horizontal scroll position
            me.setScrollable({
                x: false,
                y: false
            });

            // keep horizontal position of fields in sync with view's horizontal scroll position
            //FIXME disabling the addPartner calls, since they are leading to inconsistent scrolling positions.
            //We have to test the RowEditor if scrolling works well, if yes then we can delete the addPartner calls:
            //me.getScrollable().addPartner(grid.view.getScrollable(), 'x');

            me.lockedColumnContainer = me.normalColumnContainer = me;
        }

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
     * setzt das Editor Feld im RowEditor anhand der Config in der Spalte.
     * überschreibt die Orginal Methode, die Unterschiede sind im Code kommentiert
     * das Setzen der internen Referenz mainEditor kommmt hinzu.
     * @param column
     */
    setField_Obsolete: function(column) {
        var me = this,
            field;

        if (Ext.isArray(column)) {
            Ext.Array.forEach(column, me.setField, me);
            return;
        }

        //Ist die Spalte versteckt und kann nicht angezeigt werden, soll auch kein Editor dafür angezeigt werden
        if(!column.hideable && column.hidden){
            return;
        }
        
        // Get a default display field if necessary
        field = column.getEditor(null, {
            xtype: 'displayfield',
            // Default display fields will not return values. This is done because
            // the display field will pick up column renderers from the grid.
            getModelData: function() {
                return null;
            }
        });
        field.margins = '0 0 0 2';
        
        field.setWidth(column.getDesiredWidth() - 2);
        me.mon(field, 'change', me.onFieldChange, me);

        // Maintain mapping of fields-to-columns
        // This will fire events that maintain our container items
        me.columns.add(field.id, column);
        if (column.hidden) {
            me.onColumnHide(column);
        }
        if (me.isVisible() && me.context) {
            me.renderColumnData(field, me.context.record);
        }
    },
    /**
     * handles clicking on the displayfields of the roweditor to change the editor position
     * @param {Ext.Event} ev
     * @param {DOMNode} target
     */
    changeColumnByClick: function(ev, target) {
        var me = this, 
            cmp = null;
 
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
        else if(col instanceof Editor.view.segments.column.Content) {
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
        me.repositionHorizontally();
        me.mainEditor.deferFocus();
        me.fireEvent('afterEditorMoved', me.columnToEdit, me);
        return true;
    },
    /**
     * repositions the grid view so that, the mainEditor is visible after change the editing column
     */
    repositionHorizontally: function () {
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
        me.hide();
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
            newValue = me.mainEditor.getValueAndUnMarkup().replace(/\u200B/g, ''),
            oldValue = record.get(me.columnToEdit).replace(/\u200B/g, ''),
            reMqmTag = /<img[^>]+>/g;
            
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
        if (Editor.data.task.get('notEditContent') && (newValue.replace(reMqmTag, '') != oldValue.replace(reMqmTag, '')) ) {
            Editor.MessageBox.addError(me.messages.cantEditContents);
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
        var me = this,
            form   = me.getForm(),
            fields = form.getFields(),
            items  = fields.items,
            length = items.length,
            i;
      
        me.context.record.reject();
        me.getTimeTrackingData();
        me.editingPlugin.openedRecord = null;
        
        me.restoreEditingRowHeight();
        me.hide();
        form.clearInvalid();

        // temporarily suspend events on form fields before reseting the form to prevent the fields' change events from firing
        for (i = 0; i < length; i++) {
            items[i].suspendEvents();
        }

        form.reset();

        for (i = 0; i < length; i++) {
            items[i].resumeEvents();
        }
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
    //
    onGridResize: function() {
        var me = this,
            clientWidth = me.getClientWidth(),
            grid = me.editingPlugin.grid,
            gridBody = grid.body;

        me.wrapEl.setLocalX(gridBody.getOffsetsTo(grid)[0] + gridBody.getBorderWidth('l') - grid.el.getBorderWidth('l'));
        
        me.setWidth(clientWidth);
        if (me.lockable) {
            me.lockedColumnContainer.setWidth(grid.lockedGrid.view.el.dom.clientWidth);
        }
    },
    
    syncAllFieldWidths: function() {
        var me = this,
            editors = me.query('[isEditorComponent]'),
            len = editors.length,
            column, i;

        // In a locked grid, a RowEditor uses 2 inner containers, so need to use CQ to retrieve
        // configured editors which were stamped with the isEditorComponent property in Editing.createColumnField
        for (i = 0; i < len; ++i) {
            column = editors[i].column;
            if (column.isVisible()) {
                me.onColumnShow(column);
            }
        }    
    },

    syncFieldWidth: function(column) {
        var field = column.getEditor(),
            width;

        field._marginWidth = (field._marginWidth || field.el.getMargin('lr'));
        width = column.getWidth() - field._marginWidth;
        field.setWidth(width);
        if (field.xtype === 'displayfield') {
            // displayfield must have the width set on the inputEl for ellipsis to work
            field.inputWidth = width;
        }
    },

    onErrorChange: function() {
        var me = this,
            valid;

        if (me.errorSummary && me.isVisible()) {
            valid = me.getForm().isValid();
            me[valid ? 'hideToolTip' : 'showToolTip']();
        }
    },

    afterRender: function() {
        var me = this,
            plugin = me.editingPlugin,
            grid = plugin.grid,
            view = grid.lockable ? grid.normalGrid.view : grid.view;

        me.callParent(arguments);

        // The scrollingViewEl is the TableView which scrolls
        me.scrollingView = view;
        me.scrollingViewEl = view.el;
        view.on('scroll', me.onViewScroll, me);

        // Prevent from bubbling click events to the grid view
        me.mon(me.el, {
            click: Ext.emptyFn,
            stopPropagation: true
        });

        // Ensure that the editor width always matches the total header width
        me.mon(grid, 'resize', me.onGridResize, me);

        if (me.lockable) {
            grid.lockedGrid.view.on('resize', 'onGridResize', me);
        }

        me.el.swallowEvent([
            'keypress',
            'keydown'
        ]);

        me.initKeyNav();

        me.mon(plugin.view, {
            beforerefresh: me.onBeforeViewRefresh,
            refresh: me.onViewRefresh,
            itemremove: me.onViewItemRemove,
            scope: me
        });

        // Prevent trying to reposition while we set everything up
        me.preventReposition = true;
        me.syncAllFieldWidths();
        delete me.preventReposition;    
    },

    initKeyNav: function() {
        var me = this,
            plugin = me.editingPlugin;

        me.keyNav = new Ext.util.KeyNav(me.el, {
            tab: {
                fn: me.onFieldTab,
                scope: me
            },
            enter: plugin.onEnterKey,
            esc: plugin.onEscKey,
            scope: plugin
        });
    },

    onBeforeViewRefresh: function(view) {
        var me = this,
            viewDom = view.el.dom;

        if (me.el.dom.parentNode === viewDom) {
            viewDom.removeChild(me.el.dom);
        }
    },

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
        } else {
            me.editingPlugin.cancelEdit();
        }
    },

    onViewItemRemove: function(record, index, item, view) {

        // If the itemremove is due to refreshing, ignore it.
        // If the row for the current context record has gone after the
        // refresh, editing will be canceled there. See onViewRefresh above.
        if (!view.refreshing) {
            var context = this.context;
            if (context && record === context.record) {
                // if the record being edited was removed, cancel editing
                // Deactivate field so that we do not attempt to focus the underlying cell; it's gone.
                this.activeField = null;
                this.editingPlugin.cancelEdit();
            }
        }
    },
    
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
        if (scrollTopChanged)
        {
            me.reposition();
        }
    },

    onColumnResize: function(column, width) {
        var me = this;

        if (me.rendered && !me.editingPlugin.reconfiguring) {
            // Need to ensure our lockable/normal horizontal scrollrange is set
            me.onGridResize();
            me.onViewScroll();
            if (!column.isGroupHeader) {
                me.syncFieldWidth(column);
                me.repositionIfVisible();
            }
        }
    },

    onColumnHide: function(column) {
        if (!this.editingPlugin.reconfiguring && !column.isGroupHeader) {
            column.getEditor().hide();
            this.repositionIfVisible();
        }
    },

    onColumnShow: function(column) {
        var me = this;

        if (me.rendered && !me.editingPlugin.reconfiguring && !column.isGroupHeader && column.getEditor) {
            column.getEditor().show();
            me.syncFieldWidth(column);
            if (!me.preventReposition) {
                this.repositionIfVisible();
            }
        }
    },

    onColumnMove: function(column, fromIdx, toIdx) {
        var me = this,
            locked = column.isLocked(),
            fieldContainer = locked ? me.lockedColumnContainer : me.normalColumnContainer,
            columns, i, len, after, offset;

        // If moving a group, move each leaf header
        if (column.isGroupHeader) {
            Ext.suspendLayouts();
            after = toIdx > fromIdx;
            offset = after ? 1 : 0;
            columns = column.getGridColumns();
            for (i = 0, len = columns.length; i < len; ++i) {    
                column = columns[i];
                toIdx = column.getIndex();
                if (after) {
                    ++offset;
                }
                me.setColumnEditor(column, toIdx + offset, fieldContainer);
            }
            Ext.resumeLayouts(true);
        } else {
            me.setColumnEditor(column, column.getIndex(), fieldContainer);
        }
    },

    setColumnEditor: function(column, idx, fieldContainer) {
        this.addFieldsForColumn(column);
        fieldContainer.insert(idx, column.getEditor());
    },

    onColumnAdd: function(column) {

        // If a column header added, process its leaves
        if (column.isGroupHeader) {
            column = column.getGridColumns();
        }
        //this.preventReposition = true;
        this.addFieldsForColumn(column);
        this.insertColumnEditor(column);
        this.preventReposition = false;
    },

    insertColumnEditor: function(column) {
        var me = this,
            field,
            fieldContainer,
            len, i;

        if (Ext.isArray(column)) {
            for (i = 0, len = column.length; i < len; i++) {
                me.insertColumnEditor(column[i]);
            }
            return;
        }

        if (!column.getEditor) {
            return;
        }

        fieldContainer = column.isLocked() ? me.lockedColumnContainer : me.normalColumnContainer;

        // Insert the column's field into the editor panel.
        fieldContainer.insert(column.getIndex(), field = column.getEditor());
        
        me.columns.add(field.id, column);

        // Ensure the view scrolls the field into view on focus
        field.on('focus', me.onFieldFocus, me);

        me.needsSyncFieldWidths = true;
    },

    onFieldFocus: function(field) {
        // Cache the active field so that we can restore focus into its cell onHide
        this.activeField = field;
        this.context.setColumn(field.column);
        field.column.getView().getScrollable().scrollIntoView(field.el);
    },

    onFieldTab: function(e) {
        var me = this,
            activeField = me.activeField,
            rowIdx = me.context.rowIdx,
            forwards = !e.shiftKey,
            target = activeField[forwards ? 'nextNode' : 'previousNode'](':focusable');

        // No field to TAB to, navigate forwards or backwards
        if (!target || !target.isDescendant(me)) {
            if (me.isDirty()) {
                e.preventDefault();
            } else {
                // Editor is clean - navigate to next or previous row
                rowIdx = rowIdx + (forwards ? 1 : -1);
                if (rowIdx >= 0 && rowIdx <= me.view.dataSource.getCount()) {

                    if (forwards) {
                        target = me.down(':focusable:not([isButton]):first');

                        // If going back to the first column, scroll back to field.
                        // If we're in a locking view, this has to be done programatically to avoid jarring
                        // when navigating from the locked back into the normal side
                        activeField.column.getView().getScrollable().scrollIntoView(activeField.ownerCt.child(':focusable').el);
                    } else {
                        target = me.down(':focusable:not([isButton]):last');
                    }
                    me.editingPlugin.startEdit(rowIdx, target.column);
                }
            }
        }
    },

    destroyColumnEditor: function(column) {
        var field;
        if (column.hasEditor() && (field = column.getEditor())) {
            field.destroy();
        }
    },

    repositionIfVisible: function(c) {
        var me = this,
            view = me.view;

        // If we're showing ourselves, jump out
        // If the component we're showing doesn't contain the view
        if (c && (c === me || !c.el.isAncestor(view.el))) {
            return;
        }

        if (me.isVisible() && view.isVisible(true)) {
            me.reposition();
        }
    },

    isLayoutChild: function(ownerCandidate) {
        // RowEditor is not a floating component, but won't be laid out by the grid
        return false;
    },

    getRefOwner: function() {
        return this.editingPlugin.grid;
    },

    getRefItems: function(deep) {
        var me = this,
            result;

        if (me.lockable) {
            // refItems must include ALL children. Must include the two containers
            // because we don't know what is being searched for.
            result = [me.lockedColumnContainer];
            result.push.apply(result, me.lockedColumnContainer.getRefItems(deep));
            result.push(me.normalColumnContainer);
            result.push.apply(result, me.normalColumnContainer.getRefItems(deep));
        } else {
            result = me.callParent(arguments);
        }
        return result;
    },

    reposition: function(animateConfig, fromScrollHandler) {
        var me = this,
            view = me.view,
            context = me.context,
            row = context && context.row,
            yOffset = 0,
            wrapEl = me.wrapEl,
            rowTop = 0,
            localY,
            deltaY = 0,
            afterPosition;

        // Position this editor if the context row is rendered (buffered rendering may mean that it's not in the DOM at all)
        if (row && Ext.isElement(row)) 
        {
            localY = me.calculateEditorTop(rowTop) + yOffset;

            // If not being called from scroll handler...
            // If the editor's top will end up above the fold
            // or the bottom will end up below the fold,
            // organize an afterPosition handler which will bring it into view and focus the correct input field
            if (!fromScrollHandler) {
                afterPosition = function() {
                    me.focusColumnField(context.column);
                };
            }

            // Get the y position of the row relative to its top-most static parent.
            // offsetTop will be relative to the table, and is incorrect
            // when mixed with certain grid features (e.g., grouping).
            if (animateConfig) {
                wrapEl.animate(Ext.applyIf({
                    to: {
                        top: localY
                    },
                    duration: animateConfig.duration || 125,
                    callback: afterPosition
                }, animateConfig));
            } else {
                wrapEl.setLocalY(localY + me.lastScrollTop);
                if (afterPosition) {
                    afterPosition();
                }
            }
        }
    },

    /**
     * @private
     * Returns the scroll delta required to scroll the context row into view in order to make
     * the whole of this editor visible.
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
    calculateLocalRowTop: function(row) {
        var me = this,
            grid = me.editingPlugin.grid,
            scrollingView = me.scrollingView,
            scrollTop  = scrollingView.getScrollY();
            
        return Ext.fly(row).getOffsetsTo(grid)[1] - grid.el.getBorderWidth('t') + scrollTop;
    },

    // Given the top pixel position of a row in the scroll space,
    // calculate the editor top position in the view's encapsulating element.
    // This will only ever be in the visible range of the view's element.
    calculateEditorTop: function(rowTop) {
        var me = this,
            context = me.context,
            row = Ext.get(context.row),
            grid = me.editingPlugin.grid,
            viewHeight = grid.getHeight();
            
        return (viewHeight / 2);
    },

    getClientWidth: function() {
        var me = this,
            grid = me.editingPlugin.grid,
            result;

        if (me.lockable) {
            result =
               grid.lockedGrid.getWidth() +
               grid.normalGrid.view.el.dom.clientWidth;
        }
        else {
            result = grid.view.el.dom.clientWidth;
        }
        return result;
    },

    getEditor: function(fieldInfo) {
        var me = this;

        if (Ext.isNumber(fieldInfo)) {
            // In a locked grid, a RowEditor uses 2 inner containers, so need to use CQ to retrieve
            // configured editors which were stamped with the isEditorComponent property in Editing.createColumnField
            return me.query('[isEditorComponent]')[fieldInfo];
        } else if (fieldInfo.isHeader && !fieldInfo.isGroupHeader) {
            return fieldInfo.getEditor();
        }
    },    

    addFieldsForColumn: function(column, initial) {
        var me = this,
            i,
            length, field;

        if (Ext.isArray(column)) {
            for (i = 0, length = column.length; i < length; i++) {
                me.addFieldsForColumn(column[i], initial);
            }
            return;
        }

        if (column.getEditor) {

            // Get a default display field if necessary
            field = column.getEditor(null, me.getDefaultFieldCfg(column));

            if (column.align === 'right') {
                field.fieldStyle = 'text-align:right';
            }

            if (column.xtype === 'actioncolumn') {
                field.fieldCls += ' ' + Ext.baseCSSPrefix + 'form-action-col-field';
            }

            if (me.isVisible() && me.context) {
                if (field.is('displayfield')) {
                    me.renderColumnData(field, me.context.record, column);
                } else {
                    field.suspendEvents();
                    field.setValue(me.context.record.get(column.dataIndex));
                    field.resumeEvents();
                }
            }
            if (column.hidden) {
                me.onColumnHide(column);
            } else if (column.rendered && !initial) {
                // Setting after initial render
                me.onColumnShow(column);
            }
        }
    },
    
    /**
     * @param {Ext.grid.column.Column} column
     */
    getDefaultFieldCfg: function(column) {
        var specificConf = column.getEditorDefaultConfig ? column.getEditorDefaultConfig() : {};
        return Ext.applyIf({
            xtype: 'displayfield',
            // Override Field's implementation so that the default display fields will not return values. This is done because
            // the display field will pick up column renderers from the grid.
            getModelData: function() {
                return null;
            }
        }, specificConf);
    },

    loadRecord: function(record) {
        var me     = this,
            form   = me.getForm(),
            fields = form.getFields(),
            items  = fields.items,
            length = items.length,
            i, displayFields,
            isValid, item;

        // temporarily suspend events on form fields before loading record to prevent the fields' change events from firing
        for (i = 0; i < length; i++) {
            item = items[i];
            item.suspendEvents();
            item.resetToInitialValue();
        }

        form.loadRecord(record);

        for (i = 0; i < length; i++) {
            items[i].resumeEvents();
        }

        // Because we suspend the events, none of the field events will get propagated to
        // the form, so the valid state won't be correct.
        if (form.hasInvalidField() === form.wasValid) {
            delete form.wasValid;
        }
        isValid = form.isValid();
        if (me.errorSummary) {
            if (isValid) {
                me.hideToolTip();
            } else {
                me.showToolTip();
            }
        }
        
        // render display fields so they honor the column renderer/template
        displayFields = me.query('>displayfield');
        length = displayFields.length;

        for (i = 0; i < length; i++) {
            me.renderColumnData(displayFields[i], record);
        }
        
        // From Ext4 begin
        me.setColumnToEdit(me.context.column);
        me.mainEditor.setValueAndMarkup(record.get(me.columnToEdit), record.get('id'), me.columnToEdit);
        me.setLastSegmentShortInfo(me.mainEditor.lastSegmentContentWithoutTags.join(''));
        me.focusContextCell();
        // From Ext4 end
    },

    renderColumnData: function(field, record, activeColumn) {
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

    beforeEdit: function() {
        var me = this,
            scrollDelta;

        if (me.isVisible() && me.errorSummary && !me.autoCancel && me.isDirty()) {

            // Scroll the visible RowEditor that is in error state back into view
            scrollDelta = me.getScrollDelta();
            if (scrollDelta) {
                me.scrollingViewEl.scrollBy(0, scrollDelta, true);
            }
            me.showToolTip();
            return false;
        }
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

        if (!me.rendered || !wrapEl) {
            if (!me.rendered)
            {
                me.width = me.getClientWidth();
                me.render(grid.el, grid.el.dom.firstChild);
            }
            // The wrapEl is a container for the editor.
            wrapEl = me.wrapEl = me.el.wrap();
            // Change the visibilityMode to offsets so that we get accurate measurements
            // when the roweditor is hidden for laying out things like a TriggerField.
            wrapEl.setVisibilityMode(3);

            wrapEl.addCls(me._wrapCls);
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
            // We need to make sure that the target row is visible in the grid view. For
            // example, a row could be added to the view and then immediately edited. In
            // this case, we need to ensure that the row is visible in the view before the
            // editor is shown and is positioned.
            // See EXTJS-17349.
            grid.ensureVisible(record);
            me.show();
        }
        me.focusContextCell();
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
    focusColumnField: function(column) {
        var field, didFocus;
        
        if (column && !column.destroyed) {   
            if (column.isVisible()) {
                field = this.getEditor(column);   
                if (field && field.isFocusable(true)) {
                    didFocus = true;
                    field.focus();
                }
            }
            if (!didFocus) {
                this.focusColumnField(column.next());
            }
        }
    },
    setEditorHeight: function() {
        var me = this,
            context = me.context,
            row = Ext.get(context.row),
            rowHeight = row.getHeight(),
            editorHeight = rowHeight + me.editorExtraHeight,
            editorFieldHeight = rowHeight + me.editorFieldExtraHeight;
        
        me.rowToEditOrigHeight = rowHeight;
        row.setHeight(editorHeight + me.editorShadowsExtraHeight);
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
    toggleExtraSpaceToGridBody: function(isOnShow) {
        var me = this,
            editingPlugin = me.editingPlugin,
            grid = editingPlugin.grid,
            viewEl = grid.getView().getEl(),
            context = me.context,
            wrapEl = me.wrapEl,
            row = Ext.get(context.row),
            editorTop = me.calculateLocalRowTop(wrapEl),
            rowTop = me.calculateLocalRowTop(row),
            editorHeight = me.rowToEditOrigHeight + me.editorExtraHeight + me.editorShadowsExtraHeight,
            rowHeight = row.getHeight(),
            editorBottom = editorTop + editorHeight,
            rowBottom = rowTop + rowHeight,
            padding = 0;
            
        if (!isOnShow) {
            viewEl.setStyle('padding-top', 'initial');
            //viewEl.setStyle('padding-bottom', 'initial');
            return true;
        }
            
        if (editorTop > rowTop) {
            padding = editorTop - rowTop;
            viewEl.setStyle('padding-top', padding+'px');
        }
        
        // this somehow messes the scrolling
        /*if (editorBottom < rowBottom) {
            padding = rowBottom - editorBottom;
            viewEl.setStyle('padding-bottom', padding+'px');
        }*/
    },
    scrollEditedRowUnderTheEditor: function() {
        var me = this,
            editingPlugin = me.editingPlugin,
            grid = editingPlugin.grid,
            view = grid.getView(),
            viewEl = view.getEl(),
            context = me.context,
            wrapEl = me.wrapEl,
            row = Ext.get(context.row),
            editorTop = me.calculateLocalRowTop(wrapEl),
            rowTop = me.calculateLocalRowTop(row),
            scrollDelta = Math.abs(editorTop - rowTop);

        if (scrollDelta != 0) {
            viewEl.scrollBy(0, scrollDelta, false);
        }
    },
    onShow: function() {
        var me = this;

        me.wrapEl.show();
        me.callParent(arguments);
        if (me.needsSyncFieldWidths) {
            me.suspendLayouts();
            me.syncAllFieldWidths();
            me.resumeLayouts(true);
        }
        delete me.needsSyncFieldWidths;

        me.setEditorHeight();
        me.setEditorWidth();
        me.reposition();
        me.toggleExtraSpaceToGridBody(true);
        me.scrollEditedRowUnderTheEditor();
    },

    onHide: function() {
        var me = this,
            column,
            focusContext;

        me.toggleExtraSpaceToGridBody(false);
            // Try to push focus into the cell below the active field
        if (me.activeField) {
            column = me.activeField.column;
            focusContext = new Ext.grid.CellContext(column.getView()).setPosition(me.context.record, column);
            focusContext.view.getNavigationModel().setPosition(focusContext);
            me.activeField = null;
        }
        me.wrapEl.hide();
        me.callParent(arguments);
        if (me.tooltip) {
            me.hideToolTip();
        }
    },

    onResize: function(width, height) {
        this.wrapEl.setSize(width, height);
    },

    isDirty: function() {
        return this.getForm().isDirty();
    },

    getToolTip: function() {
        var me = this,
            tip = me.tooltip,
            grid = me.editingPlugin.grid;

        if (!tip) {
            me.tooltip = tip = new Ext.tip.ToolTip({
                cls: Ext.baseCSSPrefix + 'grid-row-editor-errors',
                title: me.errorsText,
                autoHide: false,
                closable: true,
                closeAction: 'disable',
                anchor: 'left',
                anchorToTarget: true,
                constrainPosition: true,
                constrainTo: document.body
            });
            grid.add(tip);

            // Layout may change the grid's positioning.
            me.mon(grid, {
                afterlayout: me.onGridLayout,
                scope: me
            });
        }
        return tip;
    },

    hideToolTip: function() {
        var me = this,
            tip = me.getToolTip();
        if (tip.rendered) {
            tip.disable();
        }
        me.hiddenTip = false;
    },

    showToolTip: function() {
        var me = this,
            tip = me.getToolTip();

        tip.update(me.getErrors());
        me.repositionTip();
        tip.enable();
    },

    onGridLayout: function() {
        if (this.tooltip && this.tooltip.isVisible()) {
            this.repositionTip();
        }
    },

    repositionTip: function() {
        var me = this,
            tip = me.getToolTip(),
            context = me.context,
            row = Ext.get(context.row),
            viewEl = me.scrollingViewEl,
            viewHeight = viewEl.dom.clientHeight,
            viewTop = viewEl.getY(),
            viewBottom = viewTop + viewHeight,
            rowHeight = row.getHeight(),
            rowTop = row.getY(),
            rowBottom = rowTop + rowHeight;

        if (rowBottom > viewTop && rowTop < viewBottom) {

            // Use the ToolTip's anchoring to get the left/right positioning correct with
            // respect to space available on the default (right) side.
            tip.anchorTarget = viewEl;
            tip.mouseOffset = [0, row.getOffsetsTo(viewEl)[1]];

            // The tip will realign itself based upon its new offset
            tip.show();
            me.hiddenTip = false;
        } else {
            tip.hide();
            me.hiddenTip = true;
        }
    },

    getErrors: function() {
        var me        = this,
            errors    = [],
            fields    = me.query('>[isFormField]'),
            length    = fields.length,
            i, fieldErrors, field;

        for (i = 0; i < length; i++) {
            field = fields[i];
            fieldErrors = field.getErrors();
            if (fieldErrors.length) {
                errors.push(me.createErrorListItem(fieldErrors[0], field.column.text));
            }
        }

        // Only complain about unsaved changes if all the fields are valid
        if (!errors.length && !me.autoCancel && me.isDirty()) {
            errors[0] = me.createErrorListItem(me.dirtyText);
        }

        return '<ul class="' + Ext.baseCSSPrefix + 'list-plain">' + errors.join('') + '</ul>';
    },

    createErrorListItem: function(e, name) {
        e = name ? name + ': ' + e : e;
        return '<li class="' + this.errorCls + '">' + e + '</li>';
    },

    beforeDestroy: function(){
        Ext.destroy(this.tooltip);
        this.callParent();
    },

    clipBottom: function(el, value) {
        el.setStyle('clip', 'rect(0 auto ' + value + 'px 0)');
    },

    clipTop: function(el, value) {
        el.setStyle('clip', 'rect(' + value + 'px, auto, auto, 0)');
    },

    clearClip: function(el) {
        el.setStyle(
            'clip',
            Ext.isIE8 ? 'rect(-1000px auto 1000px auto)' : 'auto'
        );
    }
});
