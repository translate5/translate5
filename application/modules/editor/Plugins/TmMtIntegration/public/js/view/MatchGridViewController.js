
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
      	  }
        }
    },
    assocStore : null,
    DELAY_ASSOC_STORE_LOAD_TIME : 2000,
    cachedResults : new Array(),
    makeQuery: function(taskGuid, query) {
    	var me = this;
        me.loadCachedDataIntoGrid();
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
    		var el = segments.getAt(0);
    	    me.assocStore.each(function(record){
    	       me.cacheMatchPanelResults(record,el.data.source,taskGuid);//FIXME another way to take source ?  get() ?
    	    });
    	});
    	task.delay(me.DELAY_ASSOC_STORE_LOAD_TIME);
    },
    cacheMatchPanelResults:function(record, query,taskGuid){
    	var me = this;
        Ext.Ajax.request({
            url:Editor.data.restpath+'plugins_tmmtintegration_query',
                method: "POST",
                params: {
                    data: Ext.JSON.encode({
                        type: 'query', //query => mtmatch | search => concorance,
                        //column for which the search was done (target | source)
                        segmentId: 1,//FIXME get from segment
                        query: query,
                        tmmtId: record.get('id'),
                        taskGuid: taskGuid
                    })
                },
                success: function(response){
  				  var resp = Ext.util.JSON.decode(response.responseText);
  				  if(resp.rows.result){
  					var record,
  						segId;
  					  for(var i=0; i<resp.rows.result.length;i++){
  						  if(resp.rows.result[i].matchrate > 0){
  							record = Editor.plugins.TmMtIntegration.model.EditorQuery.create(resp.rows.result[i]);
  							segId = record.get('segmentId');
		  				  	me.cachedResults.push(
		  				  			{ 
		  				  				segId : record
		  				  			}
		  				  			);
  						  }
  					  }
  				  }
                }, 
                failure: function(response){ 
                    console.log(response.responseText); 
                }
        });
    },
    loadCachedDataIntoGrid : function() {
    	var me = this,
    		segmentId = 1;//FIXME get from segment
    	
		me.getView().getStore('editorquery').removeAll();
    	
		for(var i=0; i<me.cachedResults.length;i++){
			me.getView().getStore('editorquery').add(me.cachedResults[i][segmentId]);
		}
	}

});
