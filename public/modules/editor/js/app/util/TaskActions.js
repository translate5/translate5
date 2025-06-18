
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
 * @class Editor.util.TaskActions
 * contains reusable actions on the currently loaded task in the editor
 * 
 * provides methods to close and finish the task.
 * Ending a task is not provided, this must be done in taskOverview / task administration in general
 * 
 */
Ext.define('Editor.util.TaskActions', {
    strings: {
        taskOpening: '#UT#Aufgabe wird im Editor geöffnet...',
        forcedReadOnly: '#UT#Aufgabe wird durch Benutzer "{0}" bearbeitet und ist daher schreibgeschützt!',
        confirmFinish: "#UT#Noch etwas zu tun?",
        confirmFinishMsg: "#UT#Möchten Sie später weitermachen?",
        taskClosed: '#UT#Aufgabe wurde erfolgreich verlassen.',
        taskConfirmed: '#UT#Aufgabe wurde bestätigt und zum Bearbeiten freigegeben.',
        taskFinished: '#UT#Aufgabe wurde erfolgreich abgeschlossen.',
        taskClosing: '#UT#Aufgabe wird verlassen...',
        taskCancelImport: '#UT#Import wird abgebrochen...',
        taskConfirming: '#UT#Aufgabe wird bestätigt...',
        taskFinishing: '#UT#Aufgabe wird abgeschlossen und verlassen...',
        saveSegmentFirst: '#UT#Die gewünschte Aktion kann nicht durchgeführt werden! Das aktuell geöffnete Segment muss zunächst gespeichert bzw. geschlossen werden.',
    },
    statics: {
        /**
         * Closes the task and calls the given callback
         * @param {Function} callback
         */
        close: function(callback) {
            (new this()).close(callback);
        },
        
        /**
         * Finishes the task and calls the given callback, parameters are:
         * {Editor.model.admin.Task} task
         * {Ext.controller.Application} app
         * {Object} strings Object containing default translations
         * 
         * @param {Function} callback
         */
        finish: function(callback) {
            (new this()).finish(callback);
        },
        
        /**
         * Confirms the task (if unconfirmed) and calls the given callback only if a confirmation was done, parameters are:
         * {Editor.model.admin.Task} task
         * {Ext.controller.Application} app
         * {Object} strings Object containing default translations
         * 
         * @param {Function} callback
         */
        confirm: function(callback) {
            (new this()).confirm(callback);
        },
        
        /**
         * Opens the given task for editing or viewing (readonly = true)
         * @param {Editor.model.admin.Task} task
         * @param {boolean} readonly
         */
        openTask: function(task, readonly) {
            (new this()).openTask(task, readonly);
        },

        /**
         * 
         * @param {Editor.model.admin.Task} task
         * @param {boolean} readonly
         */
        openTaskRequest: function(task, readonly) {
            (new this()).openTaskRequest(task, readonly);
        },

        /**
         * Opens the given task for editing or viewing (readonly = true)
         * @param {Editor.model.admin.Task} task
         */
        cancelImport: function(task) {
            var me = new this();
            me.modifyTask(
                function(){
                    Editor.app.unmask();
                },
                {
                    state: 'error'
                },
                me.strings.taskCancelImport,
                task,
                false
            );
        },
        
        /**
         * Returns true if the currently opened task is exportable
         * (currently checks only if a segment save is still in progress)
         * @return {boolean}
         */
        isTaskExportable: function() {
            var ctrl = Editor.app.getController('Segments');
            return !ctrl.saveChainMutex && !ctrl.saveIsRunning;
        },
        
        /**
         * QUIRK: this API should better be in a global task Controller but unfortunately there is none
         * Starts an operation of the given type & updates the task-state in all available stores
         * @param {string} operationType see editor_Task_Operation::XXX
         * @param {int} taskId
         * @param {object} params
         */
        operation: function(operationType, taskId, params){
            //'editor/:entity/:id/operation/:operation'
            const tasks = Editor.util.TaskActions.findTaskProjectAndState(taskId),
                baseUrl = (Editor.data.restpath.indexOf('/task/') === -1) ?
                    Editor.data.restpath : Editor.data.restpath.split('/task/')[0] + '/';
            // Before the analysis is started, set the task state to 'autoqa' in the task/project stores
            // the matchanalysis and languageresourcesassoc panel loading masks are binded to the task status.
            // Changing the status to autoqa will automaticly apply the loading masks for those panels
            Editor.util.TaskActions.setTasksState(tasks, operationType);
            
            Ext.Ajax.request({
                url: baseUrl + 'task/' + taskId + '/' + operationType + '/operation',
                method: "PUT",
                params: params,
                scope: this,
                failure: function(response){
                    // on failure, we have to reset the task-state
                    Editor.util.TaskActions.unsetTasksState(tasks, operationType);
                    Editor.app.getController('ServerException').handleException(response);
                }
            });
        },

        /**
         * Retrieves a task, it's project and it's state by task-id
         * All of these props may be null if the task is not in the grid's store
         * @param {int} taskId
         * @returns {{task: Editor.model.admin.Task, project: Editor.model.admin.Task, initialState: string}}
         */
        findTaskProjectAndState: function(taskId) {
            let taskStore = Ext.StoreManager.get('tasksStore'),
                adminTask = (taskStore && taskId) ? taskStore.getById(taskId) : null,
                projectTaskGridQuery = Ext.ComponentQuery.query('#projectTaskGrid'),
                projectTaskGrid = (projectTaskGridQuery && projectTaskGridQuery.length > 0) ?
                    projectTaskGridQuery[0] : null,
                projectsTask = (projectTaskGrid && projectTaskGrid.getStore()) ?
                    projectTaskGrid.getStore().getById(taskId) : null,
                taskInitialState = (adminTask || projectsTask) ?
                    (adminTask ? adminTask.get('state') : projectsTask.get('state')) : null;
            return {
                task: projectsTask,
                project: adminTask,
                initialState: taskInitialState
            };
        },
        /**
         * sets the given tasks to the passed state.
         * Param tasksObj is expected to be the return val of ::findTaskProjectAndState
         * @param {{task: Editor.model.admin.Task, project: Editor.model.admin.Task, initialState: string}} tasksObj
         * @param {string} newState
         */
        setTasksState: function (tasksObj, newState) {
            if(tasksObj.project && tasksObj.initialState !== newState){
                tasksObj.project.set('state', newState);
            }
            if(tasksObj.task && tasksObj.initialState !== newState){
                tasksObj.task.set('state', newState);
            }
        },
        /**
         * unsets the given tasks back to the initial state.
         * Param tasksObj is expected to be the return val of ::findTaskProjectAndState
         * @param {{task: Editor.model.admin.Task, project: Editor.model.admin.Task, initialState: string}} tasksObj
         * @param {string} newState
         */
        unsetTasksState: function (tasksObj, newState) {
            if(tasksObj.task && tasksObj.initialState !== newState){
                tasksObj.task.set('state', tasksObj.initialState);
            }
            if(tasksObj.project && tasksObj.initialState !== newState){
                tasksObj.project.set('state', tasksObj.initialState);
            }
        }
    },
    
    /**
     * if a segment is opened for editing, show a warning and return true
     * @return {Boolean}
     */
    isEditing: function() {
        if(Editor.app.getController('Editor').isEditing) {
            Editor.MessageBox.addWarning(this.strings.saveSegmentFirst);
            return true;
        }
        return false;
    },
    
    /**
     * closes the current task
     * @param {Function} callback
     */
    close: function(callback) {
        var me = this;
        if(me.isEditing()) {
            return;
        }
        me.modifyTask(
            callback,
            {
                userState: 'open'
            },
            me.strings.taskClosing,
            Editor.data.task,
            true
        );
    },
    
    /**
     * finishes the current task
     * @param {Function} callback
     */
    finish: function(callback) {
        var me = this;
        if(me.isEditing()) {
            return;
        }
        if(! Editor.app.authenticatedUser.isAllowed('editorFinishTask')){
            return;
        }

        //do not show the confirmation window if it is configured so
        if(Editor.app.getTaskConfig('editor.showConfirmFinishTaskPopup') !== true){
            me._doFinish(callback);
            return;
        }

        Ext.Msg.confirm(me.strings.confirmFinish, me.strings.confirmFinishMsg, function(btnId){
            if(btnId === 'no') {
                me._doFinish(callback);
            }
        });
    },
    
    /**
     * reusable function which performs the real finish call (without confirmation)
     * @param {Function} callback
     */
    _doFinish: function(callback) {
        var me = this;
        me.modifyTask(
            callback,
            {
                userState: 'finished'
            },
            me.strings.taskFinishing,
            Editor.data.task,
            true
        );
    },
    
    /**
     * confirms the current task
     * @param {Function} callback
     */
    confirm: function(callback) {
        var me = this,
            initialState = Editor.model.admin.Task.userStates.EDIT, //confirm request should go to edit mode if possible
            innerCallback = function(task, app, strings, op){
                //call given callback
                callback(task, app, strings);
                //call additional callback for confirmation
                me.onOpenTask(task, initialState, op);
            };
            
        if(me.isEditing()) {
            return;
        }
        me.modifyTask(
            innerCallback,
            {
                state: 'open', //confirms the task on task level (if task state was unconfirmed)
                userState: initialState
            },
            me.strings.taskConfirming,
            Editor.data.task,
            false
        );
    },
    
    /**
     * internal method to modify the task with the given values
     * @param {Function} callback
     * @param {Object} data
     * @param {string} maskingText
     * @param {Editor.model.admin.Task} task
     * @param {boolean} doFireLeaveEvent
     */
    modifyTask: function(callback, data, maskingText, task, doFireLeaveEvent) {
        var me = this,
            app = Editor.app;
        if(! task){
            Ext.raise('Can not modify task since no task opened!');
            return;
        }        
        if(doFireLeaveEvent){
            Editor.app.fireEvent('beforeLeaveTask');
        }
        callback = callback || Ext.emptyFn;
        app.mask(maskingText, task.getTaskName());
        task.set(data);
        task.save({
            success: function(rec, op) {
                callback(task, app, me.strings, op);

                if(doFireLeaveEvent){
                    Editor.app.fireEvent('afterTaskModifiedBeforeLeave');
                }
            },
            callback: function(rec, op) {
                if (op.hasOwnProperty('error') &&
                    op.error.hasOwnProperty('response') &&
                    op.error.response.responseJson.errorCode === 'E1163'
                ) {
                    app.openAdministration(null);
                    app.unmask();
                }

                Editor.MessageBox.addByOperation(op);
            },
            failure: app.unmask
        });
    },
    
    /**
     * 
     * @param {Editor.model.admin.Task} task
     * @param {boolean} readonly
     */
    openTask: function(task, readonly) {
        var me = this,
            initialState,
            app = Editor.app;

        initialState = me.getInitialState(task, readonly);
        task.set('userState', initialState);
        app.mask(me.strings.taskOpening, task.getTaskName());
        task.save({
            success: function(rec, op) {
                me.onOpenTask(rec, initialState, op);
            },
            failure: app.unmask
        });
    },
    
    /**
     *
     * @param {Editor.model.admin.Task} task
     * @param {boolean} readonly
     */
    openTaskRequest: function(task, readonly) {
        var me = this,
            initialState,
            app = Editor.app;

        initialState = me.getInitialState(task, readonly);
        task.set('userState', initialState);
        task.save({
            success: function() {},
            failure: app.unmask
        });
    },

    /**
     * calculates the initial userState for a task for open requests
     * @param {Editor.model.admin.Task} task
     * @param {Boolean} readonly
     */
    getInitialState: function(task, readonly) {
        readonly = (readonly === true || task.isReadOnly(false));
        return readonly ? Editor.model.admin.Task.userStates.VIEW : Editor.model.admin.Task.userStates.EDIT;
    },
    
    /**
     * Generic handler to be called on success handlers of task open calls
     * @param {Editor.model.admin.Task} task
     * @param {String} initialState
     * @param {Ext.data.operation.Update} operation
     */
    onOpenTask: function(task, initialState, operation) {
        var me = this,
            app = Editor.app,
            confirmed = !task.isUnconfirmed();
        if(task &&
            initialState === Editor.model.admin.Task.userStates.EDIT &&
            task.get('userState') === Editor.model.admin.Task.userStates.VIEW &&
            confirmed
        ) {
            Editor.MessageBox.addInfo(Ext.String.format(me.strings.forcedReadOnly, task.get('lockingUsername')));
        }
        app.openEditor(task, operation);
    },
});
