
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

Ext.define('Editor.view.project.ProjectPanelViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.projectPanel',

    strings:{
        noProjectMessage:'#UT#Das angeforderte Projekt existiert nicht',
        noProjectTaskMessage:'#UT#Die angeforderte Projektaufgabe existiert nicht',
        noProjectInFilter:'#UT#Projekt im aktuellen Filter nicht gefunden'
    },
    rootRoute:'#project',

    routes:{
        'project':'onProjectRoute',
        'project/:id/focus' :'onProjectFocusRoute',
        'project/:id/:taskId/focus' :'onProjectTaskFocusRoute'
    },

    listen:{
        messagebus: {
            '#translate5 task': {
                updateProgress: 'onUpdateProgress'
            }
        },
        component:{
            '#reloadProjectbtn':{
                click:'onReloadProjectBtnClick'
            }
        },
        controller: {
            '#admin.TaskOverview':{
                afterTaskDelete:'onAfterTaskDeleteEventHandler',
                beforeTaskDelete:'onBeforeTaskDeleteEventHandler'
            }
        },
        store: {
            '#project.Project':{
                load:'onProjectStoreLoad'
            }
        }
    },

    /***
     * Redirect to project focus route
     */
    redirectFocus:function(record, includeTask){
        if(!record){
            this.reset();
            return;
        }
        var me = this,
            isModel = record && record.isModel,
            id = isModel ? record.get('id') : record.id,
            projectId = isModel ? record.get('projectId') : record.projectId,
            action='focus',
            route=['project', projectId];

        if(includeTask){
            route.push(id);
        }

        route.push(action);
        route = route.join('/');

        Editor.app.openAdministrationSection(me.getView(),route);
    },

    /***
     * On project rute
     */
    onProjectRoute:function(){
        var me=this;
        //if it is not the default route, ignore it
        if(window.location.hash!=me.rootRoute){
            return;
        }
        me.checkAndReloadStores();
    },


    /***
     * On Project Focus rute
     */
    onProjectFocusRoute:function(id){
        var me=this;
        me.selectProjectRecord(id);
    },

    /***
     * On ProjectTask rute
     */
    onProjectTaskFocusRoute:function(id,taskId){
        var me=this;
        //focus the project record
        me.selectProjectRecord(id,taskId);
    },

    /***
     * Focus project task grid row. This is called after project task store is loaded.
     * The taskId is calculated based on the current window hash
     */
    focusProjectTask:function(store){
        var me=this,
            rute=window.location.hash,
            rute=rute.split('/'),
            isFocus=(rute.length==4 && rute[3]=='focus'),
            id=null,
            record=null;

        if(isFocus){
            id=parseInt(rute[2]);
            record=store.getById(parseInt(id));
        }
        if(!record){
            record=store.getAt(0);
        }
        me.selectProjectTaskRecord(record);
        me.lookup('projectGrid').setLoading(false);
    },

    /***
     * After current selected task import is finished, reload the selected task and with this all view model bindings will be triggered
     * @param params
     */
    onUpdateProgress: function(params) {
        if(params.progress !== 100){
            return;
        }
        var me = this,
            preferences = me.getView().down('adminTaskPreferencesWindow'),
            currentTask = preferences.getCurrentTask();

        if(!currentTask || currentTask.get('taskGuid')!== params.taskGuid){
            return;
        }

        currentTask.load({
            callback:function (){
                preferences.setCurrentTask(currentTask);
                me.getView().down('adminTaskUserAssocGrid').getStore().load();
            }
        });
    },

    /***
     * Before project task store load
     */
    onProjectTaskBeforeLoad:function(){
        this.lookup('projectGrid').setLoading(true);
    },

    /***
     * After project task store is loaded
     */
    onProjectTaskLoad:function(store){
        var me=this;
        //if the component is not visualy active, do not focus the project tasks.
        if(!me.getView().isVisible(true)){
            return;
        }
        me.focusProjectTask(store);
    },

    /***
     * On project store load
     */
    onProjectStoreLoad:function(store){
        //if the project panel is not active, ignore the redirect,
        //when we redirect, the component focus is changed
        if(!this.getView().isVisible(true)){
            return;
        }

        var me = this,
            record = me.getView().getViewModel().get('projectSelection'),
            task = null;
        //when the global task is set, this is a "leave task action"
        //set the route to this task
        if(Editor.data.task){
            me.redirectFocus(Editor.data.task,true);
            return;
        }

        //if selected record already exist, use it
        if(record){
            task = store.getById(record.get('id'));
        }
        //no selected record is found, use the first in the store
        if(!task){
            task = store.getAt(0);
        }
        me.redirectFocus(task,false);
    },

    onReloadProjectBtnClick:function(){
        var me=this;
        me.reloadProject();
    },

    /***
     * Before task delete event handler
     * Return true so the event call chain continues
     */
    onBeforeTaskDeleteEventHandler:function(task){
        var me=this,
            projectTaskGrid = me.lookup('projectTaskGrid');
        projectTaskGrid.getStore().remove(task);
        return true;
    },

    /***
     * After task remove event handelr
     */
    onAfterTaskDeleteEventHandler:function(task){
        var me = this,
            grid = me.lookup('projectGrid');
        this.checkAndReloadStores();
        grid && grid.store.load();
    },

    onProjectPanelDeactivate:function(){
        this.resetSelection();
    },

    /***
     * Reload projects
     */
    reloadProject:function(){
        var me = this,
            grid = me.lookup('projectGrid');

        me.resetSelection();

        grid.getController().reloadProjects().then(function(records) {}, function(operation) {
            Editor.app.getController('ServerException').handleException(operation.error.response);
        });
    },

    /***
     * Select project record in the projectGrid. This will also search for the record index if the record is not loaded in the buffered grid
     * After the index is found and project is selected, select the project task to (if requested)
     */
    selectProjectRecord:function(id,taskId){
        var me=this,
            grid=me.lookup('projectGrid'),
            record=null;

        //search for the task store record index
        me.searchIndex(id,grid).then(function(index){
            //do not scroll on empty store
            if(grid.getStore().getTotalCount()==0){
                Editor.MessageBox.addInfo(me.strings.noProjectInFilter);
                return;
            }
            grid.bufferedRenderer.scrollTo(index,{
                callback:function(){
                    //no db index is found
                    if(index===undefined || index<0){
                        Editor.MessageBox.addInfo(me.strings.noProjectMessage);
                        me.selectProjectTaskRecord(taskId);
                        return;
                    }

                    //reset the task frontend object after valid index is found
                    if(Editor.data.task){
                        Editor.data.task=null;
                    }

                    record=grid.getStore().getById(parseInt(id));
                    if(record){
                        me.focusRecordSilent(grid,record,'projectSelection');
                        me.selectProjectTaskRecord(taskId);
                        return;
                    }
                    grid.getController().reloadProjects().then(function(){
                        record=grid.getStore().getById(parseInt(id));
                        me.focusRecordSilent(grid,record,'projectSelection');
                        me.selectProjectTaskRecord(taskId);
                    });
                },
                notScrollCallback:function(){
                    //reset the task frontend object after no valid index is found
                    if(Editor.data.task){
                        Editor.data.task=null;
                    }
                    Editor.MessageBox.addInfo(me.strings.noProjectInFilter);
                    me.selectProjectTaskRecord(taskId);
                }
            });
        }, function(err) {
            //the exception is handled in the searchIndex
        });
    },

    /***
     * Select project task record in the projectTask grid
     */
    selectProjectTaskRecord:function(record){
        var me=this,
            grid=me.lookup('projectTaskGrid'),
            store=grid.getStore();

        //if the requested project is not a model
        if(Ext.isNumeric(record)){
            record=store.getById(record);
        }

        //focus and select the record
        me.focusRecordSilent(grid,record,'projectTaskSelection');

        if(!record){
            me.lookup('projectGrid').setLoading(false);
            return;
        }

        //update the location hash
        me.redirectFocus(record,true);
        me.lookup('projectGrid').setLoading(false);
    },

    /***
     * Search the index of the record id in the given grid view.
     * If the index does not exist in the store, the index will be loaded from the db
     */
    searchIndex:function(id,grid){
        var me=this,
            store=grid.getStore(),
            record=store.getById(parseInt(id)),
            row=record ? grid.getView().getRow(record) : null,
            index=row ? grid.getView().indexOf(row) : null,
            proxy = store.getProxy(),
            params = {};
        //the record exist in the grid view
        if(index!=null){
            return new Ext.Promise(function (resolve, reject) {
                resolve(index);
            });
        }
        //the grid does not exist in the grid, get the index from the db
        params[proxy.getFilterParam()] = proxy.encodeFilters(store.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(store.getSorters().items);
        params['projectsOnly'] = true;
        return new Ext.Promise(function (resolve, reject) {
            Ext.Ajax.request({
                url: Editor.data.restpath+'task/'+id+'/position',
                method: 'GET',
                params: params,
                scope: me,
                success: function(response){
                    var responseData = Ext.JSON.decode(response.responseText);
                    if(!responseData){
                        resolve(-1)
                        return;
                    }
                    resolve(responseData.index);
                },
                failure: function(response){
                    Editor.app.getController('ServerException').handleException(response);
                    reject("Error on search index request.");
                }
            });
        });
    },

    /***
     * Check if the project tasks store is empty, if yes full reload is required (projects + project tasks).
     * If the project tasks store is not empty, just refresh the data.
     */
    checkAndReloadStores:function(){
        var me=this,
            grid = me.lookup('projectTaskGrid'),
            store = grid.getStore();
        if(store.getCount()==0){
            me.reloadProject();
            return;
        }
        store.load();
    },

    /***
     * Focus and select grid record without firing the selectionchange event.
     * This will also update the viw model variable name with the record
     */
    focusRecordSilent:function(grid,record,name){
        var me=this;
        grid.suspendEvent('selectionchange');
        me.getViewModel().set(name,record);
        grid.setSelection(record);
        record && grid.getView().focusRow(record);
        grid.resumeEvent('selectionchange');
    },

    /***
     * Set the default route
     */
    resetRoute:function(){
        var me=this;
        Editor.app.openAdministrationSection(me.getView(),me.rootRoute);
        me.redirectTo(me.rootRoute);
    },

    /***
     * Reset view model selections
     */
    resetSelection:function(){
        var me=this,
            projectTaskGrid = me.lookup('projectTaskGrid'),
            projectGrid = me.lookup('projectGrid');

        projectTaskGrid.getStore().removeAll(true);
        projectTaskGrid.view.refresh();
        //reset the vm selection properties
        me.focusRecordSilent(projectGrid,null,'projectSelection');
        me.focusRecordSilent(projectTaskGrid,null,'projectTaskSelection');
    },

    /***
     * Reset the task/project selection and set the default route
     */
    reset:function(){
        this.resetSelection();
        this.resetRoute();
    }
});