
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
Ext.define('Editor.controller.MetaPanel', {
  extend : 'Ext.app.Controller',
  requires: ['Editor.view.qmsubsegments.AddFlagFieldset'],
  messages: {
    segmentNotBuffered: '#UT#Das angeforderte Segment liegt noch nicht im Zwischenspeicher. Bitte scrollen Sie manuell weiter!',
    gridEndReached: '#UT#Ende der Segmente erreicht!',
    gridStartReached: '#UT#Start der Segmente erreicht!'
  },
  refs : [{
    ref : 'metaPanel',
    selector : '#metapanel'
  },{
    ref : 'metaTermPanel',
    selector : '#metapanel #metaTermPanel'
  },{
    ref : 'leftBtn',
    selector : '#metapanel #goAlternateLeftBtn'
  },{
    ref : 'rightBtn',
    selector : '#metapanel #goAlternateRightBtn'
  },{
      ref : 'navi',
      selector : '#metapanel #naviToolbar'
  },{
      ref : 'segmentMeta',
      selector : '#metapanel .segmentsMetapanel'
  },{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  }],
  hideLeftRight: false,
  calledSaveMethod:false,
  
  init : function() {
      var me = this;
      me.control({
      '#metapanel #watchSegmentBtn' : {
        click : me.toggleWatchSegment
      },
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
      '#metapanel' : {
          show : me.layout
      },
      //disabled ctrl enter since this produces errors in the save chain
      //'segmentsHtmleditor': {
      //    afteriniteditor: me.initEditor
      //},
      '#segmentgrid': {
          afterrender: me.initEditPluginHandler
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
  initEditPluginHandler: function() {
      var me = this, 
          multiEdit = me.getSegmentGrid().query('.contentEditableColumn').length > 1,
          useChangeAlikes = Editor.app.authenticatedUser.isAllowed('useChangeAlikes', Editor.data.task);

    //Diese Events können erst in onlauch gebunden werden, in init existiert das Plugin noch nicht
      me.getEditPlugin().on('beforeedit', me.startEdit, me);
      me.getEditPlugin().on('canceledit', me.cancelEdit, me);
      me.getEditPlugin().on('edit', me.saveEdit, me);
      me.getEditPlugin().on('canCompleteEdit', me.canCompleteEdit, me);
    
      me.getLeftBtn().setVisible(multiEdit && ! useChangeAlikes);
      me.getRightBtn().setVisible(multiEdit && ! useChangeAlikes);
  },
  /**
   * binds strg + enter as save segment combination
   * @param editor
   */
  initEditor: function(editor){
      var me = this,
          keyev = Ext.EventManager.useKeyDown ? 'keydown' : 'keypress';
      Ext.EventManager.on(editor.getDoc(), keyev, function(e){
          if(e.ctrlKey && e.getKey() == e.ENTER) {
              me.saveNext();
          }
      });
  },
  /**
   * Handler für save Button
   */
  layout: function() {
    this.getNavi().doLayout(); //FIXME noch was anderes layouten?
  },
  /**
   * Handler for watchSegmentBtn
   * @param button
   * @param pressed
   */
  toggleWatchSegment: function(but, pressed) {
      var me = this,
        model = me.getModel('SegmentUserAssoc');
        segmentId = me.record.get('id'),
        isWatched = Boolean(me.record.get('isWatched')),
        segmentUserAssocId = me.record.get('segmentUserAssocId'),
        navi = me.getNavi(),
        tooltip = (isWatched) ? navi.item_stopWatchingSegment : navi.item_startWatchingSegment;
    
    if (isWatched)
    {
        
    }
    else
    {
        // model i undefined !? why???
        model.set('segmentId', segmentId);
        model.save({
            success: function(rec, op) {
                var s1 = '';
                for (var n1 in rec)
                {
                    s1 += '\n'+n1+': '+rec[n1];    
                }
                var s2 = '';
                for (var n2 in op)
                {
                    s2 += '\n'+n2+': '+op[n2];    
                }
                console.log(s1);
                console.log('========');
                console.log(s2);
            }
        });
    }
        
    but.toggle(isWatched, true);
    but.setTooltip(tooltip);
      
      
      var me = this,
          navi = me.getNavi();
          
      if (button.pressed)
      {
         button.setTooltip(navi.item_stopWatchingSegment); 
      }
      else
      {
          button.setTooltip(navi.item_startWatchingSegment); 
      }
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
   * Editor.view.segments.RowEditing beforeedit handler, initiert das MetaPanel mit den Daten
   * @param {Object} context
   */
  startEdit: function(context) {
    var me = this,
        mp = me.getMetaPanel(),
        segmentId = context.record.get('id'),
        isWatched = Boolean(context.record.get('isWatched')),
        segmentUserAssocId = context.record.get('segmentUserAssocId'),
        navi = me.getNavi(),
        but = Ext.getCmp('watchSegmentBtn'),
        tooltip = (isWatched) ? navi.item_stopWatchingSegment : navi.item_startWatchingSegment;
        
    but.toggle(isWatched, true);
    but.setTooltip(tooltip);
    
    me.record = context.record;
    me.getMetaTermPanel().getLoader().load({params: {id: segmentId}});
    //bindStore(me.record.terms());
    me.loadRecord(me.record);
    //FIXME here doLayout???
    navi.enable();
    me.getSegmentMeta().show();
    mp.show();
  },
  /**
   * opens metapanel for readonly segments
   * @param {Editor.model.Segment} record
   */
  openReadonly: function(record) {
      var me = this,
      mp = me.getMetaPanel();
      me.record = record;
      me.getNavi().disable();
      me.getSegmentMeta().hide();
      mp.show();
  },
  /**
   * lädt die konkreten record ins Meta Panel 
   * @param {Ext.data.Model} record
   */
  loadRecord: function(record) {
    var me = this,
        mp = me.getMetaPanel(),
        form = mp.down('#metaInfoForm'),
        values = record.getQmAsArray(),
        qmBoxes = mp.query('#metaQm .checkbox');
    statBoxes = mp.query('#metaStates .radio');
    Ext.each(statBoxes, function(box){
      box.setValue(false);
    });
    form.loadRecord(record);
    Ext.each(qmBoxes, function(box){
      box.setValue(Ext.Array.contains(values, box.inputValue));
    });
  },
  /**
   * Editor.view.segments.RowEditing edit handler, Speichert die Daten aus dem MetaPanel im record
   */
  saveEdit: function() {
    var me = this,
        mp = me.getMetaPanel(),
        form = mp.down('#metaInfoForm'),
        qmBoxes = mp.query('#metaQm .checkbox'),
        quality = [];
    Ext.each(qmBoxes, function(box){box.getValue() && quality.push(box.inputValue);});
    me.record.set('stateId', form.getValues().stateId);
    me.record.setQmFromArray(quality);
    //close the metapanel
    me.cancelEdit();
  },
  /**
   * Editor.view.segments.RowEditing canceledit handler
   */
  cancelEdit: function() {
    this.getMetaPanel().hide();
  },
  /**
   * Move the editor about one editable field
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
