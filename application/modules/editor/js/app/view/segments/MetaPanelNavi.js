/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
    item_saveAndNext: '#UT#Speichern und nächstes öffnen',
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
            itemId: 'cancelSegmentBtn',
            tooltip: me.item_cancel,
            icon: Editor.data.moduleFolder+'images/cross.png',
            iconAlign: 'right'
          },
          {
            xtype: 'button',
            itemId: 'saveSegmentBtn',
            tooltip: me.item_save,
            icon: Editor.data.moduleFolder+'images/tick.png',
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