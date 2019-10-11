
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.view.ViewPortEditor
 * @extends Ext.container.Viewport
 */
Ext.define('Editor.view.ViewPortEditor', {
    extend: 'Ext.container.Viewport',
    itemId: 'editorViewport',
    isEditorViewport: true,
    requires: [
        'Editor.view.ViewPortEditorViewModel',
        'Editor.view.fileorder.Tree',
        'Editor.view.fileorder.ReferenceTree',
        'Editor.view.segments.Grid',
        'Editor.view.segments.MetaPanelNavi',
        'Editor.view.segments.MetaPanel'
    ],

    viewModel: {
        type: 'viewportEditor'
    },
    
    layout: {
      type: 'border'
    },

    //Item Strings:
    items_north_title: 'Header',
    items_west_title: '#UT#Dateien',
    initComponent: function() {
      var me = this,
          items = [{
              xtype: 'panel',
              region: 'west',
              weight: 30,
              resizable: true,
              resizeHandles: 'e',
              title: me.items_west_title,
              width: 250,
              collapsible: true,
              layout: {type:'accordion'},
              animCollapse: !Ext.isIE, //BugID 3
              itemId: 'filepanel',
              items: [{
                  xtype: 'fileorder.tree'
              },{
                  xtype: 'referenceFileTree'
              }]
          },{
              region: 'center',
              xtype: 'segments.grid',
              itemId: 'segmentgrid'
          },{
              xtype: 'panel',
              region: 'east',
              width: 330,
              weight: 30,
              collapsible: true,
              layout:'border',
              animCollapse: !Ext.isIE, //BugID 3
              items:[{
            	  xtype: 'panel',
            	  cls: 'head-panel-brand',
            	  maxHeight:150,
            	  maxWidth:'100%',
            	  region: 'north',
            	  autoScroll:true,
            	  html: Editor.data.app.branding,
              },{
                  xtype: 'panel',
                  region: 'center',
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
                      xtype: 'commentPanel'
                  }]
              }]
          }];
      //},{
      //example of adding an additional south panel with width 100%, 
      // as heigher the weight of the region, as "outer" it is rendererd, 
      // since east and west have weight 30, a panel with lesser weight will be rendered more "inner"  
      //xtype: 'panel',
      //weight: 51,
      //region: 'south'
      Ext.applyIf(me, {
          items: items
      });
      me.callParent(arguments);
    }
});