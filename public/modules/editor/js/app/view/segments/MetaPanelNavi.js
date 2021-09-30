
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
 * @class Editor.view.segments.MetaPanelNavi
 * @extends Ext.toolbar.Toolbar
 */
Ext.define('Editor.view.segments.MetaPanelNavi', {
    alias: 'widget.metapanelNavi',
    extend: 'Ext.toolbar.Toolbar',
    
    border: false,
    layout: {
        type: 'vbox',
        align: 'left'
    },
    
    itemId: 'naviToolbar',

    //height: 250,
    //bodyPadding: 10,
    //autoScroll: true,
    //frameHeader: false,

    //Item Strings:
    item_startWatchingSegment: '#UT#Segment auf Lesezeichenliste (STRG + D)',
    item_stopWatchingSegment: '#UT#Segment von Lesezeichenliste entfernen (STRG + D)',
    item_scrollToSegment: '#UT#Zur Bearbeitung geöffnetes Segment wieder in den Sichtbereich bewegen (STRG + G)',
    item_cancel: '#UT#Abbrechen (ESC)',
    item_reset: '#UT#Segment auf initialen Inhalt zurücksetzen (ALT + ENTF)',
    item_save: '#UT#Speichern (STRG + S)',
    item_saveAndNext: '#UT#Speichern und nächstes bearbeitbares Segment öffnen (STRG + ALT + ENTER)',
    item_saveAndNextFiltered: '#UT#Speichern. <br/>Nächstes unbestätigtes, bearbeitbares Segment öffnen<br/>(STRG + ENTER)',
    item_saveAndPrevious: '#UT#Speichern und vorheriges bearbeitbares Segment öffnen (STRG + ALT + SHIFT + ENTER)',    
    item_alternateLeft: '#UT#Vorherige Spalte editieren (STRG + ALT + ←)',
    item_alternateRight: '#UT#Nächste Spalte editieren (STRG + ALT + →)',
    item_next: '#UT#Nicht speichern und nächstes bearbeitbares Segment öffnen (STRG + ALT + ↓)',
    item_nextFiltered: '#UT#Nicht speichern.<br/>Nächstes unbestätigtes, bearbeitbares Segment öffnen<br/>(ALT + Bild ↓)',
    item_prev: '#UT#Nicht speichern und vorheriges bearbeitbares Segment öffnen (STRG + ALT + ↑)',
    item_prevFiltered: '#UT#Nicht speichern.<br/>Vorheriges unbestätigtes, bearbeitbares Segment öffnen<br/>(ALT + Bild ↑)',
    item_whitespaceButtonGroup: '#UT#Sonderzeichen hinzufügen:',
    initComponent: function() {
      var me = this,
          //fields = Editor.data.task.segmentFields(),
          //editableCnt = 0,
          useHNavArrow = false,
          userCanModifyWhitespaceTags = Editor.app.getTaskConfig('segments.userCanModifyWhitespaceTags'),
          userCanInsertWhitespaceTags = Editor.app.getTaskConfig('segments.userCanInsertWhitespaceTags'),
          items=[],
          tooltip = function(text) {
              return {
                  dismissDelay: 0,
                  text: text
              };
          };

      //TODO: this is disabled because of the TRANSLATE-1827 !!!
      //the button layout when the buttons are active is calculated wrong!!!
      //fields.each(function(field) {
      //    if(field.get('editable')) {
      //        editableCnt++;
      //    }
      //});
      //useHNavArrow = editableCnt > 1;
      
      items=[{
    	  xtype:'container',
    	  layout: {
    		  type: 'hbox'
	      },
	      flex:1,
    	  items:[{
	          xtype: 'buttongroup',
	          columns: 3,
	          defaults: {
	              scale: 'small'
	          },
	          items: [{
	              xtype: 'button',
	              itemId: 'saveBtn',
	              tooltip: tooltip(me.item_save),
	              icon: Editor.data.moduleFolder+'images/tick.png',
	              iconAlign: 'right'
	          },{
	              xtype: 'button',
	              itemId: 'cancelBtn',
	              tooltip: tooltip(me.item_cancel),
	              icon: Editor.data.moduleFolder+'images/cross.png',
	              iconAlign: 'right'
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
	              tooltip: tooltip(me.item_reset),
	              icon: Editor.data.moduleFolder+'images/arrow_undo.png',
	              iconAlign: 'right'
	          },{
	              xtype: 'button',
	              itemId: 'scrollToSegmentBtn',
	              tooltip: tooltip(me.item_scrollToSegment),
	              icon: Editor.data.moduleFolder+'images/scrollTo.png',
	              iconAlign: 'right'
	          }]
	      },{
	          xtype: 'buttongroup',
	          columns: 2,
	          defaults: {
	              scale: 'small'
	          },
	          items: [{
	              xtype: 'button',
	              itemId: 'goToUpperByWorkflowNoSaveBtn',
	              icon: Editor.data.moduleFolder+'images/arrow_up_filtered_nosave.png ',
	              iconAlign: 'right',
	              tooltip: tooltip(me.item_prevFiltered)
	          },{
	              xtype: 'button',
	              itemId: 'saveNextByWorkflowBtn',
	              icon: Editor.data.moduleFolder+'images/arrow_down_filtered.png',
	              iconAlign: 'right',
	              tooltip: tooltip(me.item_saveAndNextFiltered)
	          },{
	              xtype: 'button',
	              itemId: 'goToLowerByWorkflowNoSaveBtn',
	              icon: Editor.data.moduleFolder+'images/arrow_down_filtered_nosave.png',
	              iconAlign: 'right',
	              tooltip: tooltip(me.item_nextFiltered)
	          }]
	      },{
	          xtype: 'buttongroup',
	          columns: useHNavArrow ? 3 : 2,
	          defaults: {
	              scale: 'small'
	          },
	          items: [{
	              xtype: 'button',
	              itemId: 'savePreviousBtn',
	              icon: Editor.data.moduleFolder+'images/arrow_up.png',
	              iconAlign: 'right',
	              tooltip: tooltip(me.item_saveAndPrevious)
	          },{
	              xtype: 'button',
	              itemId: 'goToUpperNoSaveBtn',
	              icon: Editor.data.moduleFolder+'images/arrow_up_nosave.png ',
	              iconAlign: 'right',
	              tooltip: tooltip(me.item_prev)
	          },{
	              xtype: 'button',
	              itemId: 'goAlternateLeftBtn',
	              hidden: !useHNavArrow,
	              icon: Editor.data.moduleFolder+'images/arrow_left.png',
	              iconAlign: 'right',
	              tooltip: tooltip(me.item_alternateLeft)
	          },{
	              xtype: 'button',
	              itemId: 'saveNextBtn',
	              icon: Editor.data.moduleFolder+'images/arrow_down.png',
	              iconAlign: 'right',
	              tooltip: tooltip(me.item_saveAndNext)
	          },{
	              xtype: 'button',
	              itemId: 'goToLowerNoSaveBtn',
	              icon: Editor.data.moduleFolder+'images/arrow_down_nosave.png',
	              iconAlign: 'right',
	              tooltip: tooltip(me.item_next)
	          },{
	              xtype: 'button',
	              itemId: 'goAlternateRightBtn',
	              hidden: !useHNavArrow,
	              icon: Editor.data.moduleFolder+'images/arrow_right.png',
	              iconAlign: 'right',
	              tooltip: tooltip(me.item_alternateRight)
	          }]
	      }]
      }];
      
      // whitespace-icons
      if (userCanModifyWhitespaceTags && userCanInsertWhitespaceTags) {
    	  items.push({
  	 		 	xtype: 'buttongroup',
  	 		 	width:'97%',
  	 		 	height:45,
  	 			items:[{
  	 				xtype: 'displayfield',
  	 	            value: me.item_whitespaceButtonGroup
  	 			},{
  	 				xtype: 'button',
  	 				border: 1,
  	 				style: {
  	 				    borderColor: '#d0d0d0',
  	 				    borderStyle: 'solid'
  	 				},
  	 				width:28,
  	 				height:28,
  	 				padding:0,
  	 				text: '→',
  	 				itemId: 'btnInsertWhitespaceTab',
  	 				tooltip: 'TAB'
  	 			},{
  	 				xtype: 'button',
  	 				border: 1,
  	 				style: {
  	 				    borderColor: '#d0d0d0',
  	 				    borderStyle: 'solid'
  	 				},
  	 				width:28,
  	 				height:28,
  	 				padding:0,
  	 				text: '↵',
  	 				itemId: 'btnInsertWhitespaceNewline',
  	 				tooltip: 'SHIFT+ENTER'
  	 			},{
  	 				xtype: 'button',
  	 				border: 1,
  	 				style: {
  	 				    borderColor: '#d0d0d0',
  	 				    borderStyle: 'solid'
  	 				},
  	 				width:28,
  	 				height:28,
  	 				padding:0,
  	 				text: '⎵',
  	 				itemId: 'btnInsertWhitespaceNbsp',
  	 				tooltip: 'CTRL+SHIFT+Space'
  	 			}]
    	  });
      }
         
      Ext.applyIf(me, {
        items:items
      });
      me.callParent(arguments);
    }
  });