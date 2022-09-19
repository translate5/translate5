
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
    alikesDisabled: '#UT#Das Projekt enthält alternative Übersetzungen. Der Wiederholungseditor wurde daher deaktiviert.',
    alikesFailure: '#UT#Die Wiederholungen konnten nicht gespeichert werden',
    alikesNotAllSaved: '#UT#Es konnten nicht alle wiederholten Segmente gespeichert werden! Dies kann unterschiedliche Ursachen haben. Bitte speichern Sie das zuletzt bearbeitete Segment erneut und verwenden Sie den manuellen Modus des Wiederholungseditor um die betroffenen Segmente zu identifizieren um sie danach händisch zu bearbeiten.'
  },
  alikesToProcess: null,
  fetchedAlikes: null,
  saveIsRunning: false,
  window: null,
  alikeSegmentsUrl: '',
  actualRecord: null,
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
    selector : '#changealikeWindow gridpanel'
  },{
    ref : 'optionsBtn',
    selector : '#segmentgrid #optionsBtn'
  }],
  listen: {
      messagebus: {
          '#translate5': {
              reconnect: 'handleBusReconnect'
          }
      },
      component: {
          '#changealikeWindow #saveBtn' : {
              click: 'handleSaveChangeAlike'
          },
          '#changealikeWindow' : {
              show: 'focusButton',
              onEscape: 'handleCancelChangeAlike',
              onCtrlS: 'handleSaveChangeAlike'
          },
          '#changealikeWindow #cancelBtn' : {
              click: 'handleCancelChangeAlike'
          },
          '#changealikeWindow tool[type=close]' : {
              click: 'handleCancelChangeAlike'
          },
          '#segmentgrid': {
              afterrender: 'initEditPluginHandler'
          }
      },
      controller: {
          '#Editor.$application': {
              editorViewportOpened: 'initWindow',
              editorViewportClosed: 'clearAlikeSegments'
          },
          '#Segments': {
              afterSaveCall: 'onAfterSaveCall',
              saveComplete: 'onSaveComplete'
          }
      }
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
  },
  
  /**
   * The changealikeWindow must be reinitialized on each task change
   */
  initWindow: function() {
      if(this.window) {
          this.window.destroy();
      }
      this.window = Ext.widget('changealikeWindow');
  },
  
  /**
   * inits the editing plugin
   */
  initEditPluginHandler: function() {
      var me = this,
          t = Editor.data.task,
          auth = Editor.app.authenticatedUser,
          enabledACL = auth.isAllowed('useChangeAlikes'),
          enabled = auth.isAllowed('useChangeAlikes', t);
      //disable the whole settings button, since no other settings are currently available!
      me.getOptionsBtn().setVisible(enabled);
      me.isDisabled = ! enabled;
      me.getEditPlugin().on('beforeedit', me.handleBeforeEdit, me);
      if(!t.get('defaultSegmentLayout') && enabledACL) {
          Editor.MessageBox.addInfo(this.messages.alikesDisabled, 1.4);
      }
  },
  focusButton: function(win) {
      win.down('#saveBtn').focus(false, 201);
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
  handleBeforeEdit: function(editingPlugin, context) {
    var me = this, 
        rec = context.record,
        //id des bearbeiteten Segments
        id = rec.get('id'),
        store = me.getStore('AlikeSegments'),
        segmentStore = me.getSegmentGrid().getStore(),
        segmentsProxy = segmentStore.getProxy(),
        params = {};
    
    if(me.isDisabled || me.isManualProcessingDisabled()) {
        return;
    }
    
    params[segmentsProxy.getFilterParam()] = segmentsProxy.encodeFilters(segmentStore.getFilters().items);
    params[segmentsProxy.getSortParam()] = segmentsProxy.encodeSorters(segmentStore.getSorters().items);
    
    //stop loading first!
    store.getProxy().abort();
    store.load({
        url: me.alikeSegmentsUrl+'/'+id,
        params: params,
        //prevent default ServerException handling
        preventDefaultHandler: true,
        callback: function(recs, op, success) {
            success && me.handleAlikesRead(op, id);
        }
    });
  },

  /**
   * on frontendmessag bus reconnection we have to retrigger the get alike call
   */
  handleBusReconnect: function(bus) {
      var grid = this.getSegmentGrid();
      Editor.app.getController('JsLogger').addLogEntryToLogger('ChangeAlike::handleBusReconnect');
      if(grid && grid.editingPlugin.editing) {
          //trigger alikes GET again! No ability to trigger that via a senseful event, 
          this.handleBeforeEdit(grid.editingPlugin, grid.editingPlugin.context);
      }
  },
  
  /**
   * handle if alikes are successful read
   * @param {Ext.data.Operation} operation
   * @param {Integer} id
   */
  handleAlikesRead: function(operation, id) {
      var me = this 
      
      if(me.isDisabled || ! operation.wasSuccessful()){
          operation.handleReadAfterSave && operation.handleReadAfterSave();
          return;
      }
      
      me.fetchedAlikes = operation.getRecords();
      Editor.app.getController('JsLogger').addLogEntryToLogger('ChangeAlike::handleAlikesRead for id '+id+', loaded alikes: '+me.getAllAlikeIds().join(','));
      if(me.isManualProcessing()) {
          me.window.setAlikes(id, me.fetchedAlikes);
      }
      operation.handleReadAfterSave && operation.handleReadAfterSave();
  },
  
  /**
   * is invoked by the save chain, directly after starting the save request of the segment
   * @param {Function} finalCallback to return to save chain
   */
  onAfterSaveCall: function(finalCallback, record) {
	  var me = this;
	  me.callbackToSaveChain = finalCallback;
	  me.actualRecord = record;
	  me.saveIsRunning = true;

      Editor.app.getController('JsLogger').addLogEntryToLogger('ChangeAlike::onAfterSaveCall set record id: '+record.get('id'));

	  //If it is set to true, the repetition editor only pops up (processes automatically) 
	  //when the target of the current segment is empty
	  if(Editor.app.getUserConfig('alike.showOnEmptyTarget') && record.get('target') != ''){
		  me.fireEvent('segmentUsageFinished', me);
		  //FIXME should be no callback to save chain here, since we are still in the save chain!
          me.callbackToSaveChain();
		  return;
	  }
	  
      if(me.isDisabled || me.isManualProcessingDisabled() || me.noAlikes()) {
          me.fireEvent('segmentUsageFinished', me);
          //FIXME should be no callback to save chain here, since we are still in the save chain!
          me.callbackToSaveChain();
          return;
      }
      if(me.isAutoProcessing()) {
          me.handleSaveChangeAlike();
          return;
      }
      //manualProcessing:
      me.timeTracking = new Date(); // starting the time tracking
      me.window.show(record);
  },
  /**
   * @return boolean true, if no alikes are present
   */
  noAlikes: function() {
      var me = this;
      me.allAlikes = me.getAllAlikeIds();
      if(Ext.isEmpty(me.allAlikes)) {
          return true;
      }
      return false;
  },
  /**
   * checks if source editing is enabled
   * @returns {Boolean}
   */
  getSourceEditing: function() {
      return Editor.data.task.get('enableSourceEditing');
  },
  /**
   * Startet das Speichern der Wiederholungen. Wird je nach Einstellung automatisch oder manuell getriggert.
   */
  handleSaveChangeAlike: function() {
    var me = this,
        rec = me.actualRecord,
        meta = rec.get('metaCache'),
        newLength = null,
    
    //Daten des aktuelle bearbeiteten Segments, die angezeigten AlikeSegmente im Segment Store werden mit diesen überschrieben 
    //Hier wird auch das Alike Segment vorübergehend auf nicht editierbar gesetzt, bis das OK vom Server kommt
    data = {
      stateId: rec.data.stateId,
      editable: 0,
      autoStateId: 999
    };
    //get the length of the changed master segment
    if(meta && meta.siblingData && meta.siblingData[rec.get('id')]) {
        newLength = Ext.clone(meta.siblingData[rec.get('id')].length);
    }
    if(me.getSourceEditing()) {
        data.sourceEdit = rec.data.sourceEdit;
    }
    else {
        data.source = rec.data.source;
    }
    data.targetEdit = rec.data.targetEdit;
    // when a LanguageResource match was taken over, we will have to update the original target as well
    // this affects only the view, the actual copying in the backend is based on evaluations there
    if(rec.wasOriginalTargetUpdated) {
        data.target = rec.get('target');
    }
    me.alikesToProcess = me.getAlikesToProcess();
    me.calculateUsedTime();
    
    //just fill the alike segments with the changes. The commit of the them will be triggered  
    // in alikesSaveSuccessHandler when the Alike Segments are saved on the server
    Ext.Array.each(me.alikesToProcess, function(alikeId){
        me.updateSegment(alikeId, data, newLength);
    });
    
    //Next Step in save chain Callback
    me.fireEvent('segmentUsageFinished', me);
    
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
      //if no alikes are used or available, return to save chain
      //FIXME SHOULD BE: if(me.isDisabled || me.isManualProcessingDisabled() || me.noAlikes()) {
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
      timeout: 90000,
      params: {
          "duration": me.timeTracking,
          "alikes": Ext.JSON.encode(alikes)
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
      var me = this, byRepetitionType, bySameContextOnly,
          repetitionType = Editor.app.getUserConfig('alike.repetitionType'),
          sameContextOnly = Editor.app.getUserConfig('alike.sameContextOnly');

      // If alike-segments should be automatically processed
      if (me.isAutoProcessing()) {

          // Return all found alike-segments ids, filtered according to repetitionType and sameContextOnly configs
          return me.getAllAlikeIds(function(rec) {

              // Check whether current alike-segment conforms repetitionType-clause
              switch (repetitionType) {
                  case 'bothOr' : byRepetitionType = true;                                             break;
                  case 'bothAnd': byRepetitionType = rec.get('sourceMatch') && rec.get('targetMatch'); break;
                  case 'source' : byRepetitionType = rec.get('sourceMatch');                           break;
                  case 'target' : byRepetitionType = rec.get('targetMatch');                           break;
              }

              // Check whether current alike-segment conforms sameContextOnly-clause
              bySameContextOnly = sameContextOnly ? rec.get('contextMatch') : true;

              // Return true of both conditions are ok
              return byRepetitionType && bySameContextOnly;
          });
      }

      // Else return all alike-segments, currently selected in repetitions-dialog
      return me.getSelectedAlikeIds(me.getAllAlikeIds());
  },
  /**
   * returns an array with all alike ids
   * @returns {Array}
   */
  getAllAlikeIds: function(filterFn) {
    var result = [];
    Ext.Array.each(this.fetchedAlikes, function(rec){
        if (typeof filterFn != 'function' || filterFn(rec)) result.push(rec.get('id'));
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
    this.cleanUpAlikeSegments(true);
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
 	    me.cleanUpAlikeSegments(false);
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
    // finish process by checking of unsaved alikes occured
    if(me.cleanUpAlikeSegments(false)){
        Editor.MessageBox.addSuccess(Ext.String.format(alikesSaved, data.rows.length));
    }

    //TODO: change to websocket
    me.fireEvent('alikesSaveSuccess',data);
  },
  /**
   * Die übriggebliebenen IDs in der Pending Liste wurden auf dem Server nicht erfolgreich gespeichert, 
   * und werden daher im Segments Store rejectet, sprich wieder die unveränderten Daten angezeigt 
   * 
   * This method should be the last called method in the changealike processing. Its responsible to jump back to the saveChain
   * It returns, if no error saving the alikes occured (and no message-box was generated unless "silent" was passed)
   */
  cleanUpAlikeSegments: function(silent) {
      var me = this,
          alikes = me.alikesToProcess;
      me.alikesToProcess = null;
      if(!alikes || alikes.length == 0) {
          me.callbackToSaveChain();
          return true;
      }
      Ext.Array.each(alikes, function(revertId){
          var rec = me.getStore('Segments').getById(revertId);
          if(rec) {
              rec.data = Ext.apply({}, rec._editorDataSave);
              rec.reject();
              delete rec._editorDataSave;
          }
      });
      if(!silent){
          Editor.MessageBox.addError(me.messages.alikesNotAllSaved);
      }
      me.callbackToSaveChain();
      // TODO FIXME: This leads to ugly JS-Errors, it seems Rootcause does not catch these reliably. therefore deactivated
      // throw "not all alikes saved"; // exception here to trigger the rootcause logging
      return false;
  },
  /**
   * Befüllt das Segment mit der gegebenen ID im Segment Store mit den übergebenen Daten,
   * ohne den Segment Store über die Änderung zu informieren (dass kein automatisches PUT stattfindet)
   * speichert die Orginal Daten für ein Rollback des Segments. Die vorhandene reject / commit Methodik kann 
   * zur Zwischenspeicherung hier nicht verwendet werden, da diese sonst automatisch den Store gegen den Server synct. 
   * @param {Number} id
   * @param {Object} data
   * @param {Object} newLength optional, defaults to null then. If given then length object: {"targetEdit": 123} 
   * @return {Editor.model.Segment}
   */
  updateSegment: function(id, data, newLength) {
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
    if(newLength && Ext.isNumeric(newLength.targetEdit)) {
        //alten metaCache holen, die neue targetEdit (fix da wdhe nur das kann) length einfügen, den neuen metaCache einsetzen
        //since changeAlike can not be done with multiple targets, and lengths are only usable for target fields, targetEdit is hardcoded here
        rec.updateMetaCacheLength('targetEdit', newLength.targetEdit);
    }
    rec.set(data);
    rec.endEdit(true);
    rec.commit();
    
    this.fireEvent('afterUpdateChangeAlike',rec);
    
    return rec;
  },
  handleCancelChangeAlike: function() {
      this.fireEvent('cancelManualProcessing', this.actualRecord, this.window, this);
      this.callbackToSaveChain();
      this.window.close();
      return false; //prevent default close action
  },
  isManualProcessingDisabled: function() {
    return (Editor.app.getUserConfig('alike.defaultBehaviour') == 'never');
  }, 
  isAutoProcessing: function() {
    return (Editor.app.getUserConfig('alike.defaultBehaviour') == 'always');
  }, 
  isManualProcessing: function() {
    return (Editor.app.getUserConfig('alike.defaultBehaviour') == 'individual');
  }
});