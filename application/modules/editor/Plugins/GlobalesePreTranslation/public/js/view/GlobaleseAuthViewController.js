
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
    
    onAuthPanelBeforeRender:function(panel,eOpts){
        this.initFieldDefaultValues();
    },

    initFieldDefaultValues:function(){
        var me=this,
            view=me.getView(),
            username=view.down('#apiUsername'),
            password=view.down('#apiPassword'),
            configUsername=Editor.data.plugins.GlobalesePreTranslation.api.username,
            configPassword=Editor.data.plugins.GlobalesePreTranslation.api.apiKey;
        
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
            password=view.down('#apiPassword');
        
        if(username.getValue()!="" && password.getValue()!=""){
            me.getGroups(view);
        }
    },
    
    handleSkipCardClick:function(){
        var me=this,
            view=me.getView();
        view.fireEvent('wizardCardFinished',2);
    },
    
    /***
     * get the engines for the selected user, and the provided language combination
     */
    getEngines:function(view){
        //if there are no results, or the auth failed, show info message
        
        //this.nextWizardWindow(winLayout,nextItem);
        //return;
        var me=this,
            view=me.getView(),
            sourceLangValue=view.up('window').down('combo[name="sourceLang"]').getValue(),
            targetLangValue=view.up('window').down('combo[name="targetLang"]').getValue(),
            apiusername=view.down('#apiUsername').getValue(),
            apipassword=view.down('#apiPassword').getValue(),
            globaleseEngine=view.up('window').down('#globaleseEngine'),
            url = Editor.data.restpath+'plugins_globalesepretranslation_globalese/engines',
            params = {},
            method = 'GET',
            paramsData = Ext.JSON.encode({
                username: apiusername,
                apiKey: apipassword,
                sourceLang:sourceLangValue,
                targetLang:targetLangValue
            }),
            params = {data: paramsData};
        
        Ext.Ajax.request({
            url:url,
            method: 'GET',
            params: params,
            success: function(response){
                var responsData = JSON.parse(response.responseText);
                if(!responsData){
                    Editor.MessageBox.addInfo(me.strings.noEnginesFoundMsg, 1.4);
                }
                var engines = Ext.create('Ext.data.Store', {
                    fields: [
                        {name: 'id', type: 'int'},
                        {name: 'name',  type: 'string'},
                        {name: 'group',  type: 'int'},
                        {name: 'source',  type: 'string'},
                        {name: 'target',  type: 'string'},
                        {name: 'status',  type: 'string'},
                    ],
                    data : responsData.rows
                });
                globaleseEngine.setStore(engines);
                //FIXME set the engines to the engines combo
                view.fireEvent('wizardCardFinished');
                return;
            },
            failure: function(response){
                Editor.MessageBox.addError(me.strings.enginesErrorMsg);
            } 
        });
    },
    /***
     * get the groups for the selected user
     */
    getGroups:function(view){
        
        var me=this,
            view=me.getView(),
            apiusername=view.down('#apiUsername').getValue(),
            apipassword=view.down('#apiPassword').getValue(),
            globaleseGroup=view.up('window').down('#globaleseGroup'),
            url = Editor.data.restpath+'plugins_globalesepretranslation_globalese/groups';
        
            //str = me.strings,
            params = {},
            method = 'GET',
            authData = Ext.JSON.encode({
                username: apiusername,
                apiKey: apipassword,
            }),
            params = {data: authData};
        
        Ext.Ajax.request({
            url:url,
            method: 'GET',
            params: params,
            success: function(response){
                var responseObject = JSON.parse(response.responseText);
                if(responseObject && responseObject.rows){
                    var groups = Ext.create('Ext.data.Store', {
                        fields: [
                            {name: 'id', type: 'int'},
                            {name: 'name',  type: 'string'},
                        ],
                        data : responseObject.rows
                    });
                    globaleseGroup.setStore(groups);
                    me.getEngines();
                    return;
                }
                
                Editor.MessageBox.addInfo(me.strings.noGroupsFoundMsg, 1.4);
                
                //this event shuld be fiered after the engines are found, and users also (this here will switch to the last window)
                //view.fireEvent('wizardCardFinished');
            },
            failure: function(response){
                Editor.MessageBox.addError(me.strings.groupsErrorMsg);
            } 
        });
    }
});
















