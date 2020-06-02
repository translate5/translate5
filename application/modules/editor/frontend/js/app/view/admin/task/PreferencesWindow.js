
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

//TODO: remove the window from the name(controller and vm to) and update all references
Ext.define('Editor.view.admin.task.PreferencesWindow', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.adminTaskPreferencesWindow',
    controller: 'editortaskPreferencesWindowController',
    requires: [
       'Editor.view.admin.task.PreferencesWindowController',
       'Editor.view.admin.task.PreferencesWindowViewModel',
       'Editor.view.admin.task.UserAssoc',
       'Editor.view.admin.task.Preferences',
       'Editor.view.admin.task.TaskAttributes',
       'Editor.view.admin.task.LogGrid',
       'Editor.view.admin.task.LogWindow'
    ],
    itemId: 'adminTaskPreferencesWindow',
    header:false,
    strings: {
        close: '#UT#Fenster schließen',
        events: '#UT#Ereignisse',
        notifyUsersTitle: '#UT#Zugewiesene Benutzer benachrichtigen?',
        notifyUsersMsg: '#UT#Sollen die zugewiesenen Benutzer über die Zuweisung der Aufgabe benachrichtigt werden?',
        userNotifySuccess:'#UT#Benutzer wurden erfolgreich per E-Mail benachrichtigt'
    },
    layout: 'fit',
    viewModel: {
        type: 'taskpreferences'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            task = me.initialConfig.actualTask,
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
        
        if(auth.isAllowed('editorEditTaskPm') || 
            auth.isAllowed('editorEditTaskOrderDate') ||
            auth.isAllowed('editorEditTaskTaskName')||
            auth.isAllowed('editorEditTaskEdit100PercentMatch')){
            tabs.push({
                xtype: 'taskattributes'
            });
        }
        
        if(auth.isAllowed('editorTaskLog')) {
            tabs.push({
                xtype: 'editorAdminTaskLogGrid',
                title: this.strings.events,
                bind:{
                	task:'{currentTask}'
                }
            });
        }
        
        config = {
            items : [{
                xtype: 'tabpanel',
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
        vm.set('currentTask',task);
    },

    getCurrentTask:function(){
        return this.getViewModel().get('currentTask');
    }
});
