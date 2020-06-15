
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
Ext.define('Editor.view.admin.task.menu.TaskActionMenuViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.taskActionMenu',
    
    data: {
        task: false
    },
    formulas: {
    	isNotErrorImportPendingCustom:{
    		get: function(get) {
            	// !error && !import && !pending && !customState
            	return this.get('task').isNotErrorImportPendingCustom();
            },
            bind:{bindTo:'{task}',deep:true}
    	},
    	isNotImportPendingCustom:{
    		get: function(get) {
            	return this.get('task').isNotImportPendingCustom();
            },
            bind:{bindTo:'{task}',deep:true}
    	},
    	isEditorEditTask:{
    		get: function(get) {
            	return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorEditTask',this.get('task'));
            },
            bind:{bindTo:'{task}',deep:true}
    	},
        isEditorOpenTask: {
            get: function(get) {
            	var task=this.get('task');
            	//!error && !import && !pending && (!customState || customState == ExcelExported)
            	return this.isMenuAllowed('editorOpenTask',task) && task.isNotErrorImportPending() && (!task.isCustomState() || task.get('state') == 'ExcelExported');
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorFinishTask:{
    		get: function(get) {
            	return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorFinishTask',this.get('task'));
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorUnfinishTask:{
    		get: function(get) {
            	return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorUnfinishTask',this.get('task'));
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorEndTask:{
    		get: function(get) {
            	return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorEndTask',this.get('task'));
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorReopenTask:{
    		get: function(get) {
            	return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorReopenTask',this.get('task'));
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorPreferencesTask:{
    		get: function(get) {
            	return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorPreferencesTask',this.get('task'));
            },
            bind: '{task}'
        },
        isEditorCloneTask:{
        	get: function(get) {
            	return this.get('isNotImportPendingCustom') && this.isMenuAllowed('editorCloneTask',this.get('task'));
            },
            bind: '{task}'
        },
        isEditorShowexportmenuTask:{
        	get: function(get) {
        		//(!error || error && downloadable) && !import && !pending && (!customState || customState == ExcelExported)
            	var task=this.get('task'),
            		downloadable=task.isErroneous() && Editor.app.authenticatedUser.isAllowed('downloadImportArchive'),
            		allowed=this.isMenuAllowed('editorShowexportmenuTask',task);
            	if(downloadable && allowed){
            	    return true;
            	}
            	return allowed && !task.isImporting() && !task.isPending() && (!task.isCustomState() || task.get('state') == 'ExcelExported');
            },
            bind: '{task}'
        },
        isEditorExcelreimportTask:{
        	get: function(get) {
            	return this.isMenuAllowed('editorExcelreimportTask',this.get('task')) && (this.get('task').get('state') == 'ExcelExported');
            },
            bind: '{task}'
        },
        isEditorDeleteTask:{
        	get: function(get) {
        		return this.get('isNotImportPendingCustom') && this.isMenuAllowed('editorDeleteTask',this.get('task'));
        	},
            bind: '{task}'
        },
        isEditorLogTask:{
        	get: function(get) {
            	var task=this.get('task');
            	// !import && !pending && (!customState || customState == ExcelExported)
            	return this.isMenuAllowed('editorLogTask',task) && !task.isImporting() && !task.isPending() && (!task.isCustomState() || task.get('state') == 'ExcelExported');
            },
            bind: '{task}'
        },
        //the menu grup lines are only visible for pm users
        isMenuGroupVisible:{
        	get: function(get) {
        		return this.get('isEditorPreferencesTask');
            },
            bind: '{task}'
        },
        
        /***
         * On task change, reconfigure the export menu
         */
        exportMenuConfig:{
            get: function(task) {
                //the old menu will be destroyed by the owner component
                var me = this,
                    hasQm = task.hasQmSub(),
                    menu;
                
                menu = Ext.widget('adminExportMenu', {
                    task: task,
                    fields: hasQm ? task.segmentFields() : false
                });
                return menu;
            },
            bind: '{task}'
        }
    },
    
    /***
     * Is the menu action allowed for the user for given task
     */
    isMenuAllowed:function(action,task){
    	return Editor.app.authenticatedUser.isAllowed(action,task)
    }
    
});