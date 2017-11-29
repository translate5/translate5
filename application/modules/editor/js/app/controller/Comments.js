
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
                
                //here must be done different stuff, depending if commentspanel is in the window or not
                '#editorCommentBtn' : {
                    click: 'handleEditorCommentBtn'
                },

                '#metapanel #commentPanel' : {
                    expand: 'loadCommentPanel'
                },
            },
            controller:{
                '#Comments':{
                    editorCommentBtnClick:'handleEditorCommentBtnClick'
                },
                '#Editor': {
                    openComments: 'handleEditorCommentBtnClick'
                }
            }
    },
    
    cancelEdit: function() {
        var me=this,
            panel =me.getCommentPanel();
        
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
    handleCommentsColumnClick: function(view, rec, tr, idx, ev) {
        var me = this,
            ed = me.getEditPlugin(),
            add = ev.getTarget('img.add'),
            edit = ev.getTarget('img.edit'),
            mpController = Editor.app.getController('MetaPanel');
        me.record = null;
        
        if(!me.getCommentPanel()){
            return;
        }
        
        me.getCommentPanel().handleCollapse();
        
        if(!rec.get('editable') && !me.isEnabledForLocked()) {
            return;
        }
        
        if(add || edit) {
            if(rec.get('editable')) {
                ed.startEdit(rec, view.getHeaderAtIndex(0));
            }
            else {
                me.record = rec;
                mpController.openReadonly(rec);
            }
            me.handleEditorCommentBtn();
            return;
        }
        //close metapanel if clicking single on a row, and the previous row was not editable
        if(!ed.editing || !ed.context.record) {
            mpController.cancelEdit();
        }
    },
    /**
     * Opens comment column for not editable segments on dblclick on comments column
     */
    handleCommentsColumnDblClick: function (view, rec, tr, idx, ev) {
        var me = this,
            isCommentsCol = ev.getTarget('td.comments-field'),
            isForced = me.isEnabledForLocked();
        
        if(rec.get('editable') || !isCommentsCol || !isForced) {
            return;
        }
        me.record = rec;
        me.getCommentPanel().handleCollapse();
        Editor.app.getController('MetaPanel').openReadonly(rec);
        me.getCommentPanel().handleExpand();
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
            plug = me.getEditPlugin(),
            rec = plug.editing && plug.context.record || me.record,
            id = rec && rec.get('id'),
            box = me.getCommentContainer(),
            form = me.getCommentForm();
            
        if(!form){
            return;
        }
        
        if(form._enabled) {
            form.enable();
        }

        if(!id) {
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
            form = me.getCommentForm(),
            area = form.down('textarea'),
            store = me.getCommentsStore(),
            id = rec.get('id'),
            commentPanel=me.getCommentPanel(),
            panelController = commentPanel.getController();
        
        panelController.clearComments();

        if(commentPanel.collapsed) {
            return; //collapsed no data load needed
        }
        
        panelController.handleAddComment();

        if(area.rendered && area.isVisible()) {
            new Ext.util.DelayedTask(function(){
                area.selectText();
                area.focus();
            }).delay(100);
        }
        
        store.load({
            params: {segmentId: id}
        });
        panelController.loadedSegmentId = id;
    },

    /**
     * Handles the click on the button in the comment displayfield
     */
    handleEditorCommentBtn: function() {
        this.fireEvent('editorCommentBtnClick');
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

    handleEditorCommentBtnClick:function(){
        var me = this,
            commentPanel = me.getCommentPanel();
        
        if(!commentPanel){
            return;
        }
        
        var form = me.getCommentForm(),
            area = form.down('textarea');

        if(!commentPanel.isCollapsable){
            return;
        }

        if (commentPanel.collapsed)
        {
            commentPanel.expand();
        }
        else
        {
            if (area.rendered && area.isVisible())
            {
                area.focus(false, 500);
            }
        }
    },

    getLoadedSegmentId:function(){
        return this.getCommentPanel().getController().loadedSegmentId;
    },
});