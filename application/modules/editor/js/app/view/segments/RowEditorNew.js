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
 * @class Editor.view.segments.RowEditor
 * @extends Ext.grid.RowEditor
 * 
 * erweitert den Orginal Ext Editor um eigene Funktionalität
 */
Ext.define('Editor.view.segments.RowEditorNew', {
    extend: 'Ext.grid.RowEditor',
    alias: 'widget.roweditornew',
    
    itemId: 'roweditor',
    
    //beinhaltet den gekürzten Inhalt des letzten geöffneteten Segments
    lastSegmentShortInfo: '',
    columnToEdit: null,
    fieldToEdit: null,
    previousRecord: null,
    timeTrackingData: null,
    messages: {
        segmentNotSavedUserMessage: 'Das Segment konnte nicht gespeichert werden. Bitte schließen Sie das Segment ggf. durch Klick auf "Abbrechen" und öffnen, bearbeiten und speichern Sie es erneut. Vielen Dank!',
        cantSaveEmptySegment: '#UT#Das Segment kann nicht ohne Inhalt gespeichert werden!'
    },
    
    //***********************************************************************************
    //Begin Events
    //***********************************************************************************
    /**
     * @event afterEditorMoved
     * @param {String} toEdit the dataIndex of the actual edited column
     * @param {Editor.view.segments.RowEditor} editor the rowEditor instance
     * Fires after the html maineditor was moved to another column
     */
    //***********************************************************************************
    //End Events
    //***********************************************************************************
    initComponent: function() {
        var me = this;
        
        // Maintain field-to-column mapping
        // It's easy to get a field from a column, but not vice versa
        me.columns = Ext.create('Ext.util.HashMap');

        me.callParent(arguments);
        me.on('render', function(p) {
            p.body.on('dblclick', me.changeColumnByClick, me);
            me.wrapEl = me.el.wrap();
        });
        me.mainEditor = me.add(new Editor.view.segments.HtmlEditor());
        
    },
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
    /**
     * setzt das Editor Feld im RowEditor anhand der Config in der Spalte.
     * überschreibt die Orginal Methode, die Unterschiede sind im Code kommentiert
     * das Setzen der internen Referenz mainEditor kommmt hinzu.
     * @param column
     */
    setField: function(column) {
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

        if (column.hidden) {
            me.onColumnHide(column);
        }
        if (me.isVisible() && me.context) {
            me.renderColumnData(field, me.context.record);
        }
    },
    renderColumnData: function(field, record, activeColumn) {
        var me,
            grid,
            headerCt,
            view,
            store,
            column,
            columns,
            value,
            renderer,
            metaData, rowIdx, colIdx,
            scope;
            
        me = this;
        grid = me.editingPlugin.grid;
        headerCt = grid.headerCt;
        view = me.scrollingView;
        store = view.dataSource;
        column = activeColumn || field.column;
        if (!column)
        {
            columns = grid.columns;
            for (var i = 0; i < columns.length; i++)
            {
                if (columns[i].field.id == field.id)
                {
                    column = columns[i];
                    break;
                }
            }
        }
        value = record.get(column.dataIndex);
        renderer = column.editRenderer || column.renderer;
        scope = (column.usingDefaultRenderer && !column.scope) ? column : column.scope;
        // honor our column's renderer (TemplateHeader sets renderer for us!)
        if (renderer) {
            metaData = {
                tdCls: '',
                style: ''
            };
            rowIdx = store.indexOf(record);
            colIdx = headerCt.getHeaderIndex(column);
            value = renderer.call(scope || headerCt.ownerCt, value, metaData, record, rowIdx, colIdx, store, view);
        }
        // Maintain mapping of fields-to-columns
        // This will fire events that maintain our container items
        me.columns.add(field.id, column);
        field.setRawValue(value);
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
        if(oldField && oldField.length > 0) {
            oldField[0].setValue(rec.get(oldIdx));
        }
        if(me.setColumnToEdit(column)) {
            me.mainEditor.setValueAndMarkup(rec.get(me.columnToEdit), rec.get('id'), me.columnToEdit);
            me.setLastSegmentShortInfo(me.mainEditor.lastSegmentContentWithoutTags.join(''));
            me.focusContextCell();
        }
    },
    /**
     * Lädt den Datensatz in den Editor, erweitert das Orginal um die Integration des Markup.
     * Da die HtmlEditor.[set|get]Value Methoden aus Performance Gründen nicht überschrieben werden können, 
     * muss das (Un)Markup hier in der loadRecord bzw. completeEdit passieren.
     * Performance Gründe deshalb, weil set und getValue mehrmals aufgerufen wird (getValue z.B. in isDirty)
     * Alternate Targets: Method contains also logic to reposition the HtmlEditor according to the column to be edited. 
     *   A better Place for this logic would be in the startEdit Method before loadRecord is called, but then the complete startEdit Method must be duplicated. 
     * @override
     * @param {Editor.model.Segment} record
     */
    loadRecord: function(record) {
        var me = this;
        me.callParent(arguments);
        me.setColumnToEdit(me.context.column);
        me.mainEditor.setValueAndMarkup(record.get(me.columnToEdit), record.get('id'), me.columnToEdit);
        me.setLastSegmentShortInfo(me.mainEditor.lastSegmentContentWithoutTags.join(''));
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
            fieldToDisable = null,
            items, i, field, vis;
        
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
        
        items = me.items.items;
        for (i = 0; i < items.length; i++){ 
            field = items[i];
            
            if(!me.columns.containsKey(field.id)) {
                break; //ignore the editor itself, which has no col mapping
            }
            vis = me.columns.get(field.id).isVisible();
            if(field.name == toEdit) {
                field.setVisible(false);
                me.mainEditor.setVisible(vis);
                fieldToDisable = field;
                break;
            }
            else if(field.name == me.columnToEdit) {
                field.setVisible(vis);
                break;
            }
        }
        
        //all editor fields disabled
        if(!fieldToDisable || !hasToSwap) {
            me.fieldToDisable = false;
            return false;
        }
        me.columnToEdit = toEdit;
        me.columnClicked = col.dataIndex;
        
        //if isset fieldToDisable the cols get changed in focusContextCell
        me.fieldToDisable = fieldToDisable;
        return true;
    },
    
    /**
     * overrides original focusing with our repositioning of the editor
     */
    focusContextCell: function() {
        var me = this, 
            posMain, posToEdit,
            toDis = me.fieldToDisable;
        
        if(! toDis) {
            me.mainEditor.deferFocus();
            return;
        }
        posMain = me.items.indexOf(me.mainEditor),
        posToEdit = me.items.indexOf(toDis);
        
        //disable editor if column was also disabled
        me.mainEditor.setWidth(toDis.width);
        //swap position
        me.move(posMain, posToEdit);
        me.repositionHorizontally();
        me.mainEditor.deferFocus();
        me.fireEvent('afterEditorMoved', me.columnToEdit, me);
        return true;
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
    
    onViewScroll: function(){
        
    },
    
    calculateEditorTop: function(rowTop) {
        return 50;
    },
    
    syncButtonPosition: function(scrollDelta) {
        var me = this,
            floatingButtons = me.getFloatingButtons();
            
        floatingButtons.setButtonPosition('top'); // just to hide them

        return scrollDelta;
    }
});