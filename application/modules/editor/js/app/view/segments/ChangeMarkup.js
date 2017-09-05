
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
    KEYCODE_SPACE: 32,
    
    // https://github.com/timdown/rangy/wiki/Rangy-Range#compareboundarypointsnumber-comparisontype-range-range
    RANGY_RANGE_IS_BEFORE: -1,
    RANGY_RANGE_IS_AFTER: 1,
    
    /**
     * The given segment content is the base for the operations provided by this method
     * @param {Editor.view.segments.HtmlEditor} editor
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
     * @param {object} event
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

    // =========================================================================
    // Helpers regarding the Event
    // =========================================================================
    
    /**
     * Set infos about key from event.
     * Further information on keyboard events:
     * - http://unixpapa.com/js/key.html
     * - https://www.quirksmode.org/js/keys.html#t20
     * @param {Object} event
     */
    setEventKey: function(event) {
        this.eventKey = event.keyCode;
    },
    /**
     * Has the Key-Event to be IGNORED?
     * @returns {Boolean}
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
     * @returns {Boolean}
     */
    eventIsDeletion: function() {
        var keyCodesForDeletion = [this.KEYCODE_BACKSPACE, this.KEYCODE_DELETE];
        return (keyCodesForDeletion.indexOf(this.eventKey) != -1);
    },
    /**
     * Is the Key-Event an INSERTION?
     * @returns {Boolean}
     */
    eventIsInsertion: function() {
        return true;
    },

    // =========================================================================
    // "Controller"
    // =========================================================================
    
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
        
        // AUCH BEACHTEN:
        // - Überlappende Markierungen, zB. Selektieren von bereits markierten Inhalten und noch bestehenden Inhalten
        // - Ersetzen von markierten Inhalten (= dann also gelöscht) durch neuen Inhalt (= als INS markieren).
        // - Sollen Strg-C und Strg-V auch unterstützt werden?
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
            case (this.isWithinOfSameKind()):
                // Wenn wir uns in einem DEL befinden: stoppen. (Das zu löschende Zeichen ist ja bereits als gelöscht markiert.)
                // TODO: Wenn wir allerdings ganz am Ende innerhalb des DEL sind, muss bei "Delete" das Zeichen dahinter mit dazugenommen werden.
                console.log("DEL: isWithinOfSameKind..., we do nothing.");
            break;
            case (this.isAtPreviousOfSameKind() && this.eventKey == this.KEYCODE_BACKSPACE):
                // Bei Backspace in ein davor befindliches DEL hinein: nix machen, dort ist ja schon das DEL-Markup gesetzt.
                console.log("DEL (BACKSPACE): Already marked as deleted.")
            break;
            case (this.isAtPreviousOfSameKind() && this.eventKey == this.KEYCODE_DELETE):
                // Bei delete ganz am Ende des DELs, dann das Zeichen dahinter löschen sprich in den DEL mit reinpacken.
                console.log("TODO: Ins vorherige DEL mit reinpacken.")
            break;
            case (this.isAtNextOfSameKind() && this.eventKey == this.KEYCODE_BACKSPACE):
                // Bei backspace ganz am Anfang des DELs, dann das Zeichen davor löschen sprich in den DEL mit reinpacken.
                console.log("TODO: Ins nachfolgende DEL mit reinpacken.")
            break;
            case (this.isAtNextOfSameKind() && this.eventKey == this.KEYCODE_DELETE):
                // Bei Delete in ein nachfolgend bestehendes DEL hinein: nix machen, dort ist ja schon das DEL-Markup gesetzt.
                console.log("DEL (DELETE): Already marked as deleted.")
            break;
            default:
                // Wenn wir uns nicht in oder an einem DEL-Node befinden, müssen wir uns jetzt um das Markup kümmern:
                console.log("DEL: insert Markup...");
                this.addDel();
        }
    },
    /**
     * Handle insert-Events.
     */
    handleInsert: function() {
        // Das Event wird anschließend ausgeführt (= das Einfügen geht dann normal vonstatten)...
        this.stopEvent = false;
        // ... vorher kümmern wir uns aber noch um das ins-Tag:
        switch(true) {
            case (this.isWithinOfSameKind()):
                // Wenn wir schon im richtigen MarkUp sind, machen wir sonst weiter nichts.
                console.log("INS: isWithinOfSameKind..., we do nothing.");
            break;
            case this.isAtNextOfSameKind():
             // if this new node is right before an ins-node that already exists, we use that one:
                console.log("INS: use next...");
                this.useNextIns();
            break;
            case this.isAtPreviousOfSameKind():
                // if this new node is right behind an ins-node that already exists, we use that one:
                console.log("INS: use previous..."); // (scheint aber nie vorzukommen, wird immer als isWithinOfSameKind erkannt.)
            break;
            default:
                // if we are neither in nor at an ins-node:
                // (1) Wenn wir uns in einem DEL befinden, dieses an dieser Stelle zuerst auseinanderbrechen.
                var surroundingDelNode = this.getSurroundingDel();
                if (surroundingDelNode != null) {
                    console.log("INS: split DEL first...");
                    this.splitNode(surroundingDelNode);
                }
                // (2) Wenn wir uns in einem fremden INS befinden, dieses an dieser Stelle zuerst auseinanderbrechen.
                var surroundingForeignIns = this.getSurroundingForeignIns();
                if (surroundingForeignIns != null) {
                    console.log("INS: split foreign INS first...");
                    this.splitNode(surroundingForeignIns);
                }
                // (3) create and insert <ins>-node:
                if (this.eventKey == this.KEYCODE_SPACE) { // Workaround for inserting space (otherwise creates <u>-Tag, don't know why).
                    console.log("INS: insert space with Markup...");
                    this.addInsForSpace();
                } else {
                    console.log("INS: insert Markup...");
                    this.addIns();
                }
        }
    },

    // =========================================================================
    // Deletions
    // =========================================================================
    
    /**
     * Mark a deletion as deleted:
     * Instead of deleting the character, we wrap it into a DEL-node.
     */
    addDel: function() {
        // create a range to be marked as deleted
        // var rangeForDel = this.docSelRange.cloneRange(); // buggy: sometimes the cloned range is collapsed to the start although this.docSelRange is NOT.
        var rangeForDel = this.docSelRange;
        // If nothing is selected, the caret has just been placed somewhere:
        if (rangeForDel.collapsed) {
            switch(this.eventKey) {
                case this.KEYCODE_BACKSPACE: // Backspace: "deletes" the previous character
                    rangeForDel.moveStart("character", -1);
                break;
                case this.KEYCODE_DELETE: // Delete "deletes" the next character
                    rangeForDel.moveEnd("character", 1);
                break;
            }
        }
        // create and attach <del>-Element around the range for deletion
        delNode = this.createNodeForMarkup(this.NODE_NAME_DEL);
        if (rangeForDel.canSurroundContents(delNode)) { // TODO: When some characters have already been marked and some other characters get selected and deleted, this sometimes throws an Uncaught Error "Range is not valid. This usually happens after DOM mutation."
            rangeForDel.surroundContents(delNode);
        } else {
            console.log("Unable to surround range because range partially selects a non-text node. See DOM4 spec for more information.");
        }
        // TODO: position the caret!
    },

    // =========================================================================
    // Insertions
    // =========================================================================
    
    /**
     * Mark an insertion as inserted:
     * Before the insertion is added in the editor, we create an INS-node and position the caret in there.
     */
    addIns: function() {
        var insNode = this.createNodeForMarkup(this.NODE_NAME_INS);
        insNode.appendChild(document.createTextNode('x'));   // Google Chrome gets lost otherwise
        this.docSelRange.insertNode(insNode);
        // position the caret over the content of the ins-node
        var rangeForPos = rangy.createRange();
        rangeForPos.selectNodeContents(insNode);
        this.docSel.setSingleRange(rangeForPos);
        insNode.nodeValue = '';                              // Google Chrome; see above...
    },
    /**
     * Add space and mark it as inserted.
     */
    addInsForSpace: function() {
        var insNode = this.createNodeForMarkup(this.NODE_NAME_INS);
        insNode.appendChild(document.createTextNode(' '));
        this.docSelRange.insertNode(insNode);
        // position the caret at the end of the ins-node
        var rangeForPos = rangy.createRange();
        rangeForPos.selectNodeContents(insNode);
        rangeForPos.collapse(false);
        this.docSel.setSingleRange(rangeForPos);
        // space is inserted already, stop the keyboard-Event!
        this.stopEvent = true;
        
        // TODO: ins-Tags in the beginning or at the end of the editor are not recognized by the editor.
        // - Problem 1: The ins-Node including the space is visible in the code, but not in the editor.
        // - Problem 2: when inserting another character in the beginning of the editor, the caret is recognized as being WITHIN the ins-Node with the space, 
        //   but then the editor adds the character AFTER the space (= NOT inside the ins-Node with the space)
    },
    /**
     * Place the caret at the beginning of the next INS-Node (within!!!).
     */
    useNextIns:  function() {
        var focusNode = document.createElement('div'), // Workaround: temporär Node an Anfang einfügen, um setEndAfter daran ausführen zu können (andernfalls landen wir nicht INNERHALB des nextNodes!)
            nextNode = this.getNextNode(this.docSelRange.endContainer),
            rangeForPos = rangy.createRange();
        focusNode.style.position = "absolute"; // display the temp div out of sight, otherwise the displayed content flickers
        focusNode.style.left = "-1000px";
        nextNode.insertBefore(focusNode,nextNode.childNodes[0]);
        rangeForPos.setEndAfter(focusNode); // setStartBefore(nextNode) springt VOR die Boundary v. nextNode; setStart() mit Offset-Angabe ist ungünstig, da wir erst prüfen müssten, ob sich das Offset je nach Art auf etwas anderes bezieht (wir müssten also erst prüfen, ob im <ins> zuerst ein Textnode kommt oder nicht)
        rangeForPos.collapse(false);
        this.docSel.setSingleRange(rangeForPos);
        // Cleanup
        setTimeout(function() { // removing the focusNode before the insert is done looses the correct position of the caret
            nextNode.removeChild(focusNode);
        }, 10);
    },

    // =========================================================================
    // Check if the current selection is in or at already existing markup-nodes
    // =========================================================================
    
    /**
     * Checks if the current selection is within a del-node of any kind and returns that node (or null otherwise).
     * @returns {?Object}
     */
    getSurroundingDel: function() {
        console.log("getSurroundingDel?");
        var surroundingDelNode = null,
            nodeForMarkup = this.createNodeForMarkup(this.NODE_NAME_DEL),
            currentNode = this.docSelRange.startContainer,
            parentNode = this.docSelRange.startContainer.parentNode;
        // is within a del-node?
        if (this.isNodesOfSameName(nodeForMarkup,currentNode)) {
            surroundingDelNode = currentNode;
        } else if (this.isNodesOfSameName(nodeForMarkup,parentNode)) {
            surroundingDelNode = parentNode;
        }
        return surroundingDelNode;
    },
    /**
     * Checks if the current selection is within an ins-node with different conditions (user, workflow, ...) and returns that node (or null otherwise).
     * @returns {?Object}
     */
    getSurroundingForeignIns: function() {
        console.log("getSurroundingForeignIns?");
        var surroundingForeignInsNode = null,
            nodeForMarkup = this.createNodeForMarkup(this.NODE_NAME_INS),
            currentNode = this.docSelRange.startContainer,
            parentNode = this.docSelRange.startContainer.parentNode;
        // Bin ich (1) in einem ins-Node, der (2) nicht zu mir und dem Event gehört?
        if (this.isNodesOfSameName(nodeForMarkup,currentNode)                          // (1)
                && !this.isNodesOfSameConditionsAndEvent(nodeForMarkup,currentNode)) { // (2)
            surroundingForeignInsNode = currentNode;
        } else if (this.isNodesOfSameName(nodeForMarkup,parentNode)                    // (1)
                && !this.isNodesOfSameConditionsAndEvent(nodeForMarkup,parentNode)) {  // (2)
            surroundingForeignInsNode = parentNode;
        }
        return surroundingForeignInsNode;
    },
    /**
     * Checks if the current selection is within an existing markup-node of the same kind.
     * @returns {Boolean}
     */
    isWithinOfSameKind: function() {
        console.log("isWithinOfSameKind?");
        var nodeName = this.getNodeNameAccordingToEvent(),
            nodeForMarkup = this.createNodeForMarkup(nodeName),
            currentNode = this.docSelRange.startContainer,
            parentNode = this.docSelRange.startContainer.parentNode;
        // same conditions?
        var sameAsCurrent = this.isNodesOfSameConditionsAndEvent(nodeForMarkup,currentNode);
        var sameAsParent = this.isNodesOfSameConditionsAndEvent(nodeForMarkup,parentNode);
        return (sameAsCurrent || sameAsParent);
    },
    /**
     * Checks if the current selection is right behind an already existing markup-node of the same kind.
     * @returns {Boolean}
     */
    isAtPreviousOfSameKind: function() {
        console.log("isAtPreviousOfSameKind?");
        var nodeName = this.getNodeNameAccordingToEvent(),
            nodeForMarkup = this.createNodeForMarkup(nodeName),
            previousNode = this.getPreviousNode(this.docSelRange.startContainer);
        
        // ------ (1) same conditions & event? ------
        
        var currentIsOfSameConditionsAsNext = this.isNodesOfSameConditionsAndEvent(nodeForMarkup,previousNode);
        console.log("isNodesOfSameConditionsAndEvent: " + currentIsOfSameConditionsAsNext);
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
    /**
     * Checks if the current selection is right before an already existing markup-node of the same kind.
     * @returns {Boolean}
     */
    isAtNextOfSameKind: function() {
        console.log("isAtNextOfSameKind?");
        var nodeName = this.getNodeNameAccordingToEvent(),
            nodeForMarkup = this.createNodeForMarkup(nodeName),
            nextNode = this.getNextNode(this.docSelRange.endContainer);
        
        // ------ (1) same conditions & event? ------
        
        var currentIsOfSameConditionsAsNext = this.isNodesOfSameConditionsAndEvent(nodeForMarkup,nextNode);
        console.log("isNodesOfSameConditionsAndEvent: " + currentIsOfSameConditionsAsNext);
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

    // =========================================================================
    // Helpers for creating and comparing Nodes
    // =========================================================================
    
    /**
     * Returns the markup-nodeName for the current event.
     * @returns {?String} 
     */
    getNodeNameAccordingToEvent: function(){
        switch(true) {
            case this.eventIsDeletion():
                return this.NODE_NAME_DEL;
            break;
            case this.eventIsInsertion():
                return this.NODE_NAME_INS;
            break;
            default:
                return null;
        }
    },
    /**
     * Create and return a new node for Markup.
     * @param {String} nodeName
     * @returns {Object} 
     */
    createNodeForMarkup: function(nodeName){
        var nodeEl = document.createElement(nodeName); // TODO: use Ext.DomHelper.createDom() instead?
        nodeEl.id = Ext.id();
        // NEXT STEPS: Add info about user, workflow, ...
        return nodeEl;
    },
    /**
     * Do the nodes share the same conditions (nodeName, user, workflow....) AND match the event?
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameConditionsAndEvent: function(nodeA,nodeB) {
        // The conditions of the given nodes must BOTH
        // (1) fit to each other
        // AND
        // (2) fit to the current Event
        var isNodesOfSameConditions = this.isNodesOfSameConditions(nodeA,nodeB),
            isNodesAccordingToEvent = ( (this.isNodeAccordingToEvent(nodeA)) && (this.isNodeAccordingToEvent(nodeB)) );
        return isNodesOfSameConditions && isNodesAccordingToEvent;
    },
    /**
     * Do the nodes share the same conditions? (nodeName, user, workflow....)
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameConditions: function(nodeA,nodeB) {
        var isNodesOfSameName       = this.isNodesOfSameName(nodeA,nodeB),      // nodeName
            isNodesOfSameUser       = this.isNodesOfSameUser(nodeA,nodeB),      // user
            isNodesOfSameWorkflow   = this.isNodesOfSameWorkflow(nodeA,nodeB);  // workflow
        return isNodesOfSameName && isNodesOfSameUser && isNodesOfSameWorkflow;
    },
    /**
     * Do the given node's conditions match the conditions according to the event? (nodeName, user, workflow....)
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeAccordingToEvent: function(node) {
        var isNodeNameAccordingToEvent       = this.isNodeNameAccordingToEvent(node),       // nodeName
            isNodeOfUserAccordingToEvent     = this.isNodeOfUserAccordingToEvent(node),     // user
            isNodeOfWorkflowAccordingToEvent = this.isNodeOfWorkflowAccordingToEvent(node); // workflow
        return isNodeNameAccordingToEvent && isNodeOfUserAccordingToEvent && isNodeOfWorkflowAccordingToEvent;
    },
    /**
     * Do the nodes share the same nodeName?
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameName: function(nodeA,nodeB) {
        var nodeNameA = (nodeA == null) ? 'null' : nodeA.nodeName,
            nodeNameB = (nodeB == null) ? 'null' : nodeB.nodeName;
        return (nodeNameA == nodeNameB);
    },
    /**
     * Does the given node's nodeName match the nodeName according to the event?
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeNameAccordingToEvent: function(node) {
        var nodeName = (node == null) ? 'null' : node.nodeName,
            nodeNameAccordingToEvent = this.getNodeNameAccordingToEvent();
        return (nodeName == nodeNameAccordingToEvent);
    },
    /**
     * Do the nodes share the same user?
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameUser: function(nodeA,nodeB) {
        return true;
    },
    /**
     * Does the given node's user match the user according to the event?
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeOfUserAccordingToEvent: function(node) {
        return true;
    },
    /**
     * Do the nodes share the same workflow?
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameWorkflow: function(nodeA,nodeB) {
        return true;
    },
    /**
     * Does the given node's workflow match the workflow according to the event?
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeOfWorkflowAccordingToEvent: function(node) {
        return true;
    },

    // =========================================================================
    // Helpers for Nodes in general
    // =========================================================================
    
    /**
     * Split a node at the current position of the selection.
     * @param {Object} nodeToSplit
     */
    splitNode: function(nodeToSplit) {
        // extract what's on the left from the caret and insert it before the node as a new node
        var rangeForExtract = rangy.createRange(),
            selectionStartNode = this.docSelRange.startContainer,
            selectionStartOffset = this.docSelRange.startOffset,
            parentNode = nodeToSplit.parentNode;
        rangeForExtract.setStartBefore(nodeToSplit);
        rangeForExtract.setEnd(selectionStartNode, selectionStartOffset);
        var firstPartNewNode = rangeForExtract.extractContents();
        var firstPartNodeInserted = parentNode.insertBefore(firstPartNewNode, nodeToSplit);
        // TODO: Prüfen, ob beide Teile des gesplitteten del-Nodes dieselben Angaben haben (User, Workflow, ...)
        // set position for further inserting (= where the delNode was split)
        this.docSelRange.setEndBefore(nodeToSplit);
        this.docSelRange.collapse(false);
        this.docSel.setSingleRange(this.docSelRange);
    },
    /**
     * Get the previous node according to the DOM.
     * @param {Object} currentNode
     * @returns {Object}
     */
    getPreviousNode: function(currentNode){
        return currentNode.previousSibling;
    },
    /**
     * Get the next node according to the DOM.
     * @param {Object} currentNode
     * @returns {Object}
     */
    getNextNode: function(currentNode){
        return currentNode.nextSibling;
    }
});
