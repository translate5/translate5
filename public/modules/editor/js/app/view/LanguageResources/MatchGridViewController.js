
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.LanguageResources.MatchGridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.LanguageResources.MatchGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.languageResourceMatchGrid',
    strings: {
        loading: '#UT#wird geladen...',
        noresults: '#UT#Es wurden keine Ergebnisse gefunden.',
        serverErrorMsgDefault: '#UT#Die Anfrage an die Sprachressource dauerte zu lange.',
        serverErrorMsg500: '#UT#Die Anfrage führte zu einem Fehler im angefragten Dienst.',
        serverErrorMsg502: '#UT#Es gibt Probleme mit dem angefragten Dienst.',
        serverErrorMsg408: '#UT#Die Anfrage an die Sprachressource dauerte zu lange.',
        delInsTagTooltip:'#UT#Unterschied zu Quellsegment'
    },
    refs:[{
        ref: 'matchgrid',
        selector: '#matchGrid'
    }],
    listen: {
        store: {
            '#Segments':{
                load: 'onSegmentStoreLoad'
            }
        },
        component: {
            '#matchGrid': {
                itemdblclick: 'chooseMatch'
            }
        },
        controller:{
            '#Editor': {
                prevnextloaded:'calculateRows'
            }
        }
    },
    SERVER_STATUS: null,//initialized after the segments stor is loaded
    assocStore: null,
    nextSegment: null,
    cacheSegmentIndex: new Array(),
    segmentStack: [],
    cachedResults: new Ext.util.HashMap(),
    editedSegmentId: -1, //the id of the edited segment
    firstEditableRow: -1,
    NUMBER_OF_CHACHED_SEGMENTS:10,
    //tooltip for source and del tags in grid source column
    sourceFieldDelInsTooltip:null,
    /**
     * if segment store was already loaded before, we have to set the firstEditableRow in here too
     */
    init: function() {
    	var me = this,
    		firstEditableRow = Ext.StoreManager.get('Segments').getFirsteditableRow();
        me.assocStore = me.getView().assocStore;
        me.SERVER_STATUS=Editor.model.LanguageResources.EditorQuery.prototype;
		if(firstEditableRow != null) {
			me.setFirsEditableRow(firstEditableRow);
		}
    },
    startEditing: function(context) {
        var me = this;
        me.editedSegmentId = context.record.id;
        //me.loadCachedDataIntoGrid(context.record.id,-1);
        me.cacheSegmentIndex = new Array();
        me.cacheSegmentIndex.push(context.rowIdx);
        me.registerDelInsTagTooltip();
    },
    endEditing: function() {
        var me = this;
        Ext.Array.remove(me.cacheSegmentIndex, me.editedSegmentId);
        me.cachedResults.removeAtKey(me.editedSegmentId);
        me.editedSegmentId = -1;
        me.getView().getStore('editorquery').removeAll();
	},
	/**
	 * on each segment store load update the firstEditableRow info, needed for match result preloading
	 */
    onSegmentStoreLoad: function (store, records) {
        var me = this,
            er =store.getFirsteditableRow();
        me.setFirsEditableRow(er);
    },
    calculateRows: function(controller){
        var me = this,
            maxSegments = Editor.data.LanguageResources.preloadedSegments;
        me.nextSegment = controller.next.nextEditable;
        if(me.nextSegment){
            var retval = controller.findNextRows(controller.next.nextEditable.idx,maxSegments);
            me.cacheSegmentIndex = me.cacheSegmentIndex.concat(retval);
        }
        me.checkCacheLength();
        me.cache();
    },
    cache: function(){
        var me = this,
        segments = Ext.data.StoreManager.get('Segments');
        for(var i=0;i<me.cacheSegmentIndex.length;i++){
            var segment = segments.getAt(me.cacheSegmentIndex[i]),
                segId = segment.get('id');
            if(segId == this.editedSegmentId){
                me.getView().getStore('editorquery').removeAll();
            }
            if(me.cachedResults.get(segId)){
                me.loadCachedDataIntoGrid(segId,-1,segment.get('source'));
                continue;
            }
            me.cachedResults.add(segId,new Ext.util.HashMap());
            me.segmentStack.push(segId);
            me.assocStore.each(function(record){
               me.cacheMatchPanelResults(record,segment);
            });
        }
    },
    cacheMatchPanelResults:function(languageResource, segment){
        var me = this;
            segmentId = segment.get('id');
            languageResourceid = languageResource.get('id');
            dummyObj = {
                rows: [{
                    id: '',
                    source: me.strings.loading,
                    target: me.strings.loading,
                    matchrate: '',
                    languageResourceid: languageResourceid,
                    segmentId: '',
                    state: me.SERVER_STATUS.SERVER_STATUS_LOADING
                }]
            };
        me.cachedResults.get(segmentId).add(languageResourceid,dummyObj);
        me.loadCachedDataIntoGrid(segmentId,languageResourceid);
        me.sendRequest(segmentId, segment.get('source'), languageResourceid);
    },
    cacheSingleMatchPanelResults: function(languageResource,segmentId,query){
        var me = this;
        languageResourceid = languageResource.get('id');
        dummyObj = {
            rows: [{
                id: '',
                source: me.strings.loading,
                target: me.strings.loading,
                matchrate: '',
                languageResourceid: languageResourceid,
                segmentId: '',
                state:  me.SERVER_STATUS.SERVER_STATUS_LOADING
            }]
        };
        me.cachedResults.get(segmentId).add(languageResourceid,dummyObj);
        me.loadCachedDataIntoGrid(segmentId,languageResourceid);
        me.sendRequest(segmentId, query, languageResourceid);
    },
    sendRequest: function(segmentId,query,languageResourceid) {
        var me = this;
        Ext.Ajax.request({
            url:Editor.data.restpath+'languageresourceinstance/'+languageResourceid+'/query',
                method: "POST",
                params: {
                    //column for which the search was done (target | source)
                    segmentId: segmentId,
                    query: query
                },
                success: function(response){
                    me.handleRequestSuccess(me, response, segmentId, languageResourceid);
                }, 
                failure: function(response){
                    //if failure on server side (HTTP 5?? / HTTP 4??), print a nice error message that failure happend on server side
                    // if we get timeout on the ajax connection, then print a nice timeout message
                    me.handleRequestFailure(me, response, segmentId, languageResourceid);
                }
        });
    },
    loadCachedDataIntoGrid: function(segmentId,languageResourceid,query) {
        if(segmentId != this.editedSegmentId){
            return;
        }
        var me = this;
        if(!me.cachedResults.get(segmentId)){
            return;
        }
        var res =me.cachedResults.get(segmentId);
        if(languageResourceid > 0 && res.get(languageResourceid)){
            me.getView().getStore('editorquery').loadRawData(res.get(languageResourceid).rows,true);
            return;
        }
        me.assocStore.each(function(record){
            if(res.get(record.get('id'))){
                var rcd =res.get(record.get('id')).rows;
                me.getView().getStore('editorquery').loadRawData(rcd,true);
            }else{
                me.cacheSingleMatchPanelResults(record,segmentId,query);
            }
        });
    },
    setFirsEditableRow: function(fer) {
        var me = this;
        me.cacheSegmentIndex = new Array();
        me.cacheSegmentIndex.push(fer);
    },
    checkCacheLength: function(){
        var me = this,
            diff=me.segmentStack.length - me.NUMBER_OF_CHACHED_SEGMENTS;
        if(diff > 0){
            for(var t=0;t<diff;t++){
                me.cachedResults.removeAtKey(me.segmentStack.shift());
            }
        }
    },
    /**
     * fires the cooseMatch event when the users chooses one specific match
     */
    chooseMatch: function(view, record) {
        this.getView().fireEvent('chooseMatch', record);
    },
    handleRequestSuccess: function(controller,response,segmentId,languageResourceid,query){
        var me = controller,
            resp = Ext.util.JSON.decode(response.responseText),
            editorquery =me.getView() &&  me.getView().getStore('editorquery');

        if(!editorquery){
            return;
        }
        
        if(segmentId == me.editedSegmentId){
            editorquery.remove([editorquery.findExact('languageResourceid',languageResourceid)]);
        }

        //when saving a segment before the match requests are loaded, 
        // then the segment is removed already from the cache, 
        // so there is no way and no need to show data
        if(!me.cachedResults.get(segmentId)){
            return;
        }

        if(typeof resp.rows !== 'undefined' && resp.rows !== null && resp.rows.length){
            me.cachedResults.get(segmentId).add(languageResourceid,resp);
            me.loadCachedDataIntoGrid(segmentId,languageResourceid);
            return;
        }
        
        var noresults = {
                rows: [{
                    source: me.strings.noresults,
                    languageResourceid: languageResourceid,
                    state:  me.SERVER_STATUS.SERVER_STATUS_NORESULT
                }]
            };
        me.cachedResults.get(segmentId).add(languageResourceid,noresults);
        me.loadCachedDataIntoGrid(segmentId,languageResourceid,query);
        me.cachedResults.get(segmentId).removeAtKey(languageResourceid);
    },
    handleRequestFailure: function(controller,response,segmentId,languageResourceid){
        var me = controller,
            respStatusMsg = me.strings.serverErrorMsgDefault,
            strState = me.SERVER_STATUS &&  me.SERVER_STATUS.SERVER_STATUS_SERVERERROR,
            targetMsg = '',
            responseText = '',
            result = {},
            store = me.getView() && me.getView().getStore('editorquery'),
            json = null,
            segment;
        
        if(!store){
            return;
        }
        
        if(segmentId == me.editedSegmentId){
            //removing by index is working as array only!
            store.remove([store.findExact('languageResourceid',languageResourceid)]);
        }
        switch(response.status){
            case -1:
                respStatusMsg = me.strings.serverErrorMsgDefault;
                break;
            case 408:
                respStatusMsg = me.strings.serverErrorMsg408;
                strState = me.SERVER_STATUS.SERVER_STATUS_CLIENTTIMEOUT;
                break;
            case 502:
                respStatusMsg = me.strings.serverErrorMsg502;
                responseText = response.responseText;
                break;
            case 500:
                respStatusMsg = me.strings.serverErrorMsg500;
                responseText = response.responseText;
                break;
        }
        
        if (responseText != "") {
            json = Ext.JSON.decode(response.responseText);
            if(json.errorMessage){
                targetMsg = json.errorMessage;
            }
            else {
                targetMsg = response.responseText;
            }
        }

        result.rows = [{
            source: respStatusMsg,
            target: targetMsg,
            languageResourceid: languageResourceid,
            state: strState
        }];
        segment = me.cachedResults.get(segmentId);
        if(segment) {
            segment.add(languageResourceid, result);
            me.loadCachedDataIntoGrid(segmentId,languageResourceid);
            segment.removeAtKey(languageResourceid);
        }
    },

    /***
     * Init the del/ins tag source column tooltip.
     */
    registerDelInsTagTooltip:function(){
        var me=this;
        //if it is registered, do nothing
        if(me.sourceFieldDelInsTooltip){
            return;
        }

        me.sourceFieldDelInsTooltip = Ext.create('Ext.tip.ToolTip', {
            // The overall target element.
            target: me.getView().getEl(),
            // tag selector class
            delegate: '.tmMatchGridResultTooltip',
            // Moving within the row should not hide the tip.
            trackMouse: true,
            renderTo: Ext.getBody(),
            html:me.strings.delInsTagTooltip
        });
    }
});
