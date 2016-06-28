
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
 * @class Editor.plugins.MatchResource.controller.Editor
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.MatchResource.controller.Editor', {
  extend : 'Ext.app.Controller',
  views: ['Editor.plugins.MatchResource.view.EditorPanel'],
  models:['Editor.plugins.MatchResource.model.EditorQuery'],
  refs:[{
      ref: 'matchgrid',
      selector: '#matchGrid'
  },{
      ref : 'segmentGrid',
      selector:'#segmentgrid'
  },{
      ref: 'matchrateDisplay',
      selector: '#roweditor displayfield[name=matchRate]'
  },{
      ref : 'editorPanel',
      selector:'#tmMtIntegrationTmMtEditorPanel'
  }],
  msgDisabledMatchRate: '#UT#Das Projekt enthält alternative Übersetzungen. Bei der Übernahme von Matches wird die Segment Matchrate daher nicht verändert.',
  assocStore : null,
  listen: {
      component: {
          '#segmentgrid': {
              render: 'onSegmentGridRender',
              beforeedit: 'startEditing',
              canceledit: 'endEditing',
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
          '#editorcontroller': {
              beforeKeyMapUsage: 'handleEditorKeyMapUsage'
          },
          '#ViewModes':{
              viewModeChanged:'viewModeChangeEvent'
          }
      }
  },
  SERVER_STATUS_STATE: {
      LOADED:'loaded',
      LOADING: 'loading',
      NORESULT: 'noresult',
      SERVERERROR:'servererror',
      CLIENTTIMEOUT:'clienttimeout'
  },
  afterInitEditor: function() {
      var task = Editor.data.task;
      if(!task.get('defaultSegmentLayout')){
          Editor.MessageBox.addInfo(this.msgDisabledMatchRate, 1.4);
      }
  },
  startEditing: function(plugin,context) {
      var me=this;
      if(me.assocStore.totalCount > 0){
          me.getMatchgrid().controller.startEditing(context);//(context.record.get('taskGuid'),context.value);
      }
  },
  endEditing : function(plugin,context) {
      var me=this;
      if(me.assocStore.totalCount > 0){
          me.getMatchgrid().controller.endEditing();//(context.record.get('taskGuid'),context.value);
      }
  },
  onSegmentGridRender: function(grid) {
      var me=this;
      var authUser = Editor.app.authenticatedUser;
      if(!Editor.data.task.isReadOnly() && (authUser.isAllowed('pluginMatchResourceMatchQuery') || authUser.isAllowed('pluginMatchResourceSearchQuery'))){
          me.checkAssocStore(grid);
      }
  },
  handleEditorKeyMapUsage: function(cont, area, mapOverwrite) {
      var me = this;
      
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
      
      if(matchRecord.get('state')!=me.SERVER_STATUS_STATE.LOADED){
          return;
      }
      //we dont support the matchrate saving for tasks with alternatives:
      if(!task.get('defaultSegmentLayout')) {
          return;
      }
      
      if(plug.editing && plug.context.record && rec.get('editable')) {
          //Editor.MessageBox.addInfo("Show a message on take over content?");
          editor.mainEditor.setValueAndMarkup(matchRecord.get('target'), rec.get('id'), editor.columnToEdit);
          rec.set('matchRate', matchrate);
          rec.set('matchRateType', 'matchresourceusage'); 
          me.getMatchrateDisplay().setRawValue(matchrate);
      } 
  },
  viewModeChangeEvent : function(controller){
      var me = this;
      //isViewMode
      //isErgonomicMode
      //isEditMode
      if(controller.self.isViewMode()){
          if(this.getEditorPanel()){
              this.getEditorPanel().hide();
          }
          return;
      }
      if(this.getEditorPanel()){
          this.getEditorPanel().show();
      }
  },
  checkAssocStore: function(grid){
      var me=this
      taskGuid = Editor.data.task.get('taskGuid'),
      prm = {
            params: {
                filter: '[{"operator":"like","value":"'+taskGuid+'","property":"taskGuid"},{"operator":"eq","value":true,"property":"checked"}]'
            },
            callback:function(){
                if(me.assocStore.totalCount > 0){
                    grid.addDocked({
                        xtype: 'tmMtIntegrationTmMtEditorPanel',
                        dock:'bottom',
                        assocStore:me.assocStore,
                    });
                }
            },
            scope : me
      };
      me.assocStore = Ext.create('Ext.data.Store', {
          model: 'Editor.plugins.MatchResource.model.TaskAssoc'
      }),
      me.assocStore.load(prm);
  }
});
