
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

/**
 * @class Editor.view.LanguageResources.TaskAssocPanelViewModel
 * @extends Ext.app.ViewModel
 */
Ext.define('Editor.view.LanguageResources.TaskAssocPanelViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.languageResourceTaskAssocPanel',
    data: {
        items: null
    },
    stores: {
        taskAssoc: {
            model:'Editor.model.LanguageResources.TaskAssoc',
            storeId:'languageResourcesTaskAssoc',
            remoteFilter: true,
            pageSize: false,
            autoLoad:true,
            setFilters:function(filters){
                //the binding is also triggered when the value is empty. Ignore the filtering with empty value
                if(filters && !filters.value){
                    this.loadData([],false);
                    return;
                }
                this.superclass.superclass.setFilters.apply(this, [filters]);
            },
            filters:{
                property: 'taskGuid',
                operator:"eq",
                value:'{currentTask.taskGuid}'
            }
        }
    },
    formulas:{
        /***
         * Is the assoc panel loading active. It is active when the currentTask state is analysis
         */
        isLoadingActive:{
            get: function(task) {
                if(!task){
                    return false;
                }
                return task.isAnalysis();
            },
            bind:{bindTo:'{currentTask}',deep:true}
        },
        /***
         * Is the task assoc panel/grid enabled. Add languageresource assoc only when the task is unconfirmed or open
         */
        enablePanel:{
            get: function (task) {
                return task && (task.isUnconfirmed() || task.isOpen());
            },
            bind:{bindTo:'{currentTask}',deep:true}
        },
        /***
         * Task assoc panel bottom toolbar enable/disable formula. The panel contains the analysis/pretranslation checkboxes and button
         */
        enableDockedToolbar:{
            get: function (task) {
                var me=this,
                    isAddTask=me.getView().up('window')!==undefined;
                if(!isAddTask){
                    return task && (!task.isErroneous() && !task.isImporting());
                }
                return isAddTask;
            },
            bind:{bindTo:'{currentTask}',deep:true}
        },
        /***
         * Is the start analysis button hidden. The formula must be here since the button is part of the taskassoc panel
         */
        isAnalysisButtonHidden:{
            get: function (task) {
                var me=this,
                    isAddTask=me.getView().up('window')!==undefined;
                if(!isAddTask){
                    return task && task.isImporting();
                }
                return isAddTask
            },
            bind:{bindTo:'{currentTask}',deep:true}
        },
        
        /**
         * Are there assigned language resources
         */
        hasLanguageResourcesAssoc:function(get){
            var items=get('items');
            if(!items){
                return false;
            }
            for(var i=0;i<items.length;i++){
                if(items[i].get('checked')){
                    return true;
                }
            }
            return false;
        },
        hasTmOrCollection:function(get){
            return this.checkResourceType(get('items'),Editor.util.LanguageResources.resourceType.TM)||this.checkResourceType(get('items'),Editor.util.LanguageResources.resourceType.TERM_COLLECTION) ;
        },
        hasMt:function(get){
            return this.checkResourceType(get('items'),Editor.util.LanguageResources.resourceType.MT);
        },
        hasTermcollection:function(get){
            return this.checkResourceType(get('items'),Editor.util.LanguageResources.resourceType.TERM_COLLECTION);
        }
    },
    
    /***
     * Check if the given resource type is selected in the language resources panel
     */
    checkResourceType:function(items,resourceType){
        var hasType=false;
        if(!items){
            return hasType;
        }
        for(var i=0;i<items.length;i++){
            if(items[i].get('checked') && items[i].get('resourceType')==resourceType){
                hasType=true;
                break;
            }
        }
        return hasType;
    }
});