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
    segmentNotBuffered: '#UT#Das angeforderte Segment liegt noch nicht im Zwischenspeicher. Bitte scrollen Sie manuell weiter!',
    gridEndReached: '#UT#Ende der Segmente erreicht!',
    gridStartReached: '#UT#Start der Segmente erreicht!'
  },
  refs : [{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  }],
  calledSaveMethod:false,
  
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
      //disabled ctrl enter since this produces errors in the save chain
      'segmentsHtmleditor': {
          afteriniteditor: me.initEditor
      }
    });
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
      
      /*Ext.EventManager.on(editor.getDoc(), 'copy', function(e){
          console.log('COPY', (e.browserEvent || e).clipboardData.getData('text/plain'));//, window.clipboardData.getData('Text'));
      });

      Ext.EventManager.on(editor.getDoc(), 'paste', function(e){
          console.log('PASTE', (e.browserEvent || e).clipboardData.getData('text/plain'));//, window.clipboardData.getData('Text'));
      });

      Ext.EventManager.on(editor.getDoc(), 'selectstart', function(e){
          console.log('START');
      });
      
      Ext.EventManager.on(editor.getDoc(), 'selectionchange', function(e){
          console.log('SELECTION');
      });*/
      
      // Angel Naydenov 22.10.2015: I excluded keymap version and returned this one, because with keymap BOTH assignMQMTag and changeState are fired
      Ext.EventManager.on(editor.getDoc(), 'keydown', function(e)
      {
          if (e.ctrlKey && e.altKey && Ext.Array.contains([49, 50, 51, 52, 53, 54, 55, 56, 57], e.getKey()))
          {
              var param = Number(e.getKey()) - 48;
              console.log("CTRL+ALT+"+param);
              me.fireEvent('changeState', param);
          }
      });
      
      f.prototype = Ext.Element.prototype;
      docEl = new f();
      docEl.dom = editor.getDoc();
      
      var map = new Ext.util.KeyMap(docEl, [{
          key: [10,13],
          ctrl: true,
          scope: me,
          fn: me.saveNext
      }, {
          key: [10,13],
          ctrl: true,
          alt: true,
          scope: me,
          fn: me.saveNextByAutoStatus
      }, /*{
          key: [49, 50, 51, 52, 53, 54, 55, 56, 57],
          ctrl: true,
          alt: true,
          shift:false,
          scope: me,
          fn: function(key){
              var param = Number(key) - 48;
              me.fireEvent('changeState', param);
          }
      },*/ {
          key: Ext.EventObject.ESC,
          scope: me,
          fn: me.cancel
      }, {
          key: Ext.EventObject.LEFT,
          ctrl:true,
          scope: me,
          fn: me.goToLeft
      }, {
          key: Ext.EventObject.RIGHT,
          ctrl:true,
          scope: me,
          fn: me.goToRight
      }, {
          key: decDigits,
          ctrl: false,
          alt: true,
          shift:false,
          scope: me,
          fn: function(key){
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
          fn: function(key){
              var param = (Number(key) - 48) + 10;
              if (param == 10)
              {
                param = 20;
              }
              me.fireEvent('assignMQMTag', param);
          }
      }, {
          key: "N",
          ctrl:true,
          shift:true,
          fn: function(){ me.fireEvent('openComments'); }
      }]);
  },
  /**
   * Handler für save Button
   */
  save: function() {
      var me = this;
      if(me.record && me.record.get('editable')) {
          me.fireEvent('saveSegment');
      }
  },
  /**
   * Moves to the next or previous row without saving current record
   * @param {Integer} direction of moving
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  moveToAdjacentHoriz: function(direction) {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin(),
          ret = this.moveToOtherRow(direction);
      
      //editing by selection handler must be disabled, otherwise saveChainStart will be triggered twice
      ed.disableEditBySelect = true;
      selModel.select(ret.newRec);
      Ext.defer(ed.startEdit, 300, ed, [ret.newRec, ret.lastColumnIdx]); //defer reduces problems with editorDomCleanUp see comment on Bug 38
      ed.disableEditBySelect = false;
      me.cancel();
      return ret.existsNextSegment;
  },
  /**
   * Moves to the next row without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  moveNext: function() {
      var me = this;
      return me.moveToAdjacentHoriz(1);
  },
  /**
   * Moves to the previous row without saving current record
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  movePrevious: function() {
      var me = this;
      return me.moveToAdjacentHoriz(-1);
  },
  /**
   * Handler for saveNext Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveNext: function() {
      this.calledSaveMethod = this.saveNext;
      return this.saveOtherRow(1, this.messages.gridEndReached);
  },
  /**
   * Handler for savePrevious Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  savePrevious: function() {
      this.calledSaveMethod = this.savePrevious;
      return this.saveOtherRow(-1, this.messages.gridStartReached);
  },
  /**
   * Handler for saveNext Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveNextByAutoStatus: function() {
      this.calledSaveMethod = this.saveNext;
      return this.saveOtherRow(1, this.messages.gridEndReached, function(rec) {
          return rec.get('matchRate') > 0;
      });
  },
  /**
   * Handler for savePrevious Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  savePreviousByAutoStatus: function() {
      this.calledSaveMethod = this.savePrevious;
      return this.saveOtherRow(-1, this.messages.gridStartReached, function(rec) {
          console.log("savePreviousByAutoStatus", rec);
          return true;
      });
  },
  /**
   * go to other row
   * @param {Integer} rowIdxChange positive or negative integer value to choose the index of the next row
   * @param {Function} isEditable optional, function which consumes a segment record, returns true if segment should be opened, false if not
   * @return {Object} to be used by saveOtherRow
   */
  moveToOtherRow: function(rowIdxChange, isEditable) {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin(),
          store = grid.store,
          rec = ed.openedRecord,
          ret = {
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
      while(ret.newRec && (!ret.newRec.get('editable') || !isEditable(ret.newRec))) {
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
          ret = me.moveToOtherRow(rowIdxChange, isEditable);
          
      me.fireEvent('saveSegment', {
          scope: me,
          segmentUsageFinished: function(){
              if(ret.isBorderReached) {
                  Editor.MessageBox.addInfo(errorText);
              } else if(ret.existsNextSegment){
                  //editing by selection handler must be disabled, otherwise saveChainStart will be triggered twice
                  ed.disableEditBySelect = true;
                  selModel.select(ret.newRec);
                  Ext.defer(ed.startEdit, 300, ed, [ret.newRec, ret.lastColumnIdx]); //defer reduces problems with editorDomCleanUp see comment on Bug 38
                  ed.disableEditBySelect = false;
              }
              else {
                  Editor.MessageBox.addInfo(me.messages.segmentNotBuffered);
              }
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
          me.moveNext();
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
          me.movePrevious();
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
  goToLeft: function() {
    var me = this,
        direction = -1;
    me.goToCustom(direction, true);    
  },
  /**
   * Move the editor about one editable field right
   */
  goToRight: function() {
    var me = this,
        direction = 1;
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
   * returns true if given next Segment should be edited or not, decision by workflow autostatus
   * @return {Boolean}
   */
  filterAutoStatus: function(nextRecord) {
      //Save segment-row, change auto-status and open next below with initial autostatus
      //for the current workflow-step (or an auto-status previous to the initial autostatus for this workflow-step) in the current filter set; 
      //new button necessary
      //like initial filter for wf step?
  }
});
