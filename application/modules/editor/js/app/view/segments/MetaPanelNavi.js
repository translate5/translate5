
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
    item_cancel: '#UT#Abbrechen',
    item_save: '#UT#Speichern',
    item_saveAndNext: '#UT#Speichern und nächstes öffnen (STRG + ENTER)',
    item_saveAndPrevious: '#UT#Speichern und vorheriges öffnen',
    item_alternateLeft: '#UT#Vorherige Spalte editieren',
    item_alternateRight: '#UT#Nächste Spalte editieren',
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
        items: [
          {
            xtype: 'button',
            itemId: 'saveSegmentBtn',
            tooltip: me.item_save,
            icon: Editor.data.moduleFolder+'images/tick.png',
            iconAlign: 'right'
          },
          {
            xtype: 'button',
            itemId: 'cancelSegmentBtn',
            tooltip: me.item_cancel,
            icon: Editor.data.moduleFolder+'images/cross.png',
            iconAlign: 'right'
          },
          {
            xtype: 'button',
            itemId: 'savePreviousSegmentBtn',
            icon: Editor.data.moduleFolder+'images/arrow_up.png',
            iconAlign: 'right',
            tooltip: me.item_saveAndPrevious
          },
          {
            xtype: 'button',
            itemId: 'saveNextSegmentBtn',
            icon: Editor.data.moduleFolder+'images/arrow_down.png',
            iconAlign: 'right',
            tooltip: me.item_saveAndNext
          },
          {
              xtype: 'button',
              itemId: 'goAlternateLeftBtn',
              hidden: !useHNavArrow,
              icon: Editor.data.moduleFolder+'images/arrow_left.png',
              iconAlign: 'right',
              tooltip: me.item_alternateLeft
          },
          {
              xtype: 'button',
              itemId: 'goAlternateRightBtn',
              hidden: !useHNavArrow,
              icon: Editor.data.moduleFolder+'images/arrow_right.png',
              iconAlign: 'right',
              tooltip: me.item_alternateRight
          }
        ]
      });
      me.callParent(arguments);
    }
  });