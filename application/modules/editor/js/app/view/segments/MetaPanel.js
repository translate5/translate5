
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
    title: 'Segment-Metadaten',

    layout: 'auto',
    
    //Item Strings:
    item_metaQm_title: '#UT#QM',
    item_metaStates_title: '#UT#Status',
    item_metaTerms_title: '#UT#Terminologie',
    item_metaStates_tooltip: '#UT#Segment auf den ausgewählten Status setzen (STRG + ALT + {0})',
    
    initComponent: function() {
      var me = this;
      Ext.applyIf(me, {
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
                  items: [
                    {
                      autoScroll: true,
                      xtype: 'panel',
                      border: 0,
                      itemId: 'metaTermPanel',
                      cls: 'metaTermPanel',
                      loader: {
                        url: Editor.data.restpath+'segment/terms',
                        renderer: 'html'
                      }
                    }
                  ]
              },
              {
                xtype: 'fieldset',
                itemId: 'metaQm',
                defaultType: 'radio',
                collapsible: true,
                hideable: Editor.data.segments.showQM, 
                hidden:  !Editor.data.segments.showQM,
                title: me.item_metaQm_title
              },
              {
                xtype: 'fieldset',
                itemId: 'metaStates',
                collapsible: true,
                defaultType: 'radio',
                hideable: Editor.data.segments.showStatus, 
                hidden:  !Editor.data.segments.showStatus,
                title: me.item_metaStates_title
              }
            ]
          }
        ]
      });

      me.callParent(arguments);
      me.addQualityFlags();
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
        stati.add({
          name: 'stateId',
          anchor: '100%',
          inputValue: item.id,
          boxLabel: '<span data-qtip="'+Ext.String.format(me.item_metaStates_tooltip, counter++)+'">'+item.label+'</span>'
        });
      });
    },
    /**
     * Fügt anhand der php2js Daten die QM Felder hinzu
     */
    addQualityFlags: function() {
      var me = this,
      qm = me.down('#metaQm'),
      flags = Editor.data.segments.qualityFlags;
      Ext.each(flags, function(item){
        qm.add({
          xtype: 'checkbox',
          name: 'qmId', 
          anchor: '100%',
          inputValue: item.id,
          boxLabel: item.label
        });
      });
    }
  });