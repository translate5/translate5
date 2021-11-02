
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
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
        taskConfirming: '#UT#Aufgabe wird bestätigt...',
        taskFinishing: '#UT#Aufgabe wird abgeschlossen und verlassen...',
        saveSegmentFirst: '#UT#Die gewünschte Aktion kann nicht durchgeführt werden! Das aktuell geöffnete Segment muss zunächst gespeichert bzw. geschlossen werden.',
    },
    statics: {
        /**
         * Closes the task and calls the given callback
         */
        close: function(callback) {
            (new this()).close(callback);
            return;
        },
        /**
         * Finishes the task and calls the given callback, parameters are:
         * {Editor.models.admin.Task} task
         * {Ext.controller.Application} app
         * {Object} strings Object containing default translations
         */
        finish: function(callback) {
            (new this()).finish(callback);
            return;
        },
        /**
         * Confirms the task (if unconfirmed) and calls the given callback only if a confirmation was done, parameters are:
         * {Editor.models.admin.Task} task
         * {Ext.controller.Application} app
         * {Object} strings Object containing default translations
         */
        confirm: function(callback) {
            (new this()).confirm(callback);
            return;
        },
        /**
         * Opens the given task for editing or viewing (readonly = true)
         * @param {Editor.models.admin.Task} task
         * @param {boolean} readonly
         */
        openTask: function(task, readonly) {
            (new this()).openTask(task, readonly);
            return;
        },
        /**
         * Returns true if the currently opened task is exportable
         * (currently checks only if a segment save is still in progress)
         * @return {Boolean}
         */
        isTaskExportable: function() {
            var ctrl = Editor.app.getController('Segments');
            return !ctrl.saveChainMutex && !ctrl.saveIsRunning;
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
     */
    close: function(callback) {
        var me = this;
        if(me.isEditing()) {
            return;
        }
        me.modifyTask(callback, {
            userState: 'open'
        }, me.strings.taskClosing);
    },
    /**
     * finishes the current task
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
        if(Editor.app.getTaskConfig('editor.showConfirmFinishTaskPopup')!==true){
            me._doFinish(callback);
            return;
        }

        Ext.Msg.confirm(me.strings.confirmFinish, me.strings.confirmFinishMsg, function(btn){
            if(btn == 'no') {
                me._doFinish(callback);
            }
        });
    },
    /**
     * reusable function which performs the real finish call (without confirmation)
     */
    _doFinish: function(callback) {
        var me = this;
        me.modifyTask(callback, {
            userState: 'finished'
        }, me.strings.taskFinishing);        
    },
    /**
     * confirms the current task
     */
    confirm: function(callback) {
        var me = this,
            initialState = Editor.data.task.USER_STATE_EDIT, //confirm request should go to edit mode if possible
            innerCallback = function(task, app, strings){
                //call given callback
                callback(task, app, strings);
                //call additional callback for confirmation
                me.onOpenTask(task, initialState);
            };
            
        if(me.isEditing()) {
            return;
        }
        me.modifyTask(innerCallback, {
            state: 'open',          //confirms the task on task level (if task state was unconfirmed)
            userState: initialState //
        }, me.strings.taskConfirming);
    },
    /**
     * internal method to modify the task with the given values
     */
    modifyTask: function(callback, data, maskingText) {
        var me = this,
            task = Editor.data.task,
            app = Editor.app;
        
        if(!task){
            Ext.raise('Can not modify task since no task opened!');
            return;
        }
        
        callback = callback || Ext.emptyFn;
        app.mask(maskingText, task.get('taskName'));
        task.set(data);
        task.save({
            success: function(rec) {
                callback(task, app, me.strings);
            },
            callback: function(rec, op) {
                Editor.MessageBox.addByOperation(op);
            },
            failure: app.unmask
        });
    },
    openTask: function(task, readonly) {
        var me = this,
            initialState,
            app = Editor.app;
        
        initialState = me.getInitialState(task, readonly);
        task.set('userState', initialState);
        app.mask(me.strings.taskOpening, task.get('taskName'));
        task.save({
            success: function(rec, op) {
                me.onOpenTask(rec, initialState);
            },
            failure: app.unmask
        });
    },
    /**
     * calculates the initial userState for a task for open requests
     * @param {Editor.models.Task} task
     * @param {Boolean} readonly
     */
    getInitialState: function(task, readonly) {
        readonly = (readonly === true || task.isReadOnly());
        return readonly ? task.USER_STATE_VIEW : task.USER_STATE_EDIT;
    },
    /**
     * Generic handler to be called on success handlers of task open calls
     * @param {Editor.models.Task} task
     * @param {String} initialState
     */
    onOpenTask: function(task, initialState) {
        var me = this,
            app = Editor.app,
            confirmed = !task.isUnconfirmed();
        if(task && initialState == task.USER_STATE_EDIT && task.get('userState') == task.USER_STATE_VIEW && confirmed) {
            Editor.MessageBox.addInfo(Ext.String.format(me.strings.forcedReadOnly, task.get('lockingUsername')));
        }
        app.openEditor(task);
    }
});
