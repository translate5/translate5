
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
 * @class Editor.view.segments.MetaPanel
 * @extends Editor.view.ui.segments.MetaPanel
 * @initalGenerated
 */
Ext.define('Editor.view.segments.MetaPanel', {
    alias: 'widget.segmentsMetapanel',
    extend: 'Ext.panel.Panel',

    bodyPadding: 10,
    scrollable: 'y',
    frameHeader: false,
    id: 'segment-metadata',
    itemId:'editorSegmentsMetaData',
    strings:{
        title: '#UT#Segment-Metadaten',
    },

    layout: 'auto',

    item_metaTerms_title: '#UT#Terminologie',
    item_metaStates_title: '#UT#Status',
    item_metaStates_tooltip: '#UT#Segment auf den ausgewählten Status setzen (ALT + S danach {0})',
    item_metaStates_tooltip_nokey: '#UT#Segment auf den ausgewählten Status setzen',
    
    initComponent: function() {
      var me = this,
          showStatus = Editor.app.getTaskConfig('segments.showStatus');
          
      Ext.applyIf(me, {
        title:me.title,
        items: [
          {
            xtype: 'form',
            border: 0,
            itemId: 'metaInfoForm',
            items: [{
                  xtype: 'fieldset',
                  itemId: 'metaTerms',
                  collapsible: true,
                  title: me.item_metaTerms_title,
                  anchor: '100%',
                  items: [{
                      autoScroll: true,
                      xtype: 'panel',
                      border: 0,
                      minHeight: 60, //needed so that loader is fully shown on segments without terms
                      itemId: 'metaTermPanel',
                      cls: 'metaTermPanel',
                      loader: {
                          url: Editor.data.restpath+'segment/terms',
                          loadMask: true,
                          renderer: 'html'
                      }
                  }]
              },{
                  xtype: 'segmentQm',
                  itemId: 'segmentQm',
                  collapsible: true
              },{
                  xtype: 'segmentQualities',
                  itemId: 'segmentQualities',
                  collapsible: true
              },{
                  xtype: 'fieldset',
                  itemId: 'metaStates',
                  collapsible: true,
                  defaultType: 'radio',
                  hidden:  !showStatus,
                  title: me.item_metaStates_title
              }]
          }
        ]
      });

      me.callParent(arguments);
      me.addStateFlags();
    },
    /**
     * Fügt anhand der php2js Daten die Status Felder hinzu
     */
    addStateFlags: function() {
        var me = this,
            stati = me.down('#metaStates'),
            flags = Editor.data.segments.stateFlags,
            counter = 1;
        
        Ext.each(flags, function(item){
            var tooltip; 
            if(counter < 10) {
                tooltip = Ext.String.format(me.item_metaStates_tooltip, counter++);
            } else {
                tooltip = me.item_metaStates_tooltip_nokey;
            }
            stati.add({
                name: 'stateId',
                anchor: '100%',
                inputValue: item.id,
                boxLabel: '<span data-qtip="'+tooltip+'">'+item.label+'</span>'
            });
        });
    }
  });