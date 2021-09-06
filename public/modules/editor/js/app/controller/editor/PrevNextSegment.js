
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
 * MetaPanel Controller
 * @class Editor.controller.editor.PrevNextSegment
 */
Ext.define('Editor.controller.editor.PrevNextSegment', {
    mixins :['Ext.mixin.Observable'],
    constructor: function(config) {
        this.editingPlugin = config.editingPlugin;
        this.mixins.observable.constructor.call(this, config);
    },
    isLoading: false,
    next: null,
    prev: null,
    /**
     * internal pointer to the currently calculated prev / next
     * value depends on the last user action
     * @type 
     */
    calculated: null,
    context: null,
    types: ['editable', 'workflow'],
    parsers: {
        /**
         * just a dummy implementation for a consistent coding
         */
        editable: function(record, field){
            return true;
        },
        /**
         * returns true if segment was not edited by the current role yet
         */
        workflow: function(record, field){
            var role = Editor.data.task.get('userRole') || 'pm',
                map = Editor.data.segments.roleAutoStateMap,
                autoState = record.get('autoStateId');
            if(!map[role]) {
                return true;
            }
            return map[role].indexOf(autoState) < 0 && autoState != 999; // if segment is saving, consider it as edited!
        }
    },
    /**
     * @param {String} type
     * @param {function} parser
     */
    addType: function(type, parser){
        if(this.types.indexOf(type) == -1){
            this.types.push(type);
            this.parsers[type] = parser;
        }
    },
    /**
     * @return {Object}
     */
    getCalculated: function() {
        return this.calculated;
    },
    /**
     * resets the calculated prev/next items
     */
    reset: function() {
        this.calculated = null;
    },
    /**
     * handles the change of the sort filters
     */
    handleSortOrFilter: function() {
        this.prev = null;
        this.next = null;
    },
    /**
     * @param {String} type
     * @param {String} msg
     */
    calcNext: function(type, msg){
        this.calculated = this.addCallTimeMeta(this.next, type, msg);
    },
    /**
     * @param {String} type
     * @param {String} msg
     */
    calcPrev: function(type, msg){
        this.calculated = this.addCallTimeMeta(this.prev, type, msg);
    },
    /**
     * @return {Object}
     */
    createRowMeta: function(){
        var i, rowMeta = {
            isBorderReached: false,
            isMoveEditor: false // move or scroll the editor
        }
        for(i=0; i < this.types.length; i++){
            rowMeta[this.types[i] + 'Next'] = null;
        }
        return rowMeta;
    },
    /**
     * @return {Boolean}
     */
    rowMetaHasEmptyNext: function(rowMeta){
        for(var i=0; i < this.types.length; i++){
            if(!rowMeta[this.types[i] + 'Next']){
                return true;
            }
        }
        return false;
    },
    /**
     * Adds additional informations, only available when closing / saving the segment. 
     * @param {Object} rowMeta
     * @param {String} type
     * @param {String} errorText
     * @return {Object}
     */
    addCallTimeMeta: function(rowMeta, type, errorText) {
        var me = this,
            rowMeta = rowMeta || {}, //nothing given
            ed = me.editingPlugin,
            grid = ed.grid,
            isBorderReached = rowMeta.isBorderReached,
            rowMeta = rowMeta[type + 'Next'],
            prevIndex = me.prev['NEXTeditable'] ? me.prev['NEXTeditable'].idx : 0;
            
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
        rowMeta.isMoveEditor = me.isMoveEditor(rowMeta.idx, prevIndex);
        return rowMeta;
    },
    
    /**
     * calculates the prev/next available segments relative to the currently opened segment
     * @param {Object} context current edit context
     */
    calculateRows: function(context) {
        var me = this,
            editedField =   this.editingPlugin.editor.getEditedField();
        // TODO FIXME / QUIRK
        // When the segment editor was opened, this API will return NULL. Presumably due to problems in the initiaization-order
        // Also, when editing an editable source, this will give "sourceEdit" whereas the prev-next mechanic in Editor.controller.editor.PrevNextSegment will only work with targets
        if(!editedField || editedField.substr(0, 6) == 'source'){
            editedField = 'targetEdit';
        }
        me.context = context;
        me.prev = me.calculateRow(-1, editedField);
        me.next = me.calculateRow(1, editedField);
        //fetches missing information from server, if needed.
        me.fetchFromServer(editedField);
        if(!me.isLoading){
            me.fireEvent('prevnextloaded', me);
        }
    },
    /**
     * @param {int} rowIdxChange
     * @return {}
     */
    calculateRow: function(rowIdxChange, editedField) {
        var me = this, i,
            store = me.editingPlugin.grid.store,
            total = store.getTotalCount(),
            rec = me.context.record,
            newIdx = currentIdx = store.indexOf(rec),
            newRec = true,
            ret = me.createRowMeta(),
            evaluate;
     
        //no current record, or current not editable
        if(!rec || !rec.get('editable')) {
            return ret;
        }
        //checking always for segments editable flag + custom isEditable  
        while(newRec && me.rowMetaHasEmptyNext(ret)){
            newIdx = newIdx + rowIdxChange;
            newRec = store.getAt(newIdx);
            //no newRec found at all
            if(!newRec){
                break;
            }
            if(!newRec.get('editable')){
                continue;
            }
            for(i=0; i < this.types.length; i++){
                evaluate = this.parsers[this.types[i]];
                if(!ret[this.types[i] + 'Next'] && evaluate(newRec, editedField)){
                    ret[this.types[i] + 'Next'] = {
                        rec: newRec,
                        idx: newIdx
                    };
                }
            }
        }
        me.addReusableValues(ret, rowIdxChange, currentIdx);
        //already loaded meta data is still valid:
        ret.isBorderReached = (newIdx <= 0 || newIdx >= total);
        return ret;
    },
    /**
     * @param {int} rowIndex
     * @param {int} count
     */
    findNextRows : function(rowIndex, count){
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
            next, i;
        if(loaded){
            for(i=0; i < this.types.length; i++){
                next = loaded[this.types[i] + 'Next'];
                if(!ret[this.types[i] + 'Next'] && next && (direction * (next.idx - currentIdx) > 0)){
                    ret[this.types[i] + 'Next'] = next;
                }
            }
        }
    },
    
    isNextInWorkflowStep: function(newRec) {
        var role = Editor.data.task.get('userRole') || 'pm',
            map = Editor.data.segments.roleAutoStateMap,
            autoState = newRec.get('autoStateId');
        if(!map[role]) {
            return true;
        }
        return map[role].indexOf(autoState) < 0 && autoState != 999; //if segment is saving, consider it as edited!
    },
    
    fetchFromServer: function(editedField){
        var me = this,
            store = me.editingPlugin.grid.store,
            rec = me.context.record,
            proxy = store.getProxy(),
            params = {},
            fields = [], i;
        if(!rec) {
            return;
        }
        if(me.isLoading && me.isLoading.options.params.segmentId != rec.get('id')) {
            me.isLoading.abort();
            me.isLoading = false;
            return;
        }
        // we have to send the flag as integer instead of bool, 
        // since bool would be recognized as string on server side here
        for(i=0; i < this.types.length; i++){
            fields.push('prev_' + this.types[i]);
            if(!me.prev.isBorderReached){
                params['prev_' + this.types[i]] = me.prev[this.types[i] + 'Next'] ? 0 : 1;
            }
            fields.push('next_' +  this.types[i]);
            if(!me.next.isBorderReached){
                params['next_' +  this.types[i]] = me.next[this.types[i] + 'Next'] ? 0 : 1;
            }
        }
        // we transfere the types to expect as well
        params.segmentId = rec.get('id');
        params.parsertypes = this.types.join(',');
        params.editedField = editedField;
        params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(store.getSorters().items);
        
        
        me.isLoading = Ext.Ajax.request({
            url: Editor.data.pathToRunDir+'/editor/segment/nextsegments',
            method: 'post',
            params: params,
            scope: me,
            failure: function(response){
                me.isLoading = false;
            },
            success: function(response){
                var json = Ext.decode(response.responseText), parts;
                me.isLoading = false;
                //loop over all results and store them as needed
                Ext.each(fields, function(field) {
                    if(!json[field]){
                        return;
                    }
                    parts = field.split('_');
                    var direction = me[parts[0]];
                    //direction points to me.prev or me.next, for dataField see above rowMeta
                    direction[parts[1] + 'Next'] = {
                        idx: parseInt(json[field])
                    }
                });
                me.fireEvent('prevnextloaded',me);
            }
        });
    },
    
    /***
     * Calculate base on the visible columns in the grid, if the editor should be moved or scrolled.
     * Additionaly, for big scrolls, it will set the top offset of the editor with the calculated offset value
     * true: for move editor
     * false: for scroll editor
     */
    isMoveEditor: function(nextIndex, currentIdx){
    	//this part will calculate the start and end segments border
    	//when start-end segment border is visible, the editor should only move, not scroll
    	var me = this,
    		segmentsGrid = me.editingPlugin.grid,
    		total = segmentsGrid.store.getTotalCount(),
	    	indexBoundaries = segmentsGrid.getVisibleRowIndexBoundaries(),
	    	isIndexVisible = nextIndex >= indexBoundaries.top && nextIndex <= indexBoundaries.bottom,//is the next index in the view boundaries
	    	indexGridOffset = Math.round((indexBoundaries.bottom - indexBoundaries.top) / 4),
	    	forwardOffset = Math.round(indexGridOffset * 3.2),
	    	backwardOffset = indexGridOffset,
	    	goForward=nextIndex > currentIdx;
    	
    	//calculate if the offset border is reached
		var isOffsetBorder = goForward ? (nextIndex + forwardOffset >= total) : (nextIndex - backwardOffset <= 0);
    	
    	//if the first/last segment is in the visible area, move the editor
    	if(isOffsetBorder){
    		return true;
    	}
    	//move the editor when the current editor position is bellow or after aproximatly 1/3 of the screand
    	var totalHeight = me.editingPlugin.view.getHeight(), //the visible view height
    		scrollDelta = me.editingPlugin.editor.getScrollDeltaCustom(), //scrolled editor pixels
    		eh = me.editingPlugin.editor.getHeight(),
    		offset = totalHeight * 0.20;
    	
    	//if the next is not visible, this is a big scroll, so add the ofset to editorLocalTop
    	if(!isIndexVisible){
    		me.editingPlugin.editor.editorLocalTop = offset;
    		return false;
    	}
    	if(goForward){
    		return offset + eh > scrollDelta;
    	}
    	return offset + (2 * eh) < scrollDelta;
    }
});
