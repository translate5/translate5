
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
 * Die Einstellungen werden in einem Cookie gespeichert
 * @class Editor.controller.Preferences
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.TmMtIntegration.controller.TmOverviewController', {
  extend : 'Ext.app.Controller',
  views: ['Editor.plugins.TmMtIntegration.view.TmOverviewPanel','Editor.plugins.TmMtIntegration.view.AddTmWindow'],
  models: ['Editor.plugins.TmMtIntegration.model.Resource','Editor.plugins.TmMtIntegration.model.TmMt'],
  stores:['Editor.plugins.TmMtIntegration.store.Resources','Editor.plugins.TmMtIntegration.store.TmMts'],
  refs:[{
		ref: 'tmOverviewPanel',
		selector: '#tmOverviewPanel'
	},{
	      ref: 'centerRegion',
	      selector: 'viewport container[region="center"]'
	  },{
	      ref: 'headToolBar',
	      selector: 'headPanel toolbar#top-menu'
	  },{
	      ref: 'TmForm',
	      selector: '#addTmWindow form'
	  },{
	      ref: 'TmWindow',
	      selector: '#addTmWindow'
	  }
	],
  listen: {
      component: {
          '#btnTmOverviewWindow': {
              click: 'handleOnButtonClick'
          },
          '#tmOverviewPanel':{
        		hide: 'handleAfterHide',
        		show: 'handleAfterShow',
        		celldblclick: 'handleEditTm' 
          },
          '#btnAddTm':{
        	  click:'handleOnAddTmClick'
          },
          '#save-tm-btn':{
        	  click:'handleSaveWindowClick'
          },
          '#cancel-tm-btn':{
        	  click:'handleCancelWindowClick'
          },
          '#gridTmOverview actioncolumn':{
        	  click:'handleTmGridActionColumnClick'
          }
      }
  },
  handleAfterShow: function() {
      this.getHeadToolBar().down('#btnTmOverviewWindow').hide();
  },
  handleAfterHide: function() {
      this.getHeadToolBar().down('#btnTmOverviewWindow').show();
  },
  /**
   * inject the plugin tab and load the task meta data set
   */
  onParentRender: function(window) {
      var me = this;
      me.actualTask = window.actualTask;
      /*
      me.meta = Editor.plugins.TmMtIntegration.model.TaskAssocMeta.load(1, {
          success: function(rec) {
        	  alert('success');
              me.meta = rec;
          },
          failure: function() {
        	  alert('faill');
              me.showResult('Could not load information!');
          }
      });
      */
      me.getTaskTabs().add({xtype: 'tmMtIntegrationTaskAssocPanel', actualTask: me.actualTask});
  },
  handleOnButtonClick: function(window) {
      
      var me = this,
      grid = me.getTmOverviewPanel();
      
      me.actualTask = window.actualTask;
      
      //grid = me.getTmOverviewGrid();
      //grid.show();
      me.getCenterRegion().items.each(function(item){
          item.hide();
      });
      
      if(grid) {
          grid.show();
      }else {									
    	  me.getCenterRegion().add({xtype: 'TmOverviewPanel'}).show();
    	  me.handleAfterShow();
      }
  },
  handleOnAddTmClick : function(){
      var win = Ext.widget('addTmWindow');
      win.show();
  },
  handleSaveWindowClick:function(){
	  var me = this;

	  var win = me.getTmWindow();
	  this.getTmForm().submit({
          params: {
              format: 'jsontext'
          },
          url: Editor.data.restpath+'plugins_tmmtintegration_tmmt',
          scope: this,
          success: function(form, submit) {
        	  Ext.getCmp('gridTmOverview').getStore().load();
              win.setLoading(false);
        	  this.getTmWindow().close();
          },
          failure: function(form, submit) {
              win.setLoading(false);
              alert('Error');
          }
	  });
  },
  handleCancelWindowClick:function(){
	  this.getTmForm().getForm().reset();
      this.getTmWindow().close();
  },
  handleEditTm : function(view, cell, cellIdx, rec){
	  var win = Ext.widget('addTmWindow');
      win.show();
  },
  handleTmGridActionColumnClick:function(view, cell, row, col, ev, evObj) {
      var me = this,
      store = view.getStore(),
      selectedRow = store.getAt(row),
      t = ev.getTarget(),
      msg = me.strings,
      info,
      taskStore = Ext.StoreMgr.get('admin.Tasks'),
      f = t.className.match(/ico-tm-([^ ]+)/);
  
	  switch(f && f[1] || '') {
	      case 'edit':
	          me.handleEditTm(view,cell,col,selectedRow);
	          break;
	      case 'delete':
	    	  /*
	          if(!me.isAllowed('editorDeleteUser')) {
	              return;
	          }
	          info = Ext.String.format(msg.confirmDeleteMsg,selectedRow.get('firstName')+' '+selectedRow.get('surName'));
	          Ext.Msg.confirm(msg.confirmDeleteTitle, info, function(btn){
	              if(btn == 'yes') {
	            	  selectedRow.dropped = true;
	            	  selectedRow.save({
	                      failure: function() {
	                    	  selectedRow.reject();
	                      },
	                      success: function() {
	                          taskStore && taskStore.load();
	                          store.remove(selectedRow);
	                      }
	                  });
	              }
	          });
	          */
	          break;
	  }
  }
});
