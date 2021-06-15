
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

Ext.define('Editor.view.admin.task.UserAssocGridWindowController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminTaskUserAssocGrid',
    listen: {
        component: {
            '#notifyAssociatedUsersBtn': {
                click: 'onNotifyAssociatedUsersBtnClick'
            }
        }
    },
    
    onNotifyAssociatedUsersBtnClick: function() {
        var view = this.getView(),
            vm = view.lookupViewModel();
        Ext.Msg.show({
            title: view.strings.notifyUsersTitle,
            message: view.strings.notifyUsersMsg,
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            scope: this,
            fn: this.notifyUsers(vm.get('currentTask'), view.strings)
        });
    },
    
    /***
     * Confirm window button click handler
     */
    notifyUsers: function(task, strings) {
        var me = this;
        return function(btn) {
            if (btn !== 'yes') {
                return;
            }
            
            Ext.Ajax.request({
                url: Editor.data.restpath+'task/'+task.get('id')+'/workflow',
                method: 'POST',
                params: {
                    trigger:'notifyAllUsersAboutTaskAssociation'
                },
                scope: me,
                success: function(response){
                    var responseData = Ext.JSON.decode(response.responseText);
                    if(!responseData){
                        return;
                    }
                    Editor.MessageBox.addSuccess(strings.userNotifySuccess);
                },
                failure: function(response){
                    Editor.app.getController('ServerException').handleException(response);
                }
            });
        };
    }
});