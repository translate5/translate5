
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

Ext.define('Editor.view.admin.user.GridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminUserGrid',
    strings: {
        confirmDeleteTitle: '#UT#Benutzer endgültig löschen?',
        confirmDeleteMsg: '#UT#Soll der gewählte Benutzer "{0}" wirklich endgültig gelöscht werden?',
        confirmResetPwTitle: '#UT#Passwort zurücksetzen?',
        confirmResetPwMsg: '#UT#Soll das Passwort des Benutzers "{0}" wirklick zurückgesetzt werden?<br /> Der Benutzer wird per E-Mail benachrichtigt, dass er ein neues Passwort anfordern muss.'
    },
    listen: {
        component: {
            '#adminUserGrid #reload-user-btn': {
                click: 'handleUserReload'
            },
            '#adminUserGrid #add-user-btn': {
                click: 'handleUserAddShow'
            },
            '#adminUserGrid': {
                celldblclick: 'handleUserEdit',
                beforedestroy: 'clearUsers'
            },
            '#adminUserGrid actioncolumn': {
                click: 'userActionDispatcher'
            }
        }
    },
    
    routes: {
        'user': 'onUserRoute'
    },
    onUserRoute: function() {
        Editor.app.openAdministrationSection(this.getView(), 'user');
    },
    
    /**
     * shows the form to add a user
     */
    handleUserAddShow: function() {
        if(!this.isAllowed('editorAddUser')){
            return;
        }
        var win = Ext.widget('adminUserAddWindow'),
            newUser = this.getNewUser();
        newUser.store = this.getView().store;
        win.show();
        win.loadRecord(newUser);
    },
    /**
     * creates a new User Record
     * @returns {Editor.model.admin.User}
     */
    getNewUser: function() {
        return Ext.create('Editor.model.admin.User',{
            surName: '',
            locale:Editor.data.locale,
            firstName: '',
            email: '',
            login: '',
            gender: 'n',
            roles: 'editor'
        });
    },

    /**
     * Handles the different user action on the action column
     * @param {Ext.grid.View} view
     * @param {DOMElement} cell
     * @param {Integer} row
     * @param {Integer} col
     * @param {Ext.Event} ev
     * @param {Object} evObj
     */
    userActionDispatcher: function(view, cell, row, col, ev, evObj) {
        var me = this,
            store = view.getStore(),
            user = store.getAt(row),
            t = ev.getTarget(),
            msg = me.strings,
            info,
            taskStore = Ext.StoreMgr.get('admin.Tasks'),
            f = t.className.match(/ico-user-([^ ]+)/);
        
        switch(f && f[1] || '') {
            case 'edit':
                me.handleUserEdit(view,cell,col,user);
                break;
            case 'delete':
                if(!me.isAllowed('editorDeleteUser')) {
                    return;
                }
                info = Ext.String.format(msg.confirmDeleteMsg,user.get('firstName')+' '+user.get('surName'));
                Ext.Msg.confirm(msg.confirmDeleteTitle, info, function(btn){
                    if(btn == 'yes') {
                        user.dropped = true;
                        user.save({
                            failure: function() {
                                user.reject();
                            },
                            success: function() {
                                taskStore && taskStore.load();
                                store.remove(user);
                            }
                        });
                    }
                });
                break;
            case 'reset-pw':
                if(!me.isAllowed('editorResetPwUser')) {
                    return;
                }
                info = Ext.String.format(msg.confirmResetPwMsg,user.get('firstName')+' '+user.get('surName'));
                Ext.Msg.confirm(msg.confirmResetPwTitle, info, function(btn){
                    if(btn == 'yes') {
                        var oldPw = user.get('passwd');
                        //convert false needed since setting the passwd to null is not recognized as modifying call of the record, 
                        //  since null is converted internally to empty string (and the value is already an empty string!)
                        user.set('passwd',null, {convert: false});
                        user.save({
                            callback: function(rec) {
                                //reset the store passwd to the old value, so that further reset calls will set and save null again
                                rec.set('passwd',oldPw);
                                rec.commit();
                            }
                        });
                    }
                });
                break;
        }
    },
    /**
     * shows the form to edit a user
     */
    handleUserEdit: function(view, cell, cellIdx, rec){
        if(!this.isAllowed('editorEditUser')){
            return;
        }
        var win = Ext.widget('adminUserAddWindow',{editMode: true}),
            noEdit = ! rec.get('editable');
          win.down('form').setDisabled(noEdit);
          win.down('#save-user-btn').setDisabled(noEdit);
          win.show();
          //load the record after the window is rendered
          win.loadRecord(rec);
    },
    /**
     * Method Shortcut for convenience
     * @param {String} right
     * @return {Boolean}
     */
    isAllowed: function(right) {
        return Editor.app.authenticatedUser.isAllowed(right);
    },
    /**
     * reloads the User Grid, will also be called from other controllers
     */
    handleUserReload: function () {
        this.getView().store.load();
    },
    clearUsers: function() {
        this.getView().store.removeAll();
    }
});
