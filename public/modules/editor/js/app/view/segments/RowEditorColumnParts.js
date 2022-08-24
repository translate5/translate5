
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

/**
 * @class Editor.view.segments.RowEditorColumnParts
 * @override Editor.view.segments.RowEditor
 * 
 * This is the MainEditor moving/handling part of this multi class component.
 * MainEditor means the HtmlEditor which exists only once and is shared between multiple columns
 */
Ext.define('Editor.view.segments.RowEditorColumnParts', {
    override: 'Editor.view.segments.RowEditor',
    mixins: [
        'Editor.util.HtmlCleanup'
    ],
    columnToEdit: null,
    previousRecord: null,
    timeTrackingData: null,

    messages: {
        segmentNotSavedUserMessage: '#UT#Das Segment konnte nicht gespeichert werden. Bitte schließen Sie das Segment ggf. durch Klick auf "Abbrechen" und öffnen, bearbeiten und speichern Sie es erneut. Vielen Dank!',
        cantSaveEmptySegment: '#UT#Das Segment kann nicht ohne Inhalt gespeichert werden!'
    },
    initComponent: function() {
        var me = this;
        me.callParent();
        me.on('render', function(p) {
            p.body.on('dblclick', me.changeColumnByClick, me);
        });
        me.on('afterlayout', me.onAfterLayout, me);
        
        me.mainEditor = me.add(new Editor.view.segments.HtmlEditor());
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
        if(cmp && cmp.dataIndex != "source") { // when the dblclick comes from a source of an opened segment, the user might want to select a word for copy & paste
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
            me.mainEditor.setValueAndMarkup(rec.get(me.columnToEdit), rec, me.columnToEdit);
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
        me.startTimeTrack();
        
        me.items.each(function(field){
            if(!me.columns.containsKey(field.id)) {
                return; //ignore the editor itself, which has no col mapping
            }
            var vis = me.columns.get(field.id).isVisible();
            if(field.name == toEdit && vis) {
                linkedDisplayField = field;
                return;
            }
        });

        //all editor fields disabled
        if(!linkedDisplayField || !hasToSwap) {
            if(!linkedDisplayField) {
                me.linkedDisplayField = false;
            }
            return false;
        }
        me.columnToEdit = toEdit;
        me.columnClicked = col.dataIndex;
        me.mainEditor.dataIndex = col.dataIndex;
        
        me.mainEditor.setDirectionRtl(Editor.model.segment.Field.isDirectionRTL(me.mainEditor.fieldTypeToEdit));
        
        //if isset linkedDisplayField the cols get changed in focusContextCell
        me.linkedDisplayField = linkedDisplayField;
        return true;
    },
    onAfterLayout: function() {
        var me = this, 
            toDis = me.linkedDisplayField,
            pos;
            
        if(!me.mainEditor || !toDis) {
            return;
        }
            
        pos = toDis.getPosition(true);
        //swap position
        me.repositionMainEditor(pos[0]);
    },
    /**
     * overrides original focusing with our repositioning of the editor
     */
    focusContextCell: function() {
        var me = this, 
            toDis = me.linkedDisplayField,
            pos;
   
        if(!me.mainEditor) {
            return;
        }
            
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
        me.mainEditor.setVisible(toDis.isVisible());
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
        
        me.isRunningHorizontalScrolling = true;
        if(edReg.right > gridReg.right) {
            offset = -1 * gridReg.getOutOfBoundOffsetX(edReg.right) + 30;
            view.scrollBy(offset, 0, false);
        }
        else {
            offset = -1 * gridReg.getOutOfBoundOffsetX(edReg.x) - 20;
            view.scrollBy(offset, 0, false);
        }
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
     * Start editing the specified grid at the specified position.
     * @param {Ext.data.Model} record The Store data record which backs the row to be edited.
     * @param {Ext.data.Model} columnHeader The Column object defining the column to be focused
     */
    startEdit: function(record, columnHeader) {
        var me = this;
        me.callParent(arguments);
        me.focusContextCell();
    },
    
    /**
     * cancels the editing process
     */
    cancelEdit: function() {
        var me = this;
        me.context.record.reject();
        me.getTimeTrackingData();
        me.callParent(arguments);
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

        me.callParent(arguments);
        
        me.previousRecord = rec;
        return true;
    },
    
    loadRecord: function(record) {
        var me = this;
        me.callParent(arguments);
        
        me.setColumnToEdit(me.context.column);
        me.mainEditor.setValueAndMarkup(record.get(me.columnToEdit), record, me.columnToEdit);
        
        //init internal markup table for tag check, but only if a translation task
        if(Editor.data.task.get('emptyTargets')) {
            me.mainEditor.insertMarkup(record.get('source'), true);
        }
    },
    
    /**
     * saves the Editor Content into the loaded record
     * @param {Editor.model.Segment} record
     * @returns {Boolean}
     */
    saveMainEditorContent: function(record) {
        var me = this,
            plug = me.editingPlugin,
            //der cleanInvisibleCharacters aufruf entfernt vom Editor automatisch hinzugefügte unsichtbare Zeichen, 
            //und verhindert so, dass der Record nicht als modified markiert wird, wenn am Inhalt eigentlich nichts verändert wurde
            newValue = me.cleanInvisibleCharacters(me.mainEditor.getValueAndUnMarkup()),            
            title, msg, meta;
            
        //check, if the context delivers really the correct record, because through some issues in reallive data 
        //rose the idea, that there might exist special race conditions, where
        //the context.record is not the record of the newValue
        if(!plug.context || !plug.context.record || plug.context.record.internalId != record.internalId ){
            title = me.messages.segmentNotSavedUserMessage + newValue;
            msg = ' Das Segment konnte nicht gespeichert werden. Im folgenden der Debug-Werte: ' + newValue;
            if(plug.context && plug.context.record) {
                msg += 'me.editingPlugin.context.record.internalId: '+me.editingPlugin.context.record.internalId;
            }
            msg += ' record.internalId: ' + record.internalId;
            Editor.MessageBox.addError(title,msg);
            return false;
        }
        
        // Remove all TrackChanges Markup
        var cleanValue = me.cleanForSaveEditorContent(newValue);
        
        if(cleanValue.length == 0 && record.get(me.columnToEdit).length > 0) {
            Editor.MessageBox.addError(me.messages.cantSaveEmptySegment);
            return false;
        }
        
        if(me.mainEditor.hasAndDisplayErrors()) {
            return false;
        }
        record.beginEdit();
        record.set(me.columnToEdit, newValue);
        record.set('autoStateId', 999);
        //update the segment length in the metaCache of the edited segment:
        record.updateMetaCacheLength(me.columnToEdit, me.mainEditor.getLastSegmentLength());
        record.endEdit();
        return true;
    },
    /**
     * starts tracking the editing time for the given field
     */
    startTimeTrack: function() {
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
