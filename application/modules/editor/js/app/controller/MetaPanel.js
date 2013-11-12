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
 * MetaPanel Controller
 * @class Editor.controller.MetaPanel
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.MetaPanel', {
  extend : 'Ext.app.Controller',
  requires: ['Editor.view.qmsubsegments.AddFlagFieldset'],
  lastColumnIdx: 0, //needed for interaction with editable source column
  messages: {
    gridEndReached: 'Ende der Segmente erreicht!',
    gridStartReached: 'Start der Segmente erreicht!'
  },
  refs : [{
    ref : 'metaPanel',
    selector : '#metapanel'
  },{
    ref : 'metaTermPanel',
    selector : '#metapanel #metaTermPanel'
  },{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  }],
  init : function() {
    this.control({
      '#metapanel #cancelSegmentBtn' : {
        click : this.cancel
      },
      '#metapanel #saveSegmentBtn' : {
        click : this.save
      },
      '#metapanel #saveNextSegmentBtn' : {
        click : this.saveNext
      },
      '#metapanel #savePreviousSegmentBtn' : {
        click : this.savePrevious
      },
      '#metapanel' : {
        show : this.layout
      },
      '#segmentgrid': {
          afterrender: this.initEditPluginHandler
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
    //Diese Events können erst in onlauch gebunden werden, in init existiert das Plugin noch nicht
    this.getEditPlugin().on('beforeedit', this.startEdit, this);
    this.getEditPlugin().on('canceledit', this.cancelEdit, this);
    this.getEditPlugin().on('edit', this.saveEdit, this);
    this.getEditPlugin().on('canCompleteEdit', this.canCompleteEdit, this);
  },
  /**
   * Handler für save Button
   */
  layout: function() {
    this.getMetaPanel().down('#metaInfoForm #naviToolbar').doLayout();
  },
  /**
   * Handler für save Button
   */
  save: function() {
    this.fireEvent('saveSegment');
  },
  /**
   * Handler for saveNext Button
   */
  saveNext: function() {
      this.saveOtherRow(1, this.messages.gridEndReached);
  },
  /**
   * Handler for savePrevious Button
   */
  savePrevious: function() {
      this.saveOtherRow(-1, this.messages.gridStartReached);
  },
  /**
   * save and go to other row
   * @param {Integer} rowIdxChange positive or negative integer value to choose the index of the next row
   * @param {String} errorText
   */
  saveOtherRow: function(rowIdxChange, errorText) {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin(),
          rec = ed.openedRecord,
          store = grid.store,
          newRec = store.getAt(store.indexOf(rec) + rowIdxChange);
      while(newRec && !newRec.get('editable')) {
          newRec = store.getAt(store.indexOf(newRec) + rowIdxChange);
      }
      me.fireEvent('saveSegment', {
          scope: me,
          segmentUsageFinished: function(){
              if(newRec !== undefined){
                  //editing by selection handler must be disabled, otherwise saveChainStart will be triggered twice
                  ed.disableEditBySelect = true;
                  selModel.select(newRec);
                  Ext.defer(ed.startEdit, 100, ed, [newRec, me.lastColumnIdx]); //defer reduces problems with editorDomCleanUp see comment on Bug 38
                  ed.disableEditBySelect = false;
              }
              else{
                  Editor.MessageBox.addInfo(errorText);
              }
          }
      });
  },
  /**
   * Handler für cancel Button
   */
  cancel: function() {
    this.getEditPlugin().cancelEdit();
  },
  /**
   * Editor.view.segments.RowEditing beforeedit handler, initiert das MetaPanel mit den Daten
   * @param {Ext.grid.column.Column} column
   */
  startEdit: function(column) {
    var me = this,
    mp = me.getMetaPanel(),
    segmentId = column.record.get('id');
    me.record = column.record;
    me.lastColumnIdx = column.colIdx;
    me.getMetaTermPanel().getLoader().load({params: {id: segmentId}});
    //bindStore(me.record.terms());
    me.loadRecord(me.record);
    mp.down('#metaInfoForm').doLayout();
    mp.down('#metaInfoForm').show();
    mp.down('#metaInfoForm #naviToolbar').doLayout();
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
    this.getMetaPanel().down('#metaInfoForm').hide();
  }
});