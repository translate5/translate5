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

    requires:[
        'Editor.view.segments.SpecialCharacters'
    ],

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

    initComponent: function () {
        var me = this,
            //fields = Editor.data.task.segmentFields(),
            //editableCnt = 0,
            useHNavArrow = false,
            userCanModifyWhitespaceTags = Editor.app.getTaskConfig('segments.userCanModifyWhitespaceTags'),
            userCanInsertWhitespaceTags = Editor.app.getTaskConfig('segments.userCanInsertWhitespaceTags'),
            items = [],
            tooltip = function (text) {
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

        Ext.applyIf(me, {
            items: []
        });
        me.callParent(arguments);
    }
});