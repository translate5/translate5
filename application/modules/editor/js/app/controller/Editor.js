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
  hideLeftRight: false,
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
    debugger;  
      var me = this,
          keyev = Ext.EventManager.useKeyDown ? 'keydown' : 'keypress';

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
      
      
      //Ext.EventManager.on(editor.getDoc(), keyev, function(e){
          //console.log("KEY",e.getKey());
          //if(e.ctrlKey && e.getKey() == e.ENTER) {
              //me.saveNext();
         // }
      //});
      
      console.log("HERE", editor, editor.getDoc());
      var map = new Ext.util.KeyMap(
	editor.getDoc(),
	[
	  {                
	    key: Ext.EventObject.ESC,
	    fn: function(){ alert("ESC was pressed"); }
	  },
	  {                
	    key: "N",
	    ctrl:true,
	    shift:true,
	    fn: function(){ alert("CTRL+SHIFT+N was pressed"); }
	  }
	]
      );
      
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
   * save and go to other row
   * @param {Integer} rowIdxChange positive or negative integer value to choose the index of the next row
   * @param {String} errorText
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveOtherRow: function(rowIdxChange, errorText) {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin(),
          rec = ed.openedRecord,
          isBorderReached = false,
          store = grid.store,
          lastColumnIdx = 0,
          newRec = store.getAt(store.indexOf(rec) + rowIdxChange);
      if(!rec || !rec.get('editable')) {
          return false;
      }
      while(newRec && !newRec.get('editable')) {
          newRec = store.getAt(store.indexOf(newRec) + rowIdxChange);
      }
      if(rowIdxChange > 0) {
          isBorderReached = rec.get('id') == store.getLastSegmentId();
      }
      else {
          isBorderReached = rec.get('id') == store.getFirstSegmentId();
      }
      Ext.Array.each(grid.columns, function(col, idx) {
          if(col.dataIndex == ed.editor.getEditedField()) {
              lastColumnIdx = idx;
          }
      });
      me.fireEvent('saveSegment', {
          scope: me,
          segmentUsageFinished: function(){
              if(isBorderReached) {
                  Editor.MessageBox.addInfo(errorText);
              } else if(newRec !== undefined){
                  //editing by selection handler must be disabled, otherwise saveChainStart will be triggered twice
                  ed.disableEditBySelect = true;
                  selModel.select(newRec);
                  Ext.defer(ed.startEdit, 300, ed, [newRec, lastColumnIdx]); //defer reduces problems with editorDomCleanUp see comment on Bug 38
                  ed.disableEditBySelect = false;
              }
              else {
                  Editor.MessageBox.addInfo(me.messages.segmentNotBuffered);
              }
          }
      });
      return (newRec !== undefined);
  },
  /**
   * Handler für cancel Button
   */
  cancel: function() {
    this.getEditPlugin().cancelEdit();
  },
  /**
   * Move the editor about one editable field
   * @hint segment navigation
   */
  goToAlternate: function(btn, ev) {
    var me = this,
        direction = (btn.itemId == 'goAlternateLeftBtn' ? -1 : 1),
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
        me.saveNext();
    }
    else {
        //goto prev segment and last col
        if(newRec) {
            info.plug.editor.changeColumnToEdit(cols[cols.length - 1]);
        }
        me.savePrevious();
    }
  },
  /**
   * returns the visible columns and which column has actually the editor
   * @return {Object}
   * @hint segment navigation
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
  }
});
