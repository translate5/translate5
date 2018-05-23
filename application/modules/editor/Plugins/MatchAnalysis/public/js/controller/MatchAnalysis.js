
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class Editor.plugins.MatchAnalysis.controller.MatchAnalysis
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.MatchAnalysis.controller.MatchAnalysis', {
    extend: 'Ext.app.Controller',
    
    requires: [
        'Editor.plugins.MatchAnalysis.view.AnalysisPanel',
        'Editor.plugins.MatchAnalysis.view.MatchResources'
    ],
    
    models: ['Editor.plugins.MatchAnalysis.model.MatchAnalysis'],
    stores:['Editor.plugins.MatchAnalysis.store.MatchAnalysis'],
    
    refs:[{
        ref: 'adminTaskPreferencesWindow',
        selector: 'adminTaskPreferencesWindow'
    },{
        ref: 'preferencesTabpanal',
        selector: 'adminTaskPreferencesWindow > tabpanel'
    },{
        ref:'matchAnalysisPanel',
        selector: 'matchAnalysisPanel'
    }],
        
    strings:{
        taskGridIconTooltip:'#UT#Analysis panel'
    },
    
    listen:{
        component:{
            '#adminTaskPreferencesWindow tabpanel':{
                render:'onTaskPreferencesWindowPanelRender'
            },
            '#adminTaskAddWindow': {
                beforerender:'onAdminTaskWindowBeforeRender'
            }
        }
    },
    
    onAdminTaskWindowBeforeRender:function(window,eOpts){
        var me=this;
        window.insertCard({
            xtype:'matchResourcesPanel',
            listeners:{
                activate:me.onMatchResourcesPanelActivate
            }
        },'postimport');      
    },

    init : function() {
        var me = this,
            toc = me.application.getController('admin.TaskOverview');
        toc.on('taskActionColumnNoHandler', me.onTaskActionColumnNoHandler, me);
    },
    
    /***
     * On task preferences window tabpanel render
     */
    onTaskPreferencesWindowPanelRender:function(panel){
        var me=this,
            prefWindow=panel.up('window');
        
        //add the matchanalysis panel in the tabpanel
        panel.add({
           xtype:'matchAnalysisPanel',
           task:prefWindow.actualTask
        });
    },
    
    /***
     * When action column click with no click handler is found
     */
    onTaskActionColumnNoHandler:function(column,task){
        if(this.getAdminTaskPreferencesWindow()){
            return;
        }
        var me=this,
            taskPref = me.application.getController('admin.TaskPreferences');
        
        //display the task preferences window with focus on matchanalysis panel
        taskPref.handleTaskPreferences(task,'matchAnalysisPanel');
    },
    
    onMatchResourcesPanelActivate:function(panel){
        var me=this,
            taskAssoc=Editor.app.getController('Editor.plugins.MatchResource.controller.TaskAssoc');
        taskAssoc.handleLoadPreferences(taskAssoc,panel.task);
    }
});