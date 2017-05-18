
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.admin.TaskUploadViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.admin.TaskUploadViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.taskUpload',
    
    strings:{
        noEnginesFoundMsg:'#UT#No Globalese translation engine for the current language combination available for your username. Please change the username or skip Globalese pre-translation',
        noGroupsFoundMsg:'#UT#No groups found for curren user.',
        groupsErrorMsg:'#UT#Globalese username and password combination is not valid.',
        enginesErrorMsg:'#UT#Error on engines search.',
    },
    
/*    listen: {
        controller: {
            taskOverviewController:{
                taskWindowNextClick:'onTaskWindowNextClick'
            }
        }

//here listen on the continue button
//probably i need to fire a event in TaskOverVeiw controller, for the continue click, and then listen on the controller like i did it for taskWindowNextClick 
//pressing skip button triggers event cardfinished with integer paremter 2
//pressing next button triggers event cardfinished with integer paremter 1
    },
  */
    
    onTaskUploadActivate:function(panel,eOpts){
        var me=this,
            view=me.getView();
        view.fireEvent('wizardCardFinished');
    },
    
    onAuthPanelBeforeRender:function(panel,eOpts){
    },

    
    handleNextCardClick:function(){
        var me=this,
            view=me.getView();
        
        //fire this event only when the task upload is finished
        view.fireEvent('wizardCardFinished');
    }
});
















