
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
          '#commentPanel grid' : {
              itemdblclick: 'handleGridDblClick'
          },
          '#commentPanel actioncolumn' : {
              click: 'handleGridAction'
          },
          '#commentPanel #closeBtn' : {
              click: 'onCloseBtnClick'
          }
        },
        controller:{
            '#Editor.$application': {
              editorViewportClosed: 'clearComments'
            },
            '#Editor': {
                saveUnsavedComments: 'handleSaveUnsavedComments'
            }
        }
    },

    /***
     * Comment panel expand event handler
     */
    onCommentPanelExpand:function(){
        var me=this,
            form = me.getCommentForm(),
            area = form.down('textarea');
        if(area.rendered && area.isVisible()) {
            area.selectText();
            area.focus(false, 500);
        }
    },

    /**
     * Interceptor Method to call saveComment. 
     * Since saveComment returns boolean it can stop the event loop, which is not wanted, 
     * so we cannot use it directly as handlerMethod 
     */
    handleSaveUnsavedComments: function() {
        this.saveComment();
    },
    
    /**
     * @return {Boolean} true if save request started, false if not
     */
    saveComment:function(){
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
        
        if (rec === null) {
            return false;    
        }
        
        if(rec.phantom) {
            data.created = now;
            data.userName = Editor.data.app.user.userName;
        }

        rec.set(data);
        
        if(!rec.isModified('comment')) {
            rec.reject();
            me.handleAddComment();
            return false;
        }
        
        form.setLoading();
        
        rec.save({
            //prevent default ServerException handling
            preventDefaultHandler: true,
            callback: function(newrec, op) {
                var errorHandler = Editor.app.getController('ServerException');
                form.setLoading(false);
                me.handleAddComment();
                //enabling the collapsed form gives a visual misbehaviour, so enable it by a own flag on expand
                if(op.wasSuccessful()) {
                    me.handleCommentsChanged(rec, 'save'); //rec from outer scope is needed!
                    me.getView() && me.getView().fireEvent('requestCommentClose', me.getCommentPanel());
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
        return true;
    },
    
    onCloseBtnClick:function(){
        var me=this,
            commentPanel=me.getCommentPanel();
        if(!commentPanel){
            return;
        }
        me.handleAddComment();
        me.getView().fireEvent('requestCommentClose', commentPanel);
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
            del = ev.getTarget('div.deleteCommentClass'),
            edit = ev.getTarget('div.editCommentClass');
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
        if(me.destroyed) {
            return;
        }
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
        if(this.destroyed){
           return; 
        }
        var me = this,
            commentPanel = me.getCommentPanel();
            
        if(! rec.get('isEditable')){
            return;
        }
        me.activeComment = rec;
        commentPanel.setComment(rec.get('comment'));

        if(commentPanel.isCollapsable && commentPanel.collapsed) {
            return; //collapsed no select / focus needed
        }
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
                origRec.commit();
                if(ed && ed.context && me.loadedSegmentId == segId) {
                    //update the context of the editor, because the set comments above changes the grid view
                    ed.context.row = me.getSegmentGrid().getView().getNode(origRec);
                    ed.reposition();
                    dis.setRawValue(Editor.view.segments.column.Comments.getFirstComment(rec.get('comments')));
                    //add true as additional parameter to trigger correct rendertype in the renderer
                    stateid.setRawValue(ed.columns.get(stateid.id).renderer(rec.get('autoStateId'),{},rec, true)); 
                }
            }
        });
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
    //FIXME get the refs
    getCommentForm:function(){
        return this.getView().down('form');
    },

    getCommentPanel:function(){
        return this.getView();
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