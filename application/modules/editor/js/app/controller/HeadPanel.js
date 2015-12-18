
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
      taskEnded: '#UT#Aufgabe wurde erfolgreich beendet.',
      taskClosing: '#UT#Aufgabe wird verlassen...',
      taskFinishing: '#UT#Aufgabe wird abgeschlossen und verlassen...',
      taskEnding: '#UT#Aufgabe wird beendet und verlassen...'
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
      
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('editorViewportOpened', me.handleInitEditor, me);
      Editor.app.on('adminViewportOpened', me.handleInitAdmin, me);
      
      me.control({
          '#languageSwitch' : {
              change: me.changeLocale
          },
          '#logoutSingle' : {
              click: me.handleLogout
          },
          '#tasksMenu' : {
              afterrender: me.handleTasksMenuRender
          },
          '#tasksMenu menu' : {
              click: me.tasksMenuDispatcher
          },
          '#segmentgrid #headPanelUp' : {
              click: me.headPanelToggle
          },
          '#segmentgrid #headPanelDown' : {
              click: me.headPanelToggle
          }
      });
  },
  //***********************************************************************************
  //Begin Events
  //***********************************************************************************
  /**
   * @event taskUpdated
   * @param {Editor.model.admin.Task} task
   * Fires after a task has successfully updated / saved
   */
  //***********************************************************************************
  //End Events
  //***********************************************************************************
  headPanelToggle: function(tool) {
      this.getHeadPanel().setVisible(tool.type == 'down');
      if(tool.itemId == 'headPanelUp') {
          tool.ownerCt.down('#headPanelDown').show();
      }
      else {
          tool.ownerCt.down('#headPanelUp').show();
      }
      tool.hide();
  },
  /**
   * shows the sub components needed by the editor (hide others)
   */
  handleInitEditor: function() {
      //FIXME ext6 disable this method because of to much errors
      return;
      var hp = this.getHeadPanel(),
          data = {
              user: Editor.app.authenticatedUser.data,
              task: Editor.data.task.data,
              showTaskGuid: Editor.data.debug && Editor.data.debug.showTaskGuid,
              taskLabel: 'FIXME #UT# texts', //FIXME ext6 hp.strings.task,
              userLabel: 'FIXME #UT# texts', //FIXME ext6 hp.strings.loggedinAs,
              loginLabel: 'FIXME #UT# texts', //FIXME ext6 hp.strings.loginName,
              readonlyLabel: 'FIXME #UT# texts', //FIXME ext6 hp.strings.readonly,
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
          showTaskGuid: false,
          taskLabel: hp.strings.task,
          userLabel: hp.strings.loggedinAs,
          loginLabel: hp.strings.loginName
      });
      hp.down('#tasksMenu').hide();
  },
  handleLogout: function() {
      Editor.app.logout();
  },
  changeLocale: function(combo, locale) {
      Editor.app.setTranslation(locale);
  },
  tasksMenuDispatcher: function(menu, item) {
      var me = this,
          task = Editor.data.task,
          app = Editor.app;
      if(!item) {
        return;
      }
      switch(item.itemId){
          case 'backBtn':
              app.mask(me.strings.taskClosing, task.get('taskName'));
              task.set('userState','open');
              task.save({
                  success: function(rec) {
                      me.fireEvent('taskUpdated', rec);
                      Editor.app.openAdministration();
                      app.unmask();
                      Editor.MessageBox.addSuccess(me.strings.taskClosed);
                  },
                  failure: app.unmask
              });
              break;
          case 'finishBtn':
              if(! Editor.app.authenticatedUser.isAllowed('editorFinishTask')){
                  break;
              }
              Ext.Msg.confirm(me.strings.confirmFinish, me.strings.confirmFinishMsg, function(btn){
                  if(btn == 'yes') {
                      app.mask(me.strings.taskFinishing, task.get('taskName'));
                      task.set('userState','finished');
                      task.save({
                          success: function(rec) {
                              me.fireEvent('taskUpdated', rec);
                              Editor.app.openAdministration();
                              app.unmask();
                              Editor.MessageBox.addSuccess(me.strings.taskFinished);
                          },
                          failure: app.unmask
                      });
                  }
              });
              break;
          case 'closeBtn':
              if(! Editor.app.authenticatedUser.isAllowed('editorEndTask')){
                  break;
              }
              Ext.Msg.confirm(me.strings.confirmEnd, me.strings.confirmEndMsg, function(btn){
                  if(btn == 'yes') {
                      app.mask(me.strings.taskEnding, task.get('taskName'));
                      task.set('userState',task.get('userState')); //triggers userState as dirty
                      task.set('state','end');
                      task.save({
                          success: function(rec) {
                              me.fireEvent('taskUpdated', rec);
                              Editor.app.openAdministration();
                              app.unmask();
                              Editor.MessageBox.addSuccess(me.strings.taskEnded);
                          },
                          failure: app.unmask
                      });
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
          //since closing a task from within a opened task triggers version errors, 
          //we disable this unused simply disable this unused feature
          //menu.down('#closeBtn').setVisible(user.isAllowed('editorEndTask', task));
      }
  }
});