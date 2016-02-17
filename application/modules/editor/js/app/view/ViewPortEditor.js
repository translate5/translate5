
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
 * @class Editor.view.ViewPortEditor
 * @extends Ext.container.Viewport
 */
Ext.define('Editor.view.ViewPortEditor', {
    extend: 'Ext.container.Viewport',
    requires: [
      'Editor.view.fileorder.Tree',
      'Editor.view.fileorder.ReferenceTree',
      'Editor.view.segments.Grid',
      'Editor.view.segments.MetaPanelNavi',
      'Editor.view.segments.MetaPanel'
    ],

    layout: {
      type: 'border'
    },

    //Item Strings:
    items_north_title: 'Header',
    items_west_title: '#UT#Dateien',
    
    initComponent: function() {
      var me = this,
          items = [me.getNorth(), {
              xtype: 'panel',
              region: 'east',
              title: '&nbsp;', // here there must be a titile (otherwise warning appear), but the titile must be invisible
              collapsible: true,
              layout: 'fit',
              animCollapse: !Ext.isIE, //BugID 3
              items:[{
                  xtype: 'panel',
                  listeners: {
                      afterrender: function() {
                          this.disable();
                      }
                  },
                  preventHeader: true,
                  border:0,
                  itemId: 'metapanel',
                  layout: {type:'accordion'},
                  dockedItems: [{
                      xtype: 'metapanelNavi',
                      dock: 'top'
                  }],
                  items: [{
                      xtype: 'segmentsMetapanel'
                  },{
                      xtype: 'commentWindow'
                  }]
              }],
              width: 260
          },{
              region: 'center',
              xtype: 'segments.grid',
              itemId: 'segmentgrid'
          },{
              xtype: 'panel',
              resizable: true,
              resizeHandles: 'e',
              title: me.items_west_title,
              width: 150,
              collapsible: true,
              layout: {type:'accordion'},
              animCollapse: !Ext.isIE, //BugID 3
              region: 'west',
              itemId: 'filepanel',
              items: [{
                  xtype: 'fileorder.tree'
              },{
                  xtype: 'referenceFileTree'
              }]
          }];
      Ext.applyIf(me, {
          items: items
      });
      me.callParent(arguments);
    },
    getNorth: function() {
        return {
            xtype: 'headPanel',
            region: 'north'
        };
    }
});