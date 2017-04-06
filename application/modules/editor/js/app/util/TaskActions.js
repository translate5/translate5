
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
 * @class Editor.util.TaskActions
 * contains reusable actions on the currently loaded task in the editor
 * 
 * provides methods to close and finish the task.
 * Ending a task is not provided, this must be done in taskOverview / task administration in general
 * 
 */
Ext.define('Editor.util.TaskActions', {
    strings: {
        confirmFinish: "#UT#Aufgabe abschließen?",
        confirmFinishMsg: "#UT#Wollen Sie die Aufgabe wirklich abschließen?",
        taskClosed: '#UT#Aufgabe wurde erfolgreich verlassen.',
        taskFinished: '#UT#Aufgabe wurde erfolgreich abgeschlossen.',
        taskClosing: '#UT#Aufgabe wird verlassen...',
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
        me.modifyTask(callback, 'open', me.strings.taskClosing);
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
        Ext.Msg.confirm(me.strings.confirmFinish, me.strings.confirmFinishMsg, function(btn){
            if(btn == 'yes') {
                me.modifyTask(callback, 'finished', me.strings.taskFinishing);
            }
        });
    },
    /**
     * internal method to modify the task with the given values
     */
    modifyTask: function(callback, state, maskingText) {
        var me = this,
            task = Editor.data.task,
            app = Editor.app;
        
        if(!task){
            Ext.raise('Can not modify task since no task opened!');
            return;
        }
        
        callback = callback || Ext.emptyFn;
        app.mask(maskingText, task.get('taskName'));
        task.set('userState',state);
        task.save({
            success: function(rec) {
                callback(task, app, me.strings);
            },
            failure: app.unmask
        });
    }
});