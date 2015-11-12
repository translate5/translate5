
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
  init : function() {
      var me = this;
      me.control({
          '.gridpanel #viewModeBtn' : {
              click : me.viewMode
          },
          '.gridpanel #editModeBtn' : {
              click : me.editMode
          },
          '.gridpanel #ergonomicModeBtn' : {
              click : me.ergonomicMode
          },
          '.gridpanel #hideTagBtn' : {
              click : me.hideTags
          },
          '.gridpanel #fullTagBtn' : {
              click : me.showFullTags
          },
          '.gridpanel #shortTagBtn' : {
              click : me.showShortTags
          },
          'segmentsHtmleditor': {
              afteriniteditor: me.toggleEditorErgonomicMode
          },
          '#segmentgrid editorgridscroller': {
              beforerender: me.initScroller
          }
      });
    
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('editorViewportClosed', me.clearViewModes, me);
    
      me.toggleTags(me.self.TAG_SHORT);
  },
  statics: {
    ergonomicStyleId: 'ergonomicStyleId',
    
    TAG_FULL : 'full-tag',
    TAG_SHORT : 'short-tag',
    TAG_HIDE : 'hide-tag',
    
    MODE_VIEW : 'view',
    MODE_EDIT : 'edit',
    MODE_ERGONOMIC : 'ergonomic',
    
    currentTagMode: 'short-tag',
    currentViewMode: 'edit',
    
    ROW_HEIGHT_ERGONOMIC: 60,
    ROW_HEIGHT_DEFAULT: 15,
    
    visibleColumns: [],
    
    filesRegionVisible: true,
    
    setMode: function(mode){
        this.currentTagMode = mode; 
    },
    setViewMode: function(mode){
        var me = this,
            body = Ext.getBody(),
            modeToCls = function(mode) {
                return 'mode-'+mode;
            };
        body.removeCls(Ext.Array.map([me.MODE_VIEW, me.MODE_EDIT, me.MODE_ERGONOMIC], modeToCls));
        body.addCls(modeToCls(mode));
        me.currentViewMode = mode; 
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
    },
    isErgonomicMode: function(){
        return (this.currentViewMode == this.MODE_ERGONOMIC);
    },
    getErgonomicMode: function(){
        return this.MODE_ERGONOMIC;
    },
    isEditMode: function(){
        return (this.currentViewMode == this.MODE_EDIT);
    }    ,
    isViewMode: function(){
        return (this.currentViewMode == this.MODE_VIEW);
    }
  },
  onLaunch: function() {
      var me = this;
      me.self.setViewMode(me.self.MODE_EDIT); //initial mode is edit
  },
  clearViewModes: function() {
      this.self.visibleColumns = [];
      this.self.filesRegionVisible = true;
  },
  /**
   * Initiates the internal used rowHeight of the Segment Grid Scroller
   * @param scroller
   */
  initScroller: function(scroller) {
      if(! scroller){
          return;
      }
      var self = this.self,
          height = self.isErgonomicMode() ? self.ROW_HEIGHT_ERGONOMIC : self.ROW_HEIGHT_DEFAULT;
      scroller.setRowHeight(height);
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
            me.self.visibleColumns.push(col);
          }
      });
      me.self.filesRegionVisible = !me.getFilePanel().collapsed;
  },
  /**
   * returns all ContentColumn instances which should be hidden on view mode (and shown again in edit mode)
   * @return [Editor.view.segment.ContentColumn]
   */
  getHideColumns: function() {
      var cols = this.getSegmentGrid().query('.contentColumn');
      return Ext.Array.filter(cols, function(col) {
          return col.segmentField.get('editable');
      });
  },
  /**
   * aktiviert den Bearbeitungsmodus des Grids (alle Spalten eingeblendet, editieren möglich, Hide Tags deaktivieren) 
   */
  editMode: function(calledOnInit) {
    var me = this;
    //FIXME ext6 missing ViewModes
    //me.getViewModeMenu().hideMenu();
    //me.getSegmentGrid().removeCls(me.self.MODE_ERGONOMIC);
    //if(me.self.isErgonomicMode()){
    //    me.showNonErgonomicElements();
    //}
    //me.getShortTagBtn().toggle(true);
    //me.showShortTags();
    //me.getHideTagBtn().disable();
    if(calledOnInit === true) {
        // is needed to initilize the grid filters (createFilters method) correctly:
        //FIXME ext6 missing filters
        //me.getSegmentGrid().headerCt.getMenu(); 
    }
    else {
        Ext.Array.each(me.getHideColumns(), function(col){
            col.show();
        });
    }
    //FIXME ext6 missing plugin
    //me.getSegmentGrid().editingPlugin.enable();
    //FIXME ext6 missing ViewModes
    //me.self.setViewMode(me.self.MODE_EDIT);
  },
  /**
   * activates the ergonomic mode of the grid (source and edit-column enlarged, all other columns hidden; file-area hidden)
   */
  ergonomicMode: function() {
    var me = this;
    me.getViewModeMenu().hideMenu();
    if(me.self.isViewMode()){
        me.editMode();
    }
    me.setVisibleElements();
    
    me.initScroller(me.getSegmentPager());
    
    me.getFilePanel().collapse();
    
    /* trial to deactivate column-hide-show in ergo-mode because of ext-bug with 
     * horinzontal scrolling after showing of hidden columns. 
     * Does not work due to problems with reconfigure grid and roweditor
     *  in ext 4.0.7. See view/ui/segments/grid.js onReconfigure for more information.
    me.newColumns = new Array();
    
    Ext.Array.each(me.getSegmentGrid().columns, function(col){
        var conf = col.initialConfig;
        conf.hideable = false;
        if(col.itemId != "sourceColumn" && col.itemId != "editorColumn"){
            conf.hidden = true;
        }
        conf.hideable = false;
        me.newColumns.push(conf);
    },me);
    me.getSegmentGrid().reconfigure(me.getStore('Segments'),me.newColumns);
      */
    var widthToRedColWidth = 0;
    Ext.Array.each(me.getSegmentGrid().columns, function(col){
        if(col.isErgonomicSetWidth && col.isErgonomicVisible && col.ergonomicWidth  !== undefined){
            if(!col.isHidden()){
                widthToRedColWidth += col.ergonomicWidth;
            }
        }
    });
    me.colWidth = (me.getSegmentGrid().getWidth()- widthToRedColWidth - 12)/2;//This is a bit to large
    // by purpose, because we need a horizontal scrollbar always. Otherwhise 
    // the horizontal scrollbar will not work when showing hidden columns 
    // in some cases due to a ext-bug. Might be possible to change on ext-upgrade
    
    Ext.Array.each(me.getSegmentGrid().columns, function(col){
        if(col.isErgonomicSetWidth){
            col.show();
            if(col.ergonomicWidth  !== undefined){
                col.setWidth(col.ergonomicWidth);
            }
            else{
                col.setWidth(me.colWidth);
            }
        }
    });

    Ext.Array.each(me.self.visibleColumns, function(col){
        if(! col.isErgonomicVisible){
            col.hide();
        }
    },me);
    //inject css to the head to manipulate the column css, because it is easier than to set inject ergomic class for each column in the dom
    Ext.util.CSS.createStyleSheet('#segment-grid .x-grid-row .x-grid-cell .x-grid-cell-inner { width: '+me.colWidth+'px; } #segment-grid.ergonomic .x-grid-row .x-grid-cell, #segment-grid.ergonomic .x-grid-row-editor .x-form-display-field {    font-size: 19pt !important;    line-height: 39px;}',me.self.ergonomicStyleId);

    me.getSegmentGrid().addCls(me.self.MODE_ERGONOMIC);
    me.self.setViewMode(me.self.MODE_ERGONOMIC);
    
    me.editorDomCleanUp();
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
     body.removeCls(me.self.getErgonomicMode());
     if(me.self.isErgonomicMode()){
        body.addCls(me.self.getErgonomicMode());
     }
  },
  /**
   * aktiviert den Ansichtsmodus des Grids (Editor deaktiviert, Zieltext spalte deaktiviert, Hide Tags möglich)
   */
  viewMode: function() {
    var me = this, 
    editorPlugin = me.getSegmentGrid().editingPlugin;
    me.application.getController('Segments').saveChainStart();
    this.getViewModeMenu().hideMenu();
    this.getSegmentGrid().removeCls(this.self.MODE_ERGONOMIC);
    if(me.self.isErgonomicMode()){
        this.showNonErgonomicElements();
    }
    me.getShortTagBtn().toggle(true);
    me.showShortTags();
    me.getHideTagBtn().enable();
    Ext.Array.each(me.getHideColumns(), function(col){
        col.hide();
    });
    editorPlugin.disable();
    this.self.setViewMode(this.self.MODE_VIEW);
  },
  /**
   * show or expand all columns and areas not needed in ergonomic mode, which have been visible before
   */
  showNonErgonomicElements : function() {
    var me = this;
    me.editorDomCleanUp();
    Ext.Array.each(me.self.visibleColumns, function(col){
        col.show();
    });
    if(me.self.filesRegionVisible){
        me.getFilePanel().expand();
    }
    Ext.Array.each(me.getSegmentGrid().columns, function(col){
        if(col.originalWidth){
            col.setWidth(col.originalWidth);
        }
    });
    me.initScroller(me.getSegmentPager());
    Ext.util.CSS.removeStyleSheet(me.self.ergonomicStyleId);
  },
  /**
   * alle Tags verstecken 
   */
  hideTags : function() {
    this.toggleTags(this.self.TAG_HIDE);
  },
  /**
   * Full Tags Modus aktivieren
   */
  showFullTags : function() {
    var me = this, editor = me.getActiveEditor();
    this.toggleTags(this.self.TAG_FULL);
    editor && editor.showFullTags();
    me.repositionEditorRow();
  },
  /**
   * Short Tags Modus aktivieren
   */
  showShortTags : function() {
    var me = this, editor = me.getActiveEditor();
    this.toggleTags(this.self.TAG_SHORT);
    editor && editor.showShortTags();
    me.repositionEditorRow();
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
    if(this.getSegmentGrid().editingPlugin.editor){
      this.getSegmentGrid().editingPlugin.editor.reposition();
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
   * used to cleanup the editor before switching the view mode, so the editor gets initialized new
   */
  editorDomCleanUp: function() {
      var me = this,
          editor = me.getActiveEditor(),
          plug = me.getSegmentGrid().editingPlugin,
          segCtrl = me.application.getController('Segments');
      if(plug){
          if(plug.openedRecord && editor && editor.rendered) {
              segCtrl.addLoadMask();
              segCtrl.saveChainStart({
                  chainEnd: function() {
                      plug.editorDomCleanUp();
                  }
              });
          }
          else {
              plug.editorDomCleanUp();
          }
      }
  }
});