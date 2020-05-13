
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
    
    routes: {
    	'project': 'onProjectRoute',
    	'project/:id' : 'onProjectRoute',
    	'project/:id/:taskId' : 'onProjectRoute',
	},
	
    /***
     * Focus a grid record by given recordId. If no recordId is provided, the first row will be selected
     */
    focusGridRecord:function(grid,recordId){
    	var me=this,
			sellection=grid.getSelectionModel().getSelection()[0],
			store=grid.getStore(),
			record=recordId ? store.getById(recordId) : sellection;
		
		if(!record){
			record=store.getAt(0);
		}
		grid.setSelection(record);
		grid.getView().focusRow(record);
    },
    
	onProjectRoute:function(id,taskId){
		var me=this,
			view=me.getView(),
			projectGrid=view.down('#projectGrid'),
			projectTaskGrid=view.down('#projectTaskGrid')
			route='project',
			controller=projectGrid.getController();
		
		if(id){
			route=route+'/'+id
		}
		
		if(taskId){
			route=route+'/'+taskId;
		}
		Editor.app.openAdministrationSection(view, route);
		
		controller.reloadProjects().then(function(records) {
			me.focusGridRecord(projectGrid,id);
			//reload the projectTaskGrid store
			//this will also trigger the selection
			projectTaskGrid.getStore().load();
		}, function(operation) {
			Editor.app.getController('ServerException').handleException(operation.error.response);
		});
	},
	
    /***
     * Focus project task grid row. This is called afte project task store is updated.
     * This can not be called from separate route, since the project task store is filtered after the project store is loaded.
     * The taskId is calculated based on the current window hash
     */
    focusProjectTask:function(){
		var me=this,
			view=me.getView(),
			rute=window.location.hash,
			rute=rute.split('/'),
			recordId=null;
		if(rute.length==3){
			recordId=rute.pop();
		}
		me.focusGridRecord(me.getView().down('#projectTaskGrid'),recordId);
	},
	
	/***
	 * After project task store update
	 */
	onProjectTasksEndUpdate:function(){
		this.focusProjectTask();
	},
	
});