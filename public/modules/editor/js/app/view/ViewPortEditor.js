
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
        'Editor.view.segments.MetaPanel',
        'Editor.view.segments.TerminologyPanel',
        'Editor.view.quality.FilterPanel',
        'Editor.view.comments.Navigation',
        'Editor.view.Filepanel'
    ],

    viewModel: {
        type: 'viewportEditor'
    },
    
    layout: {
      type: 'border'
    },

    //Item Strings:
    items_north_title: 'Header',
    items_west_title: '#UT#QS &amp; Dateien',
    initComponent: function() {
    var me = this,
        task = Editor.data.task,
        isEditor = Editor.app.authenticatedUser.isAllowed('editorEditTask', task),
        items = [{
            xtype: 'panel',
            stateId: 'editor.westPanel',
            stateEvents: ['collapse', 'expand'],
            stateful:true,
            region: 'west',
            weight: 30,
            resizable: true,
            resizeHandles: 'e',
            title: me.items_west_title,
            width: 250,
            collapsible: true,
            layout: {type:'accordion'}, // accordian layout requires panels as children, calls e.g. addBodyCls
            animCollapse: true,
            bodyPadding: 0,
            defaults: {margin:0},
            itemId: 'filepanel',
            items: [{
                xtype: 'qualityFilterPanel',
                stateId: 'editor.westPanelQualityFilter',
                stateEvents: ['collapse', 'expand'],
                stateful: true
            },{
                xtype: 'commentNavigation',
                stateId: 'editor.commentNav',
                stateEvents: ['collapse', 'expand'],
                stateful: true
            },{
                xtype:'taskfiles',
                stateId: 'editor.taskFiles',
                stateEvents: ['collapse', 'expand'],
                stateful: {collapsed:true},
                itemId: 'filesection'
            }]
        },{
            region: 'center', // implicit flex:1
            height: 236, // 236 is high enough to show all action buttons on the right
            xtype: 'segments.grid',
                id: 'segment-grid',
                itemId: 'segmentgrid',
                stateful: {
                    segmentSize: true,
                    columns: true,
                    sorters: false,
                    filters: false,
                    grouper: false,
                    storeState: false // → does not work
                }
                //stateful:true → see additional config in Grid Class
        },{
            xtype: 'panel',
            stateId: 'editor.eastPanel',
            itemId:'editorEastPanel',
            stateEvents: ['collapse', 'expand'],
            stateful:true,
            region: 'east',
            width: 330,
            weight: 30,
            collapsible: true,
            layout:'border',
            animCollapse: true,
            border:0,
            header:{
                height:49,
            },
            items:[
                me.getBrandConfig(),{
                xtype: 'panel',
                region: 'center',
                preventHeader: true,
                border:0,
                itemId: 'metapanel',
                layout: { type:'accordion' },
                items: [{
                    xtype: 'terminologyPanel',
                    stateId: 'editor.eastPanelSegmentsTerminology',
                    stateEvents: ['collapse', 'expand'],
                    stateful:true
                },{
                    xtype: 'segmentsMetapanel',
                    stateId: 'editor.eastPanelSegmentsMetapanel',
                    stateEvents: ['collapse', 'expand'],
                    stateful:true
                },{
                    xtype: 'commentPanel',
                    stateId: 'editor.eastPanelCommentPanel',
                    stateEvents: ['collapse', 'expand'],
                    stateful:true
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

      //must be set before child components will use it
      me.getViewModel().set('taskIsReadonly', task.isReadOnly() || !isEditor);
      me.getViewModel().set('taskHasDefaultLayout', task.get('defaultSegmentLayout'));

      me.callParent(arguments);
    },
    
    /***
     * Get the editor branding configuration. If brandingSource is provided, component loader with this source will be initialized
     */
    getBrandConfig:function(){
    	var config={
    		  xtype: 'panel',
          	  cls: 'head-panel-brand',
          	  maxHeight:150,
          	  maxWidth:'100%',
          	  region: 'north',
          	  autoScroll:true,
          	  border:0,
    	};
    	
    	//if the branding source is provided, load the content from the branding source
    	if(Editor.data.editor.editorBrandingSource){
    		return  Ext.Object.merge(config,{
    			items: [{
    				xtype: 'component',
    				autoEl: {
    					tag: 'iframe',
    					style: {
    						border : 0
    					},
    					src:Editor.data.editor.editorBrandingSource,
    				}
    			}]
    		});
    	}
		return  Ext.Object.merge(config,{
			html: Editor.app.getTaskConfig('editor.customHtmlContainer'),
		});
    }
});
