
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
Ext.define('Editor.plugins.ChangeLog.controller.Changelog', {
  extend: 'Ext.app.Controller',
  views: ['Editor.plugins.ChangeLog.view.Changelog'],
  models: ['Editor.plugins.ChangeLog.model.Changelog'],
  stores:['Editor.plugins.ChangeLog.store.Changelog'],
  refs:[{
      ref: 'ChangeLogWindow',
      selector: '#changeLogWindow'
  }],
  listen: {
      component:{
    	'#adminTaskGrid':{
    		render:'addButtonToTaskOverviewToolbar'
    	},
    	'#btnCloseWindow':{
    		click:'btnCloseWindowClick'
    	}
      }
  },
  init: function(){
	    var me = this;
	    var store = me.getEditorPluginsChangeLogStoreChangelogStore();
	    store.on({
	        scope: me,
	        load: me.storeLoadFinished
	    });
	    me.callParent(arguments);
  },
  storeLoadFinished: function(store, records , successful , operation , eOpts){
	  if(records && records.length>0){
		  var win = Ext.widget('changeLogWindow',{changeLogStore: store});
		  win.show();
	  }
  },
  addButtonToTaskOverviewToolbar:function(panel,event){
	  var me=this,
	  	  pageingToolbar = panel.getComponent('pageingtoolbar');
	  pageingToolbar.add({
		  xtype:'button',
	      text: Editor.data.debug && Editor.data.debug.version,
	      listeners: {
	          click:me.changeLogButtonClick
	          //mouseover: function() {
	        	//  this.setTooltip(Editor.data.debug && Editor.data.debug.version + ' (ext '+Ext.getVersion().version+')');
	              // set a new config which says we moused over, if not already set
//	              if (!this.mousedOver) {
//	                  this.mousedOver = true;
//	                  alert('You moused over a button!\n\nI wont do this again.');
//	              }
	         // }
	      }
	  });
  },
  changeLogButtonClick:function(){
	  var me=this,
	  	  win,
	  	  store = Ext.create('Ext.data.Store', {
	  		  model: 'Editor.plugins.ChangeLog.model.Changelog'
	  	  });
	  store.load({
		  params: {
              filter: '[{"operator":"=","value":true,"property":"loadAll"}]'
          },
          scope: me,
          callback: function(records, operation, success) {
        	  if(records && records.length>0){
        		  win = Ext.widget('changeLogWindow',{changeLogStore: store});
        		  win.show();
        	  }
		  }
		});
  },
  btnCloseWindowClick:function(){
	  this.getChangeLogWindow().close();
  }
});
