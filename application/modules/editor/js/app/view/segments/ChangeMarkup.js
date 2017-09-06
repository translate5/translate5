
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
    
    eventKeyCode: null,     // Keyboard-Event: Key-Code
    eventCtrlKey: null,     // Keyboard-Event: Control-Key pressed?
    
    ignoreEvent: false,     // ignore event? (= we do nothing here)
    stopEvent: false,       // do we stop the event here?
    
    docSel: null,           // selection in the document (initially what the user has selected, but then constantly changed according to handling the Markup)
    docSelRange: null,      // current range for handling the markup, positioning the caret, etc... (initially what the user has selected, but then constantly changing)
    
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
    KEYCODE_Z: 90,
    
    CHAR_PLACEHOLDER: '\u0020',
    
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
        this.eventKeyCode = null;
        this.eventCtrlKey = null;
        this.ignoreEvent = false;
        this.stopEvent = false;
        this.docSel = null;
        this.docSelRange = null;
    },
    /**
     * This method is called if the keyboard event (= keydown) was not handled otherwise
     * @param {object} event
     */
    handleTargetEvent: function(event) {
        
        // alles auf Anfang
        this.initEvent();
        console.clear();
        
        // What keyboard event do we deal with?
        this.setKeyboardEvent(event);
        
        // keys, die keinen content produzieren (strg,alt,shift alleine ohne taste, pfeile etc), müssen ignoriert werden
        if(this.eventHasToBeIgnored()){ 
            console.log(" => Ignored!");
            this.ignoreEvent = true;
        }
        // keys, die unseren content nicht verändern dürfen (strg-z etc), müssen ignoriert und gestoppt werden
        if(this.eventHasToBeIgnoredAndStopped()){ 
            console.log(" => Ignored and stopped!");
            this.ignoreEvent = true;
            this.stopEvent = true;
        }
        
        // Change the Markup in the Editor
        if(!this.ignoreEvent) {
            this.changeMarkupInEditor();
        }
        
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
    setKeyboardEvent: function(event) {
        this.eventKeyCode = event.keyCode;
        this.eventCtrlKey = event.ctrlKey;
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
        return (keyCodesToIgnore.indexOf(this.eventKeyCode) != -1);
    },
    /**
     * Has the Key-Event to be IGNORED and STOPPED?
     * @returns {Boolean}
     */
    eventHasToBeIgnoredAndStopped: function() {
        var keyCodesToIgnoreAndStop = new Array(); 
        if(this.eventCtrlKey) {
            keyCodesToIgnoreAndStop.push(this.KEYCODE_Z);                                                   // Ctrl-Z
        }
        return (keyCodesToIgnoreAndStop.indexOf(this.eventKeyCode) != -1);
    },
    /**
     * Is the Key-Event a DELETION?
     * @returns {Boolean}
     */
    eventIsDeletion: function() {
        var keyCodesForDeletion = [this.KEYCODE_BACKSPACE, this.KEYCODE_DELETE];
        return (keyCodesForDeletion.indexOf(this.eventKeyCode) != -1);
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
            case (this.isWithinNode('sameNodeName')):
                // Wenn wir uns bereits in irgendeinem DEL befinden: stoppen. (Das zu löschende Zeichen ist ja bereits als gelöscht markiert.)
                // TODO: Wenn wir allerdings ganz am Ende innerhalb des DEL sind, muss bei "Delete" das Zeichen dahinter mit dazugenommen werden.
                //      (entweder mit da rein, wenn das anschliessende DEL uns gehört, oder in ein neues DEL, wenn wir an ein fremdes DEL anschliessen) 
                console.log("DEL: is surrounded by DEL already..., we do nothing.");
                break;
            case (this.isAtSiblingOfSameKind('previous') && this.eventKeyCode == this.KEYCODE_BACKSPACE):
                // Bei Backspace in ein davor befindliches DEL hinein: nix machen, dort ist ja schon das DEL-Markup gesetzt.
                console.log("DEL (BACKSPACE): Already marked as deleted.")
                break;
            case (this.isAtSiblingOfSameKind('previous') && this.eventKeyCode == this.KEYCODE_DELETE):
                // Bei delete ganz am Ende des DELs, dann das Zeichen dahinter löschen sprich in den DEL mit reinpacken.
                console.log("TODO: Ins vorherige DEL mit reinpacken.")
                break;
            case (this.isAtSiblingOfSameKind('next') && this.eventKeyCode == this.KEYCODE_BACKSPACE):
                // Bei backspace ganz am Anfang des DELs, dann das Zeichen davor löschen sprich in den DEL mit reinpacken.
                console.log("TODO: Ins nachfolgende DEL mit reinpacken.")
                break;
            case (this.isAtSiblingOfSameKind('next') && this.eventKeyCode == this.KEYCODE_DELETE):
                // Bei Delete in ein nachfolgend bestehendes DEL hinein: nix machen, dort ist ja schon das DEL-Markup gesetzt.
                console.log("DEL (DELETE): Already marked as deleted.")
                break;
            default:
                // Wenn wir uns nicht in oder an einem DEL-Node befinden, müssen wir uns jetzt um das Markup kümmern:
                console.log("DEL: insert Markup...");
                var delNode = this.addDel();
                // Wenn wir uns allerdings in einem fremden Node befinden (= INS; allg. DEL wurden oben bereits abgefragt!)...
                if (this.isWithinNode('foreignMarkup')) {
                    // ... dann müssen wir dieses jetzt noch an dieser Stelle auseinanderbrechen...
                    console.log("DEL: split foreign node...");
                    var splittedNodes = this.splitNode(this.getContainerNodeForCurrentSelection());
                    // ... und den eben erzeugten DEL aus dem ersten Teil-INS herausholen und zwischen die beiden neuen INS-Teile schieben.
                    console.log("DEL: move DEL up inbetween...");
                    this.moveNodeInbetweenSplittedNodes(delNode,splittedNodes);
                }
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
            case (this.isWithinNode('sameConditionsAndEvent')):
                // Wenn wir schon im richtigen MarkUp sind, machen wir sonst weiter nichts.
                console.log("INS: isWithinNodeOfSameKind..., we do nothing.");
                break;
            case this.isAtSiblingOfSameKind('next'):
             // if this new node is right before an ins-node that already exists, we use that one:
                console.log("INS: use next...");
                this.useNextIns();
                break;
            case this.isAtSiblingOfSameKind('previous'):
                // if this new node is right behind an ins-node that already exists, we use that one:
                console.log("INS: use previous..."); // (scheint aber nie vorzukommen, wird immer als isWithinNodeOfSameKind erkannt.)
                break;
            default:
                // if we are neither in nor at an ins-node:
                // (1) Wenn wir uns in einem DEL oder einem fremden INS befinden, dieses an dieser Stelle zuerst auseinanderbrechen.
                if (this.isWithinNode('foreignMarkup')) {
                    console.log("INS: split foreign node first...");
                    this.splitNode(this.getContainerNodeForCurrentSelection());
                }
                // (2) create and insert <ins>-node:
                if (this.eventKeyCode == this.KEYCODE_SPACE) { // Workaround for inserting space (otherwise creates <u>-Tag, don't know why).
                    console.log("INS: insert space with Markup...");
                    this.addInsWithSpace();
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
     * Instead of deleting the character, we wrap it into a DEL-node (and return that).
     * @returns {Object} delNode
     */
    addDel: function() {
        // create a range to be marked as deleted
        // var rangeForDel = this.docSelRange.cloneRange(); // buggy: sometimes the cloned range is collapsed to the start although this.docSelRange is NOT.
        var rangeForDel = this.docSelRange;
        // If nothing is selected, the caret has just been placed somewhere and the deletion refers the next single character only:
        if (rangeForDel.collapsed) {
            switch(this.eventKeyCode) {
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
        return delNode;
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
        insNode.appendChild(document.createTextNode(this.CHAR_PLACEHOLDER)); // eg Google Chrome gets lost otherwise
        this.docSelRange.insertNode(insNode);
        // position the caret over the content of the ins-node
        this.docSelRange.selectNodeContents(insNode);
        this.docSel.setSingleRange(this.docSelRange);
        insNode.nodeValue = '';                                              // Google Chrome; see above...
    },
    /**
     * Add space and mark it as inserted.
     */
    addInsWithSpace: function() {
        var insNode = this.createNodeForMarkup(this.NODE_NAME_INS);
        insNode.appendChild(document.createTextNode(this.CHAR_PLACEHOLDER));
        this.docSelRange.insertNode(insNode);
        // position the caret at the end of the ins-node
        this.docSelRange.selectNodeContents(insNode);
        this.docSelRange.collapse(false);
        this.docSel.setSingleRange(this.docSelRange);
        // space is inserted already, stop the keyboard-Event!
        this.stopEvent = true;
        
        // TODO: Empty ins-Tags (= I mean: only with space) in the beginning or at the end of the editor are not recognized by the editor.
        // - Problem 1: The ins-Node including the space is visible in the code, but not in the editor.
        // - Problem 2: when inserting another character in the beginning of the editor, the caret is recognized as being WITHIN the ins-Node with the space, 
        //   but then the editor adds the character AFTER the space (= NOT inside the ins-Node with the space)
    },
    /**
     * Place the caret at the beginning of the next INS-Node (within!!!).
     */
    useNextIns:  function() {
        var focusNode = document.createElement('div'), // Workaround: temporär Node an Anfang einfügen, um setEndAfter daran ausführen zu können (andernfalls landen wir nicht INNERHALB des nextNodes!)
            nextNode = this.getSiblingNodeForCurrentSelection('next');
        focusNode.style.position = "absolute"; // display the temp div out of sight, otherwise the displayed content flickers
        focusNode.style.left = "-1000px";
        nextNode.insertBefore(focusNode,nextNode.childNodes[0]);
        this.docSelRange.setEndAfter(focusNode); // setStartBefore(nextNode) springt VOR die Boundary v. nextNode; setStart() mit Offset-Angabe ist ungünstig, da wir erst prüfen müssten, ob sich das Offset je nach Art auf etwas anderes bezieht (wir müssten also erst prüfen, ob im <ins> zuerst ein Textnode kommt oder nicht)
        this.docSelRange.collapse(false);
        this.docSel.setSingleRange(this.docSelRange);
        // Cleanup
        setTimeout(function() { // removing the focusNode before the insert is done looses the correct position of the caret
            nextNode.removeChild(focusNode);
        }, 10);
    },

    // =========================================================================
    // Check if the current selection is in or at already existing markup-nodes
    // =========================================================================

    /**
     * Get the surrounding node of the current selection (= node where the selection starts).
     * If the selection is in a text node, the selection's parentNode is returned.
     * TODO: checking the startContainer only might not be enough when the selection compasses multiple nodes.
     * @returns {Object}
     */
    getContainerNodeForCurrentSelection: function(){
        var containerNode = this.docSelRange.startContainer;
        if (containerNode.nodeType == 3) {
            return this.docSelRange.startContainer.parentNode;
        }
        return containerNode;
    },
    /**
     * Get the previous or next node of the current selection.
     * If the selection is in a text node without a sibling, the sibling of the selection's parentNode is returned.
     * @param {String} direction (previous|next)
     * @returns {?Object}
     */
    getSiblingNodeForCurrentSelection: function(direction){
        var docSel = this.docSelRange,
            lookForPrevious = (direction == "previous") ? true : false,
            currentNode = (lookForPrevious ? docSel.startContainer : docSel.endContainer),
            currentParentNode = currentNode.parentNode;
        var siblingNode = (lookForPrevious ? currentNode.previousSibling : currentNode.nextSibling);
        if ( (siblingNode == null) && (currentNode.nodeType == 3) ) {
            siblingNode = (lookForPrevious ? currentParentNode.previousSibling : currentParentNode.nextSibling);
        }
        return siblingNode;
    },
    /**
     * Checks if the current selection is within an existing markup-node of the given conditions to check.
     * @param {String} checkConditions (sameNodeName|foreignMarkup|sameConditionsAndEvent)
     * @returns {Boolean}
     */
    isWithinNode: function(checkConditions) {
        var tmpMarkupNode = this.createNodeForMarkup(this.getNodeNameAccordingToEvent()),
            selectionNode = this.getContainerNodeForCurrentSelection();
        switch(checkConditions) {
            case 'sameNodeName':
                console.log("isWithinNode ("+checkConditions+")? " + this.isNodesOfSameName(tmpMarkupNode,selectionNode));
                return this.isNodesOfSameName(tmpMarkupNode,selectionNode);
            case 'foreignMarkup':
                console.log("isWithinNode ("+checkConditions+")? " + this.isNodesOfForeignMarkup(tmpMarkupNode,selectionNode));
                return this.isNodesOfForeignMarkup(tmpMarkupNode,selectionNode);
            case 'sameConditionsAndEvent':
                console.log("isWithinNode ("+checkConditions+")? " + this.isNodesOfSameConditionsAndEvent(tmpMarkupNode,selectionNode));
                return this.isNodesOfSameConditionsAndEvent(tmpMarkupNode,selectionNode);
            // no default, because then we would have a bug in the code. Calling this method without a correct parameter is too dangerous in consequences.
        }
    },
    /**
     * Checks if the current selection is right before or after an already existing markup-node of the same kind.
     * ('previous' checks for previous sibling, 'next' checks for next sibling).
     * @param {String} direction (previous|next)
     * @returns {Boolean}
     */
    isAtSiblingOfSameKind: function(direction) {
        console.log("isAtSiblingOfSameKind ("+direction+")?");
        var tmpMarkupNode = this.createNodeForMarkup(this.getNodeNameAccordingToEvent()),
            siblingNode = this.getSiblingNodeForCurrentSelection(direction);
        
        // ------ (1) is of same conditions & event? ------
        
        var currentIsOfSameConditionsAsSibling = this.isNodesOfSameConditionsAndEvent(tmpMarkupNode,siblingNode);
        console.log("currentIsOfSameConditionsAsSibling: " + currentIsOfSameConditionsAsSibling);
        if (!currentIsOfSameConditionsAsSibling) {
            return false;
        }
        
        //------ (2) is at sibling? ------

        var rangeAtCurrentSelection = rangy.createRange(),
            rangeForSiblingNode = rangy.createRange();
        
        // Eigentlich wollte ich die Boundaries der Ranges der aktuellen Selection und des nextNode von der Selection vergleichen; das klappt aber nicht.
        // Workaround: Selektierte Range klonen, dort einen node einfügen, die geklonte Range dort drumsetzen und DIE dann mit der Range für den nextNode vergleichen.
        rangeAtCurrentSelection = this.docSelRange.cloneRange();
        rangeAtCurrentSelection.insertNode(tmpMarkupNode);
        
        // ACHTUNG (Beispiel): Zwischen einem gelöschten a und einem eingefügten c soll ein b eingefügt werden:
        // <del>a</del><ins>c</ins> soll anschliessend sein: <del>a</del><ins>bc</ins>
        // Nun landet der Cursor ("|") beim Platzieren zwischen a und c allerdings aus Sicht des Editors IN dem DEL:
        // <del>a|</del><ins>c</ins>
        // Nun müssen wir also prüfen, ob das DEL direkt vor einem INS ist (nicht, ob das innerhalb des DEL platzierte Markup-INS direkt vor einem INS ist).
        if (this.isWithinNode('foreignMarkup')) {
            var nodeForSelection = this.getContainerNodeForCurrentSelection();
        } else {
            var nodeForSelection = tmpMarkupNode;
        }
        
        // (2a) has a sibling?
        rangeAtCurrentSelection.selectNodeContents(nodeForSelection);
        rangeForSiblingNode.selectNodeContents(siblingNode);
        if (direction == 'previous') {
            var currentHasSibling = rangeAtCurrentSelection.compareBoundaryPoints(Range.END_TO_START, rangeForSiblingNode) == this.RANGY_RANGE_IS_AFTER;
        } else {
            var currentHasSibling = rangeAtCurrentSelection.compareBoundaryPoints(Range.START_TO_END, rangeForSiblingNode) == this.RANGY_RANGE_IS_BEFORE;
        }
        console.log("compareBoundaryPoints: " + currentHasSibling);
        
        // (2b) is right at the sibling, not just somewhere?
        rangeAtCurrentSelection.selectNode(nodeForSelection);
        rangeForSiblingNode.selectNode(siblingNode);
        var currentTouchesSibling = rangeAtCurrentSelection.intersectsOrTouchesRange(rangeForSiblingNode);
        console.log("intersectsOrTouchesRange: " + currentTouchesSibling);
        
        // Cleanup
        if(tmpMarkupNode.parentNode) {
            tmpMarkupNode.parentElement.removeChild(tmpMarkupNode);
        }
        
        return currentHasSibling && currentTouchesSibling;
    },

    // =========================================================================
    // Helpers for creating and comparing Nodes
    // =========================================================================
    
    /**
     * Returns the markup-nodeNames for all events handled.
     * @returns {Object} 
     */
    getMarkupNodeNamesForEvents: function(){
        return {
            'deletion':  this.NODE_NAME_DEL,
            'insertion': this.NODE_NAME_INS
        }
    },
    /**
     * Returns the markup-nodeName according to the current event.
     * @returns {String} 
     */
    getNodeNameAccordingToEvent: function(){
        var nodeNamesForEvents = this.getMarkupNodeNamesForEvents();
        switch(true) {
            case this.eventIsDeletion():
                return nodeNamesForEvents.deletion;
            case this.eventIsInsertion():
                return nodeNamesForEvents.insertion;
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
     * Is the Node of a markup-type (DEL, INS)?
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeOfTypeMarkup: function(node) {
        var nodeName = (node == null) ? 'null' : node.nodeName,
            nodeNamesForEvents = this.getMarkupNodeNamesForEvents();
        for (var key in nodeNamesForEvents) {
            if (nodeNamesForEvents[key] == nodeName) {
                return true;
            }
        }
        return false;
    },
    /**
     * Are the nodes NOT of the same Markup?
     * 
     * Examples for true:
     * -----------------------------
     *   nodeA      |   nodeB       
     * -----------------------------
     *    DEL       |    INS        
     *    INS       |    DEL        
     *  DEL abc     |  DEL def      
     *  INS abc     |  INS def      
     * -----------------------------
     * 
     * Examples for false:
     * -----------------------------
     *   nodeA      |   nodeB       
     * -----------------------------
     *    IMG       |    INS        
     *  DEL abc     |  DEL abc      
     *  INS abc     |  INS abc      
     * -----------------------------
     * 
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfForeignMarkup: function(nodeA,nodeB) {
        // Wenn einer der beiden gar kein Markup-Node ist, brauchen wir nicht weiter prüfen.
        if (!this.isNodeOfTypeMarkup(nodeA) || !this.isNodeOfTypeMarkup(nodeB)) {
            return false; // false weil: NEIN, ist (gar) KEIN Markup (also auch kein fremdes Markup).
        }
        return !this.isNodesOfSameConditionsAndEvent(nodeA,nodeB);
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
     * Split a node at the position of the current selection.
     * Returns the two parts of the node afterwards.
     * @param {Object} nodeToSplit
     * @returns {Array}
     */
    splitNode: function(nodeToSplit) {
        var splittedNodes = new Array();
        // extract what's on the left from the caret and insert it before the node as a new node
        var rangeForExtract = rangy.createRange(),
            selectionStartNode = this.docSelRange.startContainer,
            selectionStartOffset = this.docSelRange.startOffset,
            parentNode = nodeToSplit.parentNode;
        rangeForExtract.setStartBefore(nodeToSplit);
        rangeForExtract.setEnd(selectionStartNode, selectionStartOffset);
        splittedNodes[0] = rangeForExtract.extractContents();
        splittedNodes[1] = nodeToSplit; // nodeToSplit: ist nach extractContents() jetzt nur noch der zweite Teil des gesplitteten Nodes
        parentNode.insertBefore(splittedNodes[0], splittedNodes[1]); // TODO: Prüfen, ob beide Teile des gesplitteten nodes dieselben Angaben haben (User, Workflow, ...)
        // "reset" position of the user's selection (= where the node was split)
        // TODO: prüfen, ob Rangy's getBookmark besser wäre; s.a. https://github.com/timdown/rangy/wiki/Rangy-Selection#restorerangessaved
        this.docSelRange.setEndBefore(splittedNodes[1]);
        this.docSelRange.collapse(false);
        this.docSel.setSingleRange(this.docSelRange);
        return splittedNodes;
    },
    /**
     * Move a given node inbetween the two halfs of a formerly splitted node.
     * @param {Object} nodeToMove
     * @param {Array} splittedNodes
     */
    moveNodeInbetweenSplittedNodes: function(nodeToMove,splittedNodes) {
        splittedNodes[1].parentNode.insertBefore(nodeToMove, splittedNodes[1]);
    }
});
