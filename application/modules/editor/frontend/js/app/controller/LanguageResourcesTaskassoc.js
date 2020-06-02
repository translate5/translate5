
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
 * @class Editor.controller.LanguageResourcesTaskassoc
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.LanguageResourcesTaskassoc', {
  extend : 'Ext.app.Controller',
  views: ['Editor.view.LanguageResources.TaskAssocPanel'],
  models: ['Editor.model.LanguageResources.TaskAssoc'],
  stores:['Editor.store.LanguageResources.TaskAssocStore'],
  strings: {
      assocSave: '#UT#Eintrag gespeichert!',
      assocDeleted: '#UT#Eintrag gelöscht!',
      assocSaveError: '#UT#Fehler beim Speichern der Änderungen!'
  },
  refs: [{
      ref: 'taskTabs',
      selector: 'adminTaskPreferencesWindow > tabpanel'
  },{
      ref: 'grid',
      selector: '#languageResourcesTaskAssocGrid'
  },{
      ref: 'adminTaskWindow',
      selector: 'adminTaskPreferencesWindow'
  },{
	  ref:'adminTaskAddWindow',
	  selector: '#adminTaskAddWindow'
  }],
  
  listen: {
      controller: {
          '#admin.TaskPreferences': {
              'loadPreferences': 'handleLoadPreferences'
          }
      },
      component: {
          '#languageResourcesTaskAssocGrid checkcolumn[dataIndex="segmentsUpdateable"]': {
              checkchange: 'handleSegmentsUpdateableChange'
          },
          '#languageResourcesTaskAssocGrid checkcolumn[dataIndex="checked"]': {
              checkchange: 'handleCheckedChange'
          }
      }
  },
  
  handleLoadPreferences: function(controller,task){
      var me = this,
          languageResourceparams = {
              params: {
                  filter: '[{"operator":"like","value":"'+task.get('taskGuid')+'","property":"taskGuid"}]'
              }
          };
      //set the actual task
      me.actualTask = task;
      me.getLanguageResourcesTaskAssocGrid().getStore().removeAll();
      me.getLanguageResourcesTaskAssocGrid().store.load(languageResourceparams);
  },
  
  /***
   * Save each assoc if if it is changed
   */
  saveTmAssoc: function(cmp) {
      var me = this,
          tmpStore = me.getLanguageResourcesTaskAssocGrid().store;

      if(me.getAdminTaskWindow()){
          me.getAdminTaskWindow().setLoading(true);
      }
      
      tmpStore.each(me.saveOneAssocRecord, me);
  },
  
  /**
   * uncheck segmentsUpdateable when uncheck whole row, restore segmentsUpdateable if recheck row
   */
  handleCheckedChange: function(column, rowIdx, checked){
      var me = this,
          record = me.getLanguageResourcesTaskAssocGrid().store.getAt(rowIdx),
          oldValue = record.isModified('segmentsUpdateable') && record.getModified('segmentsUpdateable');
      record.set('segmentsUpdateable', checked && oldValue);
      
      me.saveTmAssoc();
  },
  /**
   * check row when segmentsUpdateable is checked
   */
  handleSegmentsUpdateableChange: function(column, rowIdx, checked) {
      var me = this,
          record = me.getLanguageResourcesTaskAssocGrid().store.getAt(rowIdx);
      if(checked && !record.get('checked')) {
          record.set('checked', true);
      }
      me.saveTmAssoc();
  },
  /**
   * currently no easy "subentity" versioning is possible here, because of the bulk (store each) like saving / deleting.
   * on the other hand no versioning is needed, master entity languageResource does not contain changeable values which affects the taskassoc entity
   * The taskassocs itself can be handled by plain 404 already deleted and duplicate entry messages.
   */
  saveOneAssocRecord: function(record){
      var me = this;
      if(!record.dirty){
          me.hideLoadingMask();
          return;
      }
      var str = me.strings,
          params = {},
          method = 'DELETE',
          url = Editor.data.restpath+'languageresourcetaskassoc',
          checkedData = Ext.JSON.encode({
              languageResourceId: record.get('languageResourceId'),
              taskGuid: record.get('taskGuid'),
              segmentsUpdateable: record.get('segmentsUpdateable')
          });

      if(record.get('checked')) {
          method = record.get('taskassocid') ? 'PUT' : 'POST';
          params = {data: checkedData};
      }
      if(method != 'POST') {
          url = url + '/'+record.get('taskassocid');
      }
      
      Ext.Ajax.request({
          url:url,
          method: method,
          params: params,
          success: function(response){
              if(record.data.checked){
                  var resp = Ext.util.JSON.decode(response.responseText),
                      newId = resp.rows['languageResourceId'];
                  record.set('taskassocid', newId);
                  Editor.MessageBox.addSuccess(str.assocSave);
              }
              else {
                  record.set('taskassocid', 0);
                  Editor.MessageBox.addSuccess(str.assocDeleted);
              }
              record.commit();
              me.hideLoadingMask();

              //fire the event when all active requests are finished
        	  me.fireEvent('taskAssocSavingFinished',record,me.getLanguageResourcesTaskAssocGrid().getStore());
          },
          failure: function(response){
              Editor.app.getController('ServerException').handleException(response);
              me.hideLoadingMask();
          } 
      });
  },
  
  hideLoadingMask:function(){
      var me=this;
      if(!me.getAdminTaskWindow()){
          return;
      }
      var task = me.getAdminTaskWindow().getCurrentTask();
      me.getAdminTaskWindow().setLoading(false);
      task && task.load();
  },
  
  /**
   * Get the right language resources task assoc gid
   */
  getLanguageResourcesTaskAssocGrid:function(){
	  var me=this,
	  	addTaskWindow=me.getAdminTaskAddWindow();
	  if(addTaskWindow){
		  return addTaskWindow.down('#languageResourcesTaskAssocGrid');
	  }
	  return me.getAdminTaskWindow().down('#languageResourcesTaskAssocGrid');
  }
});
