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
 * Controller für den Wiederholungseditor
 * @class Editor.controller.ChangeAlike
 * @extends Ext.app.Controller
 * 
 * IMPORTANT:
 * ChangeAlikes are working only with a default column layout 
 * of source and target column (optional relais)! 
 * ChangeAlikes does not work with Alternates!!!
 * 
 * Binding to the save chain: 
 * Method onAfterSaveCall to save chain event "afterSaveCall"
 * Method onSaveComplete to save chain event "saveComplete"
 * Method handleBeforeEdit loads the change alikes of a segment and fires event "fetchChangeAlikes" with the running AJAX operation as parameter
 *   The SegmentController is bound to "fetchChangeAlikes" and injects a callback with the next step in the save chain if the operation is still running.
 * 
 * handleAlikesRead is triggered after reading the change alikes, and steps back to the saveChain if needed.
 * 
 * saveChain Flow:
 * in controller.Segment                                               in controller.ChangeAlike
 * saveChainStart
 *      ↓
 * saveChainCheckAlikes         → if still loading change alikes:
 *      ↓ directly if alikes      set callback to operation, 
 *      ↓ are already loaded      which is triggered in                handleAlikesRead
 * saveChainSave 
 *      ↓
 * saveChainSaveCallback 
 *      ↓
 * saveChainEnd
 * 
 */
