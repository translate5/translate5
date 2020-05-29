
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
    
    strings:{
        noProjectMessage:'#UT#Das angeforderte Projekt existiert nicht',
        noProjectTaskMessage:'#UT#Die angeforderte Projektaufgabe existiert nicht',
        noProjectInFilter:'#UT#Projekt im aktuellen Filter nicht gefunden'
    },
    rootRoute:'#project',
    
    routes:{
    	'project':'onProjectRoute',
    	'project/:id/focus' :'onProjectFocusRoute',
    	'project/:id/:taskId/focus' :'onProjectTaskFocusRoute' 
    },
    
    /***
     * Redirect to project focus route route
     */
    redirectFocus:function(record,includeTask){
    	var me=this,
    		action='focus',
    		route=['project',record.get('projectId')];
    	
    	if(includeTask){
    		route.push(record.get('id'));
    	}
    	
    	route.push(action);
    	route=route.join('/');
    	
    	Editor.app.openAdministrationSection(me.getView(),route);
    	me.redirectTo(route);
    },

    /***
     * On project rute
     */
    onProjectRoute:function(){
    	var me=this;
    	//if it is the default route, update the hash from the selected records
    	if(window.location.hash==me.rootRoute){
    		var rec=me.getViewModel().get('projectTaskSelection');
    		rec && me.redirectFocus(rec,true)
    	}
    },
    
    
    /***
     * On Project Focus rute
     */
    onProjectFocusRoute:function(id){
		var me=this;
		me.selectProjectRecord(id);
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
			isFocus=(rute.length==4 && rute[3]=='focus'),
			rec=null;
		if(isFocus){
			rec=parseInt(rute[2]);
		}
		me.selectProjectTaskRecord(rec);
	},
	
	/***
	 * After project task store is loaded
	 */
	onProjectTaskLoad:function(store){
		var me=this;
		me.focusProjectTask();
	},
	
	
	/***
	 * Select project record in the projectGrid. This will also search for the record index if the record is not loaded in the buffered grid
	 */
	selectProjectRecord:function(id){
		var me=this,
			view=me.getView(),
			grid=view.down('#projectGrid'),
			record=null;
		
		//serch for the task store record index
		me.searchIndex(id,grid).then(function(index){
			//do not scroll on empty store
			if(grid.getStore().getTotalCount()==0){
				Editor.MessageBox.addInfo(me.strings.noProjectInFilter);
				grid.setLoading(false);
				return;
			}
			grid.bufferedRenderer.scrollTo(index,{
				callback:function(){
					//no db index if found
					if(index===undefined || index<0){
						me.getViewModel().set('projectSelection',null);
						me.getViewModel().set('projectTaskSelection',null);
						Editor.MessageBox.addInfo(me.strings.noProjectMessage);
						grid.setLoading(false);
						return;
					}
					
					record=grid.getStore().getById(parseInt(id));
					if(record){
						me.focusRecordSilent(grid,record,'projectSelection');
						return;
					}
					grid.getController().reloadProjects().then(function(){
						record=grid.getStore().getById(parseInt(id));
						me.focusRecordSilent(grid,record,'projectSelection');
					});
				},
				notScrollCallback:function(){
					Editor.MessageBox.addInfo(me.strings.noProjectInFilter);
					grid.setLoading(false);
				}
			});
		}, function(err) {
			//the exception is handled in the searchIndex
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
			projectGrid=view.down('#projectGrid'),
			store=grid.getStore(),
			record= (id === null) ? store.getAt(0) : store.getById(parseInt(id));
		
		if(!record){
			//display info message when the flag showNoRecordMessage is set 
			showNoRecordMessage && Editor.MessageBox.addInfo(me.strings.noProjectTaskMessage);
			//update the record view model
			me.getViewModel().set('projectTaskSelection',record);
			projectGrid.setLoading(false);
			return;
		}
		
		//update the location hash
		me.redirectFocus(record,true);

		//focus and select the record
		me.focusRecordSilent(grid,record,'projectTaskSelection');
		
		projectGrid.setLoading(false);
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
    
    /***
     * Focus and select grid record without fiering the selectionchange event.
     * This will also update the viw model variable name with the record
     */
    focusRecordSilent:function(grid,record,name){
    	var me=this;
		grid.suspendEvent('selectionchange');
		me.getViewModel().set(name,record);
		grid.setSelection(record);
		grid.getView().focusRow(record);
		grid.resumeEvent('selectionchange');
    }
	
});