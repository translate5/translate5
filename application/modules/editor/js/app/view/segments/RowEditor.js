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
 * @class Editor.view.segments.RowEditor
 * @extends Ext.grid.RowEditor
 * 
 * erweitert den Orginal Ext Editor um eigene Funktionalität
 */
Ext.define('Editor.view.segments.RowEditor', {
    extend: 'Ext.grid.RowEditor',

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
    
    initComponent: function() {
        var me = this;
        me.callParent(arguments);
        me.on('render', function(p) {
            p.body.on('dblclick', me.changeColumnByClick, me);
        });
        me.mainEditor = me.add(new Editor.view.segments.HtmlEditor());
        
        this.addEvents(
             /**
              * @event afterEditorMoved
              * @param {String} toEdit the dataIndex of the actual edited column
              * @param {Editor.view.segments.RowEditor} editor the rowEditor instance
              * Fires after the html maineditor was moved to another column
              */
            'afterEditorMoved'
        );
    },
    
    /**
     * Positionierung der EditorRow im Grid und deaktivierung der Save/Cancel Buttons
     * überschreibt die Orginal Methode, alles was sich um die Buttons dreht ist rausgeflogen 
     * @param {Object} animateConfig
     */
    reposition: function(animateConfig) {
        var me = this,
            context = me.context,
            row = context && Ext.get(context.row),
            grid = me.editingPlugin.grid,
            viewEl = grid.view.el,
            scroller = grid.verticalScroller,

            // always get data from ColumnModel as its what drives
            // the GridView's sizing
            mainBodyWidth = grid.headerCt.getFullWidth(),

            // use the minimum as the columns may not fill up the entire grid
            // width
            y, rowH, newHeight,

            invalidateScroller = function() {
                if (scroller) {
                    scroller.invalidate();
                }
                if (animateConfig && animateConfig.callback) {
                    animateConfig.callback.call(animateConfig.scope || me);
                }
            };

        // need to set both top/left
        if (row && Ext.isElement(row.dom)) {
            // Bring our row into view if necessary, so a row editor that's already
            // visible and animated to the row will appear smooth
            row.scrollIntoView(viewEl, false);

            // Get the y position of the row relative to its top-most static parent.
            // offsetTop will be relative to the table, and is incorrect
            // when mixed with certain grid features (e.g., grouping).
            y = row.getXY()[1] - 5;
            rowH = row.getHeight();            
            if(rowH == 0) {
                //on moving the editor horizontally, we get here with a row without a height,
                //this positions the editor outside of the view. so return in this case.
                return; 
            }
            newHeight = rowH + 10;

            // IE doesn't set the height quite right.
            // This isn't a border-box issue, it even happens
            // in IE8 and IE7 quirks.
            // TODO: Test in IE9!
            if (Ext.isIE) {
                newHeight += 2;
            }

            // Set editor height to match the row height
            if (me.getHeight() != newHeight) {
                me.setHeight(newHeight);
                me.el.setLeft(0);
            }

            if (animateConfig) {
                var animObj = {
                    to: {
                        y: y
                    },
                    duration: animateConfig.duration || 125,
                    listeners: {
                        afteranimate: function() {
                            invalidateScroller();
                            y = row.getXY()[1] - 5;
                            me.el.setY(y);
                        }
                    }
                };
                me.animate(animObj);
            } else {
                me.el.setY(y);
                invalidateScroller();
            }
            me.mainEditor.setHeight(newHeight-6);
        }
        if (me.getWidth() != mainBodyWidth) {
            me.setWidth(mainBodyWidth);
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
            oldField = me.query('.displayfield[name="'+oldIdx+'"]');
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
            fieldToDisable = null;
        
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
                field.setVisible(false);
                me.mainEditor.setVisible(vis);
                fieldToDisable = field;
                return;
            }
            else if(field.name == me.columnToEdit) {
                field.setVisible(vis);
                return;
            }
        });
        
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
     * repositions the grid view so that, the mainEditor is visible after change the editing column
     */
    repositionHorizontally: function () {
        var me = this, 
            gridReg = me.editingPlugin.grid.getView().getEl().getRegion(),
            offset,
            edReg = me.mainEditor.getEl().getRegion();
        
        if(gridReg.contains(edReg)) {
            return;
        }
        
        if(edReg.right > gridReg.right) {
            offset = -1 * gridReg.getOutOfBoundOffsetX(edReg.right) + 10;
            me.editingPlugin.grid.horizontalScroller.scrollByDeltaX(offset);
        }
        else {
            offset = -1 * gridReg.getOutOfBoundOffsetX(edReg.x) - 10;
            me.editingPlugin.grid.horizontalScroller.scrollByDeltaX(offset);
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
            newValue = Ext.String.trim(me.mainEditor.getValueAndUnMarkup()).replace(/\u200B/g, '');
            
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
     * erweitert die Orginal Methode, setzt den Record zurück.
     */
    cancelEdit: function() {
      var me = this;
      me.context.record.reject();
      me.getTimeTrackingData();
      me.editingPlugin.openedRecord = null;
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
    }
});
