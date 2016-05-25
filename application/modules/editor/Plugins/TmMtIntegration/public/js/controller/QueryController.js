
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
Ext.define('Editor.plugins.TmMtIntegration.controller.QueryController', {
  extend : 'Ext.app.Controller',
  listen: {
      component: {
          'segmentsToolbar': {
              render: 'onToolbarRender'
          },
          'segmentsToolbar #queryTest': {
              click: 'handleOnButtonClick'
          }
      }
  },
  onToolbarRender: function(toolbar) {
      toolbar.add({xtype: 'button', text: 'TM MT Query Test', itemId: 'queryTest'});
  },
  handleOnButtonClick: function(window) {
      var me = this;
      Ext.Ajax.request({
          url:Editor.data.restpath+'plugins_tmmtintegration_query',
              method: "POST",
              params: {
                  data: Ext.JSON.encode({
                      //type: concordance|mtmatch,
                      //segmentId: segment Id for reference, 
                      query: "string to query, if omitted load above segment and get content from there",
                      tmmtId: 3
                      //taskGuid: me.actualTask.get('taskGuid')
                  })
              },
              success: function(response){ 
                  console.log(response.responseText); 
              }, 
              failure: function(response){ 
                  console.log(response.responseText); 
              } 
      });
  }
});
