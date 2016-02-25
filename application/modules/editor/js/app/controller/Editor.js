
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
  requires: ['Editor.view.segments.EditorKeyMap'],
  messages: {
      segmentReset: '#UT#Das Segment wurde auf den ursprünglichen Zustand nach dem Import zurückgesetzt.',
      segmentNotBuffered: '#UT#Kein passendes Segment vorhanden bzw. im Zwischenspeicher gefunden. Bitte scrollen Sie manuell weiter!',
      gridEndReached: '#UT#Ende der Segmente erreicht!',
      gridStartReached: '#UT#Start der Segmente erreicht!',
      errorTitle: '#UT# Fehler bei der Segment Validierung!',
      correctErrorsText: '#UT# Fehler beheben',
      saveAnyway: '#UT# Trotzdem speichern'
  },
  id: 'editorcontroller',
  refs : [{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  },{
      ref : 'navi',
      selector : '#metapanel #naviToolbar'
  }],
  lastSaveOtherRowParameter:false,
  isEditing: false,
  keyMapConfig: null,
  listen: {
      component: {
          '#metapanel #watchSegmentBtn' : {
              click : 'toggleWatchSegment'
          },
          '#metapanel #cancelSegmentBtn' : {
              click : 'cancel'
          },
          '#metapanel #saveSegmentBtn' : {
              click : 'save'
          },
          '#metapanel #saveNextSegmentBtn' : {
              click : 'saveNext'
          },
          '#metapanel #savePreviousSegmentBtn' : {
              click : 'savePrevious'
          },
          '#metapanel #goAlternateLeftBtn' : {
              click : 'goToAlternate'
          },
          '#metapanel #goAlternateRightBtn' : {
              click : 'goToAlternate'
          },
          '#metapanel #goToLowerByWorkflowNoSaveBtn' : {
              click : 'goToLowerByWorkflowNoSave'
          },
          '#metapanel #goToUpperByWorkflowNoSaveBtn' : {
              click : 'goToUpperByWorkflowNoSave'
          },
          '#metapanel #goToLowerNoSaveBtn' : {
              click : 'goToLowerNoSave'
          },
          '#metapanel #goToUpperNoSaveBtn' : {
              click : 'goToUpperNoSave'
          },
          '#metapanel #saveNextByWorkflowBtn' : {
              click : 'saveNextByWorkflow'
          },
          '#metapanel #resetSegmentBtn' : {
              click : 'resetSegment'
          },
          'segmentsHtmleditor': {
              initialize: 'initEditor',
              contentErrors: 'handleSaveWithErrors'
          },
          '#segmentgrid': {
              afterrender: 'initEditPluginHandler'
          }
      }
  },
  init : function() {
      var me = this,
          decDigits = [48, 49, 50, 51, 52, 53, 54, 55, 56, 57];
      
      //set the default config
      me.keyMapConfig = {
          'ctrl-d':         ["D",{ctrl: true, alt: false}, me.toggleWatchSegment, true],
          'ctrl-s':         ["S",{ctrl: true, alt: false}, me.save, true],
          'ctrl-enter':     [[10,13],{ctrl: true, alt: false}, me.saveNextByWorkflow],
          'ctrl-alt-enter': [[10,13],{ctrl: true, alt: true, shift: false}, me.saveNext],
          'ctrl-alt-shift-enter': [[10,13],{ctrl: true, alt: true, shift: true}, me.savePrevious],
          'ctrl-alt-DIGIT': [decDigits.slice(1),{ctrl: true, alt: true, shift: false}, me.handleChangeState],
          'esc':            [Ext.EventObjectImpl.ESC, null, me.cancel],
          'ctrl-alt-left':  [Ext.EventObjectImpl.LEFT,{ctrl: true, alt: true}, me.goToLeft],
          'ctrl-alt-right': [Ext.EventObjectImpl.RIGHT,{ctrl: true, alt: true}, me.goToRight],
          'alt-pageup':     [Ext.EventObjectImpl.PAGE_UP,{ctrl: false, alt: true}, me.goToUpperByWorkflowNoSave],
          'alt-pagedown':   [Ext.EventObjectImpl.PAGE_DOWN,{ctrl: false, alt: true}, me.goToLowerByWorkflowNoSave],
          'alt-del':        [Ext.EventObjectImpl.DELETE,{ctrl: false, alt: true}, me.resetSegment],
          'ctrl-alt-up':    [Ext.EventObjectImpl.UP,{ctrl: true, alt: true}, me.goToUpperNoSave, true],
          'ctrl-alt-down':  [Ext.EventObjectImpl.DOWN,{ctrl: true, alt: true}, me.goToLowerNoSave, true],
          'ctrl-alt-c':     ["C",{ctrl: true, alt: true}, me.handleOpenComments, true],
          'alt-DIGIT':      [decDigits,{ctrl: false, alt: true}, me.handleAssignMQMTag, true],
          'F2':             [Ext.EventObjectImpl.F2,{ctrl: false, alt: false}, me.handleF2KeyPress, true]
      };
  },
  /**
   * track isEditing state 
   */
  initEditPluginHandler: function () {
      var me = this;
      me.getEditPlugin().on('beforeedit', function(){me.isEditing = true;});
      me.getEditPlugin().on('canceledit', function(){me.isEditing = false;});
      me.getEditPlugin().on('edit', function(){me.isEditing = false;})
      
      new Ext.util.KeyMap(Ext.getDoc(), me.getKeyMapConfig({
          'ctrl-alt-c':     ["C",{ctrl: true, alt: true}, function(key, e){
              var me = this;
              if(me.isEditing) {
                  me.handleOpenComments();
                  return;
              }
              e.preventDefault();
              e.stopEvent();
              var found = Ext.select('#segment-grid-body .x-grid-row-selected td.comments-field img').first();
              if(found && (found.hasCls('add') || found.hasCls('edit'))){
                  found.dom.click();
              }
          }],
      }));
  },
  /**
   * Gibt die RowEditing Instanz des Grids zurück
   * @returns Editor.view.segments.RowEditing
   */
  getEditPlugin: function() {
    return this.getSegmentGrid().editingPlugin;
  },
  /**
   * converts the here used simple keymap config to the fullblown KeyMap config
   * the simple config contains arrays with the following indizes:
   * 0: key
   * 1: special key config
   * 2: function to be called
   * 3: boolean, if true prepend event propagation stopper
   *
   * @param {Object} overwrite a config object for dedicated overwriting of key bindings
   */
  getKeyMapConfig: function(overwrite) {
      var me = this,
          conf = [];
      Ext.Object.each(me.keyMapConfig, function(key, item){
          //applies if available the overwritten config instead the default one
          if(overwrite && overwrite[key]) {
              item = overwrite[key];
          }
          if(!item) {
              return;
          }
          //applies the keys config and scope to a fresh conf object
          var confObj = Ext.applyIf({
              key: item[0],
              scope: me
          }, item[1]);
          if(item[3]) {
              //prepends the event propagation stopper
              confObj.fn = function(key, e) {
                  e.preventDefault();
                  e.stopEvent();
                  item[2].apply(confObj.scope, arguments);
              }
          }
          else {
              confObj.fn = item[2];
          }
          conf.push(confObj);
      });
      return conf;
  },
  /**
   * binds strg + enter as save segment combination
   * @param {Editor.view.segments.HtmlEditor} editor
   */
  initEditor: function(editor){
      var me = this,
          docEl = Ext.get(editor.getDoc());

      new Editor.view.segments.EditorKeyMap({
        target: docEl,
        binding: me.getKeyMapConfig()
      });
  },
  /**
   * Handler for save Button
   */
  save: function() {
      var me = this,
          ed = me.getEditPlugin(),
          rec = ed.openedRecord;
      if(me.isEditing &&rec && rec.get('editable')) {
          me.fireEvent('saveUnsavedComments');
          me.fireEvent('saveSegment');
      }
  },
  /**
   * Cleaning up after editing segment
   * @param {Object} return data from calculateNextRow
   */
  openNextRow: function(ret) {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin();

      if (ret.existsNextSegment) {
            //editing by selection handler must be disabled, otherwise saveChainStart will be triggered twice
            ed.disableEditBySelect = true;
            selModel.select(ret.newRec);
            Ext.defer(ed.startEdit, 300, ed, [ret.newRec, ret.lastColumnIdx]); //defer reduces problems with editorDomCleanUp see comment on Bug 38
            ed.disableEditBySelect = false;
            return;
      }
      
      if (ret.isBorderReached) {
          Editor.MessageBox.addInfo(ret.errorText);
      }
      else {
          Editor.MessageBox.addInfo(me.messages.segmentNotBuffered);
      }
  },
  /**
   * Moves to the next or previous row without saving current record
   * @param {Integer} direction of moving
   * @param {String} error message if there is no more segment to move the editor
   * @param {Function} filter function for the workflow step
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  moveToAdjacentRow: function(direction, errorText, filter) {
      var me = this,
          ret = null;
      
      if(!me.isEditing) {
          return;
      }

      ret = this.calculateNextRow(direction, errorText, filter);
      me.cancel();
      me.openNextRow(ret);
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
      return me.moveToAdjacentRow(1, me.messages.gridEndReached, me.workflowStepFilter);
  },
  /**
   * Moves to the previous row with the same workflow value without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  goToUpperByWorkflowNoSave: function(key, e) {
      var me = this;
      e.preventDefault();
      e.stopEvent();
      return me.moveToAdjacentRow(-1, me.messages.gridStartReached, me.workflowStepFilter);
  },
  /**
   * Handler for saveNext Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveNext: function() {
      return this.saveOtherRow(1, this.messages.gridEndReached);
  },
  /**
   * Handler for savePrevious Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  savePrevious: function() {
      return this.saveOtherRow(-1, this.messages.gridStartReached);
  },
  /**
   * Handler for saveNext Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveNextByWorkflow: function() {
      return this.saveOtherRow(1, this.messages.gridEndReached, this.workflowStepFilter);
  },
  /**
   * Handler for savePrevious Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  savePreviousByWorkflow: function() {
      return this.saveOtherRow(-1, this.messages.gridStartReached, this.workflowStepFilter);
  },
  /**
   * returns true if segment was not edited by the current role yet
   */
  workflowStepFilter: function(rec, newRec) {
      var role = Editor.data.task.get('userRole') || 'pm',
          map = Editor.data.segments.roleAutoStateMap;
      if(!map[role]) {
          return true;
      }
      return map[role].indexOf(newRec.get('autoStateId')) < 0;
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
   * @param {String} error message if no more editable segment found
   * @param {Function} isEditable optional, function which consumes a segment record, returns true if segment should be opened, false if not
   * @return {Object} to be used by saveOtherRow
   */
  calculateNextRow: function(rowIdxChange, errorText, isEditable) {
      var me = this,
          grid = me.getSegmentGrid(),
          ed = me.getEditPlugin(),
          store = grid.store,
          rec = ed.openedRecord,
          ret = {
            'errorText'        : errorText,
            'existsNextSegment': false,
            'isBorderReached'  : false,
            'lastColumn'       : null,
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
              ret.lastColumn = col;
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
          ret = me.calculateNextRow(rowIdxChange, errorText, isEditable);
      
      if(!me.isEditing) {
          return;
      }
      //store the arguments to recall me on handleSaveWithErrors callback
      me.lastSaveOtherRowParameter = [rowIdxChange, errorText, isEditable];
          
      me.fireEvent('saveUnsavedComments');
      
      me.fireEvent('saveSegment', {
          scope: me,
          segmentUsageFinished: function(){
              me.openNextRow(ret);
          }
      });
      return ret.existsNextSegment;
  },
  /**
   * @param {Editor.view.segments.HtmlEditor} editor
   * @param {String} msg
   */
  handleSaveWithErrors: function(editor, msg){
      var me = this,
          msgBox;
      
      //if there was an empty message we assume that there was no error,
      //therefore we can delete the last saveOtherRow parameters
      if(!msg) {
          me.lastSaveOtherRowParameter = null;
          return;
      }
      
      msgBox = Ext.create('Ext.window.MessageBox', {
          buttonText:{
              yes: me.messages.correctErrorsText,
              no: me.messages.saveAnyway
          }
      });
      msgBox.confirm(me.messages.errorTitle, msg, function(btn) {
          if(btn == 'yes') {
              return;
          }
          me.saveAndIgnoreContentErrors();
      },me);
  },
  /**
   * triggers the save chain but ignoring htmleditor content errors then
   */
  saveAndIgnoreContentErrors: function() {
      //FIXME either make a default save call (see below) or trigger the initial called saveMethod again
      var me = this,
          plug = me.getEditPlugin();
      plug.editor.mainEditor.disableContentErrorCheckOnce();
      if(me.lastSaveOtherRowParameter){
          me.saveOtherRow.apply(me, me.lastSaveOtherRowParameter);
          return;
      }
      me.save();
  },
  /**
   * Handler für cancel Button
   */
  cancel: function() {
    this.getEditPlugin().cancelEdit();
  },
  /**
   * Handles pressing the keyboard shortcuts for changing the segment state
   */
  handleChangeState: function(key) {
      var param = Number(key) - 48;
      this.fireEvent('changeState', param);
  },
  /**
   * Handles pressing the comment keyboard shortcut
   */
  handleOpenComments: function(key) {
      this.fireEvent('openComments');
  },
  /**
   * Handles pressing the MQM tag shortcuts, without shift 1-10, with shift 11-20
   */
  handleAssignMQMTag: function(key, e) {
      var me = this;
      if(!me.isEditing) {
          return;
      }
      e.preventDefault();
      e.stopEvent();
      var param = Number(key) - 48;
      if (param == 0) {
          param = 10;
      }
      if(e.shiftKey) {
          param = param + 10;
      }
      me.fireEvent('assignMQMTag', param);
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
    if(direction > 0) {
        //goto next segment and first col
        if(newRec) {
            info.plug.editor.changeColumnToEdit(cols[0]);
        }
        if (saveRecord) {
          me.saveNext();
        }
        else {
          me.goToLowerNoSave();
        }
        return;
    }
    //goto prev segment and last col
    if(newRec) {
        info.plug.editor.changeColumnToEdit(cols[cols.length - 1]);
    }
    if (saveRecord) {
      me.savePrevious();
    }
    else {
      me.goToUpperNoSave();
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
        columns = me.getSegmentGrid().query('contentEditableColumn:not([hidden])'),
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
      if(!this.isEditing) {
          return;
      }
      var me = this,
          plug = me.getEditPlugin(),
          editor = plug.editor,
          rec = plug.openedRecord,
          columnToRead = editor.columnToEdit.replace(/Edit$/, '');
      Editor.MessageBox.addInfo(me.messages.segmentReset);
      editor.mainEditor.setValueAndMarkup(rec.get(columnToRead), rec.get('id'), editor.columnToEdit);
  },
  /**
   * handler for the F2 key
   */
  handleF2KeyPress: function() {
    var me = this,
        edCtrl = me.application.getController('Editor'),
        grid = me.getSegmentGrid(),
        selModel = grid.getSelectionModel(),
        ed = edCtrl.getEditPlugin(),
        cols = grid.query('contentEditableColumn:not([hidden])'),
        sel = [];
    
    if (ed.openedRecord === null)
    {
        if (!selModel.hasSelection())
        {
            grid.selectOrFocus(edCtrl.getNextEditableSegmentOffset(0));
        }
        sel = selModel.getSelection();
        ed.startEdit(sel[0], cols[0]);
    }
    else
    {
        ed.editor.mainEditor.deferFocus();
    }
  },
  /**
   * Handler for watchSegmentBtn
   * @param {Ext.button.Button} button
   */
  toggleWatchSegment: function() {
      if(!this.isEditing){
          return;
      }
      var me = this,
          model, config,
          ed = me.getEditPlugin(),
          record = ed.openedRecord,
          segmentId = record.get('id'),
          isWatched = Boolean(record.get('isWatched')),
          segmentUserAssocId = record.get('segmentUserAssocId'),
          navi = me.getNavi(),
          startText = navi.item_startWatchingSegment,
          stopText = navi.item_stopWatchingSegment,
          but = navi.down('#watchSegmentBtn'),
          success = function(rec, op) {
              //isWatched
              record.set('isWatched', !isWatched);
              record.set('segmentUserAssocId', isWatched ? null : rec.data['id']);
              but.setTooltip(isWatched ? startText : stopText);
              but.toggle(!isWatched, true);
              if(op.action == 'create') {
                  me.fireEvent('watchlistAdded', record, me, rec);
              }
              else {
                  me.fireEvent('watchlistRemoved', record, me, rec);
              }
          },
          failure = function(rec, op) {
              but.setTooltip(isWatched ? stopText : startText);
              but.toggle(isWatched, true);
          };
    
    if (isWatched)
    {
        config = {
            id: segmentUserAssocId
        }
        model = Ext.create('Editor.model.SegmentUserAssoc', config);
        model.getProxy().setAppendId(true);
        model.erase({
            success: success,
            failure: failure
        });
    }
    else
    {
        model = Ext.create('Editor.model.SegmentUserAssoc', {'segmentId': segmentId});
        model.save({
            success: success,
            failure: failure
        });
    }
  }
});