Ext.define('Editor.controller.ChangeAlike', {
  extend : 'Ext.app.Controller',
  stores: ['AlikeSegments','Segments'],
  views: ['changealike.Window'],
  messages: {
    alikeSingular: '#UT#Wiederholung wurde bearbeitet und gespeichert',
    alikePlural: '#UT#Wiederholungen wurden bearbeitet und gespeichert',
    alikesDisabled: '#UT#Das Projekt enthält alternative Übersetzungen. Der Wiederholungseditor wurde daher deaktiviert.'
  },
  alikesToProcess: null,
  fetchedAlikes: null,
  saveIsRunning: false,
  window: null,
  alikeSegmentsUrl: '',
  actualRecord: null,
  isSourceEditing: null,
  timeTracking: null,
  isDisabled: false,
  callbackToSaveChain: Ext.emptyFn,
  refs : [{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  },{
    ref : 'alikeWindow',
    selector : '#changealikeWindow'
  },{
    ref : 'alikeGrid',
    selector : '#changealikeWindow .gridpanel'
  },{
    ref : 'optionsBtn',
    selector : '#segmentgrid #optionsBtn'
  }],
  init : function() {
      var me = this,
          segCtrl = me.application.getController('Segments');
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following event bindings
      Editor.app.on('editorViewportClosed', me.clearAlikeSegments, me);
      segCtrl.on('afterSaveCall', me.onAfterSaveCall, me);
      segCtrl.on('saveComplete', me.onSaveComplete, me);

      this.control({
        '#changealikeWindow #saveBtn' : {
            click: me.handleSaveChangeAlike
        },
        '#changealikeWindow #cancelBtn' : {
            click: me.handleCancelChangeAlike
        },
        '#changealikeWindow tool[type=close]' : {
            click: me.handleCancelChangeAlike
        },
        '#segmentgrid': {
            afterrender: this.initEditPluginHandler
        }
      });
  },
  /**
   * Gibt die RowEditing Instanz des Grids zurück
   * @return {Editor.view.segments.RowEditing}
   */
  getEditPlugin: function() {
    return this.getSegmentGrid().editingPlugin;
  },
  /**
   * initiert Handler und Daten die erst nach dem rendern des Viewports gesetzt werden können
   * @see {Ext.app.Controller}
   */
  onLaunch: function() {
    var me = this;
    //Diese Events können erst in onlauch gebunden werden, in init existiert das Plugin noch nicht
    me.alikeSegmentsUrl = me.getStore('AlikeSegments').getProxy().url;
    me.window = Ext.widget('changealikeWindow');
  },
  /**
   * inits the editing plugin
   */
  initEditPluginHandler: function() {
      var me = this,
          t = Editor.data.task,
          auth = Editor.app.authenticatedUser,
          enabledACL = auth.isAllowed('useChangeAlikes');
          enabled = auth.isAllowed('useChangeAlikes', t);
      //disable the whole settings button, since no other settings are currently available!
      me.getOptionsBtn().setVisible(enabled);
      me.isDisabled = ! enabled;
      me.getEditPlugin().on('beforeedit', me.handleBeforeEdit, me);
      if(!t.get('defaultSegmentLayout') && enabledACL) {
          Editor.MessageBox.addInfo(this.messages.alikesDisabled, 1.4);
      }
  },
  clearAlikeSegments: function() {
      this.getAlikeSegmentsStore().removeAll();
  },
  /**
   * Handler to load the alike segments of actual segment.
   * Danger: if adding new methods to open segments, it must be assured, 
   * that this method is called not before the "Next Step callbacks" in handleSaveChangeAlike
   * fires event "fetchChangeAlikes" with the current AJAX operation as parameter.
   * 
   * @param {Ext.grid.plugin.Editing} column
   */
  handleBeforeEdit: function(context) {
    var me = this, 
        rec = context.record,
        //id des bearbeiteten Segments
        id = rec.get('id'),
        store = me.getStore('AlikeSegments'),
        proxy = store.getProxy(),
        op;
    
    if(me.isDisabled || me.isManualProcessingDisabled()) {
        return;
    }
    
    op = new Ext.data.Operation({
        action: 'read',
        segmentsId: id
    });
    
    me.getSegmentGrid().filters.onBeforeLoad(store, op);
    //using stores proxy to load and process data
    proxy.url = me.alikeSegmentsUrl+'/'+id;
    op.setStarted();
    me.fireEvent('fetchChangeAlikes', op);
    proxy.read(op, me.handleAlikesRead, me);
  },
  
  /**
   * handle if alikes are successful read
   * @param {Ext.data.Operation} operation
   */
  handleAlikesRead: function(operation) {
      var me = this, 
          id = operation.segmentsId;
      
      if(me.isDisabled || ! operation.wasSuccessful()){
          //@todo Meldung machen, dass keine WDHs geholt werden konnten!
          operation.handleReadAfterSave && operation.handleReadAfterSave();
          return;
      }
      
      me.fetchedAlikes = operation.getRecords();
      if(me.isManualProcessing()) {
          me.window.setAlikes(id, me.fetchedAlikes);
      }
      operation.handleReadAfterSave && operation.handleReadAfterSave();
  },
  
  /**
   * is invoked by the save chain, directly after starting the save request of the segment
   * @param {Function} finalCallback to return to save chain
   */
  onAfterSaveCall: function(finalCallback) {
      var me = this,
          plug = me.getEditPlugin(),
          context = plug.context,
          rec = context.record;
      me.callbackToSaveChain = finalCallback;
      me.actualRecord = rec;
      me.isSourceEditing = me.getSourceEditing();
      me.saveIsRunning = true;
      if(me.isDisabled || me.isManualProcessingDisabled() || me.noAlikes()) {
          me.fireEvent('segmentUsageFinished', me);
          me.callbackToSaveChain();
          return;
      }
      if(me.isAutoProcessing()) {
          me.handleSaveChangeAlike();
          return;
      }
      //manualProcessing:
      me.timeTracking = new Date(); // starting the time tracking
      me.window.show(rec, me.isSourceEditing);
  },
  /**
   * @return boolean true, if no alikes are present
   */
  noAlikes: function() {
      var me = this;
      me.isSourceEditing = me.getSourceEditing();
      me.allAlikes = me.getAllAlikeIds(me.isSourceEditing);
      if(Ext.isEmpty(me.allAlikes)) {
          return true;
      }
      return false;
  },
  /**
   * checks if we are editing the source column or target
   * @returns {Boolean}
   */
  getSourceEditing: function() {
      return this.getEditPlugin().editor.columnToEdit == "sourceEdit";
  },
  /**
   * Startet das Speichern der Wiederholungen. Wird je nach Einstellung automatisch oder manuell getriggert.
   */
  handleSaveChangeAlike: function() {
    var me = this,
        rec = me.actualRecord;
    
    //Daten des aktuelle bearbeiteten Segments, die angezeigten AlikeSegmente im Segment Store werden mit diesen überschrieben 
    //Hier wird auch das Alike Segment vorübergehend auf nicht editierbar gesetzt, bis das OK vom Server kommt
    data = {
      stateId: rec.data.stateId,
      qmId: rec.data.qmId,
      editable: 0,
      autoStateId: 999
    };
    if(me.isSourceEditing) {
        data.sourceEdit = rec.data.sourceEdit;
    }else {
        data.targetEdit = rec.data.targetEdit;
    }
    me.alikesToProcess = me.getAlikesToProcess();
    me.calculateUsedTime();
    
    //Next Step in save chain Callback
    me.fireEvent('segmentUsageFinished', me);
    //die 
    //alike segmente mit den Änderungen befüllen, aber noch nicht comitten, erst im alikesSaveSuccessHandler wenn die Alike Segmente auf dem Server gespeichert sind
    Ext.Array.each(me.alikesToProcess, function(alikeId){
        me.updateSegment(alikeId, data);
    });
    
    //ab hier nur bei manuellem processing der Alike Segmente
    if(me.isManualProcessing()) {
        me.window.close();
    }
    
    if(!me.saveIsRunning) {
        me.savePendingAlikes();
    }
  },
  /**
   * calculates the elapsed time the user needs to process the change alikes 
   */
  calculateUsedTime: function() {
      var me = this;
      //if it was a date, this was the opening time of the change alike editor
      if(me.timeTracking instanceof Date) {
          me.timeTracking = (new Date()) - me.timeTracking;
      }
      else {
          //otherwise it is automatic handling, so that we assume 0 as time.
          me.timeTracking = 0;
      }
  },
  /**
   * Handler is called after saving a segment successfully to the server in save chain (called by Ajax callback)
   * @return {Boolean}
   */
  onSaveComplete: function(){
      var me = this;
      if(me.alikesToProcess) { 
          me.savePendingAlikes();
      }
      me.saveIsRunning = false;
      //if no alikes are used, return to save chain
      if(me.isManualProcessingDisabled()) {
          return true;
      }
      //returning false prevents saveChain to end. Therefore the given finalCallback must be called after wards to proceed
      return false;
  },
  /**
   * Stößt auf der Server Seite die Verarbeitung Wiederholungen an.  
   * @param {Number[]} alikes Array mit den zu bearbeitenden Segment IDs
   */
  savePendingAlikes: function() {
    var me = this,
        id = me.actualRecord.getId(),
        alikes = me.alikesToProcess;
    if(!alikes || alikes.length == 0) {
        me.callbackToSaveChain();
        return;
    }
    Ext.Ajax.request({
      toSegmentId: id,
      url: me.alikeSegmentsUrl+'/'+id,
      method: 'put',
      params: {
          process: me.isSourceEditing ? 'source' : 'target',
          "duration": me.timeTracking,
          "alikes[]": alikes
      },
      success: me.alikesSaveSuccessHandler,
      failure: me.alikesSaveFailureHandler,
      scope: me
    });
  },
  /**
   * gibt ein Array mit den als Wiederholungen zu bearbeitenden SegmentIDs zurück
   * @return {Number[]}
   */
  getAlikesToProcess: function() {
      var me = this, 
          allIds = me.getAllAlikeIds(me.isSourceEditing);
      if(me.isAutoProcessing()){
          return allIds;
      }
      return me.getSelectedAlikeIds(allIds);
  },
  /**
   * returns an array with all alike ids (filtered by sourceEditing)
   * @param {Boolean} sourceEditing
   * @returns {Array}
   */
  getAllAlikeIds: function(sourceEditing) {
    var result = [];
    Ext.Array.each(this.fetchedAlikes, function(rec){
        //if source editing return only alikes with source matched = true 
        if(sourceEditing && !rec.get('sourceMatch')) {
            return;
        }
        result.push(rec.get('id'));
    });
    return result;
  },
  /**
   * Gibt ein Array mit den SegmentIDs anhand der Auswahl im Wiederholungseditor an
   * @param {Number[]} allIds an array with all valid change alike ids for this call, 
   * @return {Number[]}
   */
  getSelectedAlikeIds: function(validIds) {
      var sel = this.getAlikeGrid().getSelectionModel().getSelection(),
          selIds = this.getIdsFromRecords(sel);
      return Ext.Array.intersect(validIds, selIds);
  },
  /**
   * Helper: mappt die id's eines record Arrays in ein Array mit IDs
   * @param {Editor.model.AlikeSegment[]} recordArray
   * @return {Number[]}
   */
  getIdsFromRecords: function(recordArray) {
    return Ext.Array.map(recordArray, function(record){
      return record.get('id');
    });
  },
  /**
   * Fehlerhandler wenn AlikeSegmente nicht gespeichert werden konnten
   * @param {Object} resp
   * @param {Object} options
   */
  alikesSaveFailureHandler: function(resp, options) {
    this.cleanUpAlikeSegments();
    Editor.MessageBox.addError(this.messages.alikesFailure);
  },
  /**
   * Handler für erfolgreiches Speichern von Alike Segmenten
   * @param {Object} resp
   * @param {Object} options
   */
  alikesSaveSuccessHandler: function(resp, options) {
    var me = this,
    //id des Ziel Segments
    data = Ext.decode(resp.responseText),
    alikes = me.alikesToProcess,
    alikesSaved = (data.rows.length == 1 ? me.messages.alikeSingular : me.messages.alikePlural);
    if(!data.rows || data.rows.length == 0) {
 	    me.cleanUpAlikeSegments();
    	return;
    }
    //Auslesen und Verarbeiten der IDs der Alike Segmente die auf dem Server erfolgreich gespeichert wurden
    Ext.Array.each(data.rows, function(item) {
    	var updateId = parseInt(item.id);
    	//Erfolgreich als Wdh. bearbeitete und gespeicherte Segmente im Segment Store comitten:
    	var rec = me.updateSegment(updateId, item);
    	//der eigene Cache im Record wird hier (im PUT Alikes Success) nicht mehr benötigt:
    	rec && delete rec._editorDataSave;
    	//Die ID aus dem pendingArray entfernen
    	if(alikes && Ext.isArray(alikes)){
    	    Ext.Array.remove(alikes, updateId);
    	}
    });
    
    me.cleanUpAlikeSegments();
    Editor.MessageBox.addSuccess(Ext.String.format(alikesSaved, data.rows.length));
  },
  /**
   * Die übriggebliebenen IDs in der Pending Liste wurden auf dem Server nicht erfolgreich gespeichert, 
   * und werden daher im Segments Store rejectet, sprich wieder die unveränderten Daten angezeigt 
   * 
   * This method should be the last called method in the changealike processing. Its responsible to jump back to the saveChain 
   */
  cleanUpAlikeSegments: function() {
      var me = this,
          alikes = me.alikesToProcess;
      if(!alikes) {
          me.callbackToSaveChain();
          return;
      }
      Ext.Array.each(alikes, function(revertId){
          var rec = me.getStore('Segments').getById(revertId);
          if(rec) {
              rec.data = Ext.apply({}, rec._editorDataSave);
              rec.reject();
              delete rec._editorDataSave;
          }
      });
      me.alikesToProcess = null;
      me.callbackToSaveChain();
  },
  /**
   * Befüllt das Segment mit der gegebenen ID im Segment Store mit den übergebenen Daten,
   * ohne den Segment Store über die Änderung zu informieren (dass kein automatisches PUT stattfindet)
   * speichert die Orginal Daten für ein Roleback des Segments. Die vorhandene reject / commit Methodik kann 
   * zur Zwischenspeicherung hier nicht verwendet werden, da diese sonst automatisch den Store gegen den Server synct. 
   * @param {Number} id
   * @param {Object} data
   * @return {Editor.model.Segment}
   */
  updateSegment: function(id, data) {
    var store = this.getStore('Segments'),
        rec = store.getById(id);
    if(!rec) {
      if(!store.prefetchData){
          return null;
      }
      rec = store.prefetchData.findBy(function(rec){
          return rec.internalId == id;
      });
      if(!rec) {
          return null;
      }
    }
    rec._editorDataSave = Ext.apply({}, rec.data);
    rec.beginEdit();
    rec.set(data);
    rec.endEdit(true);
    rec.commit();
    return rec;
  },
  handleCancelChangeAlike: function() {
      this.callbackToSaveChain();
      this.window.close();
      return false; //prevent default close action
  },
  isManualProcessingDisabled: function() {
    return (Editor.data.preferences.alikeBehaviour == 'never');
  }, 
  isAutoProcessing: function() {
    return (Editor.data.preferences.alikeBehaviour == 'always');
  }, 
  isManualProcessing: function() {
    return (Editor.data.preferences.alikeBehaviour == 'individual');
  }
});