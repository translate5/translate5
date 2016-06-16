
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
    alias: 'controller.tmMtIntegrationMatchGrid',
    listen: {
        store: {
            '#Segments':{
                load: 'onSegmentStoreLoad'
            }
        },
        controller:{
            '#editorcontroller': {
                prevnextloaded :'calculateRows'
            },
            '#Editor.$application': {
                editorViewportOpened: 'handleInitEditor'
      	    },
      	}
    },
    assocStore : null,
    nextSegment : null,
    cacheSegmentIndex: new Array(),
    segmentStack : [],
    cachedResults : new Ext.util.HashMap(),
    editedSegmentId : -1, //the id of the edited segment
    firstEditableRow : -1,
    startEditing: function(context) {
    	var me = this;
    	me.editedSegmentId = context.record.id;
        
    	//me.loadCachedDataIntoGrid(context.record.id,-1);
        
        me.cacheSegmentIndex = new Array();
        me.cacheSegmentIndex.push(context.rowIdx);
    },
    endEditing : function() {
    	var me = this;
    	me.editedSegmentId = -1;
		var str = me.getView().getStore('editorquery');
		str.removeAll();
	},
    onSegmentStoreLoad: function (store, records) {
        var me = this,
            er =store.getFirsteditableRow();
        me.setFirsEditableRow(er);
    },
    handleInitEditor: function() {
      var me = this,
      	  taskGuid = Editor.data.task.get('taskGuid'),
      	  prm = {
	            params: {
	                filter: '[{"operator":"like","value":"'+taskGuid+'","property":"taskGuid"},{"operator":"eq","value":true,"property":"checked"}]'
	            },
	            scope : me
	  };
  	  me.assocStore = this.getStore('taskassoc').load(prm);
    },
    calculateRows : function(controller){
        var me = this,
            maxSegments = Editor.data.plugins.MatchResource.preloadedSegments;
        me.nextSegment = controller.next.nextEditable;
        if(me.nextSegment){
            var retval = controller.findNextRows(controller.next.nextEditable.idx,maxSegments);
            me.cacheSegmentIndex = me.cacheSegmentIndex.concat(retval);
        }
        //me.checkCacheLength();
        me.cache();
    },
    cache : function(){
        var me = this,
        segments = Ext.data.StoreManager.get('Segments');
        
        for(var i=0;i<me.cacheSegmentIndex.length;i++){
            var segment = segments.getAt(me.cacheSegmentIndex[i]);
            var segId = segment.get('id');
            
            if(segId == this.editedSegmentId)
                me.getView().getStore('editorquery').removeAll();
            
            if(me.cachedResults.get(segId) && me.cachedResults.get(segId).lenth >0){
                me.loadCachedDataIntoGrid(segId,-1);
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
    	var segmentId = segment.get('id');
    	var tmmtid = tmmt.get('id');
    	var dummyObj = {
    			rows :{
		    		result :new Array({
				    			id : '',
				    			source : 'Loading ...',
				    			target : 'Loading ...',
				    			matchrate : '',
				    			tmmtid: tmmtid,
				    			segmentId :'',
				    			loading :true})
				    	
    			}
    	};
    	me.cachedResults.get(segmentId).add(tmmtid,dummyObj);
    	me.loadCachedDataIntoGrid(segmentId,tmmtid);
    	me.sendRequest(segmentId, segment.get('source'), tmmtid); 	
    },
    sendRequest : function(segmentId,query,tmmtid) {
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
                    var resp = Ext.util.JSON.decode(response.responseText);
                    
                    if(segmentId == me.editedSegmentId)
                        me.getView().getStore('editorquery').remove(me.getView().getStore('editorquery').findRecord('tmmtid',tmmtid));
                    
                    if( typeof resp.rows.result !== 'undefined' && resp.rows.result !== null && resp.rows.result.length){
                        me.cachedResults.get(segmentId).add(tmmtid,resp);
                        me.loadCachedDataIntoGrid(segmentId,tmmtid);
                        return;
                    }
                    var noresults = {
                            rows :{
                                result :new Array({
                                    id : '',
                                    source : 'No results was found',
                                    target : '',
                                    matchrate : '',
                                    tmmtid: tmmtid,
                                    segmentId :'',
                                    loading :true})
                          }
                    };
                    me.cachedResults.get(segmentId).add(tmmtid,noresults);
                    me.loadCachedDataIntoGrid(segmentId,tmmtid);
                    me.cachedResults.get(segmentId).removeAtKey(tmmtid);

                }, 
                failure: function(response){
                    //if failure on server side (HTTP 5?? / HTTP 4??), print a nice error message that failure happend on server side
                    // if we get timeout on the ajax connection, then print a nice timeout message  
                    if(segmentId == me.editedSegmentId)
                        me.getView().getStore('editorquery').remove(me.getView().getStore('editorquery').findRecord('tmmtid',tmmtid));
                    
                    var timeOut = {
                            rows :{
                                result :new Array({
                                            id : '',
                                            source : 'The request to the server is taking too long.',
                                            target : 'Please try again later.',
                                            matchrate : '',
                                            tmmtid: tmmtid,
                                            segmentId :'',
                                            loading :true})
                            }
                    };
                    me.cachedResults.get(segmentId).add(tmmtid,timeOut);
                    me.loadCachedDataIntoGrid(segmentId,tmmtid);
                    me.cachedResults.get(segmentId).removeAtKey(tmmtid);
                }
        });
	},
    loadCachedDataIntoGrid : function(segmentId,tmmtid) {
    	if(segmentId != this.editedSegmentId)
    		return;
    	var me = this;
		if(me.cachedResults.get(segmentId)){
		    var res =me.cachedResults.get(segmentId);
            
		    if(tmmtid > 0){
		        me.assocStore.each(function(record){
                if(res.get(tmmtid))
                    me.getView().getStore('editorquery').loadRawData(res.get(tmmtid).rows.result,true);
                }); 
		        return;
		    }
		    if(res.get(tmmtid))
		        me.getView().getStore('editorquery').loadRawData(res.get(tmmtid).rows.result,true);
	    }
	},
	setFirsEditableRow : function(fer) {
        var me = this;
        me.cacheSegmentIndex = new Array();
        me.cacheSegmentIndex.push(fer);
    },
    checkCacheLength : function(){
        var me = this;
        if(me.segmentStack.length > 10){
            me.cachedResults.removeAtKey(me.segmentStack.shift());
        }
    }
});
