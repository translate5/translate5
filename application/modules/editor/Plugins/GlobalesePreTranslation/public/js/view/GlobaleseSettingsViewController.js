
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
Ext.define('Editor.plugins.GlobalesePreTranslation.view.GlobaleseSettingsViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.globaleseSettingsPanel',
    
    onGlobaleseGroupChange:function(field,newValue,oldValue,eOpts){
        var me=this,
            view=me.getView(),
            globaleseEngine=view.down('#globaleseEngine'),
            engineStore = globaleseEngine.getStore();
        
        if(globaleseEngine.isDisabled()){
            globaleseEngine.setDisabled(false);
        }
        
        globaleseEngine.setValue(null);
        
        engineStore.clearFilter();
        
        engineStore.filterBy(function(record){
            // load always stock engines
            if(record.get('id') == 'stock'){
                return true;
            }
            // if it is not stock engine, load only the selected group
            return record.get('group') == newValue;
        });

        if(engineStore.getCount()==1){
            globaleseEngine.setValue(globaleseEngine.getStore().getAt(0));
        }
    },
    
    handleNextCardClick:function(){
        var me=this,
            view=me.getView();
        me.isFieldsValid();
    },
    
    handleSkipCardClick:function(){
        var me=this,
            view=me.getView();
        view.fireEvent('wizardCardFinished',1);
    },
    
    isFieldsValid:function(){
        var me=this,
            view=me.getView(),
            globaleseEngine=view.down('#globaleseEngine'),
            globaleseGroup=view.down('#globaleseGroup');
        
        if(globaleseEngine.isValid() && globaleseGroup.isValid()){
        	me.queueWorker();
        }
    },
    
    /***
     * Queue the globalese worker
     */
    queueWorker:function(){
        var me=this,
            view=me.getView(),
            win=view.up('window'),
            apiusername=win.down('#apiUsername').getValue(),
            apipassword=win.down('#apiPassword').getValue(),
            globaleseEngine=view.down('#globaleseEngine').getValue(),
            globaleseGroup=view.down('#globaleseGroup').getValue(),
            url = Editor.data.restpath+'plugins_globalesepretranslation_globalese',
            extraData = Ext.JSON.encode({
                apiUsername: apiusername,
                apiKey: apipassword,
                group:globaleseGroup,
                engine:globaleseEngine,
                taskGuid:view.task.get('taskGuid')
            }),
            params = {data: extraData};
        
        Ext.Ajax.request({
            url:url,
            method: 'POST',
            params: params,
            success: function(response){
                view.fireEvent('wizardCardFinished');
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            } 
        });
    }
});