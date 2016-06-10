
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
 * @class Editor.plugins.TmMtIntegration.view.MatchGridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.TmMtIntegration.view.MatchGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.tmMtIntegrationMatchGrid',
    listen: {
        controller:{
            '#Editor.$application': {
                editorViewportOpened: 'handleInitEditor'
      	  },
      	}
    },
    assocStore : null,
    editedSegmentId : -1, //the id of the edited segment
    DELAY_ASSOC_STORE_LOAD_TIME : 2000,
    cachedResults :new Array(),
    startEditing: function(context) {
    	var me = this;
    	me.editedSegmentId = context.record.id;
        me.loadCachedDataIntoGrid(context.record.id);
    },
    endEditing : function() {
    	var me = this;
    	me.editedSegmentId = -1;
		var str = me.getView().getStore('editorquery');
		str.removeAll();
	},
    testFunc : function() {
		alert('test');
	},
    handleInitEditor: function() {
      var me = this,
      	  taskGuid = Editor.data.task.get('taskGuid'),
      	  prm = {
	           params: {
	                filter: '[{"operator":"like","value":"'+taskGuid+'","property":"taskGuid"}]'
	            },
	            callback : me.afterAssocStoreLoad,
	            scope : me
	  };
  	  this.assocStore = new Ext.data.Store({model: 'Editor.plugins.TmMtIntegration.model.TaskAssoc'});
  	  this.assocStore.load(prm);
    },
    afterAssocStoreLoad:function(records, operation, success){
    	var me = this;
    	var segments = Ext.data.StoreManager.get('Segments'),
    		taskGuid = Editor.data.task.get('taskGuid');
    	
		me.getView().getStore('editorquery').removeAll();
    	
		var task = new Ext.util.DelayedTask(function(){
			for(var i=0;i<=10;i++){//cahce for first 11 segments
	    		var segment = segments.getAt(i);//loop through all segments or n segments and cache the data
	    		me.cachedResults[segment.get('id')] = {}; 
	    	    me.assocStore.each(function(record){
	    	       me.cacheMatchPanelResults(record,segment,taskGuid);
	    	    });
			}
    	});
    	task.delay(me.DELAY_ASSOC_STORE_LOAD_TIME);
    },
    cacheMatchPanelResults:function(tmmt, segment,taskGuid){
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
				    			segmentId :'',
				    			loading :true})
				    	
    			}
    	};
    	
    	me.cachedResults[segmentId][tmmtid] = dummyObj;

    	me.sendRequest(segmentId, segment.get('source'), tmmtid, taskGuid); 	
    },
    sendRequest : function(segmentId,query,tmmtid,taskGuid) {
    	var me = this;
    	Ext.Ajax.request({
            url:Editor.data.restpath+'plugins_tmmtintegration_query',
                method: "POST",
                params: {
                    data: Ext.JSON.encode({
                        type: 'query', //query => mtmatch | search => concorance,
                        //column for which the search was done (target | source)
                        segmentId: segmentId,
                        query: query,
                        tmmtId: tmmtid,
                        taskGuid: taskGuid
                    })
                },
                success: function(response){
  				  var resp = Ext.util.JSON.decode(response.responseText);
  				  //console.log(response.responseText);
  				  if( typeof resp.rows.result !== 'undefined' && resp.rows.result !== null && resp.rows.result.length){
  					//console.log(resp.rows.result[0].segmentId +"<->"+resp.rows.result[0].tmmtid);
  					  //me.cachedResults[resp.rows.result[0].segmentId][resp.rows.result[0].tmmtid] = {};
  					
  					me.cachedResults[resp.rows.result[0].segmentId][resp.rows.result[0].tmmtid] = resp;
  					
  					me.loadCachedDataIntoGrid(resp.rows.result[0].segmentId);
  					/*var rec,
  						obj = {};
  					  for(var i=0; i<resp.rows.result.length;i++){
  						  if(resp.rows.result[i].matchrate > 0){
  							obj = {};
  							rec = Editor.plugins.TmMtIntegration.model.EditorQuery.create(resp.rows.result[i]);
  							obj[rec.get('segmentId')] = rec;
		  				  	me.cachedResults.push(obj);
  						  }
  					  }*/
  				  }
                }, 
                failure: function(response){ 
                    console.log(response.responseText); 
                }
        });
	},
    loadCachedDataIntoGrid : function(segmentId) {
    	if(segmentId != this.editedSegmentId)
    		return;
    	
    	var me = this;
		var str = me.getView().getStore('editorquery');
		str.removeAll();
    	if(me.cachedResults[segmentId]){
    		var res =me.cachedResults[segmentId];    		
	    		me.assocStore.each(function(record){
	    			var itm = res[record.get('id')];
	        		
	    			me.getView().getStore('editorquery').loadRawData(itm.rows.result,true);
	    			/*
	    			for(var i =0;i<itm.rows.result.length;i++){
	        			me.getView().getStore('editorquery').add(Editor.plugins.TmMtIntegration.model.EditorQuery.create(itm.rows.result[i]));
	        		}*/
	    			
	    		});
    	}
	}
});
