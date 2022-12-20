
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
        'Editor.view.admin.task.TaskManagement'
    ],
    itemId: 'projectPanel',
    controller:'projectPanel',
    helpSection: 'project',
    title: '#UT#Projekte',
    glyph: 'xf0e8@FontAwesome5FreeSolid',
    layout: {
        type: 'border',
        regionWeights: {
            west: 20,
            north: 10,
            south: -10,
            east: -20
        }
    },
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
                    xtype: 'projectGrid',
                    reference: 'projectGrid',
                    stateful: {
                        height: false,
                        weight: false,
                        columns: false, //grid state not tested here yet, so save only height and width
                        width: true
                    },
                    stateId: 'projectGrid',
                    stateEvents: ['resize'], //currently we save sizes only!
                    header: false,
                    region: 'west',
                    split: true,
                    //resizable: false,
                    width: '50%',
                    scrollable: true
                },{
                    xtype: 'projectTaskGrid',
                    reference:'projectTaskGrid',
                    stateful: {
                        height: true,
                        weight: false,
                        columns: false, //grid state not tested here yet, so save only height and width
                        width: true
                    },
                    stateId: 'projectTaskGrid',
                    stateEvents: ['resize'], //currently we save sizes only!
                    region: 'center',
                    scrollable: true,
                    //resizable: false,
                    height: '30%',
                    width: '50%',
                    split: true,
                    bind:{
                        title:me.strings.projectTasksTitle,
                        disabled:'{!projectSelection}',
                        store:'{projectTasks}'
                    }
                },{
                    xtype:'adminTaskTaskManagement',
                    scrollable: true,
                    stateful: {
                        height: true,
                        weight: false,
                        width: true
                    },
                    stateId: 'projectTaskPrefWindow',
                    stateEvents: ['resize'], //currently we save sizes only!
                    region: 'south',
                    height: '70%',
                    width: '50%',
                    split: true,
                    bind:{
                        disabled:'{!projectSelection}',
                        currentTask:'{projectTaskSelection}'
                    }
                }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});