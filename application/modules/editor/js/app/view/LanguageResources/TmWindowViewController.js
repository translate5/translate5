
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

/**
 * @class Editor.view.LanguageResources.TmWindowViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.LanguageResources.TmWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.tmwindowviewcontroller',

    /**
     * On add new tm window render handler
     */
    onTmWindowRender:function(){
        var sdlStore=Ext.StoreManager.get('sdlEngine');

        //set the data to the sdl language store
        sdlStore.setData(Editor.data.LanguageResources.sdlEngines);
    },


    /**
     * Resource combo handler
     */
    onResourceChange:function(){
        var me=this,
            view=me.getView(),
            isSdl=me.isSdlResource(),
            sdlEngineCombo=view.down('#sdlEngine');
        
        //is visible when sdl as resource is selected
        sdlEngineCombo.setVisible(isSdl);
        sdlEngineCombo.setDisabled(!isSdl);

        if(!isSdl){
            return;
        }

        sdlEngineCombo.getStore().clearFilter();
    },

    /**
     * Sdl engine combo change handler
     */
    onSdlEngineComboChange:function(combo,newVal,oldVal,eOpts){
        var me=this,
            view=me.getView(),
            selection=combo.getSelection(),
            source=selection && selection.get('source'),
            target=selection && selection.get('target'),
            sourceField=view.down('combo[name="sourceLang"]'),
            targetField=view.down('combo[name="targetLang"]'),
            langStore=sourceField.getStore();

        //reset the store on empty value
        if(!newVal){
            combo.getStore().clearFilter();
        }

        //clean the languages selection on each engine change
        //suspend the events (the change event)
        sourceField.suspendEvents();
        targetField.suspendEvents();
        sourceField.clearValue(null);
        targetField.clearValue(null);
        
        //get the selection source rfc value, and set the source language combo with it
        if(source){
            var sr=langStore.findRecord ('rfc5646', source,0,false,false,true);
            if(sr){
                sourceField.setSelection(sr);
            }
        }
        //get the selection target rfc value, and set the target language combo with it
        if(target){
            var sr=langStore.findRecord ('rfc5646', target,0,false,false,true);
            if(sr){
                targetField.setSelection(sr);
            }
        }
        sourceField.resumeEvents();
        targetField.resumeEvents();
    },

    /**
     * Source and target language change handler
     */
    onLanguageComboChange:function(){
        var me=this,
            isSdl=me.isSdlResource();
        if(!isSdl){
            return;
        }

        me.filterSdlEngine();
    },

    /**
     * Filter the sdl engine store, when source or target language is selected
     */
    filterSdlEngine:function(){
        var me=this,
            view=me.getView(),
            sdlEngineComboField=view.down('#sdlEngine'),
            sdlEngineComboStore=sdlEngineComboField.getStore(),
            sourceLang=view.down('combo[name="sourceLang"]').getSelection(),
            targetLang=view.down('combo[name="targetLang"]').getSelection(),
            filterData=[];
        
        //clean the engine filters
        sdlEngineComboStore.clearFilter();

        //preselect when only one result is filtered
        sdlEngineComboStore.on({
            filterchange:function (sdlstore,filters,eOpts){
                if(sdlstore.getData().length==1){
                    sdlEngineComboField.suspendEvents();
                    sdlEngineComboField.setSelection(sdlstore.getAt(0));
                    sdlEngineComboField.resumeEvents();
                }
            }
        })

        //clean the selected value in the sdl combo
        sdlEngineComboField.suspendEvents();
        sdlEngineComboField.clearValue(null);
        sdlEngineComboField.resumeEvents();
        
        //if source is selected, create source filter
        if(sourceLang){
            filterData.push({
                property:'source',
                value:sourceLang.get('rfc5646')
            });
        }

        //if target is selected, create target filter
        if(targetLang){
            filterData.push({
                property:'target',
                value:targetLang.get('rfc5646')
            });
        }

        //apply the filters to the sdl language store
        if(filterData.length>0){
            sdlEngineComboStore.addFilter(filterData,true);
        }
    },

    /**
     * Is the current selected resource of sdl cloud type
     */
    isSdlResource:function(){
        var selection=this.getView().down('combo[name="resourceId"]').getSelection();
        return selection && selection.get('serviceName')==Editor.model.LanguageResources.Resource.SDL_RESOURCE_NAME;
    }

});