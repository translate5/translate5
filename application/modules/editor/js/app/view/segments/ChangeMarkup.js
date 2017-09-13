
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
    
    // https://github.com/timdown/rangy/wiki/Rangy-Range#compareboundarypointsnumber-comparisontype-range-range
    RANGY_RANGE_IS_BEFORE: -1,
    RANGY_RANGE_IS_AFTER: 1,
    
    // "SETTINGS/CONFIG"
    CHAR_PLACEHOLDER: '\u0020', // what's inserted when a space is inserted 
    
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
            console.log(" => Ignored!"); // TODO: console.logs nicht irgendwann rausschmeißen, sondern in Fkt kapseln und ein-/ausschalten
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
        var extEv = Ext.event.Event,
            keyCodesToIgnore = [
                                extEv.LEFT, extEv.UP, extEv.RIGHT, extEv.DOWN,          // Arrow Keys
                                extEv.ALT, extEv.CAPS_LOCK, extEv.CTRL, extEv.SHIFT,    // Modifier Keys
                                extEv.HOME, extEv.END, extEv.PAGE_UP, extEv.PAGE_DOWN,  // Special Keys
                                extEv.F1, extEv.F2, extEv.F3, extEv.F4, extEv.F5, extEv.F6, extEv.F7, extEv.F8, extEv.F9, extEv.F10, extEv.F11, extEv.F12, // Function Keys
                                extEv.ENTER                                             // Other Keys To Ignore
                               ];
        if(this.eventCtrlKey) {
            keyCodesToIgnore.push(extEv.C);                                             // Ctrl-C
        }
        return (keyCodesToIgnore.indexOf(this.eventKeyCode) != -1);
    },
    /**
     * Has the Key-Event to be IGNORED and STOPPED?
     * @returns {Boolean}
     */
    eventHasToBeIgnoredAndStopped: function() {
        var extEv = Ext.event.Event,
            keyCodesToIgnoreAndStop = new Array(); 
        if(this.eventCtrlKey) {
            keyCodesToIgnoreAndStop.push(extEv.Z);                                      // Ctrl-Z
        }
        return (keyCodesToIgnoreAndStop.indexOf(this.eventKeyCode) != -1);
    },
    /**
     * Is the Key-Event a DELETION?
     * @returns {Boolean}
     */
    eventIsDeletion: function() {
        var extEv = Ext.event.Event,
            keyCodesForDeletion = [extEv.BACKSPACE, extEv.DELETE];
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
                if (this.isWithinNode('insNodeWithSameConditions')) {
                    // DEL in einem INS von demselben User und in demselben Workflow löscht das Zeichen tatsächlich einfach.
                    console.log(" => eventIsDeletion, Zeichen werden (da in INS) tatsächlich gelöscht und wir machen sonst nichts.");
                    this.stopEvent = false; // Das Event wird anschließend ausgeführt (= das Löschen geht dann normal vonstatten).
                } else {
                    // Ansonsten:
                    console.log(" => eventIsDeletion");
                    this.stopEvent = true;  // Das Event wird auf jeden Fall gestoppt; Zeichen werden nicht mehr gelöscht (sondern nur noch als gelöscht markiert).
                    this.handleDeletion();
                }
            break;
            case this.eventIsInsertion():
                console.log(" => eventIsInsertion");
                this.stopEvent = false; // Das Event wird anschließend ausgeführt (= das Einfügen geht dann normal vonstatten).
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
        // - "Rückwärts" markierte Selections.
    },
    /**
     * Handle deletion-Events.
     */
    handleDeletion: function() {
        
        // Is the DEL-markup already in place?
        
            if(this.isWithinNode('sameNodeName')) {
                if ( (this.eventKeyCode == Ext.event.Event.BACKSPACE) && this.isWithinAtFirstPosition() ) {
                    // Wenn wir ganz am Anfang innerhalb irgendeines(!) DEL sind, muss bei "Backspace" das Zeichen davor mit dazugenommen werden.
                    // Also: Kein return hierfür!
                } else if ( (this.eventKeyCode == Ext.event.Event.DELETE) && this.isWithinAtLastPosition() ) {
                    // Wenn wir ganz am Ende innerhalb irgendeines(!) DEL sind, muss bei "Delete" das Zeichen dahinter gelöscht werden.
                    // Dafür setzen gehen wir mit dem Caret HINTER das DEL und machen von da aus ganz normal weiter.
                    console.log("'DELETE' && sameNodeName && isWithinAtLastPosition): moveCaretToTheRight.");
                    this.moveCaretToTheRight();
                } else {
                    // Wenn wir uns ansonsten bereits in irgendeinem(!) DEL befinden: stoppen. (Das zu löschende Zeichen ist ja bereits als gelöscht markiert.)
                    console.log("DEL: is part of DEL already..., we do nothing.");
                    return;
                }
            }
            
            if (this.isAtSibling('previous','sameNodeName') && this.eventKeyCode == Ext.event.Event.BACKSPACE) {
                // Bei "Backspace" direkt hinter irgendeinem(!) DEL: stoppen. (Das zu löschende Zeichen ist ja bereits als gelöscht markiert.)
                console.log("DEL: is at previous DEL already..., we do nothing.");
                return;
            }
            
            if (this.isAtSibling('next','sameNodeName') && this.eventKeyCode == Ext.event.Event.DELETE ){
                // Bei "Delete" in irgendein(!) nachfolgend bestehendes DEL hinein: stoppen. (Das zu löschende Zeichen ist ja bereits als gelöscht markiert.)
                console.log("DEL: is at next DEL already..., we do nothing.");
                return;
            }
        
        // Because DEL-markup needs to be added or changed:
        
            // (1) create and insert <del>-node:
            console.log("DEL: insert Markup...");
            var delNode = this.addDel();
            
            // (2a) Wenn wir uns in einem fremden Node befinden (= INS; allg. DEL wurden oben bereits abgefragt!)...
            if (this.isWithinNode('foreignMarkup')) {
                // ... dann müssen wir dieses jetzt noch an dieser Stelle auseinanderbrechen...
                console.log("DEL: split foreign node...");
                var splittedNodes = this.splitNode(delNode.parentNode);
                // ... und den eben erzeugten DEL aus dem ersten Teil-INS herausholen und zwischen die beiden neuen INS-Teile schieben.
                console.log("DEL: move DEL up inbetween...");
                this.moveNodeInbetweenSplittedNodes(delNode,splittedNodes);
                return;
            }
            
            // (2b) Berührt das neu erzeugte DEL ein bereits bestehendes DEL? Dann müssen wir es dem bereits bestehenden DEL anschließen.
            //      Beispiele:
            //      - Wenn wir ganz am Anfang eines DEL sind, muss bei "Backspace" das Zeichen davor mit dazugenommen werden.
            //      - Wenn wir ganz am Ende eines DEL sind, muss bei "Delete" das Zeichen dahinter mit dazugenommen werden.
            //      - bei Backspace: kein neues DEL, wenn schon in der Position VOR der Position DAVOR ein DEL existiert.
            //      - bei Delete: kein neues DEL, wenn schon in der Position NACH der Position DANACH ein DEL existiert.
            console.log("Sind wir direkt vor oder hinter einem anderen DEL dran, an das wir uns anschliessen müssten?");
            
            var delNodePrevious = delNode.previousElementSibling,
                delNodeNext     = delNode.nextElementSibling;
            
            // Is at previous?
            var isOfSameConditionsAsPrevious = this.isNodesOfSameConditionsAndEvent(delNode,delNodePrevious),
                isTouchingPrevious = this.isNodesTouching(delNode,delNodePrevious,'previous');
            if ( isOfSameConditionsAsPrevious && isTouchingPrevious ) {
                console.log("- Ja, isAtSibling(previous)");
                // The deleted content can be INBETWEEN two DEL-Nodes of the same conditions.
                // Thus, for the next check we use the merged node:
                delNode = this.mergeNodesFromTo(delNode,delNodePrevious);
            }
            
            // Is at next?
            var isOfSameConditionsAsNext     = this.isNodesOfSameConditionsAndEvent(delNode,delNodeNext),
                isTouchingNext     = this.isNodesTouching(delNode,delNodeNext,'next');
            if ( isOfSameConditionsAsNext && isTouchingNext ) {
                console.log("- Ja, isAtSibling(next)");
                this.mergeNodesFromTo(delNodeNext,delNode);
            }
},
    /**
     * Handle insert-Events.
     */
    handleInsert: function() {
        
        // Are we within a foreign Markup (e.g. DEL of any conditions, or INS of another user)?
        
            // Then we need to split that one first:
            if (this.isWithinNode('foreignMarkup')) {
                console.log("INS: split foreign node first...");
                this.splitNode(this.getContainerNodeForCurrentSelection());
            }
        
        
        // Are characters marked to be replaced by the insert?
        
            // Then we have to:
            // 1) mark those with a DEL-node, and 
            // 2) position the caret at the end of the marked range afterwards.
            // Then the further procedure is as usual.
            
            if (!this.docSelRange.collapsed) {
                var delNode = this.addDel();
                this.docSelRange.collapseAfter(delNode);
            }
            
        
        // Are we in or at an INS-node?
        
            // Wenn wir schon im richtigen MarkUp sind, machen wir sonst weiter nichts.
            if (this.isWithinNode('sameConditionsAndEvent')) {
                console.log("INS: isWithinNodeOfSameKind..., we do nothing.");
                return;
            }
            
            // if this new node is right behind an ins-node that already exists, we use that one:
            if (this.isAtSibling('previous','sameConditionsAndEvent')) {
                console.log("INS: use previous..."); 
                // (scheint nie vorzukommen, wird immer als isWithinNodeOfSameKind erkannt.)
                return;
            }
            
            // if this new node is right before an ins-node that already exists, we use that one:
            if (this.isAtSibling('next','sameConditionsAndEvent')) {
                console.log("INS: use next...");
                this.useNextIns();
                return;
            }
        
        // Because we are neither in nor at an INS-node:
        
            // Create and insert <ins>-node:
            if (this.eventKeyCode == Ext.event.Event.SPACE) { // Workaround for inserting space (otherwise creates <u>-Tag, don't know why).
                console.log("INS: insert space with Markup...");
                this.addInsWithSpace();
            } else {
                console.log("INS: insert Markup...");
                this.addIns();
            }
    },

    // =========================================================================
    // Deletions
    // =========================================================================

    /**
     * Return a range with the content that is to be marked as deleted.
     * @returns {Object} rangeForDel
     */
    getRangeToBeDeleted: function() {
        var startC = this.docSelRange.startContainer,
            startO = this.docSelRange.startOffset,
            endC = this.docSelRange.endContainer,
            endO = this.docSelRange.endOffset,
            rangeForDel = this.docSelRange; // this.docSelRange.cloneRange(); // buggy: sometimes the cloned range is collapsed to the start although this.docSelRange is NOT.
        // "S""miz de ceillos" => "Smiz de ceillos"
        rangeForDel.startContainer.parentNode.normalize();
        rangeForDel.endContainer.parentNode.normalize();
        // set start and end according to the user's selection
        rangeForDel.setStart(startC, startO); 
        rangeForDel.setEnd(endC, endO);
        // If nothing is selected, the caret has just been placed somewhere and the deletion refers the next single character only:
        // (moveStart, moveEnd:) https://github.com/timdown/rangy/wiki/Text-Range-Module#movestartstring-unit-number-count-object-options
        if (rangeForDel.collapsed) {
            switch(this.eventKeyCode) {
                case Ext.event.Event.BACKSPACE: // Backspace: "deletes" the previous character
                    rangeForDel.moveStart("character", -1);
                    break;
                case Ext.event.Event.DELETE: // Delete "deletes" the next character
                    rangeForDel.moveEnd("character", 1);
                    break;
            }
        }
        return rangeForDel;
    },
    /**
     * Mark a deletion as deleted:
     * Instead of deleting the character, we wrap it into a DEL-node (and return that).
     * @returns {Object} delNode
     */
    addDel: function() {
        var rangeForDel = this.getRangeToBeDeleted(),               // Range to move into the DEL-node
            delNode = this.createNodeForMarkup(this.NODE_NAME_DEL); // <del>-Element to use around the range for deletion
        if (rangeForDel.canSurroundContents(delNode)) {
            rangeForDel.surroundContents(delNode);
        } else {
            console.log("Unable to surround range because range partially selects a non-text node. See DOM4 spec for more information.");
        }
        // For "Delete": position the caret at the end
        if (this.eventKeyCode == Ext.event.Event.DELETE) {
            rangeForDel.collapse(false);
            this.docSel.setSingleRange(rangeForDel);
        }
        return delNode;
    },
    /**
     * 
     */
    moveCaretToTheRight: function() {
        this.docSelRange.move("character", 1);
        this.docSelRange.setStart(this.docSelRange.startContainer, 0);
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
        
        // + (seems to be the same issue:) when first inserting content in an empty element (= ins is created, but not visible and not recognized)
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
     * TODO (Prio II) generell: Parameter als Klassen-KONSTANTEN zB mit namespace definieren (dann sind alle Optionen an einem Platz einsehbar)
     * @param {String} checkConditions (insNodeWithSameConditions|sameNodeName|foreignMarkup|sameConditionsAndEvent)
     * @returns {Boolean}
     */
    isWithinNode: function(checkConditions) {
        var tmpMarkupNode = this.createNodeForMarkup(this.getNodeNameAccordingToEvent()),
            selectionNode = this.getContainerNodeForCurrentSelection();
        switch(checkConditions) {
            case 'insNodeWithSameConditions':
                var tmpMarkupNode = this.createNodeForMarkup(this.NODE_NAME_INS);
                console.log("isWithinNode ("+checkConditions+")? " + this.isNodesOfSameName(tmpMarkupNode,selectionNode) + this.isNodesOfSameConditions(tmpMarkupNode,selectionNode));
                return this.isNodesOfSameName(tmpMarkupNode,selectionNode) && this.isNodesOfSameConditions(tmpMarkupNode,selectionNode);
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
     * Checks if the current selection is right before or after an already existing markup-node of the same given conditions to check.
     * ('previous' checks for previous sibling, 'next' checks for next sibling).
     * @param {String} direction (previous|next)
     * @param {String} checkConditions (sameNodeName|sameConditionsAndEvent)
     * @param {Object} tmpMarkupNode
     * @param {Object} siblingNode
     * @returns {Boolean}
     */
    isAtSibling: function(direction,checkConditions) {
        console.log("isAtSibling ("+direction+")?");
        var tmpMarkupNode = this.createNodeForMarkup(this.getNodeNameAccordingToEvent()),
            siblingNode = this.getSiblingNodeForCurrentSelection(direction);
        
        // ------ (1) check conditions -------------------------------------------------------
        
        switch(checkConditions) { // TODO: DRY (s switch(checkConditions) oben)
            case 'sameNodeName':
                var currentIsOfSameConditionsAsSibling = this.isNodesOfSameName(tmpMarkupNode,siblingNode);
            case 'sameConditionsAndEvent':
                var currentIsOfSameConditionsAsSibling = this.isNodesOfSameName(tmpMarkupNode,siblingNode) && this.isNodesOfSameConditionsAndEvent(tmpMarkupNode,siblingNode);
            // no default, because then we would have a bug in the code. Calling this method without a correct parameter is too dangerous in consequences.
        }
        console.log("- checkConditions (" + checkConditions + "): " + currentIsOfSameConditionsAsSibling);
        if (!currentIsOfSameConditionsAsSibling) {
            return false;
        }
        
      //------ (2) check position (= is really AT the sibling in the given direction?) ------

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
        console.log("- compareBoundaryPoints: " + currentHasSibling);
        
        // (2b) is right at the sibling, not just somewhere?
        rangeAtCurrentSelection.selectNode(nodeForSelection);
        rangeForSiblingNode.selectNode(siblingNode);
        var currentTouchesSibling = rangeAtCurrentSelection.intersectsOrTouchesRange(rangeForSiblingNode);
        console.log("- intersectsOrTouchesRange: " + currentTouchesSibling);
        
        // Cleanup
        if(tmpMarkupNode.parentNode) {
            tmpMarkupNode.parentElement.removeChild(tmpMarkupNode);
        }
        
        return currentHasSibling && currentTouchesSibling;
    },
    /**
     * Checks if the two given nodes touch each other in the given direction.
     * @returns {Boolean}
     */
    isNodesTouching: function(node,siblingNode,direction){
        console.log("isNodesTouching ("+direction+")?");
        if (node == null || siblingNode == null) {
            return false;   // is not even two nodes, thus it's also not two nodes touching.
        }
        
        var hasSiblingInGivenDirection = false,
            isTouchingSibling = false,
            rangeForNode                = rangy.createRange(),
            rangeForNodeContents        = rangy.createRange(),
            rangeForSiblingNode         = rangy.createRange(),
            rangeForSiblingNodeContents = rangy.createRange();
        
        rangeForNode.selectNode(node);
        rangeForSiblingNode.selectNode(siblingNode);
        rangeForNodeContents.selectNodeContents(node);
        rangeForSiblingNodeContents.selectNodeContents(siblingNode);
        
        // (a) is the sibling in the given direction?
        if (direction == 'previous') {
            hasSiblingInGivenDirection = rangeForNodeContents.compareBoundaryPoints(Range.END_TO_START, rangeForSiblingNodeContents) == this.RANGY_RANGE_IS_AFTER;
        } else {
            hasSiblingInGivenDirection = rangeForNodeContents.compareBoundaryPoints(Range.START_TO_END, rangeForSiblingNodeContents) == this.RANGY_RANGE_IS_BEFORE;
        }
        
        // (b) is right at the sibling, not just somewhere?
        // Workaround: move the node's boundary by one character "into" it's sibling (according to the direction),
        // otherwise the ranges are neighbors, but don't share one common boundary 
        // (= which is necessary for ranges to be seen as "touching": https://github.com/timdown/rangy/wiki/Rangy-Range#intersectsortouchesrangerange-range).
        if (direction == 'previous') {
            rangeForNode.moveStart('character',-1);
        } else {
            rangeForNode.moveEnd('character',1);
        }
        isTouchingSibling = rangeForNode.intersectsRange(rangeForSiblingNode); // intersectsOrTouchesRange(): touching does not apply since the nodes would be "just neighbors".
        
        console.log("- compareBoundaryPoints: " + hasSiblingInGivenDirection);
        console.log("- intersectsOrTouchesRange: " + isTouchingSibling);
        return hasSiblingInGivenDirection && isTouchingSibling;
    },
    /**
     * Checks if the current selection is at the first position within its node.
     * @returns {Boolean}
     */
    isWithinAtFirstPosition: function(){
        return (this.docSelRange.startOffset < 1);
    },
    /**
     * Checks if the current selection is at the last position within its node.
     * @returns {Boolean}
     */
    isWithinAtLastPosition: function(){
        var endC = this.docSelRange.endContainer,
            endO = this.docSelRange.endOffset;
        return (endC.length == endO);
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
        var nodeEl = document.createElement(nodeName); // TODO: use Ext.DomHelper.createDom() instead
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
        return true; // TODO
    },
    /**
     * Does the given node's user match the user according to the event?
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeOfUserAccordingToEvent: function(node) {
        return true; // TODO
    },
    /**
     * Do the nodes share the same workflow?
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameWorkflow: function(nodeA,nodeB) {
        return true; // TODO
    },
    /**
     * Does the given node's workflow match the workflow according to the event?
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeOfWorkflowAccordingToEvent: function(node) {
        return true; // TODO
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
    },
    /**
     * Merge two nodes (= move the content from one node to another and remove it then).
     * Returns the (formerly nodeTo-) node with the merged content.
     * @param {Object} nodeFrom
     * @param {Object} nodeTo
     * @returns {Object} nodeForMergedContent
     */
    mergeNodesFromTo: function(nodeFrom,nodeTo) {
        var nodeForMergedContent = nodeTo;
        while (nodeFrom.childNodes.length > 0) {
            nodeForMergedContent.appendChild(nodeFrom.childNodes[0]);
        }
        nodeForMergedContent.normalize();
        nodeFrom.parentNode.removeChild(nodeFrom);
        console.log("-> Nodes gemergt.");
        return nodeForMergedContent;
    }
});
