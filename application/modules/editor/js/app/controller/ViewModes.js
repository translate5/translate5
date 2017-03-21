
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
 * Die gesamte Logik rund um die verschiedene Darstellung der Tags ist in diese Klasse gekapselt.
 * @class Editor.controller.ViewModes
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.ViewModes', {
  extend : 'Ext.app.Controller',
  stores: ['Segments'],
  refs : [{
     selector: '#editorViewport',
     ref: 'viewPort'
  },{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  },{
    ref : 'shortTagBtn',
    selector : '#segmentgrid #shortTagBtn'
  },{
    ref : 'hideTagBtn',
    selector : '#segmentgrid #hideTagBtn'
  },{
    ref : 'segmentPager',
    selector : 'editorgridscroller'
  },{
    ref : 'segmentsHtmleditor',
    selector : 'segmentsHtmleditor'
  },{
    ref : 'filePanel',
    selector : '#filepanel'
  },{
    ref : 'viewModeMenu',
    selector : '#viewModeMenu'
  }],
  listen: {
      controller: {
          '#Editor.$application': {
              editorViewportClosed: 'clearViewModes'
          },
      },
      component: {
          '#editorViewport': {
              boxready: 'handleViewportReady'
          },
          'gridpanel segmentsToolbar menucheckitem[group="toggleView"]' : {
              click : 'handleViewMode'
          },
          'gridpanel segmentsToolbar button[toggleGroup="tagMode"]' : {
              click : 'handleTagButtonClick'
          },
          'segmentsHtmleditor': {
              initialize: 'toggleEditorErgonomicMode'
          },
          '#segmentgrid': {
              beforestartedit: 'checkModeBeforeEdit'
          }
      }
  },
  messageIsViewMode: '#UT#Das Segment kann nicht bearbeitet werden, da die Aufgabe im "nur Lese"- bzw. Ansichtsmodus ist.',
  /**
   * Flag when true, editor can not be set into a non readonly mode
   */
  readonlyForced: false,
  visibleColumns: [],
  filesRegionVisible: true,
  init : function() {
      var me = this;
      me.toggleTags(me.self.TAG_SHORT);
      
      
  },
  statics: {
    STYLE_BOX_ID: 'ergonomicStyleId',
    
    TAG_FULL : 'full-tag',
    TAG_SHORT : 'short-tag',
    TAG_HIDE : 'hide-tag',
    
    MODE_EDIT : 'edit',             //normal/small edit mode
    MODE_ERGONOMIC : 'ergonomic',   //ergonomic/big edit mode
    CLS_READONLY: 'editor-readonly',
    
    currentTagMode: 'short-tag',
    
    ROW_HEIGHT_ERGONOMIC: 60,
    ROW_HEIGHT_DEFAULT: 15,
    
    setMode: function(mode){
        this.currentTagMode = mode; 
    },
    getMode: function() {
        return this.currentTagMode;
    },
    isFullTag: function(){
        return (this.currentTagMode == this.TAG_FULL);
    },
    isShortTag: function(){
        return (this.currentTagMode == this.TAG_SHORT);
    },
    isHideTag: function(){
        return (this.currentTagMode == this.TAG_HIDE);
    }
  },
  handleViewportReady: function(grid) {
      //start editor in ergonomic mode if configured, respect before setted readonly mode
      if(Editor.data.app.startViewMode == 'ergonomic') {
          this.ergonomicMode(grid.lookupViewModel().get('editorIsReadonly'));
      }
  },
  clearViewModes: function() {
      this.visibleColumns = [];
      this.filesRegionVisible = true;
  },
    setViewMode: function(mode){
        var me = this,
            body = Ext.getBody(),
            modeToCls = function(mode) {
                return 'mode-'+mode;
            };
        
        body.removeCls(Ext.Array.map([me.self.MODE_EDIT, me.self.MODE_ERGONOMIC], modeToCls));
        body.addCls(modeToCls(mode));
        return me.getViewPort().getViewModel().set('editorViewmode', mode);
    },
    getViewMode: function() {
        return this.getViewPort().getViewModel().get('editorViewmode');
    },
    isErgonomicMode: function(){
        return (this.getViewMode() == this.self.MODE_ERGONOMIC);
    },
    isEditMode: function(){
        return (this.getViewMode() == this.self.MODE_EDIT);
    },
    /**
     * sets the editor to readonly / write mode
     * returns true when readonly / false if not. 
     * Since the value can be forced the result of this method must be used after using it
     */
    setReadonly: function(readonly) {
        var me = this,
            editingPlugin = me.getSegmentGrid().editingPlugin,
            body = Ext.getBody(),
            readonly = readonly || me.readonlyForced;

        me.getViewPort().getViewModel().set('editorIsReadonly', readonly);
        
        if(readonly) {
            me.application.getController('Segments').saveChainStart();
            body.addCls(me.CLS_READONLY);
            editingPlugin.disable();
        }
        else {
            body.removeCls(me.CLS_READONLY);
            editingPlugin.enable();
        }

        return readonly;
    },
  /**
   * saves the default values of the default view mode in order to restore them after leaving economic mode
   */
  setVisibleElements: function(){
      var me = this;
      Ext.Array.each(me.getSegmentGrid().columns, function(col){
          if(col.isErgonomicSetWidth) {
              col.originalWidth = col.getWidth() || col.initialConfig.width || 0;
          }
          if(col.isVisible()){
            me.visibleColumns.push(col);
          }
      });
      me.filesRegionVisible = !me.getFilePanel().collapsed;
  },
  /**
   * returns all ContentColumn instances which should be hidden on view mode (and shown again in edit mode)
   * @return [Editor.view.segment.ContentColumn]
   */
  getHideColumns: function() {
      var cols = this.getSegmentGrid().query('contentColumn');
      return Ext.Array.filter(cols, function(col) {
          return !col.isEditableContentColumn && col.segmentField.get('editable');
      });
  },
  handleViewMode: function(item) {
      switch (item.mode.type) {
          case 'ergonomicMode':
              this.ergonomicMode(item.mode.readonly);
              break;
          case 'editMode':
          default:
              this.editMode(item.mode.readonly);
              break;
      }
  },
  /**
   * aktiviert den Bearbeitungsmodus des Grids (alle Spalten eingeblendet, editieren möglich, Hide Tags deaktivieren) 
   */
  editMode: function(readonly, calledOnInit) {
    var me = this;

    readonly = me.setReadonly(readonly);
    me.getViewModeMenu().hideMenu();
    me.getShortTagBtn().toggle(true);

    //editMode und viewMode
    me.getSegmentGrid().removeCls(me.self.MODE_ERGONOMIC);
    if(me.isErgonomicMode()){
        me.showNonErgonomicElements();
    }

    //nur editMode
    me.setViewMode(me.self.MODE_EDIT);

    //editMode und viewMode
    me.getSegmentGrid().view.refresh();
    me.handleTagButtonClick('short');

    //show/hide target columns, only the targetEdits are visible when readonly
    Ext.Array.each(me.getHideColumns(), function(col){
        col.setVisible(!readonly);
    });

    me.fireEvent('viewModeChanged',me);
  },
  /**
   * activates the ergonomic mode of the grid (source and edit-column enlarged, all other columns hidden; file-area hidden)
   */
  ergonomicMode: function(readonly) {
    var me = this;

    readonly = me.setReadonly(readonly);
    me.getViewModeMenu().hideMenu();
    me.getShortTagBtn().toggle(true);

    //ergo only
    me.setVisibleElements();

    //ergo only    
    me.getFilePanel().collapse();
    
    //calculate width of non content columns visible in ergo mode
    var widthToRedColWidth = 0;
    Ext.Array.each(me.getSegmentGrid().columns, function(col){
        if(col.isErgonomicSetWidth && col.isErgonomicVisible && col.ergonomicWidth  !== undefined){
            if(col.isHidden()){
                return;
            }
            widthToRedColWidth += col.ergonomicWidth;
        }
    });

    //add width of the scrollbar to the non available width for columns
    widthToRedColWidth +  Ext.getScrollbarSize().width;

    //content columns width is grid width - 
    me.colWidth = (me.getSegmentGrid().getWidth()- widthToRedColWidth)/2;
    
    Ext.Array.each(me.getSegmentGrid().columns, function(col){
        if(col.isErgonomicSetWidth){
            col.show();
            if(col.ergonomicWidth  === undefined){
                col.setWidth(me.colWidth);
            }
            else{
                col.setWidth(col.ergonomicWidth);
            }
        }
    });

    Ext.Array.each(me.visibleColumns, function(col){
        if(! col.isErgonomicVisible){
            col.hide();
        }
    },me);
    //inject css to the head to manipulate the column css, because it is easier than to set inject ergomic class for each column in the dom
    Ext.util.CSS.createStyleSheet('#segment-grid .x-grid-row .x-grid-cell .x-grid-cell-inner { width: '+me.colWidth+'px; }',me.self.STYLE_BOX_ID);
    
    //ergoOnly, others remove cls
    me.getSegmentGrid().addCls(me.self.MODE_ERGONOMIC);

    //ergoOnly others, with other mode
    me.setViewMode(me.self.MODE_ERGONOMIC);
    me.getSegmentGrid().view.refresh();
    me.toggleEditorErgonomicMode();
    me.saveAlreadyOpened();


    me.fireEvent('viewModeChanged',me);
  },
  /**
   * sets and removes the ergonomic view for the editor
   */
  toggleEditorErgonomicMode: function() {
     var me = this,
         editor = me.getSegmentsHtmleditor(),
         body;
     
     if(!editor || !editor.rendered) {
         return;
     }
     
     body = Ext.fly(editor.getEditorBody());
     body.removeCls(me.self.MODE_ERGONOMIC);
     if(me.isErgonomicMode()){
        body.addCls(me.self.MODE_ERGONOMIC);
     }
  },
  /**
   * show or expand all columns and areas not needed in ergonomic mode, which have been visible before
   */
  showNonErgonomicElements : function() {
    var me = this;
    me.saveAlreadyOpened();
    Ext.Array.each(me.visibleColumns, function(col){
        col.show();
    });
    if(me.filesRegionVisible){
        me.getFilePanel().expand();
    }
    Ext.Array.each(me.getSegmentGrid().columns, function(col){
        if(col.originalWidth){
            col.setWidth(col.originalWidth);
        }
    });
    Ext.util.CSS.removeStyleSheet(me.self.STYLE_BOX_ID);
  },
  /**
   * Unified tag mode button handler
   * @param {Ext.Button|String}
   */
  handleTagButtonClick: function(btn) {
      var me = this,
          mode = Ext.isString(btn) ? btn : btn.tagMode,
          editor = me.getActiveEditor();
      
      switch(mode) {
          case 'hide': 
              me.toggleTags(me.self.TAG_HIDE);
              break;
          case 'full': 
              me.toggleTags(me.self.TAG_FULL);
              editor && editor.showFullTags();
              me.repositionEditorRow();
              break;
          case 'short': 
              me.toggleTags(me.self.TAG_SHORT);
              editor && editor.showShortTags();
              me.repositionEditorRow();
              break;
      }
  },
  getActiveEditor: function() {
    if(! this.getSegmentGrid().editingPlugin.editor){
      return null;
    }
    return this.getSegmentGrid().editingPlugin.editor.mainEditor;
  },
  /**
   * Shortcut Funktion zum repositionieren des Editors
   */
  repositionEditorRow: function() {
    var plug = this.getSegmentGrid().editingPlugin,
        ed = plug.editor;
    if(plug.editing && ed){
        ed.reposition();
        ed.setEditorHeight();
    }
    this.toggleEditorErgonomicMode();
  },
  /**
   * Hilfsfunktion zum Setzen des Tag Modus
   * @param {string} mode eine der this.self.TAG_* Konstanten  
   */
  toggleTags : function(mode) {
    var me = this;
    Ext.getBody().removeCls([this.self.TAG_FULL, this.self.TAG_SHORT, this.self.TAG_HIDE]);
    Ext.getBody().addCls(mode);
    me.self.setMode(mode);
  },
  /**
   * saving a segment on switching view mode
   */
  saveAlreadyOpened: function() {
      var me = this,
          editor = me.getActiveEditor(),
          plug = me.getSegmentGrid().editingPlugin,
          segCtrl = me.application.getController('Segments');
      if(plug && plug.editing && editor && editor.rendered) {
          segCtrl.addLoadMask();
          segCtrl.saveChainStart();
      }
  },
  checkModeBeforeEdit: function(plugin) {
      if(this.getViewPort().getViewModel().get('editorIsReadonly')) {
          Editor.MessageBox.addWarning(this.messageIsViewMode);
          return false;
      }
  }
});