
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
 * @class Editor.view.segments.MetaPanelNavi
 * @extends Ext.toolbar.Toolbar
 */
Ext.define('Editor.view.segments.MetaPanelNavi', {
    alias: 'widget.metapanelNavi',
    extend: 'Ext.toolbar.Toolbar',
    
    //ui: 'header',
    layout: {
        pack: 'start',
        type: 'hbox'
    },
    
    itemId: 'naviToolbar',

    //height: 250,
    //bodyPadding: 10,
    //autoScroll: true,
    //frameHeader: false,
    //title: 'Segment-Metadaten',

    //Item Strings:
    item_startWatchingSegment: '#UT#Segment auf die Merkliste setzen',
    item_stopWatchingSegment: '#UT#Segment von der Merkliste entfernen',
    item_cancel: '#UT#Abbrechen',
    item_reset: '#UT#Segment auf initialen Inhalt zurücksetzen',
    item_save: '#UT#Speichern',
    item_saveAndNext: '#UT#Speichern und nächstes Segment öffnen (STRG + ALT + ENTER)',
    item_saveAndNextFiltered: '#UT#Speichern und nächstes Segment im Workflow öffnen (STRG + ENTER)',
    item_saveAndPrevious: '#UT#Speichern und vorheriges Segment öffnen',
    item_alternateLeft: '#UT#Vorherige Spalte editieren (STRG + ←)',
    item_alternateRight: '#UT#Nächste Spalte editieren (STRG + →)',
    item_next: '#UT#Nicht speichern und nächstes Segment öffnen (STRG + ALT + ↓)',
    item_nextFiltered: '#UT#Nicht speichern und nächstes Segment im Workflow öffnen (STRG + ↓)',
    item_prev: '#UT#Nicht speichern und vorheriges Segment öffnen (STRG + ALT + ↑)',
    item_prevFiltered: '#UT#Nicht speichern und vorheriges Segment im Workflow öffnen (STRG + ↑)',
    initComponent: function() {
      var me = this,
          fields = Editor.data.task.segmentFields(),
          editableCnt = 0;
          useHNavArrow = false;
      fields.each(function(field) {
          if(field.get('editable')) {
              editableCnt++;
          }
      });
      useHNavArrow = editableCnt > 1;
      
      Ext.applyIf(me, {
        items: [{
            xtype: 'buttongroup',
            columns: 3,
            defaults: {
                scale: 'small'
            },
            items: [{
                xtype: 'button',
                itemId: 'saveSegmentBtn',
                tooltip: me.item_save,
                icon: Editor.data.moduleFolder+'images/tick.png',
                iconAlign: 'right'
            },{
                xtype: 'button',
                itemId: 'cancelSegmentBtn',
                tooltip: me.item_cancel,
                icon: Editor.data.moduleFolder+'images/cross.png',
                iconAlign: 'right'
            },{
                xtype: 'button',
                itemId: 'goAlternateLeftBtn',
                hidden: !useHNavArrow,
                icon: Editor.data.moduleFolder+'images/arrow_left.png',
                iconAlign: 'right',
                tooltip: me.item_alternateLeft
            },{
                xtype: 'button',
                itemId: 'watchSegmentBtn',
                id: 'watchSegmentBtn',
                icon: Editor.data.moduleFolder+'images/star.png',
                enableToggle: true,
                iconAlign: 'right'
            },{
                xtype: 'button',
                itemId: 'resetSegmentBtn',
                tooltip: me.item_reset,
                icon: Editor.data.moduleFolder+'images/arrow_undo.png',
                iconAlign: 'right'
            },{
                xtype: 'button',
                itemId: 'goAlternateRightBtn',
                hidden: !useHNavArrow,
                icon: Editor.data.moduleFolder+'images/arrow_right.png',
                iconAlign: 'right',
                tooltip: me.item_alternateRight
            }]
        },{
            xtype: 'buttongroup',
            columns: 2,
            defaults: {
                scale: 'small'
            },
            items: [{
                xtype: 'button',
                hidden: true
            },{
                xtype: 'button',
                itemId: 'goToUpperByWorkflowNoSaveBtn',
                icon: Editor.data.moduleFolder+'images/arrow_up_filtered_nosave.png ',
                iconAlign: 'right',
                tooltip: me.item_prevFiltered
            },{
                xtype: 'button',
                itemId: 'saveNextByWorkflowBtn',
                icon: Editor.data.moduleFolder+'images/arrow_down_filtered.png',
                iconAlign: 'right',
                tooltip: me.item_saveAndNextFiltered
            },{
                xtype: 'button',
                itemId: 'goToLowerByWorkflowNoSaveBtn',
                icon: Editor.data.moduleFolder+'images/arrow_down_filtered_nosave.png',
                iconAlign: 'right',
                tooltip: me.item_nextFiltered
            }]
        },{
            xtype: 'buttongroup',
            columns: 2,
            defaults: {
                scale: 'small'
            },
            items: [{
                xtype: 'button',
                itemId: 'savePreviousSegmentBtn',
                icon: Editor.data.moduleFolder+'images/arrow_up.png',
                iconAlign: 'right',
                tooltip: me.item_saveAndPrevious
            },{
                xtype: 'button',
                itemId: 'goToUpperNoSaveBtn',
                icon: Editor.data.moduleFolder+'images/arrow_up_nosave.png ',
                iconAlign: 'right',
                tooltip: me.item_prev
            },{
                xtype: 'button',
                itemId: 'saveNextSegmentBtn',
                icon: Editor.data.moduleFolder+'images/arrow_down.png',
                iconAlign: 'right',
                tooltip: me.item_saveAndNext
            },{
                xtype: 'button',
                itemId: 'goToLowerNoSaveBtn',
                icon: Editor.data.moduleFolder+'images/arrow_down_nosave.png',
                iconAlign: 'right',
                tooltip: me.item_next
            }]
        }]
      });
      me.callParent(arguments);
    }
  });