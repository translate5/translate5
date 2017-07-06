
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 * @class Editor.view.segments.ChangeMarkup
 */
Ext.define('Editor.view.segments.ChangeMarkup', {
    editor: null,
    /**
     * The given segment content is the base for the operations provided by this method
     * @param {String} content
     */
    constructor: function(editor) {
        editor = editor;
    },
    /**
     * This method is called if the keyboard event was not handled otherwise
     */
    handleTargetEvent: function(event) {
        console.log("DO CHANGE MARKUP");
        //keys die keinen content produzieren (strg,alt,shift alleine ohne taste, pfeile etc) müssen ignoriert werden
        //Bei normalem Tippen:
        // Wenn wir uns in einem INS befinden das uns gehört, nichts machen
        // Wenn wir uns in keinem INS befinden, eine INS node hinzufügen und dann das event weiterlaufn lassen
        //  Wenn wir uns dabei in einem DEL befinden, dieses an dieser Stelle zuerst auseinander brechen und dann den INS einfügen
        //wenn keycode ein delete oder backspace ist: 
        // Wenn wir uns in einem DEL event stoppen
        //  Außer bei backspace ganz am Anfang des DELs, dann das Zeichen davor löschen sprich in den DEL mit reinpacken
        //  Außer bei delete ganz am Ende des DELs, dann das Zeichen dahinter löschen sprich in den DEL mit reinpacken
        //Der DOM an dieser Stelle beihaltet IMG tags, und div.term tags. 
        //Eine Verschachtelung von INS und DELs untereinander ist in keiner Weise gestattet:
        // Das darf nicht produziert werden: <ins><ins></ins></ins> bzw <ins><del></del></ins>
        // Das muss dann heißen: <ins></ins><ins></ins> bzw <ins></ins><del></del><ins></ins>
        // Sonst ist eine Auflösung bzw. Handling der change marks nur erschwert möglich.
        // Zu den div.term tags gibt es eine Notiz im Konzept, im Prinzip kann der div.term gelöscht werden wenn darin editiert wird, denn dann passt der Term eh nicht mehr.
        // img tags sind als einzelne Zeichen zu behandeln.
        //Habe ich einige Fälle vergessen?
    }
});