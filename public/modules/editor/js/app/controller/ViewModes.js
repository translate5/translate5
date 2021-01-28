
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
            'gridpanel segmentsToolbar menuitem[group="toggleView"]' : {
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
                beforeinitcolumns: 'initSegmentGridColumns',
                beforestartedit: 'checkModeBeforeEdit'
            }
        }
    },
    messageIsViewMode: '#UT#Das Segment kann nicht bearbeitet werden, da die Aufgabe im "nur Lesemodus" ist.',
    messageIsViewModeDueHiddenTags: '#UT#Das Segment kann nicht bearbeitet werden, da die "Tags" ausgeblendet wurden. Klicken Sie auf "Ansicht" und blenden Sie die Tags wieder ein.',
    
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

    active: false, //must be activated / deactivated on entering / leaving a task

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

        ERGONOMIC_MODE_DEFAULT_SEGMENT_SIZE:4,
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
        var me = this,
            readonly = grid.lookupViewModel().get('taskIsReadonly');

        //start editor in normal(ergonomic) mode if configured
        if(Editor.app.getTaskConfig('editor.startViewMode') == 'normal') {
            me.loadErgonomicModePresets(readonly, true);
        }
        else {
            me.loadEditModePresets(readonly, true);
        }
    },
    /**
     * Handler to intercept the initial segment grid column definition to make a local copy
     * @param {Array} 
     */
    initSegmentGridColumns: function(columns) {
        var me = this,
            attrToKeep = ['stateId','isFirstTarget','width','hidden','flex','isContentColumn','isEditableContentColumn'];
        //store column presets as they come from the grid
        me.gridPreset = [];
        Ext.Array.each(columns, function(column){
            var conf = {},
                columnProto = Ext.ClassManager.getByAlias('widget.'+column.xtype).prototype,
                isEditCol = column.isEditableContentColumn;
            //calculate if column should be hidden in readonly mode:
            //true if it is the non editable column but the field is editable (left part of or), or if it is the editable column
            conf.isContentColumnWithEditablePendant = (column.isContentColumn && !isEditCol && column.segmentField.get('editable')) || isEditCol;
            
            //the following values may be set either in the column class or in the column config in the grid
            // so if in the config they are missing, they have to fetched from the class
            attrToKeep.forEach(function(val){
                if(column[val] !== undefined) {
                    conf[val] = column[val];
                } else if(columnProto && columnProto[val] !== undefined) {
                    conf[val] = columnProto[val];
                }
            });
            me.gridPreset.push(conf);
        }); 
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
            body = Ext.getBody();
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
     * if the view mode is requested to be changed via menu, the custom layout is deleted!
     */
    handleViewMode: function(item) {
        var me = this,
            readonly = me.getSegmentGrid().lookupViewModel().get('taskIsReadonly');
        if(!item.mode || !item.mode.type) {
            //if no type is given, the the view mode change can not be handled here 
            // and must be done elsewhere (the place where the new view mode button was created)
            return;
        }
        switch (item.mode.type) {
            case 'ergonomicMode':
                me.loadErgonomicModePresets(readonly);
                break;
            case 'editMode':
            default:
                me.loadEditModePresets(readonly);
                break;
        }
    },
    /**
     * returns the detailed mode default state object for the segment grid
     * @param {Boolean}
     * @return {Object} 
     */
    getSegmentGridDetailModePreset: function(readonly) {
        var me = this, preset = [];
        me.gridPreset.forEach(function(column){
            preset.push({
                id: column.stateId,
                hidden: column.hidden,
                width: column.width
            });
        });
        return {columns: preset, segmentSize: 2};
    },
    /**
     * returns the normal mode default state object for the segment grid
     * @param {Boolean}
     * @return {Object} 
     */
    getSegmentGridNormalModePreset: function(readonly) {
        var me = this, 
            preset = [],
            grid = me.getSegmentGrid(),
            contentColumns = grid.hasRelaisColumn ? 3 : 2;
            
        me.gridPreset.forEach(function(column){
            
            var newcol = {
                    id: column.stateId,
                    hidden: true
                };
                
            switch(column.stateId) {
                case "segmentNrInTaskColumn":
                    newcol.hidden = false;
                    newcol.width = 60;
                    break;
                case "autoStateColumn": 
                    newcol.hidden = false;
                    newcol.width = 96;
                    break;
                case "matchrateColumn": 
                    newcol.hidden = false;
                    newcol.width = 90;
                    break;
                case "contentColumn_source": 
                    newcol.hidden = column.isContentColumnWithEditablePendant; //hides the column if there is an editable pendant
                    newcol.flex = 1/contentColumns;
                    break;
                case "contentColumn_relais": 
                    newcol.hidden = false;
                    newcol.flex = 1/contentColumns;
                    break;
                case "contentColumn_target": 
                    newcol.hidden = true;
                    newcol.flex = 1/contentColumns;
                    break;
                case "contentColumn_source_edit": 
                    newcol.hidden = !column.isContentColumnWithEditablePendant; //show the column instead of the non editable one
                    newcol.flex = 1/contentColumns;
                    break;
                case "contentColumn_target_edit": 
                    newcol.hidden = false;
                    newcol.flex = 1/contentColumns;
                    break;
            }
            
            preset.push(newcol);
        });
        return {columns: preset, segmentSize: me.self.ERGONOMIC_MODE_DEFAULT_SEGMENT_SIZE};
    },
    /**
     * @private
     */
    loadModePreset: function(mode, readonly, editorinit, doCustomStuff) {
        var me = this,
            langResPanel = me.getViewPort().down('#languageResourceEditorPanel'),
            eastPanel = me.getViewPort().down('#editorEastPanel'),
            stateProv = Ext.state.Manager.getProvider();

        if(!editorinit) {
            //if a presetted view mode is used the custom states are reset
            me.resetCustomLayout();
        }
        
        readonly = me.setReadonly(readonly);
        me.getViewModeMenu().hideMenu();
        me.getShortTagBtn().setChecked(true);
        
        doCustomStuff();
        
        if(langResPanel && (! editorinit || !stateProv.hasCustomState(langResPanel))) {
            langResPanel.expand(false);
            langResPanel.setHeight(langResPanel.config.height || langResPanel.initialConfig.height);
        }
        if(eastPanel && (! editorinit || !stateProv.hasCustomState(eastPanel))) {
            eastPanel.expand(false);
        }
        
        me.setViewMode(mode);
        
        //editMode und viewMode
        me.getSegmentGrid().view.refresh();
        me.handleTagButtonClick('short');
        me.saveAlreadyOpened();

        me.fireEvent('viewModeChanged',me);
    },
    /**
     * aktiviert den Bearbeitungsmodus des Grids (alle Spalten eingeblendet, editieren möglich, Hide Tags deaktivieren)
     */
    loadEditModePresets: function(readonly, editorinit) {
        var me = this,
            stateProv = Ext.state.Manager.getProvider();
            
        me.loadModePreset(me.self.MODE_EDIT, readonly, editorinit, function(){
            //call reconfigure only if used to change view while running
            if(! editorinit || !stateProv.hasCustomState(me.getSegmentGrid())) {
                me.getSegmentGrid().applyState(me.getSegmentGridDetailModePreset(readonly));
            }
    
            //editMode und viewMode
            if(! editorinit || !stateProv.hasCustomState(me.getFilePanel())) {
                me.getFilePanel().expand(false);
                me.getFilePanel().down('panel').expand(false);
            }
        });
        
    },
    /**
     * activates the ergonomic mode of the grid (source and edit-column enlarged, all other columns hidden; file-area hidden)
     */
    loadErgonomicModePresets: function(readonly, editorinit) {
        var me = this,
            grid = me.getSegmentGrid(),
            stateProv = Ext.state.Manager.getProvider();
            
        this.loadModePreset(me.self.MODE_ERGONOMIC, readonly, editorinit, function(){
            //collapse only if the panel is visible
            if(! editorinit || !stateProv.hasCustomState(me.getFilePanel())) {
                //reset the initial values for some elements, since they are newly set by the afterwards load*ModePreset calls
                if(me.getFilePanel().isVisible()){
                    me.getFilePanel().collapse(null, false);
                    me.getFilePanel().down('panel').expand(false);
                }
            }
    
            //call reconfigure only if used to change view while running
            if(!editorinit || !stateProv.hasCustomState(grid)) {
                grid.applyState(me.getSegmentGridNormalModePreset(readonly));
            }
        });
    },
    /**
     * Unified tag mode button handler
     * @param {Ext.Button|String}
     */
    handleTagButtonClick: function(btn) {
        var me = this,
            mode = Ext.isString(btn) ? btn : btn.tagMode,
            editor = me.getActiveEditor(),
            readonly = me.getSegmentGrid().lookupViewModel().get('taskIsReadonly');

        switch(mode) {
            case 'hide': 
                me.setReadonly(true);
                me.toggleTags(me.self.TAG_HIDE);
                break;
            case 'full': 
                me.toggleTags(me.self.TAG_FULL);
                me.setReadonly(readonly);
                editor && editor.showFullTags();
                me.repositionEditorRow();
                break;
            case 'short': 
                me.toggleTags(me.self.TAG_SHORT);
                me.setReadonly(readonly);
                editor && editor.showShortTags();
                me.repositionEditorRow();
                break;
        }
    },
    
    /***
     * On locale switch click. This will change the translate5 interface language
     */
    handleLocaleMenuClick: function(btn){
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
        Ext.getBody().removeCls([me.self.TAG_FULL, me.self.TAG_SHORT, me.self.TAG_HIDE]);
        Ext.getBody().addCls(mode);
        me.currentTagMode = mode;
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
        var vm = this.getViewPort().getViewModel();
        if(!vm.get('editorIsReadonly')) {
            return;
        }
        if(vm.get('taskIsReadonly')) {
            Editor.MessageBox.addWarning(this.messageIsViewMode);
        } else {
            Editor.MessageBox.addWarning(this.messageIsViewModeDueHiddenTags);
        }
        return false;
    },
    /**
     * Handles clicking the zoom buttons
     */
    handleTagZoomClick: function(btn) {
        this.getSegmentGrid().setSegmentSize(btn.itemId == 'zoomInBtn' ? 1 : -1, true);
    },

    /***
     * Remove custom view state configuration and reset the order of the segments grid column to the default order.
     * After the reset, new view mode can be applied
     */
    resetCustomLayout: function(){
        Ext.state.Manager.getProvider().reset('editor');
    },

    /**
     * View mode change event handler
     */
    onViewModeChange: function(){
        var me = this,
            grid = me.getSegmentGrid(),
            pos = grid.getSelectionModel().getCurrentPosition();

        if(!pos || pos.rowIdx == undefined){
            return;
        }
        //preserve the row selection on viewmode change
        grid.scrollTo(pos.rowIdx);
    }
});
