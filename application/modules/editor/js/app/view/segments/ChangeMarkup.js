
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
    eventCharCode: null,
    eventKeyChar:  null,
    eventKeyCode:  null,
    eventKeyWhich: null,
    docSel: null,
    docSelRange: null,
    stopEvent: null,
    /**
     * The given segment content is the base for the operations provided by this method
     * @param {Editor.view.segments.HtmlEditor} content
     */
    constructor: function(editor) {
        this.editor = editor;
    },
    initEvent: function() {
        // "Reset"
        this.eventCharCode = null;
        this.eventKeyChar  = null;
        this.eventKeyCode  = null;
        this.eventKeyWhich = null;
        this.docSel = null;
        this.docSelRange = null;
        this.stopEvent = null;
    },
    /**
     * This method is called if the keyboard event (= keydown) was not handled otherwise
     */
    handleTargetEvent: function(event) {
        
        // alles auf "Null"
        this.initEvent();
        
        // What keyboard event do we deal with?
        this.setKeyEvent(event);
        
        // keys die keinen content produzieren (strg,alt,shift alleine ohne taste, pfeile etc) müssen ignoriert werden
        if(this.keyEventHasToBeIgnored()){ 
            console.log(" => Ignored!");
            return;
        }
        
        // Change the Markup in the Editor
        this.changeMarkupInEditor();
        
        // Stop event?
        if(this.stopEvent) {
            event.stopEvent(); 
        }
    },
    
    /**
     * Set infos about key from event; see also:
     * - http://unixpapa.com/js/key.html
     * - https://www.quirksmode.org/js/keys.html#t20
     */
    setKeyEvent: function(event) {
        this.eventCharCode  = event.charCode;
        this.eventKeyChar   = event.event.key;
        this.eventKeyCode   = event.keyCode;
        this.eventKeyWhich  = event.event.which;
        
        //console.dir(event); 
        console.log("event.event.key: " + event.event.key       + " => eventKeyChar: " +  this.eventKeyChar); 
        console.log("event.event.which: " + event.event.which   + " => eventKeyWhich: " + this.eventKeyWhich); 
        console.log("event.keyCode: " + event.keyCode           + " => eventKeyCode: "  + this.eventKeyCode); 
        console.log("event.charCode: " + event.charCode         + " => eventCharCode: " + this.eventCharCode);
        console.log("String.fromCharCode(event.event.which): " + String.fromCharCode(event.event.which));
        
    },
    
    /**
     * Key-Events to ignore.
     * (Detect special keys according to: http://unixpapa.com/js/key.html "Conclusions")
     */
    keyEventHasToBeIgnored: function() {
        return false; 
        // TODO: Das hier funktioniert plötzlich nicht mehr; eventCharCode ist IMMER 0, auch bei der Eingabe von Buchstaben. WARUM!!!!
        // special keys?
        if ( (this.eventKeyWhich == null) || (this.eventKeyWhich != 0 && this.eventCharCode == 0) ) {
            console.log("special");
            if (this.eventIsDeletion()) {
                console.log("eventIsDeletion");
                return false;  // if special key is deletion: HAS NOT to be ignored
            } else {
                console.log("not eventIsDeletion");
                return true;   // other special keys: HAS to be ignored
            }
        }
        // otherwise: HAS NOT to be ignored
        console.log("otherwise");
        return false;
    },

    /**
     * Is the Key-Event a DELETION?
     */
    eventIsDeletion: function() {
        var keyCodesForDeletion = [8,46];
        return (keyCodesForDeletion.indexOf(this.eventKeyCode) != -1);
    },

    /**
     * Is the Key-Event a INSERTION?
     */
    eventIsInsertion: function() {
        return true;
    },
    
    /**
     * Insert INS-/DEL-Tags while typing
     */
    changeMarkupInEditor: function() {
        
        // get range according to selection (using rangy-library)
        this.docSel = rangy.getSelection(this.editor.getDoc());
        this.docSelRange = this.docSel.rangeCount ? this.docSel.getRangeAt(0) : null;
        
        if (this.docSelRange == null) {
            console.log("ChangeMarkup: getSelection FAILED.");
            return;
        }
        
        // change markup according to event
        var node = null;
        switch(true) {
            case this.eventIsDeletion():
                console.log(this.eventKeyChar + " => eventIsDeletion");
                node = this.handleDeletion();
            break;
            case this.eventIsInsertion():
                console.log(this.eventKeyChar + " => eventIsInsertion");
                node = this.handleInsert();
            break;
            default:
                node = null;
            break;
        }
                
                // AT WORK: further checks for controlling what to do
                console.dir(this.docSelRange);
                if(this.docSelRange.startContainer != null) {
                    console.log("range.startContainer nodeName: " + this.docSelRange.startContainer.nodeName);
                }
                if(this.docSelRange.startContainer.parentElement != null) {
                    console.log("range.startContainer.parentElement nodeName: " + this.docSelRange.startContainer.parentElement.nodeName);
                }
                if(this.docSelRange.startContainer.previousSibling != null) {
                    console.log("range.startContainer.previousSibling nodeName: " + this.docSelRange.startContainer.previousSibling.nodeName);
                }
                if(this.docSelRange.startContainer.nextSibling != null) {
                    console.log("range.startContainer.nextSibling nodeName: " + this.docSelRange.startContainer.nextSibling.nodeName);
                }
                console.log("-------------------------");
        
        //Der DOM an dieser Stelle beihaltet IMG tags, und div.term tags. 
        //Eine Verschachtelung von INS und DELs untereinander ist in keiner Weise gestattet:
        // Das darf nicht produziert werden: <ins><ins></ins></ins> bzw <ins><del></del></ins>
        // Das muss dann heißen: <ins></ins><ins></ins> bzw <ins></ins><del></del><ins></ins>
        // Sonst ist eine Auflösung bzw. Handling der change marks nur erschwert möglich.
        // Zu den div.term tags gibt es eine Notiz im Konzept, im Prinzip kann der div.term gelöscht werden wenn darin editiert wird, denn dann passt der Term eh nicht mehr.
        // img tags sind als einzelne Zeichen zu behandeln.
        //Habe ich einige Fälle vergessen?
    },

    /**
     * Handle deletion-Events.
        // wenn keycode ein delete oder backspace ist: 
        // - Außer bei backspace ganz am Anfang des DELs, dann das Zeichen davor löschen sprich in den DEL mit reinpacken
        // - Außer bei delete ganz am Ende des DELs, dann das Zeichen dahinter löschen sprich in den DEL mit reinpacken
     */
    handleDeletion: function() {
        // Wenn wir uns in einem DEL event befinden: stoppen
        if(this.docSelRange.startContainer.parentElement.nodeName == "DEL") {
            return null;
        }
        
        // Andernfalls übernehmen wir die Handhabung des Events.
        this.stopEvent = true;
        this.markDeletion();
    },

    /**
     * Handle insert-Events.
        //Bei normalem Tippen:
        // - Wenn wir uns in keinem INS befinden, eine INS node hinzufügen und dann das event weiterlaufn lassen
        // - Wenn wir uns dabei in einem DEL befinden, dieses an dieser Stelle zuerst auseinander brechen und dann den INS einfügen
     */
    handleInsert: function() {
        // Wenn wir uns in einem INS befinden das uns gehört (und im gleichen Workflow Schritt ist → mit Marc klären), nichts machen
        if(this.docSelRange.startContainer.parentElement.nodeName == "INS") {
            return null;
        }
        
        // Das Event wird ausgeführt, aber vorher setzen wir das <ins> drumrum
        this.stopEvent = false;
        this.markInsertion();
    },
    
    /**
     * Marks a deletion as deleted.
     * Statt den Char zu löschen, umgeben wir ihn mit <del>
     */
    markDeletion: function() {
        // create range to be marked as deleted
        var rangeForDel = rangy.createRange(),
            startNode   = this.docSelRange.startContainer,
            startOffset = this.docSelRange.startOffset,
            endNode     = this.docSelRange.endContainer,
            endOffset   = this.docSelRange.endOffset;
        switch(this.eventKeyCode) {
            case 8: // Backspace: "deletes" the previous character 
                startOffset -= 1;
            break;
            case 46: // Delete "deletes" the next character
                endOffset += 1;
            break;
        }
        rangeForDel.setStartAndEnd(startNode, startOffset, endNode, endOffset);
        // create and attach <del>-Element
        el = document.createElement("del")
        if (rangeForDel.canSurroundContents(el)) {
            rangeForDel.surroundContents(el);
        } else {
            console.log("Unable to surround range because range partially selects a non-text node. See DOM4 spec for more information.");
        }
        // TODO: position the caret!
    },
    
    /**
     * Marks an insertion as inserted.
     * (Bevor das Einfügen ausgeführt wird, umgeben wir die Stelle mit einem <ins>.)
     */
    markInsertion: function() {
        // create and insert <ins>-node
        node = document.createElement("ins");
        this.docSelRange.collapse(false);
        this.docSelRange.insertNode(node);
        // position the caret
        var rangeForPos = rangy.createRange();
        rangeForPos.setStart(node, 0);
        rangeForPos.collapse(true);
        this.docSel.removeAllRanges();
        this.docSel.setSingleRange(rangeForPos);
    }
});
