
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
 * @class Editor.plugins.GlobalesePreTranslation.view.GlobaleseAuthViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.GlobalesePreTranslation.view.GlobaleseAuthViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.globaleseAuthPanel',
    
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
    
    onAuthPanelBeforeRender:function(panel,eOpts){
        this.initFieldDefaultValues();
    },

    initFieldDefaultValues:function(){
        var me=this,
            view=me.getView(),
            username=view.down('#apiUsername'),
            password=view.down('#apiPassword'),
            configUsername=Editor.data.plugins.GlobalesePreTranslation.api.username,
            configPassword=Editor.data.plugins.GlobalesePreTranslation.api.password;
        
        if(configUsername && configUsername!=""){
            username.setValue(configUsername);
        }else{
            username.setValue(Editor.data.app.user.login);
        }
        
        if(configPassword && configPassword!=""){
            password.setValue(configPassword);
        }
    },
    
    handleNextCardClick:function(){
        var me=this,
            view=me.getView(),
            username=view.down('#apiUsername'),
            password=view.down('#apiPassword'),
            skipPretranslation=view.down('#skipPretranslation');
        
        if(skipPretranslation.getValue()){
            view.fireEvent('wizardCardSkiped');
        }
        
        if(username.getValue()!="" && password.getValue()!=""){
            me.getEngine(view);
        }
    },
    /***
     * get the engines for the selected user, and the provided language combination
     */
    getEngine:function(view){
        //if there are no results, or the auth failed, show info message
        
        //this.nextWizardWindow(winLayout,nextItem);
        //return;
        
        var 
            //str = me.strings,
            //params = {},
            //method = 'DELETE',
            url = Editor.data.restpath+'plugins_globalesepretranslation_globalese';
            //checkedData = Ext.JSON.encode({
            //    tmmtId: record.get('id'),
            //    taskGuid: me.actualTask.get('taskGuid'),
            //    segmentsUpdateable: record.get('segmentsUpdateable')
            //});
    
        //if(record.get('checked')) {
        //    method = record.get('taskassocid') ? 'PUT' : 'POST';
        //    params = {data: checkedData};
        //}
        //if(method != 'POST') {
        //    url = url + '/'+record.get('taskassocid');
        //}
        
        Ext.Ajax.request({
            url:url,
            method: 'GET',
            //params: {},
            success: function(response){
                //this event shuld be fiered after the engines are found, and users also (this here will switch to the last window)
                view.fireEvent('wizardCardFinished');
            },
            failure: function(response){
                console.log('failure',response);
            } 
        });
    },
    /***
     * get the groups for the selected user
     */
    getGroups:function(winLayout,nextItem){
      //if there are no results, or the auth failed, show info message
    },
    
    nextWizardWindow:function(winLayout,nextItem){
        winLayout.setActiveItem(nextItem);
    },
    
    isSetActive:function(activeItemId,actuelItem){
        var active = activeItemId.match(/\d+$/)[0],
            current=actuelItem.match(/\d+$/)[0];
        
        return active<current;
    }
    
});
















