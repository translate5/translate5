
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
 * @class Editor.plugins.ChangeLog.controller.Changelog
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.ChangeLog.controller.Changelog', {
  extend: 'Ext.app.Controller',
  views: ['Editor.plugins.ChangeLog.view.Changelog'],
  models: ['Editor.plugins.ChangeLog.model.Changelog'],
  stores:['Editor.plugins.ChangeLog.store.Changelog'],
  btnText: '#UT#Änderungsprotokoll der Version ',
  refs:[{
      ref: 'ChangeLogWindow',
      selector: '#changeLogWindow'
  }],
  listen: {
      component:{
        '#adminMainSection':{
            render:'showPopup'
        },
    	'#adminTaskGrid #pageingtoolbar':{
    		render:'addButtonToTaskOverviewToolbar'
    	},
    	'#btnCloseWindow':{
    		click:'btnCloseWindowClick'
    	},
    	'#adminTaskGrid #pageingtoolbar #changelogbutton':{
    	    click:'changeLogButtonClick'
    	}
    	
      }
  },
  init: function(){
      var me = this;
      me.callParent(arguments);
  },
  showPopup: function() {
      var lastSeen = Editor.data.plugins.ChangeLog.lastSeenChangelogId;
      // show all changelogs with id bigger than given in last seen
      // lastSeen > 0 show the bigger (newer) ones
      // lastSeen < 0 (-1) User opens the application the first time, and gets a list of all changes
      if(lastSeen !== 0){
          //when id filter is set, the highest found id is stored as last seen
          this.loadChangelogStore(lastSeen);
      }
      //lastSeen == 0: User has already seen all available changelogs
  },
  addButtonToTaskOverviewToolbar:function(pageingToolbar,event){
      pageingToolbar.add(['-',{
          xtype:'button',
          itemId:'changelogbutton',
          text: this.btnText+Editor.data.app.version
      }]);
  },
  changeLogButtonClick:function(){
      var me=this;
      me.loadChangelogStore();
  },
  loadChangelogStore:function(initalFilter){
      var me = this, win,
          store = me.getEditorPluginsChangeLogStoreChangelogStore(),
          params = {};
      
      //for window creation the store suppressNextFilter must be set to true, otherwise the rendering with filters would trigger a load
      win = me.getChangeLogWindow() || Ext.widget('changeLogWindow',{changeLogStore: store});
      //disable the suppressing again after store init, so that filters can process normally
      store.suppressNextFilter = true;
      store.clearFilter();
      if(initalFilter) {
          store.filter({
              "operator":"gt",
              "value":initalFilter,
              "property":"id"
          });
      }
      store.load({
          callback: function(records, operation, success) {
              store.suppressNextFilter = false;
              if(records && records.length>0){
                  win.show();
                  win.down('pagingtoolbar').updateBarInfo();
              }
          }
      });
  },
  btnCloseWindowClick:function(){
	  this.getChangeLogWindow().close();
  }
});
