
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
  requires: [
      'Editor.view.quality.mqm.Fieldset',
      'Editor.view.quality.FalsePositives',
      'Editor.view.quality.SegmentQm',
      'Editor.store.quality.Segment' ],
  models: ['SegmentUserAssoc'],
  messages: {
      stateIdSaved: '#UT#Der Segment Status wurde gespeichert'
  },
  refs: [{
    ref: 'metaPanel',
    selector: '#metapanel'
  },{
    ref: 'metaTermPanel',
    selector: '#metapanel #metaTermPanel'
  },{
    ref: 'metaQmPanel',
    selector: '#metapanel #segmentQm'
  },{
    ref: 'metaFalPosPanel',
    selector: '#metapanel #falsePositives'
  },{
      ref: 'metaInfoForm',
      selector: '#metapanel #metaInfoForm'
  },{
    ref: 'segmentMeta',
    selector: '#metapanel segmentsMetapanel'
  },{
    ref: 'leftBtn',
    selector: '#metapanel #goAlternateLeftBtn'
  },{
    ref: 'rightBtn',
    selector: '#metapanel #goAlternateRightBtn'
  },{
      ref: 'navi',
      selector: '#metapanel #naviToolbar'
  },{
    ref: 'segmentGrid',
    selector: '#segmentgrid'
  }],
  listen: {
      component: {
          '#metapanel #metaTermPanel': {
              afterrender: 'initMetaTermHandler'
          },
          '#metapanel segmentsMetapanel': {
              segmentStateChanged: 'onSegmentStateChanged'
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
              changeSegmentState: 'onChangeSegmentState'
          }
      },
      store: {
          '#SegmentQualities': {
              load: 'handleQualitiesLoaded'
          }
      }
  },
  /**
   * If the QM qualities are enabled
   */
  hasQmQualities: false,
  /**
   * The store holding the segments qualiies. Data source for the falsePositives panel and the segmentQm panel
   */
  qualitiesStore: null,
  /**
   * A flag specifying our editing mode. can be: 'none', 'readonly', 'edit'
   */
  editingMode: 'none',
  /**
   * Gibt die RowEditing Instanz des Grids zurück
   * @returns Editor.view.segments.RowEditing
   */
  getEditPlugin: function() {
      return this.getSegmentGrid().editingPlugin;
  },
  getQualitiesStore: function(){
      if(this.qualitiesStore == null){
          this.qualitiesStore = Ext.create('Editor.store.quality.Segment');
      }
      return this.qualitiesStore;
  },
  initEditPluginHandler: function() {
      var me = this, 
          multiEdit = me.getSegmentGrid().query('contentEditableColumn').length > 1,
          useChangeAlikes = Editor.app.authenticatedUser.isAllowed('useChangeAlikes', Editor.data.task);
      // creating the store for the segment's qualities on the first edit
      me.getQualitiesStore();
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
      me.editingMode = 'edit';
      but.toggle(isWatched, true);
      but.setTooltip({
          dismissDelay: 0,
          text: tooltip
      });
      me.record = record;
      me.loadTermPanel(segmentId);
      me.hasQmQualities = Editor.app.getTaskConfig('autoQA.enableQm');
      // our component controllers are listening for the load event & create their views
      me.getQualitiesStore().load({
          params: { segmentId: segmentId }
      });
      me.loadRecord(me.record);
      navi.show();
      navi.enable();
      me.getSegmentMeta().show();
      mp.enable();
      
  },
  /**
   * Starts the creation of the segment's quality related GUIs
   */
  handleQualitiesLoaded: function(store, records){
      // for cases where user is faster than store
      if(this.editingMode == 'edit'){
          var segmentId = this.record.get('id');
          this.getMetaFalPosPanel().startEditing(records, segmentId, true);
          this.getMetaQmPanel().startEditing(records, segmentId, this.hasQmQualities);
      } else {
          store.removeAll(true);
      }
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
              callback: function(){
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
      me.editingMode = 'readonly';
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
      // this is only done to be able in the component to detect if a change was done programmatically or user generated
      // the afterwards loading of the recordstriggers the onChange in the radio controls
      this.getSegmentMeta().setSegmentStateId(record.get('stateId'));
      this.getMetaInfoForm().loadRecord(record);
  },
  /**
   * Editor.view.segments.RowEditing edit handler, Speichert die Daten aus dem MetaPanel im record
   */
  saveEdit: function() {
      this.record.set('stateId', this.getMetaInfoForm().getValues().stateId);
      //close the metapanel
      this.getMetaPanel().disable();
      this.getMetaFalPosPanel().endEditing(true, true);
      this.getMetaQmPanel().endEditing(this.hasQmQualities, true);
      this.getQualitiesStore().removeAll(true);
      this.editingMode = 'none';
  },
  /**
   * Editor.view.segments.RowEditing canceledit handler
   * @hint metapanel
   */
  cancelEdit: function() {
      this.getMetaPanel().disable();
      this.getMetaFalPosPanel().endEditing(true, false);
      this.getMetaQmPanel().endEditing(this.hasQmQualities, false);
      this.getQualitiesStore().removeAll(true);
      this.editingMode = 'none';
  },
  /**
   * Changes the state box by keyboard shortcut instead of mouseclick 
   * we do no set the stateId before to trigger a change event
   * @param {Ext.Number} param
   */
  onChangeSegmentState: function(stateId) {
      this.getSegmentMeta().showSegmentStateId(stateId);
  },
  /**
   * Listenes for segment state changes thrown from segments metapanel view
   */
  onSegmentStateChanged: function(stateId, oldStateId){
      var me = this;
      Ext.Ajax.request({
          url: Editor.data.restpath+'segment/stateid',
          method: 'GET',
          params: { id: me.record.get('id'), stateId: stateId },
          success: function(response){
              response = Ext.util.JSON.decode(response.responseText);
              if(response.success){
                  me.record.set('stateId', stateId);
                  // commit silently, oherwise the changed state gets lost on next edit of the segment
                  me.record.commit(true);
                  Editor.MessageBox.addSuccess(me.messages.stateIdSaved);
              } else {
                  console.log("Changing segments stateId via Ajax failed!");
                  var statePanel = me.getSegmentMeta();
                  statePanel.setSegmentStateId(oldStateId);
                  statePanel.showSegmentStateId(oldStateId);
              }
          },
          failure: function(response){
              Editor.app.getController('ServerException').handleException(response);
          }
      });
  }
});
