
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
 * MetaPanel Controller
 * @class Editor.controller.editor.PrevNextSegment
 */
Ext.define('Editor.controller.editor.PrevNextSegment', {
    next: null,
    prev: null,
    /**
     * internal pointer to the currently calculated prev / next
     * value depends on the last user action
     * @type 
     */
    calculated: null,
    context: null,
    strings: {
        gridEndReached: '#UT#Ende der Segmente erreicht!',
        gridStartReached: '#UT#Start der Segmente erreicht!'
    },
    isLoading: false,
    constructor: function(config) {
        this.editingPlugin = config.editingPlugin;
        foo = this;
    },
    calcNext: function(filtered){
        var me = this;
        me.calculated = me.addCallTimeMeta(me.next, filtered, me.strings.gridEndReached);
    },
    calcPrev: function(filtered){
        var me = this;
        me.calculated = me.addCallTimeMeta(me.prev, filtered, me.strings.gridStartReached);
    },
    getCalculated: function() {
        return this.calculated;
    },
    reset: function() {
        this.calculated = null;
    },
    /**
     * Gets the next editable segment offset relative to param offset
     * @param integer offset
     */
    getNextEditableSegmentOffset: function(offset, isEditable) {
        //FIXME diese methode ebenfalls auf die Datenstruktur umstellen
        var me = this,
            grid = me.getSegmentGrid(),
            store = grid.store,
            origOffset = offset,
            rec = store.getAt(offset);
      
        isEditable = (Ext.isFunction(isEditable) ? isEditable : function(){ return true; });
        do {
            if (rec && rec.get('editable') && isEditable(rec)) {
                return offset;
            }
            offset++;
            rec = store.getAt(offset);
        } while (rec);
        // no editable segment
        return origOffset;
    },
    /**
     * calculates the prev/next available segments relative to the currently opened segment
     * @param {Object} context current edit context
     */
    calculateRows: function(context) {
        var me = this;
        me.context = context;
        me.prev = me.calculateRow(-1);
        me.next = me.calculateRow(1);
    },
    /**
     * @param {} rowIdxChange
     * @return {}
     */
    calculateRow: function(rowIdxChange) {
        var me = this,
            store = me.editingPlugin.grid.store,
            total = store.getTotalCount(),
            rec = me.context.record,
            newIdx = currentIdx = store.indexOf(rec),
            newRec = true,
            ret = {
                nextEditable: null,
                nextEditableFiltered: null,
                isBorderReached: false
            }
      
        //no current record, or current not editable
        if(!rec || !rec.get('editable')) {
            return ret;
        }
        //checking always for segments editable flag + custom isEditable  
        while (newRec && (!ret.nextEditable || !ret.nextEditableFiltered)) {
            newIdx = newIdx + rowIdxChange;
            newRec = store.getAt(newIdx);
            //no newRec found at all
            if(!newRec) {
                break;
            }
            //(!newRec.get('editable') || !isEditable(rec, newRec))
            if(!newRec.get('editable')) {
                continue;
            }
            if(!ret.nextEditable) {
                ret.nextEditable = {
                    rec: newRec,
                    idx: newIdx
                };
            }
            if(!ret.nextEditableFiltered && me.isNextInWorkflowStep(newRec)) {
                ret.nextEditableFiltered = {
                    rec: newRec,
                    idx: newIdx
                };
            }
        }
      
        ret.isBorderReached = (newIdx <= 0 || newIdx >= total);
      
        if(!ret.isBorderReached && (!ret.nextEditable || !ret.nextEditableFiltered)) {
            me.fetchFromServer(rec);
        }
      
        return ret;
    },
    /**
     * returns true if segment was not edited by the current role yet
     */
    isNextInWorkflowStep: function(newRec) {
        var role = Editor.data.task.get('userRole') || 'pm',
            map = Editor.data.segments.roleAutoStateMap,
            autoState = newRec.get('autoStateId');
        if(!map[role]) {
            return true;
        }
        return map[role].indexOf(autoState) < 0 && autoState != 999; //if segment is saving, consider it as edited!
    },
    /**
     * Adds additional informations, only available when closing / saving the segment. 
     * @param {Object} rowMeta
     * @param {Boolean} filtered
     * @param {String} errorText
     * @return {Object}
     */
    addCallTimeMeta: function(rowMeta, filtered, errorText) {
        var me = this,
            ed = me.editingPlugin,
            grid = ed.grid,
            isBorderReached = rowMeta.isBorderReached,
            rowMeta = filtered ? rowMeta.nextEditableFiltered : rowMeta.nextEditable;
            
        if(!rowMeta) {
            rowMeta = {}; //nothing found
        }
        
        Ext.Array.each(grid.columns, function(col, idx) {
            if(col.dataIndex == ed.editor.getEditedField()) {
                rowMeta.lastColumn = col;
            }
        });
        rowMeta.errorText = errorText;
        rowMeta.isLoading = me.isLoading;
        rowMeta.isBorderReached = isBorderReached;
        return rowMeta;
    },
    fetchFromServer: function(rec){
        var me = this,
            store = me.editingPlugin.grid.store,
            proxy = store.getProxy(),
            params = {};
            
        if(me.isLoading) {
            console.log("MARKED ALREADY FETCHING");
            return;
        }
        console.log("START FETCH (TODO)");
        
        params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(store.getSorters().items);
        params.segmentId = rec.get('id');
        
        Ext.Ajax.request({
            url: Editor.data.pathToRunDir+'/editor/segment/nextsegments',
            method: 'post',
            params: params,
            scope: me,
            failure: function(response){
                me.isLoading = false;
            },
            success: function(response){
                //var json = Ext.decode(response.responseText);
                me.isLoading = false;
                console.log(response.responseText);
            }
        });
        
        me.isLoading = true;
    }
});
