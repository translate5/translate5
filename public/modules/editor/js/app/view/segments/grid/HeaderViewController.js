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
     * Check if there are any pending AJAX requests
     * @returns {boolean} true if there are pending requests
     */
    hasPendingAjaxRequests: function() {
        return Ext.Ajax.isLoading();
    },

    /**
     * Get count and details of pending AJAX requests
     * @returns {Object} Information about pending requests
     */
    getPendingRequestsInfo: function() {
        var activeRequests = Ext.Ajax.requests;
        return {
            count: Object.keys(activeRequests).length,
            hasAny: Ext.Ajax.isLoading(),
            requests: activeRequests
        };
    },

    /**
     * Show a message about pending requests and optionally wait for completion
     */
    showPendingRequestsMessage: function(callback, scope) {
        var me = this,
            ajaxStr = Editor.data.l10n.segmentGrid.header,
            pendingInfo = me.getPendingRequestsInfo();

        // Create a message with option to wait or force leave
        var mbox = Ext.create('Ext.window.MessageBox', {
            makeButton: function(btnIdx) {
                var btnId = this.buttonIds[btnIdx],
                    cls = '';
                if (btnId === 'cancel') {
                    cls = 'fa fa-times';
                } else if (btnId === 'yes') {
                    cls = 'fa fa-clock-o';
                } else if (btnId === 'no') {
                    cls = 'fa fa-exclamation-triangle';
                }
                return new Ext.button.Button({
                    handler: this.btnCallback,
                    itemId: btnId,
                    scope: this,
                    text: this.buttonText[btnId],
                    minWidth: 75,
                    iconCls: cls
                });
            }
        });

        mbox.show({
            title: ajaxStr.pendingRequestsTitle,
            msg: Ext.String.format(ajaxStr.pendingRequestsMessage, pendingInfo.count),
            buttons: Ext.Msg.YESNOCANCEL,
            fn: function(button) {
                if (button === 'cancel') {
                    return;
                } else if (button === 'yes') {
                    me.waitForPendingRequests(callback, scope);
                } else if (button === 'no') {
                    // force leave - execute callback immediately
                    callback.call(scope || me);
                }
            },
            scope: me,
            defaultFocus: 'yes',
            icon: Ext.MessageBox.WARNING,
            buttonText: {
                yes: ajaxStr.waitForCompletionBtn,
                no: ajaxStr.forceLeaveBtn,
                cancel: ajaxStr.cancelBtn
            }
        });
    },

    /**
     * Wait for all pending AJAX requests to complete, then execute callback
     */
    waitForPendingRequests: function(callback, scope) {
        var me = this,
            ajaxStr = Editor.data.l10n.segmentGrid.header,
            checkInterval = 500,
            maxWaitTime = 30000,
            startTime = new Date().getTime(),
            progressMsg;

        progressMsg = Ext.MessageBox.show({
            title: ajaxStr.pleaseWaitTitle,
            msg: ajaxStr.waitingMessage,
            progressText: 'Initializing...',
            width: 300,
            progress: true,
            closable: false
        });

        var checkPending = function() {
            var currentTime = new Date().getTime(),
                elapsed = currentTime - startTime,
                progress = Math.min(elapsed / maxWaitTime, 1),
                pendingInfo = me.getPendingRequestsInfo();

            progressMsg.updateProgress(
                progress,
                Ext.String.format(ajaxStr.requestsRemainingProgress, pendingInfo.count, Math.round(elapsed/1000)),
                ''
            );

            if (!me.hasPendingAjaxRequests()) {
                progressMsg.hide();
                callback.call(scope || me);
            } else if (elapsed > maxWaitTime) {
                // timeout
                progressMsg.hide();
                Ext.MessageBox.show({
                    title: ajaxStr.timeoutTitle,
                    msg: ajaxStr.timeoutMessage,
                    buttons: Ext.MessageBox.YESNO,
                    fn: function(btn) {
                        if (btn === 'yes') {
                            callback.call(scope || me);
                        }
                    },
                    icon: Ext.MessageBox.WARNING,
                    buttonText: {
                        yes: ajaxStr.leaveAnywayBtn,
                        no: ajaxStr.stayBtn
                    }
                });
            } else {
                // check again
                Ext.defer(checkPending, checkInterval);
            }
        };

        checkPending();
    },

    /**
     * Called when the leave task is clicked. This button will provide a dialog,
     * so the user can decide between just leave the task or additionally finish the task
     */
    onLeaveTaskHeaderBtn: function() {
        var me = this;

        // Check for pending AJAX requests first
        if (me.hasPendingAjaxRequests()) {
            me.showPendingRequestsMessage(function() {
                me.leaveDialog('handleLeaveTaskButton', me.getView().strings.leaveTaskWindowTitle);
            }, me);
            return;
        }

        // Show dialog
        me.leaveDialog('handleLeaveTaskButton', me.getView().strings.leaveTaskWindowTitle);
    },

    /**
     * Called when the leave application is clicked. This button will provide a dialog,
     * so the user can decide between just leave the application or additionally finish the task
     */
    onLeaveAppHeaderBtn: function() {
        var me = this,
            handlerName = 'handleLeaveAppButton';

        // Check for pending AJAX requests first
        if (me.hasPendingAjaxRequests()) {
            me.showPendingRequestsMessage(function() {
                me.proceedWithLeaveApp(handlerName);
            }, me);
            return;
        }

        me.proceedWithLeaveApp(handlerName);
    },

    /**
     * Proceed with leave app logic (extracted to avoid duplication)
     */
    proceedWithLeaveApp: function(handlerName) {
        var me = this;

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
            user = Editor.app.authenticatedUser,
            qualityFilter = Editor.app.viewport.down('qualityFilterPanel').getStore().first();

        // Without any loaded task or if the HeadPanel controller does not exist we can not leave the task
        if (!task) {
            return;
        }

        // Check if the user is allowed to finish the task
        if (!user.isAllowed('editorFinishTask', task) || (qualityFilter && qualityFilter.hasCriticalErrorsInChildren())) {
            me[handlerName]("backBtn");
            return;
        }

        // give plugins the chance to intercept...
        if (! this.getView().fireEvent('beforeLeaveTaskPrompt', task)) {
            return false;
        }

        // Create dialog
        var mbox = Ext.create('Ext.window.MessageBox', {

            // This is the only way to set custom button in extjs 6.2 in messagebox dialog
            makeButton: function(btnIdx) {
                var btnId = this.buttonIds[btnIdx],
                    cls='';
                if (btnId==='no') {
                    cls='fa fa-arrow-left';
                } else if (btnId==='yes') {
                    cls='fa fa-check-circle';
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
            // give plugins the chance to intercept ...
            if (! this.getView().fireEvent('finishTask', Editor.data.task)) {
                return false;
            }
            Editor.util.TaskActions.finish(function (task, app, strings) {
                app.openAdministration(task);
                app.unmask();
                Editor.MessageBox.addSuccess(strings.taskFinished);
            });
        } else {
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
    }
});