
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
  }],
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
      }
  },
  startEditing: function(plugin,context) {
      this.getMatchgrid().controller.startEditing(context);//(context.record.get('taskGuid'),context.value);
  },
  endEditing : function(plugin,context) {
      this.getMatchgrid().controller.endEditing();//(context.record.get('taskGuid'),context.value);
  },
  onSegmentGridRender: function(grid) {
      var authUser = Editor.app.authenticatedUser;
      if(authUser.isAllowed('pluginMatchResourceMatchQuery') || authUser.isAllowed('pluginMatchResourceSearchQuery')){
          grid.addDocked({xtype: 'tmMtIntegrationTmMtEditorPanel',dock:'bottom'});
      }
  },
  setMatchInEditor: function(matchRecord) {
      console.log('setMatchInEditor', matchRecord);
     
      var me = this,
          plug = me.getSegmentGrid().editingPlugin,
          editor = plug.editor,
          rec = plug.context.record,
          matchrate = matchRecord.get('matchrate');
      
      if(plug.editing && plug.context.record && rec.get('editable')) {
          //Editor.MessageBox.addInfo("Show a message on take over content?");
          editor.mainEditor.setValueAndMarkup(matchRecord.get('target'), rec.get('id'), editor.columnToEdit);
          rec.set('matchRate', matchrate);
          me.getMatchrateDisplay().setRawValue(matchrate);
      } 
  }
});
