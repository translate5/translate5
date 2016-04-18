
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

Ext.enableAriaButtons = false; //ext6.0.0 bug, see EXT6UPD-10 

Ext.override(Ext.data.Connection, {
    timeout: 60000
});
Ext.Ajax.timeout = 60000;
Ext.override(Ext.data.proxy.Ajax, { timeout: 60000 });
Ext.override(Ext.form.action.Action, { timeout: 60 });

Ext.Loader.setPath('Editor.controller.Localizer', Editor.data.basePath+'/editor/js/app-localized.js');
Ext.Loader.setPath('Editor.plugins', Editor.data.pluginFolder);

Editor.DATE_ISO_FORMAT = 'Y-m-d H:i:s';

Ext.application({
  name : 'Editor',
  models : [ 'File', 'Segment', 'admin.User', 'admin.Task', 'segment.Field' ],
  stores : [ 'Files', 'ReferenceFiles', 'Segments', 'AlikeSegments' ],
  requires: ['Editor.view.ViewPort', Editor.data.app.viewport, 'Editor.model.ModelOverride'],
  controllers: Editor.data.app.controllers,
  beforeUnloadCalled : false,//stellt sicher, dass aufgrund von Wechselwirkungen bei einem per JS aus einem anderen Fenster heraus getriggerten window.close die unload-Frage nicht zweimal kommt
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
      this.callParent(arguments);
  },
  launch : function() {
      var me = this,
      viewSize = Ext.getBody().getViewSize();
    Ext.QuickTips.init();
    window.onbeforeunload = Ext.bind(me.onBeforeUnload, me);
    me.authenticatedUser = Ext.create('Editor.model.admin.User', Editor.data.app.user);
    me[Editor.data.app.initMethod]();
    
    Editor.MessageBox.showInitialMessages();
    
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
  //Handler für CRQ 92 warnOnClose
  onBeforeUnload: function(e) {
    if(this.beforeUnloadCalled){ return; }
    this.beforeUnloadCalled = true;
    var lastsegment = text = '', event = e || window.event;
    if(lastsegment = this.getController('Segments').getLastSegmentShortInfo()){
      text = Ext.String.format(this.beforeUnloadText_1, lastsegment);
    }
    text += this.beforeUnloadText_2;
    if (event) {
      event.returnValue = text;
    }
    return text;
  },
  /**
   * opens the editor with the given Task
   * firing the adminViewportClosed event
   * @param {Editor.model.admin.Task} task
   * @param {Boolean} readonly optional to open the task readonly
   */
  openEditor: function(task, readonly) {
      var me = this;
      if(! (task instanceof Editor.model.admin.Task)) {
          me.openTaskDirect();
          return;
      }
      readonly = (readonly === true || task.isReadOnly());
      Editor.data.task = task;
      Editor.model.Segment.redefine(task.segmentFields());
      if(me.viewport){
          me.viewport.destroy();
          me.fireEvent('adminViewportClosed');
      }
      else {
          Ext.getBody().removeCls('loading');
      }
      task.initWorkflow();
      me.viewport = Ext.create(Editor.data.app.viewport, {
          renderTo : 'editor-viewport' //Ext.getBody()
      });
      me.viewport.show();
      
      //vp.doLayout();
      //this.viewport.setLoading(true);
      /*
      - destroys all admin components, updates all editor stores. Inits the editor component if needed.
      - deactivate "onBeforeUnload" through beforeUnloadCalled
       */
      //enable logout split button
      //disable logout normal Button
      me.beforeUnloadCalled = false;
      me.fireEvent('editorViewportOpened');
      me.getController('Fileorder').loadFileTree();//@todo bei ITL muss der load wiederum automatisch geschehen
      me.browserAdvice();
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
      var me = this;
      if(!Editor.controller.admin || ! Editor.controller.admin.TaskOverview) {
          return;
      }
      if(me.viewport){
          me.viewport.destroy();
          me.fireEvent('editorViewportClosed');
      }
      else {
          Ext.getBody().removeCls('loading');
      }
      me.viewport = Ext.create('Editor.view.ViewPort', {
          renderTo : 'editor-viewport' //Ext.getBody()
      });
      me.viewport.show();
      
      me.getController('admin.TaskOverview').loadTasks();
      
      /*
      - hides all editor components, inits all admin components, stores, etc.
      - empty editor stores
      - deactivate "onBeforeUnload" through beforeUnloadCalled
      */
      //disable logout split button
      //enable logout normal Button
      this.beforeUnloadCalled = true;
      me.fireEvent('adminViewportOpened');
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

      form.submit();
  },
  browserAdvice: function() {
      if(!Ext.isIE) {
          return;
      }
      Editor.MessageBox.addInfo(this.browserAdviceText);
  }
});
