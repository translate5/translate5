
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

Ext.define('Editor.view.admin.task.TaskManagement', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.adminTaskTaskManagement',
    requires: [
        'Editor.view.admin.task.TaskManagementViewModel',
        'Editor.view.admin.task.UserAssoc',
        'Editor.view.admin.task.Preferences',
        'Editor.view.admin.task.TaskAttributes',
        'Editor.view.quality.admin.TaskQualities',
        'Editor.view.admin.task.LogGrid',
        'Editor.view.admin.task.LogWindow',
        'Editor.view.admin.config.Grid',
        'Editor.view.LanguageResources.pivot.Assoc',
        'Editor.view.admin.task.ManageFiles.Main'
    ],
    itemId: 'adminTaskTaskManagement',
    header:false,
    strings: {
        close: '#UT#Fenster schließen',
        events: '#UT#Ereignisse',
        config : '#UT#Standardkonfiguration Kunde überschreiben',
        pivotAssoc: '#UT#Pivot-Sprachressourcen'
    },
    layout: 'fit',
    viewModel: {
        type: 'taskManagement'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            auth = Editor.app.authenticatedUser,
            tabs = [],
            config;
        
        if(auth.isAllowed('editorChangeUserAssocTask')) {
            tabs.push({
                xtype: 'adminTaskUserAssoc',
            });
        }
        
        if(auth.isAllowed('languageResourcesTaskassoc')) {
            tabs.push({
                xtype: 'languageResourceTaskAssocPanel'
            });
        }

        if(auth.isAllowed('languageResourcesTaskPivotAssoc')) {
            tabs.push({
                xtype: 'languageResourcePivotAssoc',
                title: me.strings.pivotAssoc,
                bind:{
                    disabled:'{disabledDuringTaskImport}'
                }
            });
        }

        if(auth.isAllowed('editorManageQualities')) {
            tabs.push({
                xtype: 'taskQualities',
                bind:{
                    disabled:'{disabledDuringTaskImport}',
                    extraParams:{
                        taskGuid: '{projectTaskSelection.taskGuid}',
                        taskId: '{projectTaskSelection.id}'                        
                    }
                }
            });
        }

        if(auth.isAllowed('taskReimport')) {
            tabs.push({
                xtype: 'taskManageFilesMain',
                bind:{
                    disabled:'{disabledDuringTaskImport}'
                }
            });
        }
        
        if(auth.isAllowed('editorEditTaskPm') || 
            auth.isAllowed('editorEditTaskOrderDate') ||
            auth.isAllowed('editorEditTaskTaskName')||
            auth.isAllowed('editorEditTaskEdit100PercentMatch')){
            tabs.push({
                xtype: 'taskattributes'
            });
        }
        
        if(auth.isAllowed('taskConfigOverwriteGrid')) {
            tabs.push({
                xtype: 'adminConfigGrid',
                store:'admin.task.Config',
                title:me.strings.config,
                bind:{
                    disabled:'{disabledDuringTaskImport}',
                    extraParams:{
                        taskGuid: '{projectTaskSelection.taskGuid}'
                    }
                }
            });
        }
        
        if(auth.isAllowed('editorTaskLog')) {
            tabs.push({
                xtype: 'editorAdminTaskLogGrid',
                title: this.strings.events,
                bind:{
                    task: '{currentTask}'
                }
            });
        }


        config = {
            items : [{
                xtype: 'tabpanel',
                itemId: 'taskManagementTabPanel',
                activeTab: 0,
                items: tabs
            }]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    setCurrentTask:function(task){
        var me = this,
            vm = me.getViewModel();
          
        //set the task to null. View model binding will not be triggered
        //with undefined task values
        if(task === undefined){
            task = null;
        }
        vm.set('currentTask',task);
    },

    getCurrentTask:function(){
        return this.getViewModel().get('currentTask');
    }
});
