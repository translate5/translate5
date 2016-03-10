
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
 * Editor.controller.Comments encapsulates the comment functionality
 * @class Editor.controller.Comments
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Comments', {
  extend : 'Ext.app.Controller',
  models: ['Segment'],
  stores: ['Comments', 'Segments'],
  views: ['comments.Window'],
  refs : [{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  },{
      ref : 'saveBtn',
      selector : '#commentWindow #saveBtn'
  },{
    ref: 'commentWindow',
    selector: '#commentWindow'
  },{
      ref: 'commentContainer',
      selector: '#commentWindow #commentContainer'
  },{
      ref: 'rowEditor',
      selector: '#roweditor'
  },{
    ref: 'commentForm',
    selector: '#commentForm'
  },{
      ref: 'autoStateDisplay',
      selector: '#roweditor displayfield[name=autoStateId]'
  },{
      ref: 'commentDisplay',
      selector: '#roweditor displayfield[name=comments]'
  }],
  activeComment: null,
  loadedSegmentId: null,
  init : function() {
      var me = this;
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following event bindings 
      Editor.app.on('editorViewportClosed', me.clearComments, me);
      me.control({
          '#commentWindow' : {
              expand: me.expandWindow
          },
          '#segmentgrid' : {
              itemdblclick: me.handleCommentsColumnDblClick,
              itemclick: me.handleCommentsColumnClick,
              afterrender: me.initEditPluginHandler
          },
          '#roweditor': {
              afterEditorMoved: me.onEditorMoved
          },
          '#roweditor displayfield[name=comments]': {
              change: me.updateEditorComment
          },
          '#editorCommentBtn' : {
              click: me.handleEditorCommentBtn
          },
          '#commentWindow actioncolumn' : {
              click: me.handleGridAction
          },
          '#commentWindow grid' : {
              itemdblclick: me.handleGridDblClick
          },
          '#commentWindow #saveBtn' : {
              click: me.handleCommentSave
          },
          '#commentWindow #cancelBtn' : {
              click: me.handleAddComment
          }
      });
  },
  initEditPluginHandler: function() {
      var me = this,
          edCtrl = me.application.getController('Editor');
          
      edCtrl.on('openComments', me.handleEditorCommentBtn, me);
      edCtrl.on('saveUnsavedComments', me.handleCommentSave, me);
      
    //Diese Events können erst in onlauch gebunden werden, in init existiert das Plugin noch nicht
    //FIXME ext6 disabled
      //me.getEditPlugin().on('beforeedit', me.onStartEdit, me);
      //me.getEditPlugin().on('canceledit', me.cancelEdit, me);
      //me.getEditPlugin().on('edit', me.cancelEdit, me);
  },
  cancelEdit: function() {
      this.handleAddComment();
  },
  getEditPlugin: function() {
      var me = this,
          grid, ed;
          
      grid = me.getSegmentGrid();
      ed = grid.editingPlugin;
      return ed;
  },
  /**
   * clean up the loaded comments.
   */
  clearComments: function() {
      this.loadedSegmentId = null;
      this.activeComment = null;
      this.getCommentsStore().removeAll();
      this.getCommentsStore().removed = [];
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
   * saves comment to server
   */
  handleCommentSave: function() {
      var me = this,
          now = new Date(),
          btn = me.getSaveBtn(),
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
          callback: function(newrec, op) {
              var errorHandler = Editor.app.getController('ServerException');
              me.handleAddComment();
              //enabling the collapsed form gives a visual misbehaviour, so enable it by a own flag on expand
              me.getCommentWindow().collapsed || form.enable();
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
      me.getCommentWindow().cancel();
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
      me.getCommentWindow().showDeleteConfirm(function(btn){
          if(btn != 'yes') {
              return;
          }
          var id = rec.get('segmentId');
          rec.store.remove(rec);
          rec.destroy({
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
   * loads a single comment into the comment edit form, if editable
   * @param {Editor.model.Comment} rec
   */
  loadComment: function(rec) {
      var me = this,
          form = me.getCommentForm(),
          area = form.down('textarea');
      if(! rec.get('isEditable')){
          return;
      }
      me.activeComment = rec;
      me.getCommentWindow().setComment(rec.get('comment'));
      if(me.getCommentWindow().collapsed) {
          return; //collapsed no select / focus needed
      }
      if(area.rendered && area.isVisible()) {
          area.selectText();
          area.focus(false, 500);
      }
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
      me.getCommentWindow().collapse();
      
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
      me.getCommentWindow().collapse();
      Editor.app.getController('MetaPanel').openReadonly(rec);
      me.getCommentWindow().expand();
  },
  /**
   * handles starting the segment editor
   * @param {String} toEdit field which is really be edited by the editor 
   * @param {Editor.view.segments.RowEditor} editor 
   */
  onEditorMoved: function(toEdit, editor) {
      var me = this;
      if(editor.columnClicked == 'comments' && me.getCommentWindow().collapsed) {
          me.getCommentWindow().expand();
      }
  },
  /**
   * handles starting the segment editor
   * @param {Object} context
   */
  onStartEdit: function(context) {
      var me = this,
          isOnStartEdit = context.field && !context.isPanel;
            //opens the commentpanel if the editor was started by clicking on the comment column 
      if(isOnStartEdit && context.field == 'comments' && me.getCommentWindow().collapsed) {
          me.getCommentWindow().expand();
          return;
      }
      me.expandWindow();
  },
  /**
   * handles expand of comment panel, reloads store if needed
   * @param {Ext.panel.Panel} pan
   */
  expandWindow: function(pan) {
      var me = this,
          plug = me.getEditPlugin(),
          rec = plug.editing && plug.context.record || me.record,
          id = rec && rec.get('id'),
          box = me.getCommentContainer(),
          form = me.getCommentForm();
          
      if(form._enabled) {
          form.enable();
      }
      if(!id) {
          box.hide();
          return;
      }
      box.show();
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
      if(me.getCommentWindow().collapsed) {
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
  /**
   * Handles the click on the button in the comment displayfield
   */
  handleEditorCommentBtn: function() {
      var me = this,
          win = me.getCommentWindow(),
          form = me.getCommentForm(),
          area = form.down('textarea');
      if (win.collapsed)
      {
          win.expand();
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
  }
});