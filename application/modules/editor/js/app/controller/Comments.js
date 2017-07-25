
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
      selector : '#commentPanel #saveBtn'
  },{
    ref: 'commentPanel',
    selector: '#commentPanel'
  },{
      ref: 'commentContainer',
      selector: '#commentPanel #commentContainer'
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
      var me = this,
          commentPanel=me.getCommentPanel();

      if(!commentPanel){
          return;
      }

      //opens the commentpanel if the editor was started by clicking on the comment column
      if(context.field && context.field == 'comments') {
          me.getCommentPanel().handleExpand();
          return;
      }
      me.record = context.record;
      commentPanel.getController().loadCommentPanel();
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
  }
});