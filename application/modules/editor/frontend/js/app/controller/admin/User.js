
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

/**
 * Editor.controller.admin.User encapsulates the User Administration functionality
 * @class Editor.controller.admin.User
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.User', {
  extend : 'Ext.app.Controller',
  models: ['admin.User'],
  stores: ['admin.Users'],
  views: ['admin.UserGrid', 'admin.UserAddWindow'],
  refs : [{
      ref: 'headToolBar',
      selector: 'headPanel toolbar#top-menu'
  },{
      ref: 'logoutButton',
      selector: 'headPanel toolbar#top-menu #logoutSingle'
  },{
      ref: 'centerRegion',
      selector: 'viewport container[region="center"]'
  },{
      ref: 'UserForm',
      selector: '#adminUserAddWindow form'
  },{
      ref: 'UserWindow',
      selector: '#adminUserAddWindow'
  },{
      ref: 'userGrid',
      selector: 'adminUserGrid'
  }],
  strings: {
      confirmDeleteTitle: '#UT#Benutzer endgültig löschen?',
      confirmDeleteMsg: '#UT#Soll der gewählte Benutzer "{0}" wirklich endgültig gelöscht werden?',
      confirmResetPwTitle: '#UT#Passwort zurücksetzen?',
      confirmResetPwMsg: '#UT#Soll das Passwort des Benutzers "{0}" wirklick zurückgesetzt werden?<br /> Der Benutzer wird per E-Mail benachrichtigt, dass er ein neues Passwort anfordern muss.',
      userSaved: '#UT#Der Änderungen an Benutzer "{0}" wurden erfolgreich gespeichert.',
      openUserAdminBtn: "#UT#Benutzerverwaltung",
      userAdded: '#UT#Der Benutzer "{0}" wurde erfolgreich erstellt.'
  },
  //***********************************************************************************
  //Begin Events
  //***********************************************************************************
  /**
   * @event userCreated
   * @param {Ext.form.Panel} form
   * Fires after a user has successfully created
   */
  //***********************************************************************************
  //End Events
  //***********************************************************************************
  init : function() {
      var me = this;
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('adminViewportClosed', me.clearUsers, me);
      Editor.app.on('editorViewportOpened', me.handleInitEditor, me);
      
      me.control({
          'headPanel toolbar#top-menu' : {
              beforerender: me.initMainMenu
          },
          'button#user-admin-btn': {
              click: me.openUserGrid
          },
          '#adminUserGrid #reload-user-btn': {
              click: me.handleUserReload
          },
          '#adminUserGrid #add-user-btn': {
              click: me.handleUserAddShow
          },
          '#adminUserAddWindow #save-user-btn': {
              click: me.handleUserSave
          },
          '#adminUserAddWindow #cancel-user-btn': {
              click: me.handleUserCancel
          },
          '#adminUserGrid': {
              hide: me.handleAfterHide,
              show: me.handleAfterShow,
              celldblclick: me.handleUserEdit 
          },
          '#adminUserGrid actioncolumn': {
              click: me.userActionDispatcher
          }
      });
  },
  /**
   * injects the user menu into the main menu
   */
  initMainMenu: function() {
      var toolbar = this.getHeadToolBar(),
          insertIdx = 1,
          logout = this.getLogoutButton(),
          grid = this.getUserGrid(),
          headPanel=Editor.app.getController('HeadPanel');
      if(logout) {
          insertIdx = toolbar.items.indexOf(logout) + 1;
      }
      //is the help button visible for the current section
      if(headPanel && headPanel.isHelpButtonVisible()){
    	  insertIdx=insertIdx+1;
      }
      toolbar.insert(insertIdx, {
          itemId: 'user-admin-btn',
          xtype: 'button',
          hidden: grid && grid.isVisible(),
          text: this.strings.openUserAdminBtn
      });
  },
  /**
   * handle after show of usergrid
   */
  handleAfterShow: function(grid) {
      this.getHeadToolBar() && this.getHeadToolBar().down('#user-admin-btn').hide();
      //fire the global event for component view change
      //TODO: refactor so that event is only fired once in a application view load function which should be created when rebuilding the main menu
      Ext.fireEvent('applicationViewChanged','useroverview',grid.getTitle());
  },
  /**
   * handle after hide of usergrid
   */
  handleAfterHide: function() {
      this.getHeadToolBar() && this.getHeadToolBar().down('#user-admin-btn').show();
  },
  /**
   * opens the task grid, hides all other
   */
  openUserGrid: function() {
      var me = this, 
          grid = me.getUserGrid();
      
      me.getCenterRegion().items.each(function(item){
          item.hide();
      });
      
      if(grid) {
          grid.show();
      }
      else {
          grid = me.getCenterRegion().add({
              xtype: 'adminUserGrid'
          });
          me.handleAfterShow(grid);
      }
  },
  handleInitEditor: function() {
      this.getHeadToolBar() && this.getHeadToolBar().down('#user-admin-btn').hide();
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
  clearUsers: function() {
      this.getAdminUsersStore().removeAll();
  },
  handleUserCancel: function() {
      this.getUserForm().getForm().reset();
      this.getUserWindow().close();
  },
  /**
   * is called after clicking save user
   */
  handleUserSave: function() {
      var me = this,
          form = me.getUserForm(),
          basic = form.getForm(),
          win = me.getUserWindow(),
          rec = form.getRecord();
      if(!basic.isValid()) {
          return;
      }

      //if in first save attempt we got an error from server, 
      //and we then disable the password in the second save, 
      //the password will be kept in the model, so reject it here
      rec.reject();
      basic.updateRecord(rec);
      win.setLoading(true);
      rec.save({
          //prevent default ServerException handling
          preventDefaultHandler: true,
          failure: function(rec, op) {
              win.setLoading(false);
              var errorHandler = Editor.app.getController('ServerException');
              errorHandler.handleFormFailure(basic, rec, op);
          },
          success: function() {
              var user = rec.get('surName')+', '+rec.get('firstName')+' ('+rec.get('login')+')',
                  msg = win.editMode ? me.strings.userSaved : me.strings.userAdded;
              win.setLoading(false);
              win.close();
              me.getAdminUsersStore().load();
              Editor.MessageBox.addSuccess(Ext.String.format(msg, user));
          }
      });
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
   * shows the form to add a user
   */
  handleUserAddShow: function() {
      if(!this.isAllowed('editorAddUser')){
          return;
      }
      var win = Ext.widget('adminUserAddWindow');
      win.show();
      win.loadRecord(this.getNewUser());
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
      this.getAdminUsersStore().load();
  },
});