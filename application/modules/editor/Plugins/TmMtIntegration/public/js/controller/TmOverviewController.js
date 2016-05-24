
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
  views: ['Editor.plugins.TmMtIntegration.view.TmOverviewPanel'],
  //models: ['Editor.plugins.TmMtIntegration.model.TaskAssocMeta'],
  //stores:['Editor.plugins.TmMtIntegration.store.TaskAssoc'],
  refs:[{
		ref: 'tmOverviewGrid',
		selector: '#tmOverviewGrid'
	},{
	      ref: 'centerRegion',
	      selector: 'viewport container[region="center"]'
	  },{
	      ref: 'headToolBar',
	      selector: 'headPanel toolbar#top-menu'
	  }
	],
  listen: {
      component: {
          '#btnTmOverviewWindow': {
              click: 'handleOnButtonClick'
          },
        	'#tmOverviewGrid':{
        		hide: 'handleAfterHide',
        		show: 'handleAfterShow',
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
      	  grid = me.getTmOverviewGrid();
      
      me.actualTask = window.actualTask;
      
      //grid = me.getTmOverviewGrid();
      //grid.show();
      me.getCenterRegion().items.each(function(item){
          item.hide();
      });
      
      if(grid) {
          grid.show();
      }else {									
    	  me.getCenterRegion().add({xtype: 'TmOverviewGrid'}).show();
    	  me.handleAfterShow();
      }
  }
});
