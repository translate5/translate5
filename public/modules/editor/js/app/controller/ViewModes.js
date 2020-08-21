
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
            'gridpanel segmentsToolbar menucheckitem[group="tagMode"]' : {
                click : 'handleTagButtonClick'
            },
            'gridpanel segmentsToolbar menucheckitem[group="localeMenuGroup"]' : {
                click : 'handleLocaleMenuClick'
            },
            'button[type="segment-zoom"]' : {
                click : 'handleTagZoomClick'
            },
            '#segmentgrid': {
                beforestartedit: 'checkModeBeforeEdit'
            }
        }
    },
    messageIsViewMode: '#UT#Das Segment kann nicht bearbeitet werden, da die Aufgabe im "nur Lese"- bzw. Ansichtsmodus ist.',
    currentSegmentSize: null,
    /**
     * Flag when true, editor can not be set into a non readonly mode
     */
    readonlyForced: false,
    visibleColumns: [],

    /**
     * Columns width for editor in edit mode
     */
    editModeColumnWidth:[],
    currentTagMode: 'short-tag',

    init : function() {
        var me = this;
        me.toggleTags(me.self.TAG_SHORT);
    },

    listeners:{
        viewModeChanged : 'onViewModeChange'
    },

    statics: {
        STYLE_BOX_ID: 'ergonomicStyleId',
        
        TAG_FULL : 'full-tag',
        TAG_SHORT : 'short-tag',
        TAG_HIDE : 'hide-tag',
        
        MODE_EDIT : 'edit',             //normal/small edit mode
        MODE_ERGONOMIC : 'ergonomic',   //ergonomic/big edit mode
        CLS_READONLY: 'editor-readonly',
        
        
        ROW_HEIGHT_ERGONOMIC: 60,
        ROW_HEIGHT_DEFAULT: 15,
    },
    isFullTag: function(){
        return (this.currentTagMode == this.self.TAG_FULL);
    },
    isShortTag: function(){
        return (this.currentTagMode == this.self.TAG_SHORT);
    },
    isHideTag: function(){
        return (this.currentTagMode == this.self.TAG_HIDE);
    },
    handleViewportReady: function(grid) {
        //start editor in normal(ergonomic) mode if configured, respect before set readonly mode
        if(Editor.data.app.startViewMode == 'normal') {
            this.ergonomicMode(grid.lookupViewModel().get('editorIsReadonly'));
        }
    },
    clearViewModes: function() {
        this.visibleColumns = [];
    },
    setViewMode: function(mode){
        return this.getViewPort().getViewModel().set('editorViewmode', mode);
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
    },

    /**
     * Save the edit mode column width for later usage
     */
    saveEditModeColumnWidth:function(){
        var me = this;
        Ext.Array.each(me.getSegmentGrid().columns, function(col){
            me.editModeColumnWidth[col.itemId]=col.getWidth() || col.initialConfig.width || 0;
        });
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
            case 'visualReviewMode':
                break;
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
        me.getShortTagBtn().setChecked(true);

        //editMode und viewMode
        if(me.isErgonomicMode()){
            me.showNonErgonomicElements();
        }

        //nur editMode
        me.setViewMode(me.self.MODE_EDIT);
        me.setSegmentSize(2);

        //editMode und viewMode
        me.getSegmentGrid().view.refresh();
        me.handleTagButtonClick('short');

        //show/hide target columns, only the targetEdits are visible when readonly
        Ext.Array.each(me.getHideColumns(), function(col){
            col.setVisible(!readonly);
        });

        //save the edito mode column width for later usage
        me.saveEditModeColumnWidth();
        me.fireEvent('viewModeChanged',me);
    },
    /**
     * activates the ergonomic mode of the grid (source and edit-column enlarged, all other columns hidden; file-area hidden)
     */
    ergonomicMode: function(readonly) {
        var me = this,
            grid = me.getSegmentGrid(),
            wasAlreadyErgo = me.isErgonomicMode(),
            contentColumns = grid.hasRelaisColumn ? 3 : 2;

        readonly = me.setReadonly(readonly);
        
        me.getViewModeMenu().hideMenu();
        me.getShortTagBtn().setChecked(true);
        
        wasAlreadyErgo || me.setVisibleElements();

        //ergo only
        //collapse only if the panel is visible
        if(me.getFilePanel().isVisible()){
            me.getFilePanel().collapse();
        }
        
        //calculate width of non content columns visible in ergo mode
        var widthToRedColWidth = 0;
        Ext.Array.each(grid.columns, function(col){
            if(col.isErgonomicSetWidth && col.isErgonomicVisible && col.ergonomicWidth  !== undefined){
                if(col.isHidden()){
                    return;
                }
                widthToRedColWidth += col.ergonomicWidth;
            }
        });

        //add width of the scrollbar to the non available width for columns
        widthToRedColWidth += Ext.getScrollbarSize().width;
        
        //content columns width is grid width - 
        me.colWidth = (grid.getWidth()- widthToRedColWidth) / contentColumns;
        
        Ext.Array.each(grid.columns, function(col){
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

        //set the ergonimic columns back to visible
        Ext.Array.each(grid.columns, function(col){
            if(! col.isErgonomicVisible){
                col.hide();
            }
        },me);
        //inject css to the head to manipulate the column css, because it is easier than to set inject ergomic class for each column in the dom
        Ext.util.CSS.removeStyleSheet(me.self.STYLE_BOX_ID); //delete if already exists!
        Ext.util.CSS.createStyleSheet('#segment-grid .x-grid-row .segment-tag-column.x-grid-cell .x-grid-cell-inner { width: '+me.colWidth+'px; }',me.self.STYLE_BOX_ID);
        
        //ergoOnly others, with other mode
        wasAlreadyErgo || me.setViewMode(me.self.MODE_ERGONOMIC);
        wasAlreadyErgo || me.setSegmentSize(4);

        grid.view.refresh();
        me.handleTagButtonClick('short');
        me.saveAlreadyOpened();

        me.fireEvent('viewModeChanged',me);
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
        
        me.getFilePanel().expand();
        
        Ext.Array.each(me.getSegmentGrid().columns, function(col){
            //apply the column width from saved values
            if(me.editModeColumnWidth[col.itemId]){
                col.setWidth(me.editModeColumnWidth[col.itemId]);
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
    
    /***
     * On locale switch click. This will change the translate5 interface language
     */
    handleLocaleMenuClick:function(btn){
    	Editor.app.setTranslation(btn.getValue());
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
    },
    /**
     * Hilfsfunktion zum Setzen des Tag Modus
     * @param {string} mode eine der this.self.TAG_* Konstanten  
     */
    toggleTags : function(mode) {
        var me = this;
        Ext.getBody().removeCls([this.self.TAG_FULL, this.self.TAG_SHORT, this.self.TAG_HIDE]);
        Ext.getBody().addCls(mode);
        this.currentTagMode = mode;
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
    },
    /**
     * Handles clicking the zoom buttons
     */
    handleTagZoomClick: function(btn) {
        this.setSegmentSize(btn.itemId == 'zoomInBtn' ? 1 : -1, true);
    },
    /**
     * Sets the segment font size via CSS class
     */
    setSegmentSize: function(size, relative) {
        var me = this,
            oldSize = me.currentSegmentSize;
        if(relative) {
            size = oldSize + size;
        }
        size = Math.min(Math.max(size, 1), 6);
        me.currentSegmentSize = size;
        sizer = function(size) {return 'segment-size-'+size};
        size = sizer(size);
        oldSize = sizer(oldSize);
        Ext.getBody().removeCls(oldSize);
        Ext.getBody().addCls(size);
        me.fireEvent('segmentSizeChanged', me, size, oldSize);
    },

    /***
     * View mode change event handler
     */
    onViewModeChange:function(){
        var me=this,
            grid = me.getSegmentGrid(),
            pos = grid.getSelectionModel().getCurrentPosition();

        if(!pos || pos.rowIdx == undefined){
            return;
        }
        //preserve the row selection on viewmode change
        grid.scrollTo(pos.rowIdx);
    }
});