
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
 * @class Editor.plugins.MatchResource.view.MatchGridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.MatchResource.view.MatchGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.matchResourceMatchGrid',
    strings: {
        loading: '#UT#wird geladen...',
        noresults: '#UT#Es wurden keine Ergebnisse gefunden.',
        serverErrorMsgDefault: '#UT#Die Anfrage an die Matchressource dauerte zu lange.',
        serverErrorMsg500: '#UT#Die Anfrage führte zu einem Fehler im angefragten Dienst.',
        serverErrorMsg408: '#UT#Die Anfrage an die Matchressource dauerte zu lange.'
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
            '#editorcontroller': {
                prevnextloaded:'calculateRows'
            },
            '#Editor.$application': {
                editorViewportOpened: 'handleInitEditor'//FIXME the event is not fiered (maybe becouse the view controller is init after the event ?)
            },
            '#ViewModes':{
                viewModeChanged:'viewModeChangeEvent'//FIXME it is beter to hook up on this event or define a boolen variable in Editor
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
    ergonomicMode: false,
    startEditing: function(context) {
        var me = this;
        me.editedSegmentId = context.record.id;
        //me.loadCachedDataIntoGrid(context.record.id,-1);
        me.cacheSegmentIndex = new Array();
        me.cacheSegmentIndex.push(context.rowIdx);
    },
    endEditing: function() {
        var me = this;
        me.editedSegmentId = -1;
        me.getView().getStore('editorquery').removeAll();
	},
    onSegmentStoreLoad: function (store, records) {
        var me = this,
            er =store.getFirsteditableRow();
        me.setFirsEditableRow(er);
        me.assocStore = me.getView().assocStore;
        me.SERVER_STATUS=Editor.plugins.MatchResource.model.EditorQuery.prototype;
    },
    handleInitEditor: function() {
    },
    calculateRows: function(controller){
        var me = this,
            maxSegments = Editor.data.plugins.MatchResource.preloadedSegments;
        me.nextSegment = controller.next.nextEditable;
        if(me.nextSegment){
            var retval = controller.findNextRows(controller.next.nextEditable.idx,maxSegments);
            me.cacheSegmentIndex = me.cacheSegmentIndex.concat(retval);
        }
        me.checkCacheLength();
        me.cache();
    },
    viewModeChangeEvent: function(controller){
        var me = this;
        //isViewMode
        //isErgonomicMode
        //isEditMode
        if(controller.self.isErgonomicMode()){
            this.ergonomicMode = true;
            return;
        }
        this.ergonomicMode = false;
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
    cacheMatchPanelResults:function(tmmt, segment){
        var me = this;
            segmentId = segment.get('id');
            tmmtid = tmmt.get('id');
            dummyObj = {
                rows: [{
                    id: '',
                    source: me.strings.loading,
                    target: me.strings.loading,
                    matchrate: '',
                    tmmtid: tmmtid,
                    segmentId: '',
                    state: me.SERVER_STATUS.SERVER_STATUS_LOADING
                }]
            };
        me.cachedResults.get(segmentId).add(tmmtid,dummyObj);
        me.loadCachedDataIntoGrid(segmentId,tmmtid);
        me.sendRequest(segmentId, segment.get('source'), tmmtid);
    },
    cacheSingleMatchPanelResults: function(tmmt,segmentId,query){
        var me = this;
        tmmtid = tmmt.get('id');
        dummyObj = {
            rows: [{
                id: '',
                source: me.strings.loading,
                target: me.strings.loading,
                matchrate: '',
                tmmtid: tmmtid,
                segmentId: '',
                state:  me.SERVER_STATUS.SERVER_STATUS_LOADING
            }]
        };
        me.cachedResults.get(segmentId).add(tmmtid,dummyObj);
        me.loadCachedDataIntoGrid(segmentId,tmmtid);
        me.sendRequest(segmentId, query, tmmtid);
    },
    sendRequest: function(segmentId,query,tmmtid) {
        var me = this;
        Ext.Ajax.request({
            url:Editor.data.restpath+'plugins_matchresource_tmmt/'+tmmtid+'/query',
                method: "POST",
                params: {
                    //column for which the search was done (target | source)
                    segmentId: segmentId,
                    query: query
                },
                success: function(response){
                    me.handleRequestSuccess(me, response, segmentId, tmmtid);
                }, 
                failure: function(response){
                    //if failure on server side (HTTP 5?? / HTTP 4??), print a nice error message that failure happend on server side
                    // if we get timeout on the ajax connection, then print a nice timeout message
                    me.handleRequestFailure(me, response, segmentId, tmmtid);
                }
        });
    },
    loadCachedDataIntoGrid: function(segmentId,tmmtid,query) {
        if(segmentId != this.editedSegmentId){
            return;
        }
        var me = this;
        if(me.cachedResults.get(segmentId)){
            var res =me.cachedResults.get(segmentId);
            if(tmmtid < 0){
                me.assocStore.each(function(record){
                    if(res.get(record.get('id'))){
                        var rcd =res.get(record.get('id')).rows;
                        me.getView().getStore('editorquery').loadRawData(rcd,true);
                    }else{
                        me.cacheSingleMatchPanelResults(record,segmentId,query);
                    }
                });
                return;
            }
            if(res.get(tmmtid)){
                me.getView().getStore('editorquery').loadRawData(res.get(tmmtid).rows,true);
            }
            me.handleViwMode();
        }
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
    chooseMatch: function(view, record) {
        this.getView().fireEvent('chooseMatch', record);
    },
    handleRequestSuccess: function(controller,response,segmentId,tmmtid,query){
        var me = controller,
            resp = Ext.util.JSON.decode(response.responseText); 
        if(segmentId == me.editedSegmentId){
            me.getView().getStore('editorquery').remove(me.getView().getStore('editorquery').findRecord('tmmtid',tmmtid));
        }
        if(typeof resp.rows !== 'undefined' && resp.rows !== null && resp.rows.length){
            me.cachedResults.get(segmentId).add(tmmtid,resp);
            me.loadCachedDataIntoGrid(segmentId,tmmtid);
            return;
        }
        var noresults = {
                rows: [{
                    source: me.strings.noresults,
                    tmmtid: tmmtid,
                    state:  me.SERVER_STATUS.SERVER_STATUS_NORESULT
                }]
            };
        me.cachedResults.get(segmentId).add(tmmtid,noresults);
        me.loadCachedDataIntoGrid(segmentId,tmmtid,query);
        me.cachedResults.get(segmentId).removeAtKey(tmmtid);
    },
    handleRequestFailure: function(controller,response,segmentId,tmmtid){
        var me = controller,
            respStatusMsg = me.strings.serverErrorMsgDefault,
            strState =  me.SERVER_STATUS.SERVER_STATUS_SERVERERROR,
            targetMsg = '',
            result = {},
            json = null;
        if(segmentId == me.editedSegmentId){
            me.getView().getStore('editorquery').remove(me.getView().getStore('editorquery').findRecord('tmmtid',tmmtid));
        }
        switch(response.status){
            case -1:
                respStatusMsg = me.strings.serverErrorMsgDefault;
                break;
            case 408:
                respStatusMsg = me.strings.serverErrorMsg408;
                strState = me.SERVER_STATUS.SERVER_STATUS_CLIENTTIMEOUT;
                break;
            case 500:
                json = Ext.JSON.decode(response.responseText);
                targetMsg = json.errors[0]._errorMessage;
                respStatusMsg = me.strings.serverErrorMsg500;
                break;
        }
        
        result.rows = [{
            source: respStatusMsg,
            target: targetMsg,
            tmmtid: tmmtid,
            state: strState
        }];
        me.cachedResults.get(segmentId).add(tmmtid, result);
        me.loadCachedDataIntoGrid(segmentId,tmmtid);
        me.cachedResults.get(segmentId).removeAtKey(tmmtid);
    },
    handleViwMode:function(){
        if(this.ergonomicMode){
            Ext.select('.matchGrid .x-grid-row .x-grid-cell').each(function(el){
                Ext.fly(el).addCls('ergonomic-font');
            });
            return;
        }
        Ext.select('.matchGrid .x-grid-row .x-grid-cell').each(function(el){
            Ext.fly(el).removeCls('ergonomic-font');
            Ext.fly(el).addCls('view-editor-font-size');
        });
    },
});
