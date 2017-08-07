
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
    eventKey: null,
    docSel: null,
    docSelRange: null,
    stopEvent: null,
    
    // "CONSTANTS"
    KEYCODE_BACKSPACE: 8,
    KEYCODE_DELETE: 46,
    KEYCODE_LEFT: 37,
    KEYCODE_UP: 38,
    KEYCODE_RIGHT: 39,
    KEYCODE_DOWN: 40,
    KEYCODE_ENTER: 13,
    KEYCODE_ALT: 18,
    KEYCODE_CTRL: 17,
    KEYCODE_SHIFT: 16,
    KEYCODE_ALT_GR: 225,
    
    /**
     * The given segment content is the base for the operations provided by this method
     * @param {Editor.view.segments.HtmlEditor} content
     */
    constructor: function(editor) {
        this.editor = editor;
    },
    initEvent: function() {
        // "Reset"
        this.eventKey = null;
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
        this.setEventKey(event);
        
        // keys die keinen content produzieren (strg,alt,shift alleine ohne taste, pfeile etc) müssen ignoriert werden
        if(this.eventHasToBeIgnored()){ 
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
     * Set infos about key from event.
     * Further information on keyboard events:
     * - http://unixpapa.com/js/key.html
     * - https://www.quirksmode.org/js/keys.html#t20
     */
    setEventKey: function(event) {
        this.eventKey = event.keyCode;
    },
    
    /**
     * Has the Key-Event to be IGNORED?
     */
    eventHasToBeIgnored: function() {
        var keyCodesToIgnore = [
                                this.KEYCODE_LEFT, this.KEYCODE_UP, this.KEYCODE_RIGHT, this.KEYCODE_DOWN,  // Arrow Keys
                                this.KEYCODE_ALT, this.KEYCODE_CTRL, this.KEYCODE_SHIFT,                    // Modifier Keys
                                this.KEYCODE_ENTER, this.KEYCODE_ALT_GR                                     // Other Keys To Ignore
                               ];
        return (keyCodesToIgnore.indexOf(this.eventKey) != -1);
    },

    /**
     * Is the Key-Event a DELETION?
     */
    eventIsDeletion: function() {
        var keyCodesForDeletion = [this.KEYCODE_BACKSPACE, this.KEYCODE_DELETE];
        return (keyCodesForDeletion.indexOf(this.eventKey) != -1);
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
        switch(true) {
            case this.eventIsDeletion():
                console.log(" => eventIsDeletion");
                this.handleDeletion();
            break;
            case this.eventIsInsertion():
                console.log(" => eventIsInsertion");
                this.handleInsert();
            break;
        }
        
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
        // Sind wir schon in einem DEL drin oder dran?
        switch(this.checkForExistingTags("DEL")) {
            case "useParent":
                // Wenn wir uns in einem DEL event befinden: stoppen
                console.log("Wir sind schon in einem DEL...!")
                return null;
            break;
            case "usePrevious":
                console.log("DEL von davor nehmen...!")
            break;
            case "useNext":
                console.log("DEL von dahinter nehmen...!")
            break;
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
        // Sind wir schon in einem INS drin oder dran?
        switch(this.checkForExistingTags("INS")) {
            case "useParent":
                // Wenn wir uns BEREITS IN einem INS befinden das uns gehört (und im gleichen Workflow Schritt ist → mit Marc klären), 
                // nichts machen
                console.log("Wir sind schon in einem INS...!")
                return null;
            break;
            case "usePrevious":
                // Wenn wir uns DIREKT HINTER einem INS befinden das uns gehört (und im gleichen Workflow Schritt ist → mit Marc klären),
                // das INS von davor nehmen statt ein neues aufzumachen
                console.log("INS von davor nehmen...!")
            break;
            case "useNext":
                // Wenn wir uns DIREKT VOR einem INS befinden das uns gehört (und im gleichen Workflow Schritt ist → mit Marc klären),
                // das INS von dahinter nehmen statt ein neues aufzumachen
                console.log("INS von dahinter nehmen...!")
            break;
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
        switch(this.eventKey) {
            case this.KEYCODE_BACKSPACE: // Backspace: "deletes" the previous character 
                startOffset -= 1;
            break;
            case this.KEYCODE_DELETE: // Delete "deletes" the next character
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
     * Bevor das Einfügen ausgeführt wird, umgeben wir die Stelle mit einem <ins>.
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
    },
    
    /**
     * Checks for continuing of already existing nodes instead of creating new ones
     */
    checkForExistingTags: function(nodeName) {
        // TODO: Check also for author and workflow
        switch(true) {
            case (this.getNodeNameOfParentElement() == nodeName):
                console.log("Wir sind schon in einem " + nodeName + "...!")
                return "useParent";
            break;
            case (this.getNodeNameOfPreviousElement() == nodeName):
                console.log(nodeName + " von davor nehmen...!")
                return "usePrevious";
            break;
            case (this.getNodeNameOfNextElement() == nodeName):
                console.log(nodeName + " von dahinter nehmen...!")
                return "useNext";
            break;
            default:
                return false;
        }
    },
    
    /**
     * Helper for NodeNames
     */
    getNodeNameOfParentElement: function() {
        // s.a. https://github.com/timdown/rangy/wiki/Rangy-Range#commonancestorcontainer
        if (this.docSelRange.startContainer.parentElement == null) {
            return null;
        }
        return this.docSelRange.startContainer.parentElement.nodeName;
    },
    getNodeNameOfPreviousElement: function() {
        if (this.docSelRange.startContainer.previousSibling == null) {
            return "null";
        }
        return this.docSelRange.startContainer.previousSibling.nodeName;
    },
    getNodeNameOfNextElement: function() {
        if (this.docSelRange.endContainer.nextSibling == null) {
            return "null";
        }
        return this.docSelRange.endContainer.nextSibling.nodeName;
    }
});
