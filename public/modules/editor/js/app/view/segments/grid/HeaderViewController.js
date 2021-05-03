
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

Ext.define('Editor.view.segments.grid.HeaderViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.segmentsHeader',

    listen: {
        component: {
            '#leaveTaskHeaderBtn':{
                click:'onLeaveTaskHeaderBtn'
            },
            '#closeHeaderBtn':{
                click:'onCloseBtn'
            }
        }
    },
    /**
     * Called when the leave task is clicked. This button will provide a dialog, so the user can decide between leave the task or finish the task
     */
    onLeaveTaskHeaderBtn:function(){
        var me = this,
            user = Editor.app.authenticatedUser,
            task = Editor.data.task,
            str = me.getView().strings;

        //without any loaded task or if the HeadPanel controller does not exist we can not leave the task
        if(!task){
            return;
        }
        
        //check if the user is allowed to finish the task
        if(!user.isAllowed('editorFinishTask', task)){
            me.handleLeaveTaskButton("backBtn");
            return;
        }
        
        var mbox = Ext.create('Ext.window.MessageBox',{
            //this is the only way to set custom button in extjs 6.2 in messagebox dialog
            makeButton: function(btnIdx) {
                var btnId = this.buttonIds[btnIdx],
                    cls='';
                if(btnId=='no'){
                    cls='ico-arrow-back';
                }
                
                if(btnId=='yes'){
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
            },
        });
        
        mbox.show({
            title: str.leaveTaskWindowTitle,
            msg: str.leaveTaskWindowMessage,
            buttons: Ext.Msg.YESNO,
            fn:me.handleLeaveTaskButton,
            scope:me,
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
    handleLeaveTaskButton:function(button){
        if(button == "cancel"){
            return false
        }
        
        if(button == "yes"){
            Editor.util.TaskActions.finish(function(task, app, strings){
                app.openAdministration(task);
                app.unmask();
                Editor.MessageBox.addSuccess(strings.taskFinished);
            });
        }
        else {
            Editor.util.TaskActions.close(function(task, app, strings){
                app.openAdministration(task);
                app.unmask();
                Editor.MessageBox.addSuccess(strings.taskClosed);
            });
        }
    },
    onCloseBtn: function() {
        Editor.util.TaskActions.close(function(task, app, strings){
            if(window.opener === null) {
                app.viewport.destroy();
                app.unmask();
                Ext.getBody().setCls('loading');
                Ext.getBody().update('<div class="loading"></div>');
                app.showInlineError('But the application window can not be closed automatically!', 'Application left successfully');
            }
            else {
                window.close();
            }
        });
    }
});
