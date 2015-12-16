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
 * MetaPanel Controller
 * @class Editor.controller.MetaPanel
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Editor', {
  extend : 'Ext.app.Controller',
  messages: {
      segmentReset: '#UT#Das Segment wurde auf den ursprünglichen Zustand nach dem Import zurückgesetzt.',
      segmentNotBuffered: '#UT#Das angeforderte Segment liegt noch nicht im Zwischenspeicher. Bitte scrollen Sie manuell weiter!',
      gridEndReached: '#UT#Ende der Segmente erreicht!',
      gridStartReached: '#UT#Start der Segmente erreicht!'
  },
  refs : [{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  }],
  calledSaveMethod:false,
  isEditing: false,
  init : function() {
      var me = this;
      me.control({
      '#metapanel #cancelSegmentBtn' : {
        click : me.cancel
      },
      '#metapanel #saveSegmentBtn' : {
        click : me.save
      },
      '#metapanel #saveNextSegmentBtn' : {
        click : me.saveNext
      },
      '#metapanel #savePreviousSegmentBtn' : {
        click : me.savePrevious
      },
      '#metapanel #goAlternateLeftBtn' : {
          click : me.goToAlternate
      },
      '#metapanel #goAlternateRightBtn' : {
          click : me.goToAlternate
      },
      '#metapanel #goToLowerByWorkflowNoSaveBtn' : {
          click : me.goToLowerByWorkflowNoSave
      },
      '#metapanel #goToUpperByWorkflowNoSaveBtn' : {
          click : me.goToUpperByWorkflowNoSave
      },
      '#metapanel #goToLowerNoSaveBtn' : {
          click : me.goToLowerNoSave
      },
      '#metapanel #goToUpperNoSaveBtn' : {
          click : me.goToUpperNoSave
      },
      '#metapanel #saveNextByWorkflowBtn' : {
          click : me.saveNextByWorkflow
      },
      '#metapanel #resetSegmentBtn' : {
          click : me.resetSegment
      },
      'segmentsHtmleditor': {
          afteriniteditor: me.initEditor
      },
      '#segmentgrid': {
          afterrender: me.initEditPluginHandler
      }
    });
  },
  /**
   * track isEditing state 
   */
  initEditPluginHandler: function () {
      var me = this;
      me.getEditPlugin().on('beforeedit', function(){me.isEditing = true;});
      me.getEditPlugin().on('canceledit', function(){me.isEditing = false;});
      me.getEditPlugin().on('edit', function(){me.isEditing = false;})
  },
  /**
   * Gibt die RowEditing Instanz des Grids zurück
   * @returns Editor.view.segments.RowEditing
   */
  getEditPlugin: function() {
    return this.getSegmentGrid().editingPlugin;
  },
  /**
   * binds strg + enter as save segment combination
   * @param editor
   */
  initEditor: function(editor){
      var me = this,
          f = function() {},
          decDigits = [48, 49, 50, 51, 52, 53, 54, 55, 56, 57];
      
      f.prototype = Ext.Element.prototype;
      docEl = new f();
      docEl.dom = editor.getDoc();
      
      var map = new Ext.util.KeyMap(docEl, [{
          key: [10,13],
          ctrl: true,
          alt: false,
          scope: me,
          fn: me.saveNextByWorkflow
      }, {
          key: [10,13],
          ctrl: true,
          alt: true,
          scope: me,
          fn: me.saveNext
      }, {
          key: decDigits.slice(1),
          ctrl: true,
          alt: true,
          shift:false,
          scope: me,
          fn: function(key){
              var param = Number(key) - 48;
              me.fireEvent('changeState', param);
          }
      }, {
          key: Ext.EventObject.ESC,
          scope: me,
          fn: me.cancel
      }, {
          key: Ext.EventObject.LEFT,
          ctrl:true,
          alt: true,
          scope: me,
          fn: me.goToLeft
      }, {
          key: Ext.EventObject.RIGHT,
          ctrl:true,
          alt: true,
          scope: me,
          fn: me.goToRight
      }, {
          key: Ext.EventObject.PAGE_UP,
          ctrl:false,
          alt:true,
          scope: me,
          fn: me.goToUpperByWorkflowNoSave
      }, {
          key: Ext.EventObject.PAGE_DOWN,
          ctrl:false,
          alt:true,
          scope: me,
          fn: me.goToLowerByWorkflowNoSave
      }, {
          key: Ext.EventObject.UP,
          ctrl:true,
          alt: true,
          scope: me,
          fn: function(key, e){
              e.preventDefault();
              e.stopEvent();
              me.goToUpperNoSave();
          }
      }, {
          key: Ext.EventObject.DOWN,
          ctrl:true,
          alt: true,
          scope: me,
          fn: function(key, e){
              e.preventDefault();
              e.stopEvent();
              me.goToLowerNoSave();
          }
      }, {
          key: decDigits,
          ctrl: false,
          alt: true,
          shift:false,
          scope: me,
          fn: function(key, e){
              e.preventDefault();
              e.stopEvent();
              var param = Number(key) - 48;
              if (param == 0)
              {
                param = 10;
              }
              me.fireEvent('assignMQMTag', param);
          }
       }, {
          key: decDigits,
          ctrl: false,
          alt: true,
          shift:true,
          scope: me,
          fn: function(key, e){
              if(!me.isEditing) {
                  return;
              }
              e.preventDefault();
              e.stopEvent();
              var param = (Number(key) - 48) + 10;
              if (param == 10)
              {
                param = 20;
              }
              me.fireEvent('assignMQMTag', param);
          }
      }, {
          key: "C",
          ctrl:true,
          alt:true,
          fn: function(key, e){
              e.preventDefault();
              e.stopEvent();
              me.fireEvent('openComments');
          }
      }]);
  },
  /**
   * Handler for save Button
   */
  save: function() {
      var me = this,
          ed = me.getEditPlugin(),
          rec = ed.openedRecord;
      if(me.isEditing &&rec && rec.get('editable')) {
          me.fireEvent('saveSegment');
      }
  },
  /**
   * Cleaning up after editing segment
   * @param {Object} return data from moveToOtherRow
   */
  cleanupAfterRowEdit: function(ret) {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin();

      if (ret.isBorderReached)
      {
            Editor.MessageBox.addInfo(ret.errorText);
      }
      else if (ret.existsNextSegment)
      {
            //editing by selection handler must be disabled, otherwise saveChainStart will be triggered twice
            ed.disableEditBySelect = true;
            selModel.select(ret.newRec);
            Ext.defer(ed.startEdit, 300, ed, [ret.newRec, ret.lastColumnIdx]); //defer reduces problems with editorDomCleanUp see comment on Bug 38
            ed.disableEditBySelect = false;
            me.cancel();
      }
      else
      {
         Editor.MessageBox.addInfo(me.messages.segmentNotBuffered);
      }
  },
  /**
   * Moves to the next or previous row without saving current record
   * @param {Integer} direction of moving
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  moveToAdjacentRow: function(direction, errorText, isEditable) {
      var me = this,
          ret = null;
      
      if(!me.isEditing) {
          return;
      }

      ret = this.moveToOtherRow(direction, errorText, isEditable);
      me.cleanupAfterRowEdit(ret);
      return ret.existsNextSegment;
  },
  /**
   * Moves to the next row without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  goToLowerNoSave: function() {
      var me = this;
      me.moveToAdjacentRow(1, this.messages.gridEndReached);
  },
  /**
   * Moves to the next row without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  goToUpperNoSave: function() {
      var me = this;
      me.moveToAdjacentRow(-1, this.messages.gridStartReached);
  },
  /**
   * Moves to the next row with the same workflow value without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  goToLowerByWorkflowNoSave: function(key, e) {
      var me = this;
      e.preventDefault();
      e.stopEvent();
      return me.moveToAdjacentRow(1, this.messages.gridEndReached, this.workflowStepFilter);
  },
  /**
   * Moves to the previous row with the same workflow value without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  goToUpperByWorkflowNoSave: function(key, e) {
      var me = this;
      e.preventDefault();
      e.stopEvent();
      return me.moveToAdjacentRow(-1, this.messages.gridStartReached, this.workflowStepFilter);
  },
  /**
   * Handler for saveNext Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveNext: function() {
      if(!this.isEditing) {
          return;
      }
      this.calledSaveMethod = this.saveNext;
      return this.saveOtherRow(1, this.messages.gridEndReached);
  },
  /**
   * Handler for savePrevious Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  savePrevious: function() {
      if(!this.isEditing) {
          return;
      }
      this.calledSaveMethod = this.savePrevious;
      return this.saveOtherRow(-1, this.messages.gridStartReached);
  },
  /**
   * Handler for saveNext Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveNextByWorkflow: function() {
      if(!this.isEditing) {
          return;
      }
      this.calledSaveMethod = this.saveNext;
      return this.saveOtherRow(1, this.messages.gridEndReached, this.workflowStepFilter);
  },
  /**
   * Handler for savePrevious Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  savePreviousByWorkflow: function() {
      if(!this.isEditing) {
          return;
      }
      this.calledSaveMethod = this.savePrevious;
      return this.saveOtherRow(-1, this.messages.gridStartReached, this.workflowStepFilter);
  },
  /**
   * returns true if segment was not edited in the current step yet
   */
  workflowStepFilter: function(rec, newRec) {
      //our filtering stuff
      var stepNr = newRec.get('workflowStepNr');
      return stepNr == 0 || stepNr < Editor.data.task.get('workflowStep');
  },
  /**
   * Gets the next editable segment offset relative to param offset
   * @param integer offset
   **/
  getNextEditableSegmentOffset: function(offset, isEditable) {
      var me = this,
      grid = me.getSegmentGrid(),
      store = grid.store,
      origOffset = offset,
      rec = store.getAt(offset);
      
      isEditable = (Ext.isFunction(isEditable) ? isEditable : function(){ return true; });
      do
      {
          if (rec && rec.get('editable') && isEditable(rec))
          {
              return offset;
          }
          offset++;
          rec = store.getAt(offset);
      } while (rec);
      // no editable segment
      return origOffset;
  },
  /**
   * go to other row
   * @param {Integer} rowIdxChange positive or negative integer value to choose the index of the next row
   * @param {Function} isEditable optional, function which consumes a segment record, returns true if segment should be opened, false if not
   * @return {Object} to be used by saveOtherRow
   */
  moveToOtherRow: function(rowIdxChange, errorText, isEditable) {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin(),
          store = grid.store,
          rec = ed.openedRecord,
          ret = {
            'errorText'        : errorText,
            'existsNextSegment': false,
            'isBorderReached'  : false,
            'lastColumnIdx'    : 0,
            'newRec'           : store.getAt(store.indexOf(rec) + rowIdxChange)
          };
      isEditable = (Ext.isFunction(isEditable) ? isEditable : function(){ return true; });
      if(!rec || !rec.get('editable')) {
          return ret;
      }
      //checking always for segments editable flag + custom isEditable  
      while (ret.newRec && (!ret.newRec.get('editable') || !isEditable(rec, ret.newRec)))
      {
          ret.newRec = store.getAt(store.indexOf(ret.newRec) + rowIdxChange);
      }
      if(rowIdxChange > 0) {
          ret.isBorderReached = rec.get('id') == store.getLastSegmentId();
      }
      else {
          ret.isBorderReached = rec.get('id') == store.getFirstSegmentId();
      }
      Ext.Array.each(grid.columns, function(col, idx) {
          if(col.dataIndex == ed.editor.getEditedField()) {
              ret.lastColumnIdx = idx;
          }
      });
      ret.existsNextSegment = (ret.newRec !== undefined);
      return ret;
  },
  /**
   * save and go to other row
   * @param {Integer} rowIdxChange positive or negative integer value to choose the index of the next row
   * @param {String} errorText
   * @param {Function} isEditable optional, function which consumes a segment record, returns true if segment should be opened, false if not
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveOtherRow: function(rowIdxChange, errorText, isEditable) {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin(),
          ret = me.moveToOtherRow(rowIdxChange, errorText, isEditable);
          
      me.fireEvent('saveSegment', {
          scope: me,
          segmentUsageFinished: function(){
              me.cleanupAfterRowEdit(ret);
          }
      });
      return ret.existsNextSegment;
  },
  /**
   * Handler für cancel Button
   */
  cancel: function() {
    this.getEditPlugin().cancelEdit();
  },
  /**
   * Move the editor about one editable field
   */
  goToCustom: function(direction, saveRecord) {
    var me = this,
        info = me.getColInfo(),
        idx = info && info.foundIdx,
        cols = info && info.columns,
        store = me.getSegmentGrid().store,
        newRec = store.getAt(store.indexOf(me.getEditPlugin().openedRecord) + direction);
    
    if(info === false) {
      return;
    }
    
    //check if there exists a next/prev row, if not we dont need to move the editor.
    while(newRec && !newRec.get('editable')) {
        newRec = store.getAt(store.indexOf(newRec) + direction);
    }
    if(cols[idx + direction]) {
      info.plug.editor.changeColumnToEdit(cols[idx + direction]);
      return;
    }
    me.fireEvent('saveUnsavedComments');
    if(direction > 0) {
        //goto next segment and first col
        if(newRec) {
            info.plug.editor.changeColumnToEdit(cols[0]);
        }
        if (saveRecord)
        {
          me.saveNext();
        }
        else
        {
          me.goToLowerNoSave();
        }
    }
    else {
        //goto prev segment and last col
        if(newRec) {
            info.plug.editor.changeColumnToEdit(cols[cols.length - 1]);
        }
        if (saveRecord)
        {
          me.savePrevious();
        }
        else
        {
          me.goToUpperNoSave();
        }
    }
  },
  /**
   * Move the editor about one editable field
   */
  goToAlternate: function(btn, ev) {
    var me = this,
        direction = (btn.itemId == 'goAlternateLeftBtn' ? -1 : 1);
    me.goToCustom(direction, true);    
  },
  /**
   * Move the editor about one editable field left
   */
  goToLeft: function(key, e) {
    var me = this,
        direction = -1;
    if(!me.isEditing) {
        return;
    }
    e.preventDefault();
    e.stopEvent();
    me.goToCustom(direction, true);
  },
  /**
   * Move the editor about one editable field right
   */
  goToRight: function(key, e) {
    var me = this,
        direction = 1;
    if(!me.isEditing) {
        return;
    }
    e.preventDefault();
    e.stopEvent();
    me.goToCustom(direction, true);
  },
  /**
   * returns the visible columns and which column has actually the editor
   * @return {Object}
   */
  getColInfo: function() {
    var me = this,
        plug = me.getEditPlugin(),
        columns = me.getSegmentGrid().query('.contentEditableColumn:not([hidden])'),
        foundIdx = false,
        current = plug.editor.getEditedField();
    
    if(!plug || !plug.editor) {
        return false;
    }
    
    Ext.Array.each(columns, function(col, idx) {
      if(col.dataIndex == current) {
        foundIdx = idx;
      }
    });
    if(foundIdx === false) {
      Ext.Error.raise('current dataIndex not found in visible columns!');
      return false;
    }

    return {
      plug: plug,
      columns: columns,
      foundIdx: foundIdx
    };
  },
  /**
   * resets the htmleditor content to the original content
   */
  resetSegment: function() {
      var me = this,
          plug = me.getEditPlugin(),
          editor = plug.editor,
          rec = plug.openedRecord,
          columnToRead = editor.columnToEdit.replace(/Edit$/, '');
      Editor.MessageBox.addInfo(me.messages.segmentReset);
      editor.mainEditor.setValueAndMarkup(rec.get(columnToRead), rec.get('id'), editor.columnToEdit);
  }
});
