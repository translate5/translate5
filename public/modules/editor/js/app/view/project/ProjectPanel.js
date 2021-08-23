
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

Ext.define('Editor.view.project.ProjectPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.projectPanel',
    requires:[
        'Editor.view.project.ProjectGrid',
        'Editor.view.project.ProjectTaskGrid',
        'Editor.view.project.ProjectPanelViewController',
        'Editor.view.project.ProjectPanelViewModel',
        'Editor.view.admin.task.PreferencesWindow'
    ],
    itemId: 'projectPanel',
    controller:'projectPanel',
    helpSection: 'project',
    title: '#UT#Projekte',
    glyph: 'xf0e8@FontAwesome5FreeSolid',
    layout: 'border',
    viewModel:{
        type: 'projectPanel'
    },
    
    strings:{
    	projectTasksTitle:'#UT#Aufgaben für das Projekt: {projectSelection.taskName}'
    },
    listen:{
        'deactivate':'onProjectPanelDeactivate'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config={
            title:me.title,
            items:[{
                    xtype:'projectGrid',
                    reference:'projectGrid',
                    header:false,
                    region: 'center',
                    split: true,
                    resizable: false,
                    scrollable: true
                },{
                    xtype: 'panel',
                    region: 'east',
                    split: true,
                    resizable: true,
                    width:'50%',
                    layout: {
                        type: 'vbox',
                        pack: 'start',
                        align: 'stretch'
                    },
                    bind:{
                        disabled:'{!projectSelection}'
                    },
                    items: [{
                        xtype: 'projectTaskGrid',
                        reference:'projectTaskGrid',
                        scrollable: true,
                        flex:0.3,
                        bind:{
                            title:me.strings.projectTasksTitle,
                            store:'{projectTasks}'
                        }
                    },{
                        xtype:'adminTaskPreferencesWindow',
                        scrollable: true,
                        flex:0.7,
                        bind:{
                            currentTask:'{projectTaskSelection}'
                        }
                    }]
                }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});