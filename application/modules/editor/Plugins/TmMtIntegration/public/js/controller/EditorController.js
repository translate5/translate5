
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
 * @class Editor.plugins.TmMtIntegration.controller.EditorController
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.TmMtIntegration.controller.EditorController', {
  extend : 'Ext.app.Controller',
  views: ['Editor.plugins.TmMtIntegration.view.EditorPanel'],
  models:['Editor.plugins.TmMtIntegration.model.EditorQuery'],
  refs:[{
	   ref: 'matchgrid',
 	   selector: '#matchGrid'
  }],
  listen: {
      component: {
          '#segmentgrid': {
              render: 'onEditorGridRender',
              beforeedit: 'startEditing',
              canceledit: 'endEditing',
              edit: 'endEditing'
          }
      }
  },
  init : function() {
      var me = this;
      //    toc = me.application.getController('editor.PrevNextSegment');
      console.log(me);
      //toc.on('prevnextloaded',me.prevNextLoaded, me);
  },
  startEditing: function(plugin,context) {
	  //FIXME another way to get segmentId ?
	  this.getMatchgrid().controller.startEditing(context);//(context.record.get('taskGuid'),context.value);
  },
  endEditing : function(plugin,context) {
	  this.getMatchgrid().controller.endEditing();//(context.record.get('taskGuid'),context.value);
  },
  onEditorGridRender: function(grid) {
	  if(Editor.app.authenticatedUser.isAllowed('pluginMatchMatchQuery') || Editor.app.authenticatedUser.isAllowed('pluginMatchSearchQuery')){
		  grid.addDocked({xtype: 'tmMtIntegrationTmMtEditorPanel',dock:'bottom'});
	  }
  },
  prevNextLoaded : function(){
	  alert('ace');
  }
  /*
  handleInitEditor: function() {
	  //Editor.data.task contains current task
	  
	  //this.assocStore = new Ext.data.Store({model: 'Editor.plugins.TmMtIntegration.model.TaskAssoc',});
	  //this.assocStore.load({params object as in the other controller});
  },
  handleSegmentBeginEdit : function(contex){
      var me = this;
      
      this.assocStore.each();
      
      Ext.Ajax.request({
          url:Editor.data.restpath+'plugins_tmmtintegration_query',
              method: "POST",
              params: {
                  data: Ext.JSON.encode({
                      type: 'query', //query => mtmatch | search => concorance,
                      //column for which the search was done (target | source)
                      //segmentId: segment Id for reference, 
                      query: contex.value,
                      tmmtId: 25,
                      taskGuid: contex.record.get('taskGuid')
                  })
              },
              success: function(response){
				  var resp = Ext.util.JSON.decode(response.responseText);
				  var newId = resp.rows['id'];
				  
				  var record = Editor.plugins.TmMtIntegration.model.EditorQuery.create(resp.rows.result);
				  
				  me.getEditorPluginsTmMtIntegrationStoreEditorQueryStore().add(record);
              }, 
              failure: function(response){ 
                  console.log(response.responseText); 
              } 
      });
  }
  */
});
