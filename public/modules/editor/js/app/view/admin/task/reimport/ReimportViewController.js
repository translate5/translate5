
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

Ext.define('Editor.view.admin.task.reimport.ReimportViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminTaskReimportReimport',

    requires:[
        'Editor.view.admin.task.reimport.ReimportWindow',
        'Editor.view.admin.task.reimport.ReimportZipWindow',
    ],

    listen: {
        component:{
            '#exportTranslatorPackage':{
                click:'onExportTranslatorPackageClick'
            },
            '#importTranslatorPackage':{
                click:'onImportTranslatorPackageClick'
            }
        },
        messagebus: {
            '#translate5 task': {
                triggerReload: 'onTriggerTaskReload',
            }
        }
    },

    /***
     * On task reload, refresh the files grid for the currently selected task
     * @param params
     */
    onTriggerTaskReload: function (params){
        var me = this,
            viewTask = me.getView() && me.getView().task,
            taskGuid = params && params.taskGuid;

        if( !taskGuid || !viewTask || (viewTask.get('taskGuid')!==taskGuid)){
            return;
        }
        me.loadStoreData(taskGuid);
    },

    /***
     * Load tree data in the tree grid for given taskGuid
     * @param taskGuid
     */
    loadStoreData: function (taskGuid){
        var me = this,
            store = me.getView().getStore();

        Ext.Ajax.request({
            url:Editor.data.restpath+'filetree/root',
            method: "GET",
            params:{
                taskGuid:taskGuid
            },
            scope: this,
            success: function(response){
                var resp = Ext.util.JSON.decode(response.responseText),
                    result = resp['rows'];
                // even if the root is disabled, adding root node is the only way to display the data !
                me.getView().setRootNode({
                    expanded:true,
                    children:result
                });

            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /***
     * Check if the current task allows reimport action
     * @returns {boolean}
     */
    isReimportAllowewd: function (){
        var me = this,
            task = me.getView().task;

        // if the current task state does not allow this action (is importing, is locked or is not editable)
        // show info message to the user
        if( task.isImporting() || task.isLocked() || !task.isEditable() ){
            Ext.create('Ext.window.MessageBox').show({
                title: '',
                msg: Editor.data.l10n.projectOverview.taskManagement.taskReimport.taskNotAllowThisActionMessage,
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.INFO
            });
            return false;
        }
        return true;
    },

    onUploadAction: function (grid, rowIndex, colIndex, actionItem, event, record, row){
        var me = this;

        if( me.isReimportAllowewd() === false){
            return;
        }

        var win = Ext.widget('adminTaskReimportReimportWindow');
        win.loadRecord(record,this.getView().task);
        win.show();
    },

    /***
     *
     */
    onExportTranslatorPackageClick: function (){
        var me = this,
            task = me.getView().task;
        window.open(Editor.data.restpath + Ext.String.format('task/export/id/{0}?format=package', task.get('id')), '_blank');
    },

    /***
     *
     */
    onImportTranslatorPackageClick: function (){
        var me = this;

        if( me.isReimportAllowewd() === false){
            return;
        }

        var win = Ext.widget('adminTaskReimportReimportZipWindow');
        win.loadRecord(me.getView().task);
        win.show();
    }
});