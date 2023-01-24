
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Editor.controller.Comments encapsulates the comment functionality
 * @class Editor.controller.Comments
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Comments', {
    extend : 'Ext.app.Controller',
    models: ['Segment'],
    stores: ['Comments', 'Segments'],
    views: ['comments.Panel'],
    refs : [{
        ref : 'segmentGrid',
        selector : '#segmentgrid'
    },{
        ref : 'saveBtn',
        selector : 'commentPanel[disabled=false] #saveBtn'
    },{
        ref: 'commentPanel',
        selector: 'commentPanel[disabled=false]'
    },{
        ref: 'commentContainer',
        selector: 'commentPanel[disabled=false] #commentContainer'
    },{
        ref: 'rowEditor',
        selector: '#roweditor'
    },{
        ref: 'commentForm',
        selector: 'commentPanel[disabled=false] #commentForm'
    },{
        ref: 'autoStateDisplay',
        selector: '#roweditor displayfield[name=autoStateId]'
    },{
        ref: 'commentDisplay',
        selector: '#roweditor displayfield[name=comments]'
    }],
    
    listen: {
            component: {
                '#segmentgrid' : {
                    itemdblclick: 'handleCommentsColumnDblClick',
                    itemclick: 'handleCommentsColumnClick',
                    selectionchange: 'handleSegmentSelectionChange',
                    beforeedit: 'onStartEdit',
                    canceledit: 'cancelEdit',
                    edit: 'cancelEdit'
                },
                '#roweditor': {
                    afterEditorMoved: 'onEditorMoved'
                },
                
                //this remains here, since it does nothing have todo with the panel
                '#roweditor displayfield[name=comments]': {
                    change: 'updateEditorComment'
                },
                '#metapanel #commentPanel' : {
                    expand: 'loadCommentPanel',
                    requestCommentClose: 'handleCloseComments'
                }
            },
            global: {
                editorOpenComments: {
                    fn: 'onRequestOpenComments',
                    options: {
                        priority: 100 //the handler here should run as first one, since it inits the segment record, and stops handling if comment is not editable
                    }
                }
            }
    },

    /**
     * on cancelling the segment edit the active comment is resetted by creating a new one
     */
    cancelEdit: function() {
        var me=this,
            panel = me.getCommentPanel(),
            form = me.getCommentForm();
        
        if(!panel){
            return;
        }
        
        panel.getController().handleAddComment();
    },
    
    getEditPlugin: function() {
        var me = this,
            grid, ed;
            
        grid = me.getSegmentGrid();
        ed = grid.editingPlugin;
        return ed;
    },
        
    /**
     * loads a single comment into the comment edit form, if editable
     * @param {Editor.model.Comment} rec
     */
    loadComment: function(rec) {
        this.getCommentPanel().getController().loadComment(rec);
    },
    /**
     * returns true if comments are enabled also for locked Segments
     */
    isEnabledForLocked: function() {
        return Editor.app.authenticatedUser.isAllowed('editorCommentsForLockedSegments');
    },
    
    /**
     * handle clicks on the comment column of the grid.
     * For handling only selected rows we have to use the img as clicktarget. 
     *   Native way would be checking the select state of column. But this is not sufficient, 
     *   because row is first selected, then the click event will be processed. This means click processing on every row.
     */
    handleCommentsColumnClick: function(view, segment, tr, idx, ev) {
        var me = this,
            ed = me.getEditPlugin(),
            add = ev.getTarget('img.add'),
            edit = ev.getTarget('img.edit'),
            readOnlyMode = view.lookupViewModel().get('editorIsReadonly');
        
        //close the comment panel on single clicking onto a segment
        if(add || edit) {
            //fire the global open comments request
            Ext.fireEvent('editorOpenComments', segment);
        }

        //close metapanel if clicking single on a row, and the previous row was not editable
        if(me.getCommentPanel() && (!ed.editing || !ed.context.record)) {
            Editor.app.getController('MetaPanel').cancelEdit();
        }
    },
    /**
     * Triggers comments panel for not editable segments on dblclick on comments column
     */
    handleCommentsColumnDblClick: function (view, segment, tr, idx, ev) {
        var me = this,
            readOnlyMode = view.lookupViewModel().get('editorIsReadonly'),
            isEditable = (segment.get('editable') && !readOnlyMode);
            isCommentsCol = ev.getTarget('td.comments-field'),
            isForced = me.isEnabledForLocked();
    
        //if isEditable then dbl click should handle open the whole segment as usually
        //if not dbl clicked on comments column do nothing
        //if not isForced do nothing
        if(isEditable || !isCommentsCol || !isForced) {
            return;
        }
        
        //fire the global open comments request
        Ext.fireEvent('editorOpenComments', segment);
    },
    /**
     * Handle close comments request depending on the place where the panel is located
     */
    handleCloseComments: function(panel) {
        if(panel.isCollapsable){
            panel.collapse();
        }
    },
    /**
     * handles starting the segment editor
     * @param {String} toEdit field which is really be edited by the editor 
     * @param {Editor.view.segments.RowEditor} editor 
     */
    onEditorMoved: function(toEdit, editor) {
        var me = this,
            commentPanel = me.getCommentPanel();
        if(editor.columnClicked == 'comments' && commentPanel) {
            commentPanel.handleExpand();
            //example to solve the expand problem:
            //make a new method open in the commentPanel
            //this open method just triggers an event "openrequest"
            //listen here to "#metapanel #commentpanel" triggers then expand
            //listen here to "#commentwindow #commentpanel" triggers then o
        }
    },


    handleSegmentSelectionChange: function(sm, selectedRecords) {
        var me = this;
        me.refCache={};
        var commentPanel=me.getCommentPanel();

        if(!commentPanel){
            return;
        }

        // If no selection - return
        if (selectedRecords.length == 0) {
            return;
        }

        me.record = selectedRecords[0];
        me.loadCommentPanel();
    },

    /**
     * handles starting the segment editor
     * @param {Object} context
     */
    onStartEdit: function(plug, context) {
        var me = this;
        me.refCache={};
        var commentPanel=me.getCommentPanel();

        if(!commentPanel){
            return;
        }

        //opens the commentpanel if the editor was started by clicking on the comment column
        if(context.field && context.field == 'comments') {
            me.getCommentPanel().handleExpand();
            return;
        }
        me.record = context.record;
        me.loadCommentPanel();
    },

    /**
    * handles expand of comment panel, reloads store if needed
    * @param {Ext.panel.Panel} pan
    */
    loadCommentPanel: function(pan) {
        var me = this,
            rec = me.record,
            id = rec && rec.get('id'),
            form = me.getCommentForm();
            
        if(!form || !id){
            return;
        }
        
        //jump out here if comments already loaded for this segment.
        if(me.getLoadedSegmentId && me.getLoadedSegmentId == id) {
            return;
        }
        me.openCommentWindow(rec);
    },

    openCommentWindow: function(rec) {
        var me = this,
            store = me.getCommentsStore(),
            id = rec.get('id'),
            commentPanel=me.getCommentPanel(),
            panelController = commentPanel.getController();
        
        panelController.clearComments();

        if(commentPanel.collapsed) {
            return; //collapsed no data load needed
        }
        
        panelController.handleAddComment();

        store.load({
            params: {segmentId: id}
        });
        panelController.loadedSegmentId = id;
    },

    /**
     * updates the tooltip in the comment displayfield
     */
    updateEditorComment: function(field, val) {
        if(field.tooltip) {
            field.tooltip.update(val);
            field.tooltip.setDisabled(!val);
        }
        else {
            field.tooltip = val;
        }
    },

    /**
     * If the global open comment request is triggered, this function tries to load the comments into 
     * the expandable comment panel in the metapanel
     * @param {Editor.models.Segment} optional, the affected segment if the calling code nows it
     */
    onRequestOpenComments: function(segment){
        var me = this,
            isForced = me.isEnabledForLocked(),
            ed = this.getEditPlugin(),
            panel = me.getCommentPanel(),
            inMetapanel = panel && panel.up('#metapanel'),
            readOnlyMode = panel && panel.lookupViewModel().get('editorIsReadonly');

        //remove current segment
        me.record = null;
        
        //use given segment record or get the current used one
        segment = (segment && segment.isModel ? segment : me.getCurrentSegment());
        
        //if we can not find any segment, we can not open the comments
        if(!segment) {
            return false; //stop further comment open handling
        }
        
        //do nothing is segment is not editable or task is readOnly unless isForced is true
        if(!isForced && (!segment.get('editable') || readOnlyMode)) {
            return false; //stop further comment open handling
        }
        //this reference is needed in general, since this controller is used also for comment invocations other as via comment panel 
        me.record = segment;

        //start segment editing if possible
        if(!ed.editing) {
            if(segment.get('editable') && !readOnlyMode) {
                ed.startEdit(segment, me.getSegmentGrid().getView().getHeaderAtIndex(0));
            }
            //otherwise open MetaPanel readonly
            else {
                inMetapanel && Editor.app.getController('MetaPanel').openReadonly(segment);
            }
        }
        
        //if comment panel does not exist or is not in metapanel, we can not handle open comments here
        if(! inMetapanel) {
            return;
        }
    
        var form = me.getCommentForm(),
            area = form.down('textarea');

        //expand the comment panel if possible
        if(panel.isCollapsable && panel.collapsed){
            panel.expand();
            return;
        }

        //set the focus of the text area
        if (area.rendered && area.isVisible()) {
            if(area.hasFocus) {
                ed.editing && ed.editor.mainEditor.deferFocus();
            }
            else {
                area.focus(false, 500);
            }
        }
    },

    /** 
     * return the currently used segment based on roweditor or row selection.
     */
    getCurrentSegment: function() {
        var ed = this.getEditPlugin(),
            selection = this.getSegmentGrid().getSelection();

        //get segment, if no segment was given as parameter:
        if(ed.editing && ed.context.record) {
            return ed.context.record;
        }
        if(selection && selection.length > 0) {
            return selection[0];
        }
        return null;
    },
    
    getLoadedSegmentId:function(){
        return this.getCommentPanel().getController().loadedSegmentId;
    },
});