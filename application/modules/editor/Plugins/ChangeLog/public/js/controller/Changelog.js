
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
  btnText: '#UT#Änderungsprotokoll der Version ',
  refs:[{
      ref: 'ChangeLogWindow',
      selector: '#changeLogWindow'
  }],
  listen: {
      component:{
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
  addButtonToTaskOverviewToolbar:function(pageingToolbar,event){
      var me = this,
          changelogfilter;
      pageingToolbar.add(['-',{
          xtype:'button',
          itemId:'changelogbutton',
          text: me.btnText+Editor.data.app.version
      }]);
      
      if(Editor.data.plugins.ChangeLog.lastSeenChangelogId>0){
          changelogfilter='[{"operator":"gt","value":'+Editor.data.plugins.ChangeLog.lastSeenChangelogId+',"property":"id"}]'; 
          me.loadChangelogStore(changelogfilter);
      }
      if(Editor.data.plugins.ChangeLog.lastSeenChangelogId<0){
          me.loadChangelogStore(changelogfilter);
      }
  },
  changeLogButtonClick:function(){
      var me=this;
      me.loadChangelogStore('');
  },
  loadChangelogStore:function(changelogfilter){
      var me = this, win,
          store = me.getEditorPluginsChangeLogStoreChangelogStore();
      
      store.clearFilter();
      win = me.getChangeLogWindow() || Ext.widget('changeLogWindow',{changeLogStore: store});
      store.load({
          params: {
              filter: changelogfilter
          },
          scope: me,
          callback: function(records, operation, success) {
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
