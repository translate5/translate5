/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
    ref: 'commentWindow',
    selector: '#commentWindow'
  },{
      ref: 'rowEditor',
      selector: '#roweditor'
  },{
    ref: 'commentForm',
    selector: '#commentForm'
  },{
      ref: 'commentAddBtn',
      selector: '#commentAddBtn'
  },{
      ref: 'closeBtn',
      selector: '#commentWindow #closeBtn'
  },{
      ref: 'commentDisplay',
      selector: '#roweditor .displayfield[name=comments]'
  }],
  loadedSegmentId: null,
  runningRequests: 0,
  init : function() {
      var me = this;
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following event bindings 
      Editor.app.on('editorViewportClosed', me.clearComments, me);
      me.control({
          '#commentWindow' : {
              beforeshow: me.initWindow,
              beforeclose: function() {
                  return this.runningRequests <= 0;
              }
          },
          '#segmentgrid' : {
              itemclick: me.handleCommentsColumnClick
          },
          '#roweditor .displayfield[name=comments]': {
              change: me.updateEditorComment
          },
          '#editorCommentBtn' : {
              click: me.handleEditorCommentBtn
          },
          '#commentWindow .actioncolumn' : {
              click: me.handleGridAction
          },
          '#commentWindow .grid' : {
              itemdblclick: me.handleGridDblClick
          },
          '#commentWindow #closeBtn' : {
              click: me.handleWindowClose
          },
          '#commentWindow #saveBtn' : {
              click: me.handleCommentSave
          },
          '#commentWindow #cancelBtn' : {
              click: me.handleCommentCancel
          },
          '#commentWindow #commentAddBtn' : {
              click: me.handleAddComment
          }
      });
      me.getCommentsStore().on('write', me.handleCommentsChanged, me);
      me.getCommentsStore().getProxy().on('exception', me.removeRequest, me);
  },
  initWindow: function () {
      var me = this;
      me.getCommentForm().getForm().reset();
      me.handleAddComment();
  },
  handleWindowClose: function () {
      this.getCommentWindow().close();
  },
  clearComments: function() {
      this.getCommentsStore().removeAll();
  },
  /**
   * loads the clicked comment into the comment form
   * @param {Ext.grid.View} view
   * @param {Editor.model.Comment} rec
   */
  handleGridDblClick: function(view, rec) {
      var me = this,
          form = me.getCommentForm(),
          area = form.down('textarea');
      if(rec.get('isEditable')){
          me.getCommentAddBtn().disable();
          form.enable();
          form.loadRecord(rec);
          area.selectText();
          area.focus(false, 500);
      }
  },
  /**
   * saves comment to server
   */
  handleCommentSave: function() {
      var me = this,
          store = me.getCommentsStore(),
          form = me.getCommentForm(),
          rec = form.getRecord();
      me.addRequest();
      form.getForm().updateRecord(rec);
      if(!rec.store){
          store.insert(0, rec);
      }
      me.getCommentAddBtn().enable();
      me.getCommentForm().disable();
  },
  handleCommentCancel: function() {
      var me = this;
      me.getCommentAddBtn().enable();
      me.getCommentForm().disable();
  },
  /**
   * creates a new record and loads it into the form
   */
  handleAddComment: function() {
      var me = this, 
          now = new Date(),
          form = me.getCommentForm(),
          rec = me.getCommentsStore().model.create({
              modified: now,
              created: now,
              segmentId: me.loadedSegmentId
          });
      rec.phantom = true;
      
      me.getCommentAddBtn().disable();
      form.enable();
      form.loadRecord(rec);
      form.down('textarea').focus(false, 500);
  },
  /**
   * Handles the action column clicks
   */
  handleGridAction: function(view, td, rowIdx, cellIdx, ev) {
      var me = this,
          rec = me.getCommentsStore().getAt(rowIdx),
          del = ev.getTarget('img.ico-comment-delete'),
          edit = ev.getTarget('img.ico-comment-edit'),
          form = me.getCommentForm(),
          area = form.down('textarea');
      if(!rec.get('isEditable')){
          return;
      }
      if(del) {
          me.getCommentWindow().showDeleteConfirm(function(btn){
              if(btn == 'yes') {
                  me.deleteComment(rec);
              }
          });
          return;
      }
      if(edit) {
          me.getCommentAddBtn().disable();
          form.enable();
          form.loadRecord(rec);
          area.selectText();
          area.focus(false, 500);
          return;
      }
  },
  deleteComment: function(rec) {
      this.addRequest();
      rec.store.remove(rec);
  },
  /**
   * handle clicks on the comment column of the grid.
   * For handling only selected rows we have to use the img as clicktarget. 
   *   Native way would be checking the select state of column. But this is not sufficient, 
   *   because row is first selected, then the click event will be processed. This means click processing on every row.
   */
  handleCommentsColumnClick: function(view, rec, tr, idx, ev) {
      var me = this,
          add = ev.getTarget('img.add'),
          edit = ev.getTarget('img.edit');
      if(add || edit) {
          me.openCommentWindow(rec);
      }
  },
  /**
   * prevents the window to be closed unless all requests are saved
   */
  addRequest: function() {
      var btn = this.getCloseBtn();
      this.runningRequests++;
      btn.setIconCls('ico-loading');
      btn.disable();
  },
  /**
   * removes a request from the request stack.
   * enables the window closing if no requests are running.
   */
  removeRequest: function() {
      var btn = this.getCloseBtn();
      this.runningRequests--;
      if(this.runningRequests <= 0) {
          btn.setIconCls('');
          btn.enable();
          this.runningRequests = 0;
      }
  },
  /**
   * Opens a Commen window to the given Segment Id
   * @param {Editor.model.Segment} rec
   */
  openCommentWindow: function(rec) {
      var me = this,
          store = me.getCommentsStore(),
          id = rec.get('id'),
          win;
      store.removeAll();
      store.load({
          params: {segmentId: id}
      });
      me.loadedSegmentId = id;
      win = me.getCommentWindow();
      if(!win) {
          win = Ext.widget('commentWindow');
      }
      win.updateInfoText(rec);
      win.show();
  },
  /**
   * updates the comments column in the grid
   * @param {Ext.data.Store} store
   * @param {Ext.data.Operation} op
   */
  handleCommentsChanged: function(store, op) {
      var me = this, 
          rec = null,
          segId = 0;
      if(op.records && op.records.length > 0) {
          rec = op.records[0];
          segId = rec.get('segmentId');
      }
      if(! rec) {
          me.removeRequest();
          return;
      }
      Editor.model.Segment.load(segId, {
          failure: function() {
              me.removeRequest();
          },
          success: function(rec, op) {
              var dis = me.getCommentDisplay(),
              ed = me.getRowEditor(),
              origRec = me.getSegmentsStore().getById(segId);
              //we cant update the complete segment, since this would overwrite unsaved 
              //changes the user has made in the opened segment
              origRec.beginEdit();
              origRec.set('autoStateId', rec.get('autoStateId'));
              origRec.set('workflowStep', rec.get('workflowStep'));
              origRec.set('comments', rec.get('comments'));
              delete origRec.modified.autoStateId;
              delete origRec.modified.workflowStep;
              origRec.endEdit();
              if(ed && ed.context) {
                  //update the context of the editor, because the set comments above changes the grid view
                  ed.context.row = me.getSegmentGrid().getView().getNode(origRec);
                  ed.reposition();
                  try {
                      //very few times some part of the following object path is not defined. 
                      // since its not reproducable we will try / catch it
                      dis.setRawValue(ed.context.column.self.getFirstComment(rec.get('comments')));
                  }
                  catch(e){}
              }
              me.removeRequest();
          }
      });
  },
  /**
   * Handles the click on the button in the comment displayfield
   */
  handleEditorCommentBtn: function() {
      var me = this,
          rec = me.getRowEditor().editingPlugin.openedRecord;
      me.openCommentWindow(rec);
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