
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
        noEnginesFoundMsg:'#UT#Keine Globalese Übersetzungs-Engine verfügbar (für Ihren Globalese Benutzer und Ihre Sprachkombination). Bitte ändern Sie den Benutzer oder überspringen Sie die Vorübersetzung.',
        noGroupsFoundMsg:'#UT#Keine Globalese Benutzergruppe verfügbar (für Ihren Globalese Benutzer und Ihre Sprachkombination). Bitte ändern Sie den Benutzer oder überspringen Sie die Vorübersetzung.',
        authErrorMsg:'#UT#Benutzer oder Passwort sind nicht valide.',
        loadingWindowMessage:"#UT#Laden",
        noEnginesForLanguageComboMsg:'#UT#Für die aktuelle Sprachkombination existiert keine Engine in Globalese. Bitte überspringen Sie den Globalese Schritt für diesen Import.'
        
    },
    
    onAuthPanelBeforeRender:function(panel,eOpts){
        this.initFieldDefaultValues();
    },
    
    /***
     * On panel activate validate if the current import is project import.
     */
    onAuthPanelActivate:function(){
        var me=this,
            view=me.getView(),
            window=view.up('window'),
            targetLangValue=window.down('combo[name="targetLang[]"]').getValue();
        //if the current import is project, skip the globalese pretranslation
        if(targetLangValue.length > 1){
            view.fireEvent('wizardCardFinished',2);
        }
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
        var me=this,
            view=me.getView(),
            window=view.up('window'),
            sourceLangValue=window.down('combo[name="sourceLang"]').getValue(),
            targetLangValue=window.down('combo[name="targetLang[]"]').getValue(),
            apiusername=view.down('#apiUsername').getValue(),
            apipassword=view.down('#apiPassword').getValue(),
            globaleseEngine=window.down('#globaleseEngine'),
            globaleseGroup=window.down('#globaleseGroup'),
            url = Editor.data.restpath+'plugins_globalesepretranslation_globalese/engines',
            paramsData = Ext.JSON.encode({
                username: apiusername,
                apiKey: apipassword,
                sourceLang:sourceLangValue,
                targetLang:targetLangValue[0]
            }),
            params = {data: paramsData};
        
        Ext.Ajax.request({
            url:url,
            method: 'GET',
            params: params,
            success: function(response){
                var responsData = Ext.JSON.decode(response.responseText);
                if(!responsData){
                    Editor.MessageBox.addInfo(me.strings.noEnginesFoundMsg, 1.4);
                }
                if(responsData.rows && responsData.rows.length<1){
                    Editor.MessageBox.addInfo(me.strings.noEnginesForLanguageComboMsg, 1.4);
                    globaleseEngine.setDisabled(true);
                    globaleseGroup.setDisabled(true);
                }else{
                    //engines exist, set the store from the loaded data
                    var engines = Ext.create('Ext.data.Store', {
                        fields: [
                            {name: 'id', type: 'string'}, // Engine id can be string or number. Since we are not saving this into the database, we can set the model to string.
                            {name: 'name',  type: 'string'},
                            {name: 'group',  type: 'int'},
                            {name: 'source',  type: 'string'},
                            {name: 'target',  type: 'string'},
                            {name: 'status',  type: 'string'}
                            ],
                            data : responsData.rows
                    });
                    globaleseEngine.setStore(engines);
                }
                
                view.fireEvent('wizardCardFinished');
                window.setLoading(false);
                return;
            },
            failure: function(response){
                window.setLoading(false);
                Editor.app.getController('ServerException').handleException(response);
            } 
        });
    },
    /***
     * get the groups for the selected user
     */
    getGroups:function(view){
        
        var me=this,
            view=me.getView(),
            window=view.up('window'),
            apiusername=view.down('#apiUsername').getValue(),
            apipassword=view.down('#apiPassword').getValue(),
            globaleseGroup=window.down('#globaleseGroup'),
            url = Editor.data.restpath+'plugins_globalesepretranslation_globalese/groups',
            authData = Ext.JSON.encode({
                username: apiusername,
                apiKey: apipassword,
            }),
            params = {data: authData};
        
        window.setLoading(me.strings.loadingWindowMessage);
        Ext.Ajax.request({
            url:url,
            method: 'GET',
            params: params,
            success: function(response){
                var responseObject = Ext.JSON.decode(response.responseText);
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
            },
            failure: function(response){
                window.setLoading(false);
                if(response.status=401){
                    Editor.MessageBox.addError(me.strings.authErrorMsg);
                    return;
                }
                Editor.app.getController('ServerException').handleException(response);
            } 
        });
    }
});