
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
    eventKey: null,         // Keyboard-Event
    docSel: null,           // what the user has selected
    docSelRange: null,      // range for what the user has selected
    stopEvent: null,        // do we stop the event here?
    
    // "CONSTANTS"
    NODE_NAME_DEL: 'DEL',
    NODE_NAME_INS: 'INS',
    
    // Key-Codes
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
    
    // https://github.com/timdown/rangy/wiki/Rangy-Range#compareboundarypointsnumber-comparisontype-range-range
    RANGY_RANGE_IS_BEFORE: -1,
    RANGY_RANGE_IS_AFTER: 1,
    
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
     * - TODO: Bei Backspace kein neues DEL, wenn schon in der Position VOR der Position DAVOR ein DEL existiert
     * - TODO: Bei Delete kein neues DEL, wenn schon in der Position NACH der Position DANACH ein DEL existiert
     */
    handleDeletion: function() {
        // Das Event wird auf jeden Fall gestoppt; Zeichen werden nicht mehr gelöscht...
        this.stopEvent = true;
        // ... sondern als gelöscht markiert:
        switch(true) {
            case (this.isInParent()):
                // Wenn wir uns in einem DEL befinden: stoppen. (Das zu löschende Zeichen ist ja bereits als gelöscht markiert.)
                // TODO: Wenn wir allerdings ganz am Ende innerhalb des DEL sind, muss bei "Delete" das Zeichen dahinter mit dazugenommen werden.
                console.log("DEL: isInParent...");
            break;
            case (this.isAtPrevious() && this.eventKey == this.KEYCODE_BACKSPACE):
                // Bei Backspace in ein davor befindliches DEL hinein: nix machen, dort ist ja schon das DEL-Markup gesetzt.
                console.log("DEL (BACKSPACE): Already marked as deleted.")
            break;
            case (this.isAtPrevious() && this.eventKey == this.KEYCODE_DELETE):
                // Bei delete ganz am Ende des DELs, dann das Zeichen dahinter löschen sprich in den DEL mit reinpacken.
                console.log("TODO: Ins vorherige DEL mit reinpacken.")
            break;
            case (this.isAtNext() && this.eventKey == this.KEYCODE_BACKSPACE):
                // Bei backspace ganz am Anfang des DELs, dann das Zeichen davor löschen sprich in den DEL mit reinpacken.
                console.log("TODO: Ins nachfolgende DEL mit reinpacken.")
            break;
            case (this.isAtNext() && this.eventKey == this.KEYCODE_DELETE):
                // Bei Delete in ein nachfolgend bestehendes DEL hinein: nix machen, dort ist ja schon das DEL-Markup gesetzt.
                console.log("DEL (DELETE): Already marked as deleted.")
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
        // Wenn wir schon im richtigen MarkUp sind, machen wir sonst weiter nichts.
        if (this.isInParent()) {
            console.log("INS: isInParent...");
            return;
        }
        // Ansonsten kümmern wir uns vorher noch um das ins-Tag.
        this.markInsertion();
    },
    
    /**
     * Mark a deletion as deleted:
     * Instead of deleting the character, we wrap it into a DEL-node.
     */
    markDeletion: function() {
        // create a range to be marked as deleted
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
        rangeForDel.setStartAndEnd(startNode, startOffset, endNode, endOffset); // TODO: Fails to execute for 'setEnd' on 'Range' when The offset 2 is larger than the node's length (1).
        // create and attach <del>-Element
        node = this.createNewNodeForMarkup();
        if (rangeForDel.canSurroundContents(node)) {
            rangeForDel.surroundContents(node);
        } else {
            console.log("Unable to surround range because range partially selects a non-text node. See DOM4 spec for more information.");
        }
        // TODO: position the caret!
    },
    
    /**
     * Mark an insertion as inserted:
     * Before the insertion is added in the editor, we create an INS-node and position the caret in there.
     */
    markInsertion: function() {
        switch(true) {
            // if this new node is right before an ins-node that already exists, we use that one:
                case this.isAtNext():
                    console.log("INS: use next...");
                    // Caret am Ende vom previousNode platzieren (innerhalb!)
                    var focusNode = document.createElement('div'), // Workaround: temporär Node an Anfang einfügen, um setEndAfter daran ausführen zu können (andernfalls landen wir nicht INNERHALB des nextNodes!)
                        nextNode = this.getNextNode(this.docSelRange.endContainer),
                        rangeForPos = rangy.createRange();
                    focusNode.style.position = "absolute"; // display the temp div out of sight, otherwise the screen flickers
                    focusNode.style.left = "-1000px";
                    nextNode.insertBefore(focusNode,nextNode.childNodes[0]);
                    rangeForPos.setEndAfter(focusNode); // setStartBefore(nextNode) springt VOR die Boundary v. nextNode; setStart() mit Offset-Angabe ist ungünstig, da wir erst prüfen müssten, ob sich das Offset je nach Art auf etwas anderes bezieht (wir müssten also erst prüfen, ob im <ins> zuerst ein Textnode kommt oder nicht)
                    rangeForPos.collapse(false);
                    this.docSel.setSingleRange(rangeForPos);
                    // removing the focusNode before the insert is done looses the correct position of the caret
                    setTimeout(function() {
                        nextNode.removeChild(focusNode);
                      }, 10);
                    return rangeForPos;
                break;
            // if this new node is right behind an ins-node that already exists, we use that one:
                case this.isAtPrevious():
                    console.log("INS: use previous...")
                    // Caret am Ende vom previousNode platzieren (innerhalb!)
                break;
            // if we are neither in nor at an ins-node: create and insert <ins>-node:
                default:
                    console.log("INS: insert Markup...");
                    var nodeEl = this.createNewNodeForMarkup();
                    nodeEl.appendChild(document.createTextNode(' '));   // Google Chrome gets lost otherwise
                    this.docSelRange.insertNode(nodeEl);
                    // position the caret
                    rangeForPosAfterInsert = this.positionCaretInNode(nodeEl);
                    nodeEl.nodeValue = '';                              // Google Chrome; see above...
        }
    },
    
    /**
     * Helper for positioning the caret in the given node.
     * Returns the range with the new position.
     */
    positionCaretInNode: function(node) {
        var rangeForPos = rangy.createRange();
        rangeForPos.selectNodeContents(node);
        this.docSel.setSingleRange(rangeForPos);
        return rangeForPos;
    },
    
    /**
     * Check if the current selection is in or at already existing markup-nodes.
     */
    isInParent: function() {
        console.log("isInParent?");
        var nodeForMarkup = this.createNewNodeForMarkup(),
            parentNode = this.docSelRange.startContainer.parentNode;
        // same conditions?
        if (this.isNodesOfSameConditions(nodeForMarkup,parentNode)) {
            return true;
        }
        // otherwise:
        return false;
    },
    isAtPrevious: function() {
        console.log("isAtPrevious?");
        var nodeForMarkup = this.createNewNodeForMarkup(),
            previousNode = this.getPreviousNode(this.docSelRange.startContainer);
        
        // ------ (1) same conditions? ------
        
        var currentIsOfSameConditionsAsNext = this.isNodesOfSameConditions(nodeForMarkup,previousNode);
        console.log("isNodesOfSameConditions: " + currentIsOfSameConditionsAsNext);
        if (!currentIsOfSameConditionsAsNext) {
            return false;
        }
        
        // ------ (2) is after previous? ------
        
        // Eigentlich wollte ich die Boundaries der Ranges der aktuellen Selection und des previousNode von der Selection vergleichen; das klappt aber nicht.
        // Workaround: Selektierte Range klonen, dort einen node einfügen, die geklonte Range dort drumsetzen und DIE dann mit der Range für den previousNode vergleichen.
        // - For current selection:
        var rangeForCurrentSelection = this.docSelRange.cloneRange();
        rangeForCurrentSelection.insertNode(nodeForMarkup);
        // - For previous node:
        var rangeForPreviousNode = rangy.createRange();

        // (2a) is after previous?
        rangeForCurrentSelection.selectNodeContents(nodeForMarkup);
        rangeForPreviousNode.selectNodeContents(previousNode);
        var currentIsAfterPrevious = rangeForCurrentSelection.compareBoundaryPoints(Range.END_TO_START, rangeForPreviousNode) == this.RANGY_RANGE_IS_AFTER;
        console.log("compareBoundaryPoints: is after?: " + currentIsAfterPrevious);
        
        // (2b) is right after previous, not just somewhere after it?
        rangeForCurrentSelection.selectNode(nodeForMarkup);
        rangeForPreviousNode.selectNode(previousNode);
        var currentTouchesPrevious = rangeForCurrentSelection.intersectsOrTouchesRange(rangeForPreviousNode);
        console.log("intersectsOrTouchesRange: " + currentTouchesPrevious);
        
        // Cleanup
        nodeForMarkup.parentElement.removeChild(nodeForMarkup);
        
        return currentIsAfterPrevious && currentTouchesPrevious;
    },
    isAtNext: function() {
        console.log("isAtNext?");
        var nodeForMarkup = this.createNewNodeForMarkup(),
            nextNode = this.getNextNode(this.docSelRange.endContainer);
        
        // ------ (1) same conditions? ------
        
        var currentIsOfSameConditionsAsNext = this.isNodesOfSameConditions(nodeForMarkup,nextNode);
        console.log("isNodesOfSameConditions: " + currentIsOfSameConditionsAsNext);
        if (!currentIsOfSameConditionsAsNext) {
            return false;
        }
        
        //------ (2) is before next? ------
        
        // Eigentlich wollte ich die Boundaries der Ranges der aktuellen Selection und des nextNode von der Selection vergleichen; das klappt aber nicht.
        // Workaround: Selektierte Range klonen, dort einen node einfügen, die geklonte Range dort drumsetzen und DIE dann mit der Range für den nextNode vergleichen.
        // - For current selection:
        var rangeForCurrentSelection = this.docSelRange.cloneRange();
        rangeForCurrentSelection.insertNode(nodeForMarkup);
        // - For next node:
        var rangeForNextNode = rangy.createRange();
        
        // (2a) is before next?
        rangeForCurrentSelection.selectNodeContents(nodeForMarkup);
        rangeForNextNode.selectNodeContents(nextNode);
        var currentIsBeforeNext = rangeForCurrentSelection.compareBoundaryPoints(Range.START_TO_END, rangeForNextNode) == this.RANGY_RANGE_IS_BEFORE;
        console.log("compareBoundaryPoints: is before? " + currentIsBeforeNext);
        
        // (2b) is right before next, not just somewhere before it?
        rangeForCurrentSelection.selectNode(nodeForMarkup);
        rangeForNextNode.selectNode(nextNode);
        var currentTouchesNext = rangeForCurrentSelection.intersectsOrTouchesRange(rangeForNextNode);
        console.log("intersectsOrTouchesRange: " + currentTouchesNext);
        
        // Cleanup
        nodeForMarkup.parentElement.removeChild(nodeForMarkup);
        
        return currentIsBeforeNext && currentTouchesNext;
    },
    
    /**
     * Create and return a new node for Markup.
     */
    createNewNodeForMarkup: function(){
        switch(true) {
            case this.eventIsDeletion():
                nodeNameAccordingToEvent = this.NODE_NAME_DEL;
            break;
            case this.eventIsInsertion():
                nodeNameAccordingToEvent = this.NODE_NAME_INS;
            break;
            default:
                nodeNameAccordingToEvent = undefined;
        }
        
        if(!nodeNameAccordingToEvent) {
            return null;
        }
        
        nodeEl = document.createElement(nodeNameAccordingToEvent);
        nodeEl.id = nodeNameAccordingToEvent + Date.now();
        // NEXT STEPS: Add info about user, workflow, ...
        return nodeEl;
    },
    
    /**
     * Helper for Nodes.
     */
    getParentNode: function(currentNode){
        return currentNode.parentNode;
    },
    getPreviousNode: function(currentNode){
        return currentNode.previousSibling;
    },
    getNextNode: function(currentNode){
        return currentNode.nextSibling;
    },
    
    /**
     * Do the nodes share the same conditions? (nodeName, user, workflow....)
     */
    isNodesOfSameConditions: function(nodeA,nodeB) {
        
        // The conditions of the given nodes must BOTH
        // (1) fit to each other
        // AND
        // (2) fit to the current Event
        switch(true) {
            case this.eventIsDeletion():
                nodeNameAccordingToEvent = this.NODE_NAME_DEL;
            break;
            case this.eventIsInsertion():
                nodeNameAccordingToEvent = this.NODE_NAME_INS;
            break;
            default:
                nodeNameAccordingToEvent = undefined;
        }
        
        // Same NodeName?
        var nodeNameA = (nodeA == null) ? 'null' : nodeA.nodeName,
            nodeNameB = (nodeB == null) ? 'null' : nodeB.nodeName;
        var sameNodeNameOfNodes = (nodeNameA == nodeNameB);                                                               // (1)
        var sameNodeNameAsEvent = ( (nodeNameA == nodeNameAccordingToEvent) && (nodeNameB == nodeNameAccordingToEvent) ); // (2)
        
        // NEXT STEPS: Check also for user, workflow, ...
        sameUser = true;
        sameWorkflow = true;
        
        return sameNodeNameOfNodes && sameNodeNameAsEvent && sameUser && sameWorkflow;
    }
});
