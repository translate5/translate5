
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

Ext.define('Editor.view.project.ProjectGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.projectGrid',
    strings: {
    	deleteProjectDialogTitle:'#UT#Projekt komplett löschen?',
    	deleteProjectDialogMessage:'#UT#Sollten das Projekt und alle im Projekt enthaltenen Aufgaben gelöscht werden?',
    	projectDeleteButtonText:'#UT#Projekt löschen',
    	projectCanceltButtonText:'#UT#Nein',
    	projectRemovedMessage:'#UT#Das Projekt wurde erfolgreich entfernt!'
    },
    listen: {
        messagebus: {
            '#translate5 task': {
                triggerReload: 'onTriggerTaskReload'
            }
        },
        component:{
            '#resetFilterBtn':{
                click:'onResetFilterButtonClick'
            }
        }
    },
    
    onTriggerTaskReload: function(params) {
        var store = this.getView().getStore(),
            project;
        if(params.taskId) {
            project = store.getById(params.taskId);
        }
        else {
            project = store.findRecord( 'taskGuid', params.taskGuid, 0, false, true, true);
        }
        project && project.load();
    },
    
    /***
     * Reset filter button click handler
     */
    onResetFilterButtonClick:function(){
        this.getView().getPlugin('gridfilters').clearFilters();
    },
    
    /***
     * Delete project button handler
     */
    handleProjectDelete:function(project,event){
    	var me=this;
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
                	me.deleteProject(project);
                }
            }
          });
    },

    /**
     * Find the action icon click handler
     */
    projectActionDispatcher: function(view, cell, row, col, ev, evObj) {
        var me = this,
            t = ev.getTarget(),
            f = t.className.match(/ico-project-([^ ]+)/),
            camelRe = /(-[a-z])/gi,
            camelFn = function(m, a) {
                return a.charAt(1).toUpperCase();
            },
            actionIdx = ((f && f[1]) ? f[1] : "not-existing"),
            //build camelized action out of icon css class:
            action = ('handleProject-'+actionIdx).replace(camelRe, camelFn),
            right = action.replace(/^handleProject/, 'editor')+'Project',
            project = view.getStore().getAt(row);

        if(! Editor.app.authenticatedUser.isAllowed(right)){
            return;
        }
        
        if(! me[action] || ! Ext.isFunction(me[action])){
            return;
        }
        me[action](project, ev);
    },
    
    
    /***
     * Delete project by givent projectId
     */
    deleteProject:function(project){
    	var me=this;
    	project.dropped = true; //doing the drop / erase manually
    	project.save({
            //prevent default ServerException handling
            preventDefaultHandler: true,
            callback: function(rec, op) {
                Editor.MessageBox.addByOperation(op);
            },
            success: function() {
            	me.reloadProjects();
            	Ext.StoreManager.get('admin.Tasks').reload();
            	Editor.MessageBox.addSuccess(me.strings.projectRemovedMessage,2);
            },
            failure: function(records, op){
            	Editor.app.getController('ServerException').handleException(op.error.response);
            }
        });
    },
    
    /***
     * Reload the project store. Return promisse after the store is loaded
     */
    reloadProjects:function(){
         var store = this.getView().getStore();
         return new Ext.Promise(function (resolve, reject) {
             store.load({
                 callback: function(records, operation, success) {
                	 success ? resolve(records) : reject(operation); 
                 }
             });
         });
    }
});
