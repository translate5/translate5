
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
Ext.define('Editor.view.admin.ProjectGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.projectTaskGrid',
    
    routes: {
    	'project': 'onProjectRoute',
    	'project/:id' : 'onProjectRoute'
	},
	
	
	onProjectRoute:function(projectId) {
		var me=this,
			route=!projectId ? 'project' : ('project/'+projectId);
		Editor.app.openAdministrationSection(me.getView(), route);
		me.reloadProjects().then(function(records) {
			projectId && me.onProjectFocus(projectId)
		}, function(operation) {
			Editor.app.getController('ServerException').handleException(operation.error.response);
		});
	},
    	
	strings: {
		deleteProjectDialogTitle:'#UT#Projekt komplett löschen?',
		deleteProjectDialogMessage:'#UT#Sollten das Projekt und alle im Projekt enthaltenen Aufgaben gelöscht werden?',
		projectDeleteButtonText:'#UT#Projekt löschen',
		projectCanceltButtonText:'#UT#Nein',
		projectRemovedMessage:'#UT#Das Projekt wurde erfolgreich entfernt!'
	},
	
    onProjectFocus:function(projectId){
    	var me=this,
    		record=me.getView().getStore().getById(projectId);
    	me.getView().expandRow(record);
    },
    
    onDeleteProjectClick:function(grid, rowIndex, colIndex){
    	var me=this,
    		rec = grid.getStore().getAt(rowIndex);
        
        Ext.Msg.show({
            title:me.strings.deleteProjectDialogTitle,
            message: me.strings.deleteProjectDialogMessage,
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            closable:false,
            buttonText: {
                yes: me.strings.projectDeleteButtonText,
                no: me.strings.projectCanceltButtonText
            },
            fn: function(btn) {
                if (btn === 'yes') {
                	me.deleteProject(rec.get('projectId'));
                }
            }
          });
    },
    
    deleteProject:function(taskProjectId){
    	var me=this;
    	Ext.Ajax.request({
            url: Editor.data.restpath+'task/deleteproject',
            method: 'post',
            scope: me,
            params:{
            	projectId:taskProjectId
            },
            success: function(response){
            	me.reloadProjects();
            	Editor.MessageBox.addSuccess(me.strings.projectRemovedMessage,2);
            },
            failure: function(response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },
    
    reloadProjects:function(reloadCallback){
    	 var store = this.getView().getStore();
         return new Ext.Promise(function (resolve, reject) {
        	 store.load({
                 callback: function(records, operation, success) {
                	 success ? resolve(records) : reject(operation); 
                 }
             });
         });
    },
    
    onExpandCollapseAllBtnClick:function(btn){
    	this.handleAllExpandCollapse(btn.pressed);
    },
    
    handleAllExpandCollapse:function(btnPressed){
    	var me=this,
			view=me.getView(),
			vm=view.getViewModel(),
			strings=view.strings;
		vm.set('expandCollapseIconCls',btnPressed ? 'ico-toggle' : 'ico-toggle-expand');
		vm.set('expandCollapseText',btnPressed ? strings.collapseAllBtn : strings.expandAllBtn);
		vm.set('expandCollapseTip',btnPressed ? strings.collapseAllBtnTip : strings.expandAllBtnTip);
		view.handleExpandCollapseAll(btnPressed);
    }
});
















