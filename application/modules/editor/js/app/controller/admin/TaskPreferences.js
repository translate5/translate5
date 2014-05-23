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
/**
 * Editor.controller.admin.TaskPreferences encapsulates the logic around the Task Preferences Window and the UserPrefs Tab 
 * @class Editor.controller.admin.TaskPreferences
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.TaskPreferences', {
  extend : 'Ext.app.Controller',
  models: ['admin.TaskUserAssoc','admin.Task','admin.task.UserPref'],
  stores: ['admin.Users', 'admin.TaskUserAssocs', 'admin.task.UserPrefs'],
  views: ['Editor.view.admin.task.PreferencesWindow', 'Editor.view.admin.task.UserAssocGrid','Editor.view.admin.task.Preferences'],
  refs : [{
      ref: 'taskAddWindow', //FIXME DUMMY entry
      selector: '#adminTaskAddWindow'
  }],
  strings: {
      taskWorkflowSaved: "#UT#Workflow der Aufgabe gespeichert!"
  },
  actualTask: null,
  init : function() {
      var me = this,
          toc = me.application.getController('admin.TaskOverview');
      
      toc.on('handleTaskPreferences', me.handleTaskPreferences, me);
      
      me.addEvents(
              /**
               * @event taskCreated
               * @param {Ext.form.Panel} form
               * @param {Ext.action.Submit} submit
               * FIXME
               */
              'DUMMY EVENT'
      );
      
      //@todo on updating ExtJS to >4.2 use Event Domains and this.listen for the following controller / store event bindings
      Editor.app.on('adminViewportClosed', me.clearStores, me);

      me.control({
          '.editorAdminTaskPreferences #taskWorkflow': {
              change: me.changeWorkflow
          },
          '.editorAdminTaskUserPrefsForm #alternates .checkboxgroup': {
              beforerender: me.prepareAlternates
          },
          '.editorAdminTaskUserPrefsGrid': {
              beforerender: me.setActualTaskInGrid
          }
      });
  },
  /**
   * Method Shortcut for convenience
   * @param {String} right
   * @param {Editor.model.admin.Task} task [optional]
   * @return {Boolean}
   */
  isAllowed: function(right, task) {
      return Editor.app.authenticatedUser.isAllowed(right, task);
  },
  
  /**
   * Opens the Preferences to the choosen Task
   * fires also an event to allow flexible handling of this click, 
   * if handler(s) return false no preferences are opened!
   * triggerd by click on the Task Preferences Button / (Cell also => @todo)
   * @param {Editor.model.admin.Task} task
   */
  handleTaskPreferences: function(task) {
      var me = this,
          userPrefs = me.getAdminTaskUserPrefsStore(),
          userAssocs = me.getAdminTaskUserAssocsStore(),
          //wenn die Stores echte Filter bekommt muss der Wert hier miteingebaut werden!
          tuaParams = {
              params: {
                  filter: '[{"type":"string","value":"'+task.get('taskGuid')+'","field":"taskGuid"}]'
              }
          };
      
      me.actualTask = task;
      
      if(me.isAllowed('editorPreferencesTask')){
          //FIXME dieses isAllowed nur in der view!
      }
      if(me.isAllowed('editorChangeUserAssocTask')){
          //FIXME dieses isAllowed nur in der view!
      }
      
      //userPrefs must be loaded after userAssocs, 
      //so add the load as a callback dynamically, based on the rights 
      if(me.isAllowed('editorUserPrefsTask')){
          userPrefs.loadData([],false); //cleanup old contents
          var tupParams = Ext.apply({}, tuaParams); //duplicate params, and add the callback
          tuaParams.callback = function() {
              userPrefs.load(tupParams);
          };
      }
      
      userAssocs.loadData([],false); //cleanup old contents
      userAssocs.load(tuaParams);
      
      Ext.widget('adminTaskPreferencesWindow',{
          actualTask: task
      }).show();
  },
  
  /**
   * saves the new workflow into the task, and to the server
   * @param {Ext.form.fiueld.ComboBox} combo
   * @param {String} val
   */
  changeWorkflow: function(combo, val) {
      var me = this;
      me.actualTask.set('workflow', val);
      me.actualTask.save({
          success: function(rec, op) {
              Editor.MessageBox.addInfo(me.strings.taskWorkflowSaved);
          }
      });
      
  },
  /**
   * adds one checkbox per alternate in the config form 
   * @param {Ext.form.CheckboxGroup} checkboxGroup
   */
  prepareAlternates: function(checkboxGroup) {
      this.actualTask.segmentFields().each(function(field){
          checkboxGroup.add({
              xtype: 'checkbox',
              boxLabel: field.get('label'),
              name: field.get('name')
          });
      });
  },
  /**
   * sets a reference of the actual task in the grid
   * @param grid
   */
  setActualTaskInGrid: function(grid) {
      var labels = {};
      grid.actualTask = this.actualTask;
      this.actualTask.segmentFields().each(function(field){
          labels[field.get('name')] = field.get('label');
      });
      grid.fieldLabels = labels;
  },
  //FIXME clear the local used stores?
  clearStores: function() {
  }
});