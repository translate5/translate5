
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
 * @class Editor.plugins.ChangeLog.controller.Changelog
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.FrontEndMessageBus.controller.MessageBus', {
    extend: 'Ext.app.Controller',
    strings: {
        taskCloseTitle: '#UT#Aufgabe in anderem Fenster geschlossen',
        taskCloseMsg: '#UT#Sie haben die Aufgabe in anderem Fenster geschlossen. Schließen Sie dieses Fenster ebenfalls, oder laden Sie das Fenster neu um die Aufgabe erneut zu öffnen.',
        taskCloseClose: '#UT#Schließen',
        taskCloseReload: '#UT#Erneut öffnen'
    },
    listen: {
        messagebus: {
            '#translate5 instance': {
                notifyUser: 'onUserNotification'
            }
        },
        component: {
            '#segmentgrid' : {
                render: 'onSegmentGridRender'
            }
        }
    },
    onSegmentGridRender: function(){
        return; //FIXME prepare that socket server is only triggered for simultaneous usage, for beta testing we enable socket server just for each task 
        var bus = Editor.app.getController('Editor.plugins.FrontEndMessageBus.controller.MultiUserUsage');
        if(Editor.data.task.get('usageMode') === Editor.model.admin.Task.USAGE_MODE_SIMULTANEOUS){
            bus.activate();
        }
        else {
            bus.deactivate();
        }
    },
    /**
     * This notifications can be send from the server.
     */
    onUserNotification: function(data) {
        var str = this.strings;
        switch(data.message) {
            // We have to ask the user what to do:
            case 'taskClosedInOtherWindow':          
                if(!Editor.data.task || !Editor.data.task.isModel || Editor.data.task.get('id') !== data.taskId) {
                    //notify the user only if the currently opened task was closed
                    return;
                }

                Ext.Msg.show({
                    title: str.taskCloseTitle,
                    message: str.taskCloseMsg,
                    buttons: Ext.Msg.YESNO,
                    icon: Ext.Msg.QUESTION,
                    closable: false,
                    buttonText: {
                        yes: str.taskCloseClose,
                        no: str.taskCloseReload
                    },
                    fn: function (btn) {
                        //yes -> try to close the window
                        //no  -> reopen the task
                        if (btn === 'yes') {
                            Editor.app.closeWindow();
                        } else {
                            //reload to reopen the task!
                            Editor.data.logoutOnWindowClose = false;
                            window.location.reload();
                        }
                    }
                });
                break;

            // Currently we just trigger a reload, instead showing a message. Should be fine in that situations
            case 'sessionDeleted':
                //instead of showing a message, we just trigger a reload of the window (without logout in this special case)
                Editor.data.logoutOnWindowClose = false;
                window.location.reload();
                break;

            //possibility to notify the users via messagebus
            default:
                Editor.MessageBox.addInfo(data.message);
                break;
        }
    }
});