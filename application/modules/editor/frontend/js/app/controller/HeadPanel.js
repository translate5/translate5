
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
  helpBtn: '#UT#Hilfe',
  requires:[
  	'Editor.view.ApplicationInfoPanel'
  ],
  refs:[{
      ref : 'headPanel',
      selector : 'headPanel'
  },{
      ref: 'tasksMenu',
      selector: '#tasksMenu'
  },{
      ref: 'headToolBar',
      selector: 'headPanel toolbar#top-menu'
  },{
      ref: 'northPanelEditor',
      selector: '#editorViewport headPanel[region="north"]'
  },{
	  ref:'applicationInfoPanel',
	  selector:'#applicationInfoPanel'
  },{
	  ref:'mainHelpButton',
	  selector:'#mainHelpButton'
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
              click:'headPanelToggle'
          },
          '#segmentgrid #headPanelDown' : {
              click:'headPanelToggle'
          },
          '#mainHelpButton':{
        	click:'mainHelpButtonClick'  
          },
          'headPanel toolbar#top-menu':{
        	  beforerender:'headPanelToolbarBeforeRender',
        	  add: {
                  fn: function(toolbar){
                      if(toolbar.rendered){
                          toolbar.updateLayout();
                          //just for slow rendering browsers we do this again after one second
                          setTimeout(toolbar.updateLayout, 1500);
                      }
                  },
                  delay: 700
              }
          }
      },
      store:{
    	  '#UserConfig':{
    		  load:'onUserConfigLoad'
    	  }  
      },
      global:{
    	  applicationViewChanged:'onApplicationViewChanged'
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
      //head panel may be disabled in editor view
      if(!this.getHeadPanel()) {
          return;
      }
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
      //head panel may be disabled in editor view
      if(!this.getHeadPanel()) {
          return;
      }
      var hp = this.getHeadPanel();
      hp.down('#applicationInfoPanel').update(this.getApplicationInfoPanel().getEditorTplData());
      hp.down('#tasksMenu').show();
  },
  /**
   * shows the sub components needed by the task overview (hide others)
   */
  handleInitAdmin: function() {
      var hp = this.getHeadPanel();
      hp.down('#applicationInfoPanel').update(this.getApplicationInfoPanel().getAdminTplData());
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
	  this.showHelpWindow();
  },
  
  /***
   * Show the help window 
   */
  showHelpWindow:function(){
	  var me=this,
		  win = Ext.widget('helpWindow');
	  win.setTitle(me.helpBtn + ' - ' + Editor.data.helpSectionTitle)
	  win.show();
  },
  
  /***
   * On component view change event handler. This event is a global event.
   */
  onApplicationViewChanged:function(name,title){
	  //TODO:refactor this when the menu is implemented
	  Editor.data.helpSection = name;
	  Editor.data.helpSectionTitle = title;
	  
	  var me=this,
	  	isHelpButtonVisible=me.isHelpButtonVisible(),
	  	helpButton=me.getMainHelpButton();
	  
	  //the button is not visible when there is not url defined for the section
	  helpButton.setHidden(!isHelpButtonVisible);
	  
	  if(!isHelpButtonVisible){
		  return;
	  }
	  //the help button exist, call the show help window function
	  this.showUserStateHelpWindow();
  },
  
  /***
   * On user config load
   */
  onUserConfigLoad:function(){
	  //after config load, check if the helpwindow should be displayed
	  this.showUserStateHelpWindow();
  },
  
  /***
   * Show the help window only it is alowed for the curent userstate
   */
  showUserStateHelpWindow:function(){
	  var provider=Ext.state.Manager.getProvider(),
	      helpWindowState=provider.get(Editor.view.HelpWindow.getStateIdStatic());
	  //is the doNotShowAgain userstate enabled
	  if(!helpWindowState || helpWindowState.doNotShowAgain){
		  return;
	  }
	  
	  this.showHelpWindow();
  },
  
  headPanelToolbarBeforeRender:function(toolbar){
      var me=this;
      toolbar.insert(2, {
    	  xtype:'button',
    	  itemId:'mainHelpButton',
    	  text:me.helpBtn,
    	  hidden:true
       });
  },
  /**
   * The help button is visible when for the helpwindow there is loaderUrl configured
   */
  isHelpButtonVisible:function(){
	  var provider=Ext.state.Manager.getProvider(),
	      state=provider.get(Editor.view.HelpWindow.getStateIdStatic());
	  return state && state.loaderUrl!=="";
  }
  
});