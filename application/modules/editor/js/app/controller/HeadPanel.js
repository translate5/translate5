
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
  views: ['HeadPanel','HelpWindow'],
  refs:[{
      ref : 'headPanel',
      selector : 'headPanel'
  },{
      ref: 'tasksMenu',
      selector: '#tasksMenu'
  }],
  listen: {
      controller: {
          '#Editor.$application': {
              editorViewportOpened: 'handleInitEditor',
              adminViewportOpened: 'handleInitAdmin'
          }
      },
      component: {
          '#languageSwitch' : {
              change: 'changeLocale'
          },
          '#logoutSingle' : {
              click: 'handleLogout'
          },
          '#tasksMenu' : {
              afterrender: 'handleTasksMenuRender'
          },
          '#tasksMenu menu' : {
              click: 'tasksMenuDispatcher'
          },
          '#segmentgrid #headPanelUp' : {
              click: 'headPanelToggle'
          },
          '#segmentgrid #headPanelDown' : {
              click: 'headPanelToggle'
          },
          '#mainHelpButton':{
        	click:'mainHelpButtonClick'  
          },
          '#helpWindow':{
        	  beforerender:'helpWindowBeforeRender'
          }
      }
          
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
      var hp = this.getHeadPanel(),
          data = {
              user: Editor.app.authenticatedUser.data,
              task: Editor.data.task.data,
              showTaskGuid: Editor.data.debug && Editor.data.debug.showTaskGuid,
              version: Editor.data.debug && Editor.data.debug.version + ' (ext '+Ext.getVersion().version+')',
              browser: Editor.data.debug && Ext.browser.identity,
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
          showTaskGuid: false,
          version: Editor.data.debug && Editor.data.debug.version + ' (ext '+Ext.getVersion().version+')',
          browser: Editor.data.debug && Ext.browser.identity,
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
      var me = this;
      if(!item) {
        return;
      }
      switch(item.itemId){
          case 'backBtn':
              Editor.util.TaskActions.close(function(task, app, strings){
                  me.fireEvent('taskUpdated', task);
                  app.openAdministration();
                  app.unmask();
                  Editor.MessageBox.addSuccess(strings.taskClosed);
              });
              break;
          case 'finishBtn':
              Editor.util.TaskActions.finish(function(task, app, strings){
                  me.fireEvent('taskUpdated', task);
                  app.openAdministration();
                  app.unmask();
                  Editor.MessageBox.addSuccess(strings.taskFinished);
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
      }
  },
  mainHelpButtonClick:function(){
	  var me=this;
      var win = Ext.widget('helpWindow');
      win.show();
  },
  helpWindowBeforeRender:function(win,event){
	  var url = Ext.String.format(Editor.data.helpUrl, Editor.data.helpSection);
	  Ext.Ajax.request({
          url:url,
              method: "POST",
              success: function(response){
            	Ext.getCmp('helpPreviewContainer').add(Ext.create('Ext.Panel', {
            		html: response.responseText
            	}));
              }, 
              failure: function(response){
            	  console.log("response failure");
              }
      });
  }
});