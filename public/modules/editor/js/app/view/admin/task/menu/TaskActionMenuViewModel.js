
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

Ext.define('Editor.view.admin.task.menu.TaskActionMenuViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.taskActionMenu',
    
    data: {
        task: false
    },
    formulas: {
        isNotErrorImportPendingCustom:{
            get: function(record) {
                // !error && !import && !pending && !customState
                return record && record.isNotErrorImportPendingCustom();
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isCancelable:{
            get: function(record) {
                return record && this.isMenuAllowed('editorCancelImport', record);
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isNotImportPendingCustom:{
            get: function(record) {
                return record && record.isNotImportPendingCustom();
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorEditTask:{
            get: function(record) {
                return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorEditTask',record);
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorOpenTask: {
            get: function(record) {
                //!error && !import && !pending && (!customState || customState == ExcelExported)
                return record && this.isMenuAllowed('editorOpenTask',record) && record.isNotErrorImportPending() && (!record.isCustomState() || record.get('state') === 'ExcelExported');
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorFinishTask:{
            get: function(record) {
                return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorFinishTask',record);
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorUnfinishTask:{
            get: function(record) {
                return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorUnfinishTask',record);
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorEndTask:{
            get: function(record) {
                return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorEndTask',record);
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorReopenTask:{
            get: function(record) {
                return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorReopenTask',record);
            },
            bind:{bindTo:'{task}',deep:true}
        },
        isEditorPreferencesTask:{
            get: function(record) {
                return this.get('isNotErrorImportPendingCustom') && this.isMenuAllowed('editorPreferencesTask',record);
            },
            bind: '{task}'
        },
        isEditorCloneTask:{
            get: function(record) {
                return this.get('isNotImportPendingCustom') && this.isMenuAllowed('editorCloneTask',record);
            },
            bind: '{task}'
        },
        isEditorShowexportmenuTask:{
            get: function(task) {
                //(!error || error && downloadable) && !import && !pending && (!customState || customState == ExcelExported)
                if(!task) {
                    return false;
                }
                var downloadable = task.isErroneous() && Editor.app.authenticatedUser.isAllowed('downloadImportArchive'),
                    allowed = this.isMenuAllowed('editorShowexportmenuTask',task);
                if(downloadable && allowed){
                    return true;
                }
                return allowed && !task.isImporting() && !task.isPending() && (!task.isCustomState() || task.get('state') === 'ExcelExported');
            },
            bind: '{task}'
        },
        isEditorExcelreimportTask:{
            get: function(record) {
                return record && this.isMenuAllowed('editorExcelreimportTask',record) && (record.get('state') === 'ExcelExported');
            },
            bind: '{task}'
        },
        isEditorDeleteTask:{
            get: function(record) {
                return this.get('isNotImportPendingCustom') && this.isMenuAllowed('editorDeleteTask',record);
            },
            bind: '{task}'
        },
        isEditorLogTask:{
            get: function(task) {
                // !import && !pending && (!customState || customState == ExcelExported)
                return task && this.isMenuAllowed('editorLogTask',task) && !task.isImporting() && !task.isPending() && (!task.isCustomState() || task.get('state') === 'ExcelExported');
            },
            bind: '{task}'
        },
        //the menu group lines are only visible for pm users
        isMenuGroupVisible:{
            get: function() {
                return this.get('isEditorPreferencesTask');
            },
            bind: '{task}'
        },

        // shows if the translation package feature is available for the user. The user can export/reimport translation package only if:
        // - the user is able to edit task
        // - the task is reimportable. The task is reimportable if all of the task files are imported with xliff or
        //   file parser which extends the xliff file parser
        isTranslatorPackageAvailable: {
            get: Editor.util.Util.isTranslatorPackageAvailable,
            bind: '{task}'
        }
    },

    /***
     * Is the menu action allowed for the user for given task
     */
    isMenuAllowed:function(action,task){
        return Editor.app.authenticatedUser.isAllowed(action,task);
    }
    
});