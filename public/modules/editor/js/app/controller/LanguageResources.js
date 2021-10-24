
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
 * @class Editor.controller.LanguageResources
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.LanguageResources', {
  extend: 'Ext.app.Controller',
  views: ['Editor.view.LanguageResources.EditorPanel'],
  models: ['Editor.model.LanguageResources.EditorQuery','Editor.model.LanguageResources.TaskAssoc'],
  requires: [
      'Editor.util.SegmentContent',
      'Editor.util.LanguageResources',
      'Editor.view.LanguageResources.services.Default',
      'Editor.view.LanguageResources.services.TermCollection',
      'Editor.view.LanguageResources.services.OpenTM2'
  ],
  refs:[{
      ref: 'matchgrid',
      selector: '#matchGrid'
  },{
      ref: 'segmentGrid',
      selector:'#segmentgrid'
  },{
      ref: 'matchrateDisplay',
      selector: '#roweditor displayfield[name=matchRate]'
  },{
      ref: 'editorPanel',
      selector:'#languageResourceEditorPanel'
  },{
      ref: 'editorViewport',
      selector:'#editorViewport'
  }],
  listen: {
      component: {
          '#segmentgrid': {
              //render: 'onSegmentGridRender',
              beforeedit: 'startEditing',
              canceledit: 'endEditing',
              afterrender:'centerPanelAfterRender',
              edit: 'endEditing'
          },
          '#matchGrid': {
              chooseMatch: 'setMatchInEditor'
          }
      },
      controller: {
          '#Editor.$application': {
              editorViewportOpened: 'afterInitEditor'
          },
          '#Editor': {
              beforeKeyMapUsage: 'handleEditorKeyMapUsage'
          },
          '#ViewModes':{
              viewModeChanged:'viewModeChangeEvent'
          },
          '#Editor.plugins.TrackChanges.controller.Editor':{
              setValueForEditor:'setValueForEditor'
          }
      }
  },
  strings: {
      msgDisabledMatchRate: '#UT#Das Projekt enthält alternative Übersetzungen. Bei der Übernahme von Matches wird die Segment Matchrate daher nicht verändert.',
      msgDisabledSourceEdit: '#UT#Beim Bearbeiten der Quellspalte können Matches nicht übernommen werden.',
      instantTranslate:'#UT#InstantTranslate'
  },
  init: function() {
	  //INFO: the default service is initialized in the TmOverview controller
	  //since this controller can be disabled via the acl right, and the service classes are used
	  //in the tmoverview panel
	  
      //add the specific service instances, if needed
      Editor.util.LanguageResources.addService(Ext.create('Editor.view.LanguageResources.services.TermCollection'));
      Editor.util.LanguageResources.addService(Ext.create('Editor.view.LanguageResources.services.OpenTM2'));
  },
  assocStore: null,
  SERVER_STATUS: null,//initialized after center panel is rendered
  languageResourceValueForEditor: null,
  afterInitEditor: function() {
      var task = Editor.data.task;
      if(!task.get('defaultSegmentLayout')){
          Editor.MessageBox.addInfo(this.strings.msgDisabledMatchRate, 1.4);
      }
  },
  startEditing: function(plugin,context) {
      var me=this;
      if(!me.isLanguageResourcesDisabled()){
          me.getMatchgrid().controller.startEditing(context);//(context.record.get('taskGuid'),context.value);
      }
  },
  endEditing: function(plugin,context) {
      var me=this;
      if(!me.isLanguageResourcesDisabled()){
          me.getMatchgrid().controller.endEditing();//(context.record.get('taskGuid'),context.value);
      }
  },
  centerPanelAfterRender: function(){
      var me=this,
          authUser = Editor.app.authenticatedUser;
      if(!Editor.data.task.isReadOnly() && (authUser.isAllowed('languageResourcesMatchQuery') || authUser.isAllowed('languageResourcesSearchQuery'))){
          me.loadAssocStore();
      }
      me.SERVER_STATUS = Editor.model.LanguageResources.EditorQuery.prototype;
  },
  handleEditorKeyMapUsage: function(cont, area, mapOverwrite) {
      var me = this;
      
      if(me.isLanguageResourcesDisabled()){
    	  return;
      }
      
      cont.keyMapConfig['ctrl-DIGIT'] = [cont.DEC_DIGITS,{ctrl: true, alt: false},function(key) {
          if(!me.getMatchgrid() || !me.getMatchgrid().isVisible()) {
              return;
          }
          var toUse = Number(key) - 49,
              store = me.getMatchgrid().store,
              matchRecord = store.getAt(toUse);
          if(matchRecord) {
              me.setMatchInEditor(matchRecord);
          }
      }, true];
  },
  setMatchInEditor: function(matchRecord) {
      var me = this,
          plug = me.getSegmentGrid().editingPlugin,
          editor = plug.editor,
          task = Editor.data.task,
          rec = plug.context.record,
          matchrate = matchRecord.get('matchrate');

      if(matchRecord.get('state') !== me.SERVER_STATUS.SERVER_STATUS_LOADED){
          return;
      }
      
      //don't take over the match when the source column is edited
      if(editor.isSourceEditing()) {
          Editor.MessageBox.addWarning(this.strings.msgDisabledSourceEdit);
          return;
      }
      if(plug.editing && rec && rec.get('editable')) {
          //Editor.MessageBox.addInfo("Show a message on take over content?");
          me.setValueForEditor(matchRecord.get('target'));
          me.fireEvent('prepareCompleteReplace',matchRecord.get('target'),false); // if TrackChanges are activated, DEL- and INS-markups are added first and then setValueForEditor is applied from there (= again, but so what)
          editor.mainEditor.setValueAndMarkup(me.languageResourceValueForEditor, rec, editor.columnToEdit);
          if(Editor.data.task.get('emptyTargets')) {
        	  editor.mainEditor.insertMarkup(rec.get('source'), true);
    	  }
          //we don't support the matchrate saving for tasks with alternatives:
          if(task.get('defaultSegmentLayout')) {
              rec.set('matchRate', matchrate);
              rec.wasOriginalTargetUpdated = true;
              rec.set('target', matchRecord.get('target')); // when taking over a match we want the original target to be updated to the match/pretranslated value
              //TODO how to implement a check if user modified the match afterwards to add the "interactive" flag?
              rec.set('matchRateType', Editor.data.LanguageResources.matchrateTypeChangedState+';languageResourceid='+matchRecord.get('languageResourceid')); 
              me.getMatchrateDisplay().setRawValue(matchrate);
          }
      }
  },
  setValueForEditor: function(value) {
      var me = this;
      me.languageResourceValueForEditor = value;
  },
  viewModeChangeEvent: function(controller){
      var me = this,
          vm = me.getEditorViewport().getViewModel();

      if(!me.getEditorPanel()) {
          return;
      }

      if(me.isLanguageResourcesDisabled()){
    	  return;
      }
      
      if(vm.get('editorIsReadonly')) {
          me.getEditorPanel().collapse();
      }
      else {
          me.getEditorPanel().expand();
      }
  },
  loadAssocStore: function(){
      var me = this,
          taskGuid = Editor.data.task.get('taskGuid'),
          prm = {
                params: {
                    filter: '[{"operator":"like","value":"'+taskGuid+'","property":"taskGuid"},{"operator":"eq","value":true,"property":"checked"}]'
                },
                callback:me.addEditorPanelToViewPort,
                scope: me
          };
      me.assocStore = Ext.create('Ext.data.Store', {
          model: 'Editor.model.LanguageResources.TaskAssoc'
      });
      me.assocStore.load(prm);
  },
  addEditorPanelToViewPort: function() {
      var me = this;
      if(me.isLanguageResourcesDisabled()){
          return;
      }

      me.getEditorViewport().add({
          xtype: 'languageResourceEditorPanel',
          region: 'south',
          weight: 5,
          resizeHandles: 'n',
          listeners: {
              //remove the flex value after panel creation, since with flex value set not resizing is allowed
              boxready: function(panel) {
                  var height = panel.getHeight();
                  panel.setFlex(0);
                  panel.setHeight(height);
              }
          },
          height:Editor.data.task.get('visualReviewFiles') ? '25%' : '30%',
          // minheight remains also for manual resizing
          minHeight: 150,
          //collapsing is independant of resizing
          collapsible: true,
          resizable: true,
          assocStore:me.assocStore
      });
  },
  getAssocStoreCount: function(){
      var store = this.assocStore;
      return store ? store.getTotalCount() : 0;
  },
  
  /***
   * Check if the language resources match grid should be/is disabled
   */
  isLanguageResourcesDisabled: function(){
      var disableIfTermCollectionOnly =Editor.app.getTaskConfig('editor.LanguageResources.disableIfOnlyTermCollection'),
          assoc = Editor.data.task.get('taskassocs') ? Editor.data.task.get('taskassocs') : null,
          assocCount = assoc ? assoc.length : 0,
          termCollectionCount = 0;
      
      //no results in the assoc store -> disabled
      if(!assoc || assocCount <= 0){
        return true;
      }
      
      //foreach rec in the assoc store get the termcollection count
      assoc.forEach(function(record){
        if(record.resourceType === Editor.util.LanguageResources.resourceType.TERM_COLLECTION){
            termCollectionCount++;
        }
      });
      
      //disabled if only term collections and it is configuret do disable the panel
      return termCollectionCount === assocCount && disableIfTermCollectionOnly;
  }
});
