
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 * @class Editor.view.comments.PanelViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.comments.PanelViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.commentPanel',
    
    activeComment: null,
    loadedSegmentId: null,

    listen: {
        component: {
          '#metapanel #commentPanel' : {
              expand: 'loadCommentPanel'
          },
          '#commentPanel grid' : {
              itemdblclick: 'handleGridDblClick'
          },
          '#commentPanel actioncolumn' : {
              click: 'handleGridAction'
          },
          '#commentPanel #cancelBtn' : {
              click: 'onCancelBtnClick'
          }
        },
        controller:{
            '#Editor.$application': {
              editorViewportClosed: 'clearComments'
            },
            '#Comments':{
                editorCommentBtnClick:'handleEditorCommentBtnClick'
            },
            '#Editor': {
                openComments: 'handleEditorCommentBtnClick',
                saveUnsavedComments: 'onSaveBtnClick'
            }
        }
    },

    onSaveBtnClick:function(btn){
        var me = this,
            now = new Date(),
            store = me.getCommentsStore(),
            form = me.getCommentForm(),
            rec = me.activeComment,
            data = {
                segmentId: me.loadedSegmentId,
                comment: form.getForm().getValues().comment,
                modified: now
            };
        if (rec === null)
        {
            return;    
        }
        
        if(rec.phantom) {
            data.created = now;
            data.userName = Editor.data.app.user.userName;
        }

        rec.set(data);
        
        if(!rec.isModified('comment')) {
            rec.reject();
            me.handleAddComment();
            return;
        }
        
        form.disable();
        form._enabled = false;
        btn.setIconCls('ico-loading');
        
        rec.save({
            //prevent default ServerException handling
            preventDefaultHandler: true,
            callback: function(newrec, op) {
                var errorHandler = Editor.app.getController('ServerException');
                me.handleAddComment();
                //enabling the collapsed form gives a visual misbehaviour, so enable it by a own flag on expand
                me.getCommentPanel().collapsed || form.enable();
                form._enabled = true;
                btn.setIconCls('');
                if(op.wasSuccessful()) {
                    me.handleCommentsChanged(rec, 'save'); //rec from outer scope is needed!
                    return;
                }
                if(rec.phantom) {
                    store.remove(rec);
                }
                else {
                    rec.reject();
                }
                errorHandler.handleCallback.apply(errorHandler, arguments);
            }
        });
        
        if(!rec.store){
            store.insert(0, rec);
        }
    },

    
    onCancelBtnClick:function(){
        this.handleAddComment();
    },

    /**
     * loads the clicked comment into the comment form
     * @param {Ext.grid.View} view
     * @param {Editor.model.Comment} rec
     */
    handleGridDblClick: function(view, rec) {
        this.loadComment(rec);
    },

    /**
     * Handles the action column clicks
    */
    handleGridAction: function(view, td, rowIdx, cellIdx, ev) {
        var me = this,
            comments = me.getCommentsStore(),
            rec = comments.getAt(rowIdx),
            del = ev.getTarget('div.ico-comment-delete'),
            edit = ev.getTarget('div.ico-comment-edit');
        if(!rec.get('isEditable')){
            return;
        }
        if(edit) {
            me.loadComment(rec);
            return;
        }
        if(!del) {
            return;
        }
        me.getCommentPanel().showDeleteConfirm(function(btn){
            if(btn != 'yes') {
                return;
            }
            var id = rec.get('segmentId');
            rec.erase({
                //prevent default ServerException handling
                preventDefaultHandler: true,
                callback: function(nothing, op) {
                    var errorHandler = Editor.app.getController('ServerException');
                    //reload comments if they are still shown to the user
                    if(!op.wasSuccessful() && me.loadedSegmentId == id) {
                        comments.load({
                            params: {
                                    segmentId: id
                            }
                        });
                    }
                    if(op.wasSuccessful()) {
                        me.handleCommentsChanged(rec, 'destroy'); //rec from outer scope is needed!
                        return;
                    }
                    errorHandler.handleCallback.apply(errorHandler, arguments);
                }
            });
        });
    },

    /**
     * creates a new record and loads it into the form
     * @return {Editor.model.Comment} returns the newly created comment
    */
    handleAddComment: function() {
        var me = this, 
            rec = me.getCommentsStore().model.create({
                isEditable: true
            });
        rec.phantom = true;
        me.activeComment = rec;
        me.loadComment(rec);
        me.getCommentPanel().cancel();
    },
    
    /**
     * loads a single comment into the comment edit form, if editable
     * @param {Editor.model.Comment} rec
    */
    loadComment: function(rec) {
        var me = this,
            form = me.getCommentForm(),
            area = form.down('textarea'),
            commentPanel = me.getCommentPanel();
        if(! rec.get('isEditable')){
            return;
        }
        me.activeComment = rec;
        commentPanel.setComment(rec.get('comment'));

        if(commentPanel.isCollapsable && commentPanel.collapsed) {
            return; //collapsed no select / focus needed
        }
        if(area.rendered && area.isVisible()) {
            area.selectText();
            area.focus(false, 500);
        }
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
        if(me.loadedSegmentId && me.loadedSegmentId == id) {
            return;
        }
        me.openCommentWindow(rec);
    },

    /**
     * Opens a Commen window to the given Segment Id
     * @param {Editor.model.Segment} rec
    */
    openCommentWindow: function(rec) {
        var me = this,
            form = me.getCommentForm(),
            area = form.down('textarea'),
            store = me.getCommentsStore(),
            id = rec.get('id');
        
        me.clearComments();
        if(me.getCommentPanel().collapsed) {
            return; //collapsed no data load needed
        }
        
        me.handleAddComment();
        if(area.rendered && area.isVisible()) {
            area.selectText();
            area.focus(false, 500);
        }
        
        store.load({
            params: {segmentId: id}
        });
        me.loadedSegmentId = id;
    },

    /**
     * updates the comments column in the grid
     * @param {Editor.model.Comment} rec
     * @param {String} type change type: 'save' or 'destroy'
    */
    handleCommentsChanged: function(rec, type) {
        var me = this, 
            segId = rec && rec.get('segmentId'),
            comments = me.getCommentsStore(),
            comment = comments.getById(rec.get('id'));
        
        if(! segId) {
            return;
        }
        
        //if comment store was changed and restored in the meantime, 
        //  we have to add / edit / delete the record again
        if(type == 'save' && comment !== rec && segId == me.loadedSegmentId) {
            if(comment) {
                comment.set(rec.data);
                comment.commit();
            }
            else {
                comments.insert(0, rec);
            }
        }
        if(type == 'destroy' && comment && comment.get('id') == rec.get('id') && segId == me.loadedSegmentId) {
            comments.remove(comment);
        }
        
        Editor.model.Segment.load(segId, {
            success: function(rec, op) {
                var dis = me.getCommentDisplay(),
                    stateid = me.getAutoStateDisplay(),
                    ed = me.getRowEditor(),
                    origRec = me.getSegmentsStore().getById(segId);
                //if no origRec is given, do nothing here.
                if(!origRec) {
                    return;
                }
                //we cant update the complete segment, since this would overwrite unsaved 
                //changes the user has made in the opened segment
                origRec.beginEdit();
                origRec.set('autoStateId', rec.get('autoStateId'));
                origRec.set('workflowStep', rec.get('workflowStep'));
                origRec.set('comments', rec.get('comments'));
                origRec.endEdit();
                if(ed && ed.context && me.loadedSegmentId == segId) {
                    //update the context of the editor, because the set comments above changes the grid view
                    ed.context.row = me.getSegmentGrid().getView().getNode(origRec);
                    ed.reposition();
                    dis.setRawValue(Editor.view.segments.column.Comments.getFirstComment(rec.get('comments')));
                    stateid.setRawValue(ed.columns.get(stateid.id).renderer(rec.get('autoStateId')));
                }
            }
        });
    },

    handleEditorCommentBtnClick:function(){
        var me = this,
            commentPanel = me.getCommentPanel(),
            form = me.getCommentForm(),
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

    /**
     * clean up the loaded comments.
    */
    clearComments: function() {
        var me=this;

        me.loadedSegmentId = null;
        me.activeComment = null;
        me.getCommentsStore().removeAll();
        me.getCommentsStore().removed = [];
    },

    getCommentsStore:function(){
        return Ext.getStore('Comments');
    },

    getSegmentsStore:function(){
        return Ext.getStore('Segments');
    },

    getCommentForm:function(){
        return Ext.ComponentQuery.query('#commentForm')[0];
    },

    getCommentPanel:function(){
        return Ext.ComponentQuery.query('#commentPanel')[0];
    },

    getCommentDisplay:function(){
        return Ext.ComponentQuery.query('#roweditor displayfield[name=comments]')[0];
    },
    
    getAutoStateDisplay:function(){
        return Ext.ComponentQuery.query('#roweditor displayfield[name=autoStateId]')[0];
    },

    getRowEditor:function(){
        return Ext.ComponentQuery.query('#roweditor')[0];
    },

    getSegmentGrid:function(){
        return Ext.ComponentQuery.query('#segmentgrid')[0];
    },

    getEditPlugin: function() {
      var me = this,
          grid, ed;
          
      grid = me.getSegmentGrid();
      ed = grid.editingPlugin;
      return ed;
    },

    getCommentContainer:function(){
        return Ext.ComponentQuery.query('#commentPanel #commentContainer')[0];
    }


});