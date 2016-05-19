
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
 * @class Editor.plugins.pluginFeasibilityTest.view.Panel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.plugins.TmMtIntegration.view.TmOverviewPanel', {
    extend : 'Ext.panel.Panel',//'Ext.grid.Panel',
    requires: 'Editor.view.CheckColumn',
    alias: 'widget.TmOverviewGrid',
    //plugins: ['gridfilters'],
    itemId: 'tmOverviewGrid',
    cls: 'adminTaskGrid',
    title: 'TM Overview',
    height: '100%',
    layout: {
        type: 'fit'
    },
    viewConfig: {
        /**
         * returns a specific row css class
         * @param {Editor.model.admin.User} user
         * @return {Boolean}
         */
        getRowClass: function(user) {
            if(!user.get('editable')) {
                return 'not-editable';
            }
            return '';
        }
    },
    initConfig: function(instanceConfig) {
      var me = this,
          itemFilter = function(item){
              return Editor.app.authenticatedUser.isAllowed(item.isAllowedFor);
          },
      config = {
        	items :[{
        			xtype:'grid',
			        title: me.title, //see EXT6UPD-9
			        columns: [{
			            xtype: 'gridcolumn',
			            width: 100,
			            dataIndex: 'tmName',
			            filter: {
			                type: 'string'
			            },
			            text: 'TM Name'
			        },{
			            xtype: 'gridcolumn',
			            width: 100,
			        	dataIndex: 'sourceLanguage',
			        	cls: 'source-lang',
			            filter: {
			                type: 'string'
			            }
			        },{
			            xtype: 'gridcolumn',
			            width: 100,
			            dataIndex: 'destinationLanguage',
			            cls: 'target-lang',
			            filter: {
			                type: 'string'
			            }
			        },{
			            xtype: 'gridcolumn',
			            width: 100,
			            dataIndex: 'color',
			            text:'Color'
			        }
			        ,{ 
			        	xtype: 'gridcolumn',
			            width: 100,
			         	text: 'Edit/Delete',
			            filter: {
			                 type: 'string'
			            }
			        },{
			        	xtype: 'gridcolumn',
			            width: 100,
			         	text: 'Resource',
			            filter: {
			                 type: 'string'
			            }
			        }
			        ],
			        dockedItems: [{
			            xtype: 'toolbar',
			            dock: 'top',
			            items: [{
			                xtype: 'button',
			                iconCls: 'ico-user-add',
			                itemId: 'add-user-btn',
			                text: 'Add TM',
			                hidden: ! Editor.app.authenticatedUser.isAllowed('editorAddUser'), 
			                tooltip: 'tooltip'
			            },{
			                xtype: 'button',
			                iconCls: 'ico-refresh',
			                itemId: 'reload-user-btn',
			                text:'Refresh',
			                tooltip: 'tooltip'
			            }]
			        }
			        ,{
			            xtype: 'pagingtoolbar',
			            //store: 'admin.Users',
			            dock: 'bottom',
			            displayInfo: true
			        }]
        	}]
      };

      if (instanceConfig) {
          me.getConfigurator().merge(me, config, instanceConfig);
      }
      return me.callParent([config]);
    }
  });