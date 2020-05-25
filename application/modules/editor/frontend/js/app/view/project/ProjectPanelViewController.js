
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
Ext.define('Editor.view.project.ProjectPanelViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.projectPanel',
    
    routes:{
    	'project': {
    		before : 'beforeProjectRoute',
            action : 'onProjectRoute'
        },
    	'project/:id/focus' :{
    		before : 'beforeProjectFocusRoute',
    		action : 'onProjectFocusRoute'
    	},
    	'project/:id/:taskId/focus' :{
    		before : 'beforeProjectTaskFocusRoute',
    		action:'onProjectTaskFocusRoute'
    	} 
    },
    
    /***
     * Handle project rute action. Stop the action to cancel the rute action call
     */
    handleBeforeProjectRute:function(action){
    	var me=this,
			view=me.getView(),
			grid=view.down('#projectGrid');
		
    	//update the route when it is triggered from different view
    	Editor.app.openAdministrationSection(view,window.location.hash);

    	action.resume();
    },
    
    /***
     * Before project rute
     */
    beforeProjectRoute:function(action){
    	this.handleBeforeProjectRute(action);
    },
    
    /***
     * On project rute
     */
    onProjectRoute:function(){
    },
    
    /***
     * Before Project Focus rute
     */
    beforeProjectFocusRoute:function(id,action){
    	this.handleBeforeProjectRute(action);
    },
    
    /***
     * On Project Focus rute
     */
    onProjectFocusRoute:function(id){
		var me=this;
		me.selectProjectRecord(id);
    },
    
    /***
     * Before ProjectTask rute
     */
    beforeProjectTaskFocusRoute:function(id,taskId,action){
    	this.handleBeforeProjectRute(action);
    },

    /***
     * On ProjectTask rute
     */
    onProjectTaskFocusRoute:function(id,taskId){
		var me=this;
		//focus the project record
		me.selectProjectRecord(id);
		me.selectProjectTaskRecord(taskId);
    },
    
    /***
     * Focus project task grid row. This is called afte project task store is loaded.
     * The taskId is calculated based on the current window hash
     */
    focusProjectTask:function(){
		var me=this,
			rute=window.location.hash,
			rute=rute.split('/'),
			rec=null;
		if(rute.length==4){
			rec=parseInt(rute[2]);
		}
		me.selectProjectTaskRecord(rec,true);
	},
	
	/***
	 * After project task store is loaded
	 */
	onProjectTaskLoad:function(){
		this.focusProjectTask();
	},
	
	
	selectProjectRecord:function(id){
		var me=this,
			view=me.getView(),
			grid=view.down('#projectGrid'),
			record=null;
		
		grid.setLoading(true);

		//serch for the task store record index
		me.searchIndex(id,grid).then(function(index){
			grid.bufferedRenderer.scrollTo(index,{
				callback:function(){
					if(index===undefined || index<0){
						//TODO: translate me
						Editor.MessageBox.addInfo("The requested project does not exist");
						grid.setLoading(false);
						return;
					}
					
					record=grid.getStore().getById(parseInt(id));
					var focusAndSelect=function(){
						grid.suspendEvent('selectionchange');
						record=grid.getStore().getById(parseInt(id));
						grid.setSelection(record);
						me.getViewModel().set('projectSelection',record);
						grid.getView().focusRow(record);
						
						grid.resumeEvent('selectionchange');
						grid.setLoading(false);
					};
					if(record){
						focusAndSelect();
						return;
					}
					grid.getController().reloadProjects().then(focusAndSelect);
				}
			});
		}, function(err) {
			grid.setLoading(false);
		});
	},
	
	/***
	 * Select project task record in the projectTask grid. The selectionchange event will be suspende.
	 * If showNoRecordMessage is set, an info message will be shown when the requested record is not in
	 * the projectTask store
	 */
	selectProjectTaskRecord:function(id,showNoRecordMessage){
		var me=this,
			view=me.getView(),
			grid=view.down('#projectTaskGrid'),
			store=grid.getStore(),
			record= (id === null) ? store.getAt(0) : store.getById(parseInt(id));
		
		if(!record){
			//display info message when the flag showNoRecordMessage is set 
			showNoRecordMessage && Editor.MessageBox.addInfo("The requested project task does not exist");
			return;
		}
		grid.suspendEvent('selectionchange');
		me.getViewModel().set('projectTaskSelection',record);
		grid.setSelection(record);
		grid.getView().focusRow(record);
		grid.resumeEvent('selectionchange');
	},
	
	/***
	 * Search the index of the record id in the given grid view.
	 * If the index does not exist in the store, the index will be loaded from the db
	 */
	searchIndex:function(id,grid){
        var me=this,
            store=grid.getStore(),
            record=store.getById(parseInt(id)),
            row=record ? grid.getView().getRow(record) : null,
            index=row ? grid.getView().indexOf(row) : null,
            proxy = store.getProxy(),
            params = {};
        //the record exist in the grid view
        if(index!=null){
        	return new Ext.Promise(function (resolve, reject) {
        		resolve(index);
            });
        }
        //the grid does not exist in the grid, get the index from the db
        params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(store.getSorters().items);
        params['projectsOnly'] = true;
        return new Ext.Promise(function (resolve, reject) {
	        Ext.Ajax.request({
	            url: Editor.data.restpath+'task/'+id+'/position',
	            method: 'GET',
	            params: params,
	            scope: me,
	            success: function(response){
	            	//TODO: handle the fail messages and so
	            	 var responseData = Ext.JSON.decode(response.responseText);
	                 if(!responseData){
	                	 resolve(-1)
	                     return;
	                 }
	            	 resolve(responseData.index);
	            },
	            failure: function(response){
	            	Editor.app.getController('ServerException').handleException(response);
	                reject("Error on search index request.");
	            }
	        });
        });
    },
	
});