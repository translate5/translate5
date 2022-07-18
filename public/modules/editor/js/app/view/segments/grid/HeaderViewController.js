
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

Ext.define('Editor.view.segments.grid.HeaderViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.segmentsHeader',

    listen: {
        component: {
            '#leaveTaskHeaderBtn':{
                click:'onLeaveTaskHeaderBtn'
            },
            '#closeHeaderBtn':{
                click:'onLeaveAppHeaderBtn'
            }
        }
    },

    /**
     * Called when the leave task is clicked. This button will provide a dialog,
     * so the user can decide between just leave the task or additionally finish the task
     */
    onLeaveTaskHeaderBtn: function() {
        var me = this;

        // Show dialog
        me.leaveDialog('handleLeaveTaskButton', me.getView().strings.leaveTaskWindowTitle);
    },

    /**
     * Called when the leave application is clicked. This button will provide a dialog,
     * so the user can decide between just leave the application or additionally finish the task
     */
    onLeaveAppHeaderBtn: function() {
        var me = this, handlerName = 'handleLeaveAppButton';

        // If there should be no dialog shown on attempt to leave the app - just leave the app
        if (!Editor.data.editor.toolbar.askFinishOnClose) {
            me[handlerName]("backBtn");
            return;
        }

        // Show dialog
        me.leaveDialog(handlerName, me.getView().strings.closeBtn);
    },

    /**
     * Show leave task/app dialog
     *
     * @param handlerName
     * @param dialogTitle
     */
    leaveDialog: function(handlerName, dialogTitle) {
        var me = this,
            str = me.getView().strings,
            task = Editor.data.task,
            user = Editor.app.authenticatedUser;

        // Without any loaded task or if the HeadPanel controller does not exist we can not leave the task
        if (!task) {
            return;
        }

        // Check if the user is allowed to finish the task
        if (!user.isAllowed('editorFinishTask', task)) {
            me[handlerName]("backBtn");
            return;
        }

        // Create dialog
        var mbox = Ext.create('Ext.window.MessageBox', {

            // This is the only way to set custom button in extjs 6.2 in messagebox dialog
            makeButton: function(btnIdx) {
                var btnId = this.buttonIds[btnIdx],
                    cls='';
                if (btnId==='no') {
                    cls='ico-arrow-back';
                }

                if (btnId==='yes') {
                    cls='ico-finish-task';
                }
                return new Ext.button.Button({
                    handler: this.btnCallback,
                    itemId: btnId,
                    scope: this,
                    text: this.buttonText[btnId],
                    minWidth: 75,
                    iconCls:cls
                });
            }
        });

        // Show it
        mbox.show({
            title: dialogTitle,
            msg: str.leaveTaskWindowMessage,
            buttons: Ext.Msg.YESNO,
            fn: me[handlerName],
            scope: me,
            defaultFocus:'no',
            icon: Ext.MessageBox.QUESTION,
            buttonText: {
                yes: str.leaveTaskWindowFinishBtn,
                no: str.leaveTaskWindowCancelBtn
            }
        });
    },

    /**
     * Handler for the leave task dialog window.
     */
    handleLeaveTaskButton: function (button) {
        if (button === "cancel") {
            return false;
        }
        
        if (button === "yes") {
            Editor.util.TaskActions.finish(function (task, app, strings) {
                app.openAdministration(task);
                app.unmask();
                Editor.MessageBox.addSuccess(strings.taskFinished);
            });
        }
        else {
            Editor.util.TaskActions.close(function (task, app, strings) {
                app.openAdministration(task);
                app.unmask();
                Editor.MessageBox.addSuccess(strings.taskClosed);
            });
        }
    },

    /**
     * Handler for the leave application dialog window.
     */
    handleLeaveAppButton: function(button) {
        if (button === "cancel") {
            return false;
        }

        if (button === "yes") {
            Editor.util.TaskActions.finish(function(task, app, strings) {
                app.closeWindow();
                Editor.MessageBox.addSuccess(strings.taskFinished);
            });
        }
        else {
            Editor.util.TaskActions.close(function(task, app) {
                app.closeWindow();
            });
        }
    },
});
