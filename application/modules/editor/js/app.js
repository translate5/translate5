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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
Ext.Loader.setConfig({
  enabled: true,
  disableCaching: false
});
Ext.data.Connection.disableCaching = false;
Ext.data.JsonP.disableCaching = false;
Ext.data.proxy.Server.prototype.noCache = false;
Ext.Ajax.disableCaching = false;

Ext.override(Ext.data.Connection, {
    timeout: 60000
});
Ext.Ajax.timeout = 60000;
Ext.override(Ext.data.proxy.Ajax, { timeout: 60000 });
Ext.override(Ext.form.action.Action, { timeout: 60 });

/**
 * enables the ability to set a optional menuOffset in menus
 * @todo this override must be revalidated on extjs update
 */
Ext.override(Ext.menu.Item, {
    deferExpandMenu: function() {
        var me = this;

        if (!me.menu.rendered || !me.menu.isVisible()) {
            me.parentMenu.activeChild = me.menu;
            me.menu.parentItem = me;
            me.menu.parentMenu = me.menu.ownerCt = me.parentMenu;
            me.menu.showBy(me, me.menuAlign, me.menuOffset);
        }
    }
});

Ext.override(Ext.app.Application, {
    constructor: function() {
        Editor.app = this; //@todo remove after upgrade to extjs > 4.1.3
        this.callOverridden(arguments);
    }
});

Editor.DATE_ISO_FORMAT = 'Y-m-d H:i:s';

Ext.application({
  name : 'Editor',
  models : [ 'File', 'Segment', 'admin.User' ],
  stores : [ 'Files', 'Segments', 'AlikeSegments' ],
  requires: ['Editor.view.ViewPortEditor', Editor.data.app.viewport, 'Editor.model.ModelOverride'],
  controllers: Editor.data.app.controllers,
  beforeUnloadCalled : false,//stellt sicher, dass aufgrund von Wechselwirkungen bei einem per JS aus einem anderen Fenster heraus getriggerten window.close die unload-Frage nicht zweimal kommt
  appFolder : Editor.data.appFolder,
  viewport: null,
  launch : function() {
      var me = this;
      this.addEvents(
          /**
           * @event editorViewportClosed
           * Fires after the editor viewport was destroyed and before the other viewport is created.
           */
          'editorViewportClosed',
          
          /**
           * @event adminViewportClosed
           * Fires after the admin viewport was destroyed and before the other viewport is created.
           */
          'adminViewportClosed',
          
          /**
           * @event editorViewportOpened
           * Fires after the editor viewport was opened by the app (nothing to do with ext rendered or show).
           */
          'editorViewportOpened',
          
          /**
           * @event adminViewportOpened
           * Fires after the admin viewport was opened by the app (nothing to do with ext rendered or show).
           */
          'adminViewportOpened'
      );
      
    Ext.QuickTips.init();
    //Anbindung des Handlers für CRQ 92 warnOnClose
    window.onbeforeunload = Ext.bind(me.onBeforeUnload, me);
    me.authenticatedUser = Ext.create('Editor.model.admin.User', Editor.data.app.user);
    me[Editor.data.app.initMethod]();
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
      
      me.getController('Fileorder').loadFileTree();//@todo bei ITL muss der load wiederum automatisch geschehen
      me.getController('Segments').loadSegments();//@todo bei ITL muss der load wiederum automatisch geschehen
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
  },
  /**
   * Used to open a task directly without administration panel
   */
  openTaskDirect: function(){
      task = Editor.model.admin.Task.create(Editor.data.task);
      task.set('userState',Editor.data.app.initState);
      task.save({
          scope: this,
          success: this.openEditor
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
  }
});
