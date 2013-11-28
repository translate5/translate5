/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Die Einstellungen werden in einem Cookie gespeichert
 * @class Editor.controller.HeadPanel
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.HeadPanel', {
  extend : 'Ext.app.Controller',
  views: ['HeadPanel'],
  strings: {
      confirmFinish: "#UT#Aufgabe abschließen?",
      confirmFinishMsg: "#UT#Wollen Sie die Aufgabe wirklich abschließen?",
      confirmEnd: "#UT#Aufgabe beenden?",
      confirmEndMsg: "#UT#Wollen Sie die Aufgabe wirklich beenden?",
      taskClosed: '#UT#Aufgabe wurde erfolgreich verlassen.',
      taskFinished: '#UT#Aufgabe wurde erfolgreich abgeschlossen.',
      taskEnded: '#UT#Aufgabe wurde erfolgreich beendet.'
  },
  refs:[{
      ref : 'headPanel',
      selector : 'headPanel'
  },{
      ref: 'tasksMenu',
      selector: '#tasksMenu'
  }],
  init : function() {
      var me = this;
      
      this.addEvents(
              /**
               * @event taskUpdated
               * @param {Editor.model.admin.Task} task
               * Fires after a task has successfully updated / saved
               */
              'taskUpdated'
      );
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('editorViewportOpened', me.handleInitEditor, me);
      Editor.app.on('adminViewportOpened', me.handleInitAdmin, me);
      
      me.control({
          '#logoutSingle' : {
              click: me.handleLogout
          },
          '#tasksMenu' : {
              afterrender: me.handleTasksMenuRender
          },
          '#tasksMenu menu' : {
              click: me.tasksMenuDispatcher
          }
      });
  },
  /**
   * shows the sub components needed by the editor (hide others)
   */
  handleInitEditor: function() {
      var hp = this.getHeadPanel(),
          data = {
              user: Editor.app.authenticatedUser.data,
              task: Editor.data.task.data,
              taskLabel: hp.strings.task,
              userLabel: hp.strings.loggedinAs,
              loginLabel: hp.strings.loginName,
              readonlyLabel: hp.strings.readonly,
              isReadonly: Editor.data.task.isReadOnly()
          };
      hp.down('#infoPanel').update(data);
      hp.down('#tasksMenu').show();
  },
  /**
   * shows the sub components needed by the task overview (hide others)
   */
  handleInitAdmin: function() {
      var hp = this.getHeadPanel();
      hp.down('#infoPanel').update({
          user: Editor.app.authenticatedUser.data,
          task: null,
          taskLabel: hp.strings.task,
          userLabel: hp.strings.loggedinAs,
          loginLabel: hp.strings.loginName
      });
      hp.down('#tasksMenu').hide();
  },
  handleLogout: function() {
      Editor.app.logout();
  },
  tasksMenuDispatcher: function(menu, item) {
      var me = this,
          task = Editor.data.task; 
      switch(item.itemId){
          case 'backBtn':
              task.set('userState','open');
              task.save({
                  success: function(rec) {
                      me.fireEvent('taskUpdated', rec);
                      Editor.MessageBox.addSuccess(me.strings.taskClosed);
                  }
              });
              Editor.app.openAdministration();
              break;
          case 'finishBtn':
              if(! Editor.app.authenticatedUser.isAllowed('editorFinishTask')){
                  break;
              }
              Ext.Msg.confirm(me.strings.confirmFinish, me.strings.confirmFinishMsg, function(btn){
                  if(btn == 'yes') {
                      task.set('userState','finished');
                      task.save({
                          success: function(rec) {
                              me.fireEvent('taskUpdated', rec);
                              Editor.MessageBox.addSuccess(me.strings.taskFinished);
                          }
                      });
                      Editor.app.openAdministration();
                  }
              });
              break;
          case 'closeBtn':
              if(! Editor.app.authenticatedUser.isAllowed('editorEndTask')){
                  break;
              }
              Ext.Msg.confirm(me.strings.confirmEnd, me.strings.confirmEndMsg, function(btn){
                  if(btn == 'yes') {
                      task.set('userState',task.get('userState')); //triggers userState as dirty
                      task.set('state','end');
                      task.save({
                          success: function(rec) {
                              me.fireEvent('taskUpdated', rec);
                              Editor.MessageBox.addSuccess(me.strings.taskEnded);
                          }
                      });
                      Editor.app.openAdministration();
                  }
              });
              break;
      }
  },
  /**
   * initializes the visibility of the logout menu buttons
   */
  handleTasksMenuRender: function() {
      var menu = this.getTasksMenu().menu,
          user = Editor.app.authenticatedUser,
          task = Editor.data.task;

      if(task) {
          menu.down('#finishBtn').setVisible(user.isAllowed('editorFinishTask', task));
          menu.down('#closeBtn').setVisible(user.isAllowed('editorEndTask', task));
      }
  }
});