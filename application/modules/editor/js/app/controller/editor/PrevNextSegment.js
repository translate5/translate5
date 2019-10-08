
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
    mixins :['Ext.mixin.Observable'],
    context: null,
    strings: {
        gridEndReached: '#UT#Kein weiteres Segment bearbeitbar!',
        gridStartReached: '#UT#Kein vorheriges Segment bearbeitbar!',
        gridEndReachedFiltered: '#UT#Kein weiteres Segment im Workflow bearbeitbar!',
        gridStartReachedFiltered: '#UT#Kein vorheriges Segment im Workflow bearbeitbar!'
    },
    
    isLoading: false,
    constructor: function(config) {
        this.editingPlugin = config.editingPlugin;
        this.mixins.observable.constructor.call(this, config);
    },
    calcNext: function(filtered){
        var me = this;
            msg = filtered ? me.strings.gridEndReachedFiltered : me.strings.gridEndReached;
        me.calculated = me.addCallTimeMeta(me.next, filtered, msg);
    },
    calcPrev: function(filtered){
        var me = this,
            msg = filtered ? me.strings.gridStartReachedFiltered : me.strings.gridStartReached;
        me.calculated = me.addCallTimeMeta(me.prev, filtered, msg);
    },
    getCalculated: function() {
        return this.calculated;
    },
    reset: function() {
        this.calculated = null;
    },
    handleSortOrFilter: function() {
        var me = this,
            plug = me.editingPlugin;
        me.prev = null;
        me.next = null;
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
        //fetches missing information from server, if needed.
        me.fetchFromServer();
        if(!me.isLoading){
            me.fireEvent('prevnextloaded', me);
        }
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
                isBorderReached: false,
                isNextVisible:false//is the next column visible in the segments grid
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
        me.addReusableValues(ret, rowIdxChange, currentIdx);
        
        //already loaded meta data is still valid:
        ret.isBorderReached = (newIdx <= 0 || newIdx >= total);
        return ret;
    },
    findNextRows : function(rowIndex,count){
        var me = this,
            store = me.editingPlugin.grid.store,
            total = store.getTotalCount(),
            rec = me.context.record,
            newIdx = rowIndex = store.indexOf(rec),
            newRec = true,
            ret = new Array(),
            counter = 0;
      
        //no current record, or current not editable
        if(!rec || !rec.get('editable')) {
            return ret;
        }
        //checking always for segments editable flag + custom isEditable  
        while (newRec) {
            if(counter == count)
                break;
            newIdx = newIdx + 1;
            newRec = store.getAt(newIdx);
            //no newRec found at all
            if(!newRec) {
                break;
            }
            //(!newRec.get('editable') || !isEditable(rec, newRec))
            if(!newRec.get('editable')) {
                continue;
            }
            ret.push(newIdx);
            counter++;
        }
        return ret;
    },
    /**
     * If the already loaded prev/next informations are still valid, we can reuse them
     * @param {Object} ret
     * @param {Integer} direction
     * @param {Integer} currentIdx
     */
    addReusableValues: function(ret, direction, currentIdx) {
        var me = this,
            loaded = direction > 0 ? me.next : me.prev,
            //if direction 1
            //currentIdx < loaded.nextEditable.idx results true 
            // => loaded.nextEditable.idx - currentIdx > 0
            
            //if direction -1
            //currentIdx > loaded.nextEditable.idx results true 
            // => -(loaded.nextEditable.idx - currentIdx) > 0
            isStillValid = loaded && loaded.nextEditable && (direction * (loaded.nextEditable.idx - currentIdx) > 0);
            isStillValidFiltered = loaded && loaded.nextEditableFiltered && (direction * (loaded.nextEditableFiltered.idx - currentIdx) > 0);
        
        if(!ret.nextEditable && isStillValid) {
            ret.nextEditable = loaded.nextEditable;
        }
        if(!ret.nextEditableFiltered && isStillValidFiltered) {
            ret.nextEditableFiltered = loaded.nextEditableFiltered;
        }
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
            rowMeta = rowMeta || {}, //nothing given
            ed = me.editingPlugin,
            grid = ed.grid,
            isBorderReached = rowMeta.isBorderReached,
            rowMeta = filtered ? rowMeta.nextEditableFiltered : rowMeta.nextEditable,
            prevIndex=me.prev.nextEditable ? me.prev.nextEditable.idx : 0;
            
        if(!rowMeta) {
            rowMeta = {}; //nothing found
        }
        
        Ext.Array.each(grid.columns, function(col, idx) {
            if(col.dataIndex == ed.editor.getEditedField()) {
                rowMeta.lastColumn = col;
            }
        });
        rowMeta.errorText = errorText;
        rowMeta.isLoading = !!me.isLoading;
        rowMeta.isBorderReached = isBorderReached;
        rowMeta.isNextVisible = me.isNextVisible(rowMeta.idx,prevIndex);
        return rowMeta;
    },
    fetchFromServer: function(){
        var me = this,
            store = me.editingPlugin.grid.store,
            rec = me.context.record,
            proxy = store.getProxy(),
            params = {};
            
        if(!rec) {
            return;
        }

        if(me.isLoading && me.isLoading.options.params.segmentId != rec.get('id')) {
            me.isLoading.abort();
            me.isLoading = false;
            return;
        }
        //we have to send the flag as integer instead of bool, 
        //since bool would be recognized as string on server side here
        if(!me.prev.isBorderReached) {
            params.prev = me.prev.nextEditable ? 0 : 1;
            params.prevFiltered = me.prev.nextEditableFiltered ? 0 : 1;
        }
        if(!me.next.isBorderReached) {
            params.next = me.next.nextEditable ? 0 : 1;
            params.nextFiltered = me.next.nextEditableFiltered ? 0 : 1;
        }
        
        if(!params.prev && !params.prevFiltered && !params.next && !params.nextFiltered) {
            return;
        }
        
        params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(store.getSorters().items);
        params.segmentId = rec.get('id');
        
        me.isLoading = Ext.Ajax.request({
            url: Editor.data.pathToRunDir+'/editor/segment/nextsegments',
            method: 'post',
            params: params,
            scope: me,
            failure: function(response){
                me.isLoading = false;
            },
            success: function(response){
                var json = Ext.decode(response.responseText),
                    fields = ['next', 'prev', 'nextFiltered', 'prevFiltered'];
                me.isLoading = false;
                //loop over all results and store them as needed
                Ext.each(fields, function(field) {
                    if(!json[field]){
                        return;
                    }
                    var direction = me[field.substr(0,4)],
                        dataField = "nextEditable";
                    if(field.length > 4) {
                        dataField += 'Filtered';
                    }
                    //direction points to me.prev or me.next, for dataField see above rowMeta
                    direction[dataField] = {
                        idx: json[field]
                    }
                });
                me.fireEvent('prevnextloaded',me);
            }
        });
    },
    
    /***
     * Check if the next column is visible in the segments grid
     */
    isNextVisible:function(nextIndex,currentIdx){
    	var me=this,
    		segmentsGrid=me.editingPlugin.grid,
            total = segmentsGrid.store.getTotalCount(),
	    	indexBoundaries=segmentsGrid.getVisibleRowIndexBoundaries(segmentsGrid),
	    	indexGridOffset=3,//number of segments offset for the editor
	    	isOffsetBorder=(nextIndex-indexGridOffset <= 0 || nextIndex+indexGridOffset >= total);
    	
    	//if the border is reached with o
    	if(isOffsetBorder){
    		indexGridOffset=0;
    	}
    	
    	//calculate the offset based of if the next segments is after or before the prev
    	if(currentIdx>=nextIndex){
    		indexBoundaries.top=indexBoundaries.top+indexGridOffset;
    	}else{
    		indexBoundaries.bottom=indexBoundaries.bottom-indexGridOffset;
    	}
	    return nextIndex>=indexBoundaries.top && nextIndex<=indexBoundaries.bottom;
    }
});
