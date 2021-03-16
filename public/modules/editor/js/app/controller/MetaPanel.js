
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
 * MetaPanel Controller
 * @class Editor.controller.MetaPanel
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.MetaPanel', {
  extend : 'Ext.app.Controller',
  requires: ['Editor.view.quality.mqm.Fieldset'],
  models: ['SegmentUserAssoc'],
  messages: {
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
      selector : '#metapanel segmentsMetapanel'
  },{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  }],
  
  listen: {
      component: {
          '#metapanel #metaTermPanel': {
              afterrender: 'initMetaTermHandler'
          },
          '#segmentgrid': {
              selectionchange: 'handleSegmentSelectionChange',
              afterrender: 'initEditPluginHandler',
              beforeedit: 'startEdit',
              canceledit: 'cancelEdit',
              edit: 'saveEdit'
          }
      },
      controller: {
          '#Editor': {
              changeState: 'changeState'
          }
      }
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
          multiEdit = me.getSegmentGrid().query('contentEditableColumn').length > 1,
          useChangeAlikes = Editor.app.authenticatedUser.isAllowed('useChangeAlikes', Editor.data.task);

      me.getLeftBtn().setVisible(multiEdit && ! useChangeAlikes);
      me.getRightBtn().setVisible(multiEdit && ! useChangeAlikes);
  },
  initMetaTermHandler: function() {
      this.getMetaTermPanel().getEl().on('click', function(e, span){
          if(! Ext.DomQuery.is(span, 'span.term')) {
              return;
          }
          var range;
          e.stopPropagation();
          e.preventDefault();
          if (document.selection) {
              document.selection.empty();
              range = document.body.createTextRange();
              range.moveToElementText(span);
              range.select();
          } else if (window.getSelection) {
              window.getSelection().removeAllRanges();
              range = document.createRange();
              range.selectNode(span);
              window.getSelection().addRange(range);
          }
      });
  },
  /**
   * Editor.view.segments.RowEditing beforeedit handler, initiert das MetaPanel mit den Daten
   * @param {Object} editingPlugin
   */
  startEdit: function(editingPlugin, context) {
    var me = this,
        mp = me.getMetaPanel(),
        record = context.record,
        segmentId = record.get('id'),
        isWatched = Boolean(record.get('isWatched')),
        segmentUserAssocId = record.get('segmentUserAssocId'),
        navi = me.getNavi(),
        but = Ext.getCmp('watchSegmentBtn'),
        tooltip = (isWatched) ? navi.item_stopWatchingSegment : navi.item_startWatchingSegment;
        
    but.toggle(isWatched, true);
    but.setTooltip({
        dismissDelay: 0,
        text: tooltip
    });
    
    me.record = record;
    this.loadTermPanel(segmentId);
    //bindStore(me.record.terms());
    me.loadRecord(me.record);
    navi.show();
    navi.enable();
    me.getSegmentMeta().show();
    mp.enable();
  },
  
  /**
   * @param {Ext.selection.Model} sm current selection model of 
   * @param {Array} selectedRecords 
   */
  handleSegmentSelectionChange: function(sm, selectedRecords) {
      if(selectedRecords.length == 0) {
          return;
      }
      this.loadTermPanel(selectedRecords[0].get('id'));
  },
  
  /**
   * @param {Integer} segmentId for which the terms should be loaded 
   */
  loadTermPanel: function(segmentId) {
      var me = this,
          panel = me.getMetaTermPanel();
      //if task has no terminology, we load the panel once to get the text no terminology
      if(Editor.data.task.get('terminologie') || !panel.html) {
          panel.getLoader().load({
              params: {id: segmentId},
              callback: function() {
                  me.getSegmentMeta() && me.getSegmentMeta().updateLayout();
              }
          });
      }
  },
  
  /**
   * opens metapanel for readonly segments
   * @param {Editor.model.Segment} record
   */
  openReadonly: function(record) {
      var me = this,
      mp = me.getMetaPanel();
      me.record = record;
      me.getSegmentMeta().hide();
      mp.enable();
      me.getNavi().hide();
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
        qmBoxes = mp.query('#metaQm checkbox');
    statBoxes = mp.query('#metaStates radio');
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
        qmBoxes = mp.query('#metaQm checkbox'),
        quality = [];
    Ext.each(qmBoxes, function(box){box.getValue() && quality.push(box.inputValue);});
    me.record.set('stateId', form.getValues().stateId);
    me.record.setQmFromArray(quality);
    //close the metapanel
    mp.disable();
  },
  /**
   * Changes the state box by keyboard shortcut instead of mouseclick
   * @param {Ext.Number} param
   */
  changeState: function(param) {
    var me = this,
        mp = me.getMetaPanel(),
        index = 1,
        statBoxes = mp.query('#metaStates radio');
    Ext.each(statBoxes, function(box){
      if (index++ == param){
        box.setValue(true);
      }
    });
  },  
  /**
   * Editor.view.segments.RowEditing canceledit handler
   * @hint metapanel
   */
  cancelEdit: function() {
      var me = this,
          mp = me.getMetaPanel();
        
      mp.disable();
  }
});
