
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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
Ext.Loader.setConfig({
  enabled: true
});
Ext.data.Connection.disableCaching = false;
Ext.data.JsonP.disableCaching = false;
Ext.data.proxy.Server.prototype.noCache = false;
Ext.Ajax.disableCaching = false;

Ext.ariaWarn = Ext.emptyFn; 

Ext.override(Ext.data.Connection, {
    timeout: 60000
});
Ext.Ajax.timeout = 60000;
Ext.override(Ext.data.proxy.Ajax, { timeout: 60000 });
Ext.override(Ext.form.action.Action, { timeout: 60 });

Ext.Loader.setPath('Editor.controller.Localizer', Editor.data.basePath+'/editor/js/app-localized.js');
Ext.Loader.setPath('Editor.plugins', Editor.data.pluginFolder);

Editor.DATE_ISO_FORMAT = 'Y-m-d H:i:s';
Editor.DATEONLY_ISO_FORMAT = 'Y-m-d';

Ext.ClassManager.onCreated(function(className) {
    var boot = Ext.fly('loading-indicator-text');
    if(boot) {
        boot.update(className);
    }
    else {
        Ext.Logger.info("Lazy load of: "+className);
    }
});

Ext.application({
  name : 'Editor',
  models : [ 'File', 'Segment', 'admin.User', 'admin.Task', 'segment.Field' ],
  stores : [ 'Files', 'ReferenceFiles', 'Segments', 'AlikeSegments', 'admin.Languages'],
  requires: ['Editor.view.ViewPort', Editor.data.app.viewport, 'Editor.model.ModelOverride', 'Editor.util.TaskActions'],
  controllers: Editor.data.app.controllers,
  appFolder : Editor.data.appFolder,
  viewport: null,
    //***********************************************************************************
    //Begin Events
    //***********************************************************************************
    /**
     * @event editorViewportClosed
     * Fires after the editor viewport was destroyed and before the other viewport is created.
     */
    /**
     * @event adminViewportClosed
     * Fires after the admin viewport was destroyed and before the other viewport is created.
     */
    /**
     * @event editorViewportOpened
     * Fires after the editor viewport was opened by the app (nothing to do with ext rendered or show).
     */
    /**
     * @event adminViewportOpened
     * Fires after the admin viewport was opened by the app (nothing to do with ext rendered or show).
     */
    //***********************************************************************************
    //End Events
    //***********************************************************************************
  init: function() {
      //enable json in our REST interface
      Ext.Ajax.setDefaultHeaders({
          'Accept': 'application/json'
      });
      
      //init the plugins namespace
      Ext.ns('Editor.plugins');
      
      this.applyDefaultState();
      this.callParent(arguments);
      this.logoutOnWindowClose();
  },
  launch : function() {
      var me = this,
      viewSize = Ext.getBody().getViewSize();
    Ext.QuickTips.init();

    me.authenticatedUser = Ext.create('Editor.model.admin.User', Editor.data.app.user);
    if(!Ext.isIE8m) {
        me[Editor.data.app.initMethod]();
    }
    me.browserAdvice();
    Editor.MessageBox.showInitialMessages();
    
    me.enableJsLoggerVideo();
    
    //Logs the users userAgent and screen size for usability improvements:
    Ext.Ajax.request({
        url: Editor.data.pathToRunDir+'/editor/index/logbrowsertype',
        method: 'post',
        params: {
            appVersion: navigator.appVersion,
            userAgent: navigator.userAgent,
            browserName: navigator.appName,
            maxHeight: window.screen.availHeight,
            maxWidth: window.screen.availWidth,
            usedHeight: viewSize.height,
            usedWidth: viewSize.width
        }
    });
  },
  /**
   * If configured the user is logged out on window close
   */
  logoutOnWindowClose: function() {
      if(!Editor.data.logoutOnWindowClose) {
          return;
      }      
      var notRun = true,
          logout = function() {
              notRun = false;
              //use the hardcoded URL since we don't want to redirect to a custom logout page, 
              //but we just want to foirce a session destroy
              Ext.Ajax.request({
                  url: Editor.data.pathToRunDir+'/login/logout',
                  method: 'get',
                  async: false
              });
          };
      Ext.EventManager.on(window, 'beforeunload', function() {
          Editor.data.logoutOnWindowClose && notRun && logout();
      });
      Ext.EventManager.on(window, 'unload', function() {
          Editor.data.logoutOnWindowClose && notRun && logout();
      });
  },
  /**
   * opens the editor with the given Task
   * firing the adminViewportClosed event
   * @param {Editor.model.admin.Task} task
   */
  openEditor: function(task) {
      var me = this,
          languages = Ext.getStore('admin.Languages'),
          taskUserTrackingsStore,
          closeEvent;
      
      if(! (task instanceof Editor.model.admin.Task)) {
          me.openTaskDirect();
          return;
      }
      Editor.data.task = task;
      Editor.model.Segment.redefine(task.segmentFields());
      
      Editor.data.taskUserTracking = Ext.create('Editor.store.admin.TaskUserTrackings').load();

      Editor.data.taskLanguages = {
          source: languages.getById(task.get('sourceLang')),
          relais: languages.getById(task.get('relaisLang')),
          target: languages.getById(task.get('targetLang'))
      }
      
      if(me.viewport){
          //trigger closeEvent depending on which viewport was open
          closeEvent = me.viewport.isEditorViewport ? 'editorViewportClosed' : 'adminViewportClosed';
          me.viewport.destroy();
          me.fireEvent(closeEvent);
      }
      else {
          Ext.getBody().removeCls('loading');
          Ext.select("body > div.loading").destroy();
      }
      task.initWorkflow();
      me.viewport = Ext.create(Editor.data.app.viewport, {
          renderTo : Ext.getBody()
      });
      me.viewport.show();
      
      //vp.doLayout();
      //this.viewport.setLoading(true);
      /*
      - destroys all admin components, updates all editor stores. Inits the editor component if needed.
       */
      //enable logout split button
      //disable logout normal Button
      me.fireEvent('editorViewportOpened', me, task);
      me.getController('Fileorder').loadFileTree();//@todo bei ITL muss der load wiederum automatisch geschehen
  },
  /**
   * Used to open a task directly without administration panel
   */
  openTaskDirect: function(){
      var me = this;
      Editor.model.admin.Task.load(Editor.data.task.id, {
          success: function(task) {
              task.set('userState',Editor.data.app.initState);
              task.save({
                  scope: me,
                  success: me.openEditor
              });
          }
      });
  },
  /**
   * opens the admin viewport
   * firing the editorViewportClosed event
   */
  openAdministration: function() {
      var me = this,
          initial;
      if(!Editor.controller.admin || ! Editor.controller.admin.TaskOverview) {
          return;
      }
      if(me.viewport){
          me.viewport.destroy();
          me.fireEvent('editorViewportClosed');
      }
      else {
          Ext.getBody().removeCls('loading');
          Ext.select("body > div.loading").destroy();
      }
      me.viewport = Ext.create('Editor.view.ViewPort', {
          renderTo: Ext.getBody()
      });
      me.viewport.show();
      
      me.getController('admin.TaskOverview').loadTasks();
      
      /*
      - hides all editor components, inits all admin components, stores, etc.
      - empty editor stores
      */
      //disable logout split button
      //enable logout normal Button
      me.fireEvent('adminViewportOpened');
      
      //set the value used for displaying the help pages
      initial = me.viewport.down(me.viewport.getInitialView());
      initial.fireEvent('show', initial);
  },

  /**
   * Apply the state for each configured grid in the defaultState config variable
   */
  applyDefaultState:function(){
    if(!Editor.data.frontend || !Editor.data.frontend.defaultState){
      return;
    }
    var me=this,
        states=Ext.clone(Editor.data.frontend.defaultState),
        gridState={};

    //loop over all states(states for grids), and create config object which will be applied on the component 
    //when afterrender event is triggered
    for (var selector in states) {
        var configObjects = Ext.clone(states[selector]);    
        gridState[selector]={
            afterrender:{
                fn:me.defaultStateHandler,
                options:{
                    //set lowes priority
                    priority:-999,
                    args:{
                        selector:selector
                    }
                }   
            }
        };
    }
    me.control(gridState);
  },

  /***
   * State handler for the controll (controls and control configs are defined in the Editor.data.frontend.defaultState variable)
   */
  defaultStateHandler:function(component,eOpts){

        //get the config object for the current component from the configuration variable
        var configObject=this.getDefaultStateConfigObject(eOpts.options.args.selector);
        //apply the configuration object to the component
        component.applyState(configObject);
        //FIXME: Extjs bug, the header is still visible after apply state
        //https://www.sencha.com/forum/showthread.php?306941-Apply-state-after-grid-reconfigure
        // workaround: rehide hidden columns
        Ext.suspendLayouts();
        Ext.Array.forEach(component.getColumns(), function (column) {
            if (column.hidden) {
                column.show();
                column.hide();
            }
        });
        Ext.resumeLayouts(true);
  },

  /***
   * Return the configuration object from the defaultState config variable.
   * The returned value needs to be cloned because the values are modefyed by the framework 
   */
  getDefaultStateConfigObject:function(selector){
      return Ext.clone(Editor.data.frontend.defaultState[selector]);
  },
  
  /***
   * Output in the console the state
   * (see the example: http://confluence.translate5.net/display/CON/Column+configuration+for+task+overview+and+user+management ) 
   * for all rendered grids in the application
   */
  getGridState:function(){
      var grids=Ext.ComponentQuery.query('grid');
      if(grids.length<1){
          console.log("No grids were found (rendered in the application).")
          return;
      }
      var gridState=null,
          gridStateContainer=[];
      grids.forEach(function(grid) {
          gridState=grid.getState();
          gridStateContainer.push('"#'+grid.getItemId()+'":'+JSON.stringify(gridState))
      });
      
      console.log('{'+gridStateContainer.join()+'}');
  },
  
  mask: function(msg, title) {
      if(!this.appMask) {
          this.appMask = Ext.widget('messagebox');
      }
      this.appMask.wait(msg, title);
  },
  unmask: function() {
      //no "this" usage, so we can use this method directly as failure handler 
      Editor.app.appMask.close();
  },
  logout: function() {
      window.location = Editor.data.loginUrl;
  },
  /**
   * sets the locale / language to be used by the application. Restarts the application.
   * @param {String} lang
   */
  setTranslation: function(lang) {
      var formSpec = {
              tag: 'form',
              action: window.location.href,
              method: 'POST',
              target: '_self',
              style: 'display:none',
              cn: [{
                  tag: 'input',
                  type: 'hidden',
                  name: 'locale',
                  value: Ext.String.htmlEncode(lang)
              }]
          },
          // Create the form
          form = Ext.DomHelper.append(Ext.getBody(), formSpec);

      Editor.data.logoutOnWindowClose = false;
      form.submit();
  },
  browserAdvice: function() {
      var me = this,
          supportedBrowser = false;
      if(Ext.isIE8m) {
          Editor.MessageBox.addInfo(me.browserAdviceTextIE, -1);
          return;
      }
      if(Ext.isIE && Editor.data.browserAdvice) {
          Editor.MessageBox.addInfo(me.browserAdviceTextIE);
          return;
      }
      //Feature disabled
      if(!Editor.data.supportedBrowsers) {
          return;
      }
      Ext.Object.each(Editor.data.supportedBrowsers, function(idx, version) {
          if(Ext.browser.name == idx && Ext.browser.version.major >= version) {
              supportedBrowser = true;
              return false;
          }
      });
      if(!supportedBrowser) {
          Ext.MessageBox.alert(me.browserAdviceTextTitle, me.browserAdviceText);
      }
  },
  
  /**
   * If the video recording is activated in Zf_configuration, every user 
   * directly after log in (before loading of theRootCause) can activate it.
   */
  enableJsLoggerVideo: function() {
      if (Editor.data.enableJsLoggerVideoConfig) {
          // TODO: title and info-text
          var title = 'enableJsLoggerVideo',
              info = 'Help translate5\'s development with screen videos in case of errors...';
          Ext.Msg.confirm(title, info, function(btn){
              if(btn === 'yes') {
                  Editor.data.enableJsLoggerVideoUser = true;
              }
          });
      }
  },
  
  /***
   * Get all classes with which are using the mixin
   */
  getClassesByMixin:function(mixinName){
      var classes=[];
      Ext.iterate(Ext.ClassManager.classes,function(className,c){
          if(c.prototype &&c.prototype.mixins &&  c.prototype.mixins[mixinName]){
              classes.push(className);
          }
      });
      return classes;
  }
});
