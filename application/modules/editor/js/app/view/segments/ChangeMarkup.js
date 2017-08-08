
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
     */
    handleDeletion: function() {
        // Das Event wird auf jeden Fall gestoppt; Zeichen werden nicht mehr gelöscht...
        this.stopEvent = true;
        // ... sondern als gelöscht markiert:
        var position = this.checkForExistingTags("DEL");
        switch(true) {
            case (position == "isInParent"):
                // Wenn wir uns in einem DEL befinden: stoppen. (Das zu löschende Zeichen ist ja bereits als gelöscht markiert.)
            break;
            case (position == "isAtPrevious" && this.eventKey == this.KEYCODE_DELETE):
                // Bei delete ganz am Ende des DELs, dann das Zeichen dahinter löschen sprich in den DEL mit reinpacken.
                console.log("TODO: Ins vorherige DEL mit reinpacken.")
            break;
            case (position == "isAtNext" && this.eventKey == this.KEYCODE_BACKSPACE):
                // Bei backspace ganz am Anfang des DELs, dann das Zeichen davor löschen sprich in den DEL mit reinpacken.
                console.log("TODO: Ins nachfolgende DEL mit reinpacken.")
            break;
            default:
                // Wenn wir uns nicht in oder an einem DEL-Node befinden, müssen wir uns jetzt um das Markup kümmern:
                this.markDeletion();
            break;
        }
    },

    /**
     * Handle insert-Events.
     * TODO: 
     * - Wenn wir uns dabei in einem DEL befinden, dieses an dieser Stelle zuerst auseinander brechen und dann den INS einfügen
     */
    handleInsert: function() {
        // Das Event wird anschließend ausgeführt (= das Einfügen geht dann normal vonstatten).
        this.stopEvent = false;
        // Aber vorher kümmern wir uns um das Markup:
        var position = this.checkForExistingTags("INS");
        switch(true) {
            case (position == "isInParent"):
                // Da wie schon im richtigen MarkUp sind, machen wir hier gar nix.
            break;
            case (position == "isAtPrevious"):
                // Es gibt bereits ein INS davor; an das schließen wir uns an.
                this.usePreviousNode();
            break;
            case (position == "isAtNext"):
                // Es gibt bereits ein INS dahinter; an das schließen wir uns an.
                this.useNextNode();
            break;
            default:
                // Wenn wir uns nicht in oder an einem INS-Node befinden, müssen wir uns jetzt um das Markup kümmern:
                this.markInsertion();
            break;
        }
    },
    
    /**
     * Marks a deletion as deleted:
     * Instead of deleting the character, we wrap it into a DEL-node.
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
        node = this.createNewNode("del");
        if (rangeForDel.canSurroundContents(node)) {
            rangeForDel.surroundContents(node);
        } else {
            console.log("Unable to surround range because range partially selects a non-text node. See DOM4 spec for more information.");
        }
        // TODO: position the caret!
    },
    
    /**
     * Marks an insertion as inserted:
     * Before the insertion is added in the editor, we create an INS-node and position the caret in there.
     */
    markInsertion: function() {
        // create and insert <ins>-node
        nodeEl = this.createNewNode("ins");
        nodeEl.appendChild(document.createTextNode(' '));   // Google Chrome gets lost otherwise
        this.docSelRange.insertNode(nodeEl);
        // position the caret
        var rangeForPos = rangy.createRange();
        rangeForPos.selectNodeContents(nodeEl);
        this.docSel.setSingleRange(rangeForPos);
        nodeEl.nodeValue = '';                              // Google Chrome; see above...
    },
    
    /**
     * "Switch" the range to previous node.
     */
    usePreviousNode: function() {
        var previousNode = this.docSelRange.startContainer.previousSibling;
        var rangeToUse = rangy.createRange();
        rangeToUse.selectNodeContents(previousNode);
        rangeToUse.collapse(false);
        this.docSel.setSingleRange(rangeToUse);
    },
    
    /**
     * "Switch" the range to next node.
     */
    useNextNode: function() {
        var nextNode = this.docSelRange.startContainer.nextSibling;
        var rangeToUse = rangy.createRange();
        rangeToUse.selectNodeContents(nextNode);
        rangeToUse.collapse(true);
        this.docSel.setSingleRange(rangeToUse);
    },
    
    /**
     * Checks for already existing nodes we are in or at.
       - NEXT STEPS: Check also for user, workflow, ...
     */
    checkForExistingTags: function(nodeName) {
        switch(true) {
            // Befinden wir uns BEREITS IN einem INS/DEL? (das uns gehört und im gleichen Workflow Schritt ist → mit Marc klären)
            case (this.hasParent(nodeName)):
                console.log("isInParent");
                return "isInParent";
            break;
            // Befinden wir uns DIREKT HINTER einem INS/DEL? (das uns gehört und im gleichen Workflow Schritt ist → mit Marc klären)
            case (this.isAtPrevious(nodeName)):
                console.log("previous node exists; TODO: check POSITION as well!");
                return "isAtPrevious";
            break;
            // Befinden wir uns DIREKT VOR einem INS/DEL? (das uns gehört und im gleichen Workflow Schritt ist → mit Marc klären)
            case (this.isAtNext(nodeName)):
                console.log("next node exists; TODO: check POSITION as well!");
                return "isAtNext";
            break;
            // Wie befinden uns weder in noch an einem INS/DEL:
            default:
                console.log("Weder in noch dran...")
                return false;
        }
    },
    hasParent: function(nodeName) {
        return (this.getNodeNameOfParentElement() == nodeName);
    },
    isAtPrevious: function(nodeName) {
        // TODO: auch Position berücksichtigen! (Sind wir wirklich direkt dahinter?)
        // => Umstellen auf rangy-Methoden
        return false;
    },
    isAtNext: function(nodeName) {
        // TODO: auch Position berücksichtigen! (Sind wir wirklich direkt davor?)
        // => Umstellen auf rangy-Methoden
        return false;
    },
    
    /**
     * Create and return a new node.
     */
    createNewNode: function(nodeName){
        nodeEl = document.createElement(nodeName);
        nodeEl.id = nodeName + Date.now();
        // NEXT STEPS: Add info about user, workflow, ...
        return nodeEl;
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
            return null;
        }
        return this.docSelRange.startContainer.previousSibling.nodeName;
    },
    
    getNodeNameOfNextElement: function() {
        if (this.docSelRange.endContainer.nextSibling == null) {
            return null;
        }
        return this.docSelRange.endContainer.nextSibling.nodeName;
    }
});
