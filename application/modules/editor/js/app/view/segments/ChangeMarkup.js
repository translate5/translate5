
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
    
    editorUsername: null,           // username of current user working in the Editor
    segmentWorkflowStepNr: null,    // workflow of the currently edited segment in the Editor
    
    eventKeyCode: null,             // Keyboard-Event: Key-Code
    eventCtrlKey: null,             // Keyboard-Event: Control-Key pressed?
    
    ignoreEvent: false,             // ignore event? (= we do nothing here)
    stopEvent: false,               // do we stop the event here?
    
    docSel: null,                   // selection in the document (initially what the user has selected, but then constantly changed according to handling the Markup)
    docSelRange: null,              // current range for handling the markup, positioning the caret, etc... (initially what the user has selected, but then constantly changing)
    initialRangeBookmark: null,     // bookmark what the user has selected initially
    
    // "CONSTANTS"
    NODE_NAME_DEL: 'DEL',
    NODE_NAME_INS: 'INS',
    
    ATTRIBUTE_USERNAME: 'data-username',
    ATTRIBUTE_USER_CSS: 'data-usercss',
    ATTRIBUTE_WORKFLOWSTEPNR: 'data-workflowstepnr',
    ATTRIBUTE_TIMESTAMP: 'data-timestamp',
    
    // https://github.com/timdown/rangy/wiki/Rangy-Range#compareboundarypointsnumber-comparisontype-range-range
    RANGY_RANGE_IS_BEFORE: -1,
    RANGY_RANGE_IS_AFTER: 1,
    
    // "SETTINGS/CONFIG"
    CHAR_PLACEHOLDER: '\u0020',     // what's inserted as placeholder when creating elements
    
    USE_CONSOLE: true,              // (true|false): use true for developing using the browser's console, otherwise use false
    
    /**
     * The given segment content is the base for the operations provided by this method
     * @param {Editor.view.segments.HtmlEditor} editor
     */
    constructor: function(editor,segmentWorkflowStepNr) {
        this.editor = editor;
        // Data we'll need independent from the event in the Editor:
        this.editorUsername = Editor.data.app.user.userName;
        this.segmentWorkflowStepNr = segmentWorkflowStepNr;
    },
    initEvent: function() {
        // "Reset"
        this.eventKeyCode = null;
        this.eventCtrlKey = null;
        this.ignoreEvent = false;
        this.stopEvent = false;
        this.docSel = null;
        this.docSelRange = null;
        this.initialRangeBookmark = null;
    },
    /**
     * This method is called if the keyboard event (= keydown) was not handled otherwise
     * @param {Object} event
     */
    handleTargetEvent: function(event) {
        
        this.initEvent();
        this.consoleClear();
        
        // What keyboard event do we deal with?
        this.setKeyboardEvent(event);
        
        // Ignore keys that dont's produce content (strg,alt,shift itself, arrows etc)
        if(this.eventHasToBeIgnored()){ 
            this.consoleLog(" => Ignored!");
            this.ignoreEvent = true;
        }
        // Ignore and stop keys that must not change the content in the Editor (e.g. strg-z might revert to an INS-node with placeholder)
        if(this.eventHasToBeIgnoredAndStopped()){ 
            this.consoleLog(" => Ignored and stopped!");
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
    // Helpers regarding the Selection
    // =========================================================================
    
    /**
     * Get range according to selection (using rangy-library: https://github.com/timdown/rangy/).
     * CAUTION! Working with cloned ranges of this.docSelRange with cloneRange() DOES affect the inital docSelRange (https://stackoverflow.com/a/11404869).
     */
    setRangeForSelection: function() {
        this.docSelRange = this.docSel.rangeCount ? this.docSel.getRangeAt(0) : null;
    },
    /**
     * https://github.com/timdown/rangy/wiki/Rangy-Selection#refreshboolean-checkforchanges
     */
    refreshSelectionAndRange: function() {
        this.docSel.refresh;
        this.setRangeForSelection();
    },

    // =========================================================================
    // "Controller"
    // =========================================================================
    
    /**
     * Insert INS-/DEL-Tags while typing
     */
    changeMarkupInEditor: function() {
        var editorContentBefore,
            editorContentAfter;
        
        // get range according to selection (using rangy-library)
        this.docSel = rangy.getSelection(this.editor.getDoc());
        this.setRangeForSelection();
        
        if (this.docSelRange == null) {
            this.consoleLog("ChangeMarkup: getSelection FAILED.");
            return;
        }
        
        this.initialRangeBookmark = this.docSelRange.getBookmark();
        
        // change markup according to event
        editorContentBefore = this.editor.getEditorBody().innerHTML;
        switch(true) {
            case this.eventIsDeletion():
                this.consoleLog(" => eventIsDeletion");
                this.handleDeletion();
                this.stopEvent = true;  // Stop the delete-Event; characters are either deleted or marked as deleted now.
            break;
            case this.eventIsInsertion():
                this.consoleLog(" => eventIsInsertion");
                this.stopEvent = false; // The insert-Event continues after creating the INS-Node.
                this.handleInsert();
            break;
        }
        editorContentAfter = this.editor.getEditorBody().innerHTML;
        
        // if changeMarkup has changed the content: clean up nested markup-tags etc.
        if (editorContentAfter != editorContentBefore) {
            this.cleanUpMarkupInEditor();
        }
    },
    /**
     * Handle deletion-Events.
     */
    handleDeletion: function() {
        var delNode = null;
        
        // TODO: this.getRangeToBeDeleted() is called multiple times during the whole process; better store it.

        // If the nodes/characters that are to be deleted are marked as deleted already...
        if(this.getRangeToBeDeleted() == null){
            this.consoleLog("DEL: nothing to mark or everything is marked as deleted already, we do nothing.");
            return; // ... we do nothing.
        }
        
        // Handle existing INS-Tags within the selection for deletion.
        this.consoleLog("DEL: handleInsNodesInDeletion...");
        delNode = this.handleInsNodesInDeletion();
        // If we have aleady removed everything that was selected, then we are done here.
        if (delNode == 'completeSelectionIsHandled') {
            return;
        }
        
        // Handle existing images within the selection for deletion.
        this.consoleLog("DEL: handleImagesInDeletion...");
        delNode = this.handleImagesInDeletion();
        // If we have marked an IMG that encompassed everything that was selected, then we are done here.
        if (delNode == 'completeSelectionIsHandled') {
            return;
        }
        
        // Content that is already marked as deleted needs no further handling.
        
        // Mark unmarked contents as deleted.
        this.consoleLog("DEL: addDel...");
        delNode = this.addDel();
        this.positionCaretAfterDeletion(delNode);
    },
    /**
     * Handle insert-Events.
     */
    handleInsert: function() {
        var insNodeBehind,
            insNodeBefore,
            foreignContainerNode;
        
        // Are characters marked to be replaced by the insert?
        
            // Then we have to:
            // 1) mark those with a DEL-node, and 
            // 2) position the caret at the end of the marked range afterwards.
            // Then the further procedure is as usual.
            if (!this.docSelRange.collapsed) {
                this.consoleLog("INS: handleDeletion first.");
                this.handleDeletion();
                this.cleanUpMarkupInEditor();
                this.positionCaretAtBookmark();
                this.refreshSelectionAndRange();
            }
        
        // Are we in or at an INS-node?
        
            // if we are in an INS-Markup that fits our needs already: nothing to do.
            if (this.selectionIsWithinINSNodeAccordingToEvent()) {
                this.consoleLog("INS: selectionIsWithinINSNodeAccordingToEvent..., we do nothing.");
                return;
            }
            
            // if we are right behind an ins-node that already exists,
            // we use that one:
            insNodeBefore = this.getINSNodeForContentBeforeCaretAccordingToEvent();
            if (insNodeBefore != null) {
                this.consoleLog("INS: use previous..."); 
                // (seems to never happen; is seen as selectionIsWithinINSNodeAccordingToEvent instead.)
                return;
            }
            
            // if we are right before an ins-node that already exists,
            // we use that one:
            insNodeBehind = this.getINSNodeForContentBehindCaretAccordingToEvent();
            if (insNodeBehind != null) {
                this.consoleLog("INS: use next..."); 
                this.positionCaretInNode(insNodeBehind);
                return;
            }
            
        // We are neither in nor at an INS-node:
            
            // Are we within a foreign Markup (e.g. DEL of any conditions, or INS of another user),
            // but (see checks above with return) NOT right at an INS-node we will use instead anyway?
            // Then we need to split that one first:
            foreignContainerNode = this.getForeignMarkupNodeForInsertion();
            if (foreignContainerNode != null) {
                this.consoleLog("INS: split foreign node first...");
                this.splitNode(foreignContainerNode,this.docSelRange);
            }
        
            // Create and insert <ins>-node:
            if (this.eventKeyCode == Ext.event.Event.SPACE) { // Workaround for inserting space (otherwise creates <u>-Tag, don't know why).
                this.consoleLog("INS: insert space with Markup...");
                this.addIns(' ');
                this.stopEvent = true; // space is already inserted now, stop the keyboard-Event!
            } else {
                this.consoleLog("INS: insert Markup...");
                this.addIns('');
            }
    },

    // =========================================================================
    // Deletions
    // =========================================================================
    
    /**
     * Return a range with the content that is to be marked as deleted
     * (or null if nothing is to be deleted).
     * @returns {?Object} rangeForDel
     */
    getRangeToBeDeleted: function() {
        var startC = this.docSelRange.startContainer,
            startO = this.docSelRange.startOffset,
            endC = this.docSelRange.endContainer,
            endO = this.docSelRange.endOffset,
            rangeForDel = rangy.createRange(),
            containerWithNeighborOfCurrentCaret;
        
        // "Default:" set start and end according to the user's selection
        rangeForDel.setStart(startC, startO); 
        rangeForDel.setEnd(endC, endO);
        
        // If everything that is selected is within a DEL-Node already,
        // nothing else needs to be marked as deleted:
        if(!rangeForDel.collapsed && this.rangeIsWithinDELNode(rangeForDel)) {
            return null; 
        }
        
        // If nothing is selected, the caret has just been placed somewhere and the deletion refers to the next single node/character only:
        if (rangeForDel.collapsed) {
            containerWithNeighborOfCurrentCaret = this.getContainerWithNeighborOfCurrentCaret();
            rangeForDel = this.selectCharacterInRangeByOne(rangeForDel,containerWithNeighborOfCurrentCaret);
        }
        if (rangeForDel == null || this.rangeIsWithinDELNode(rangeForDel)) {
            return null;
        }
        
        return rangeForDel;
    },
    /**
     * Handle all INS-content within the selected range for deletion.
     * - If the content has been inserted by the same user in the same workflow, it can really be deleted.
     * - If not, the content just has to be marked as deleted.
     * If the complete selection is handled already, we return "completeSelectionIsHandled"
     * otherwise the last node that has been created to mark a deletion is returned.
     * @returns {(?Object|String)} delNode
     */
    handleInsNodesInDeletion: function() {
        var me = this,
            delNode = null,
            tmpMarkupNode = this.createNodeForMarkup(this.getNodeNameAccordingToEvent()),
            rangeForDel = this.getRangeToBeDeleted(),
            rangeWithCharsForAction = rangy.createRange(),
            DOMWithCharsForAction,
            insNodesTotal,
            insNode,
            rangeForInsNode = rangy.createRange();
        if (rangeForDel == null) {
            return false;
        }
        // collect INS-nodes within what is to be deleted
        insNodesTotal = rangeForDel.getNodes([1], function(node) {
            return ( node.nodeName == me.NODE_NAME_INS );
        });
        // if the selection is completely within one and the same INS-node:
        if (rangeForDel.commonAncestorContainer.parentNode.nodeName == this.NODE_NAME_INS) {
            insNodesTotal.push(rangeForDel.commonAncestorContainer.parentNode);
        };
        for (var i = 0; i < insNodesTotal.length; i++) {
            insNode = insNodesTotal[i];
            rangeForInsNode.selectNodeContents(insNode);
            rangeWithCharsForAction = rangeForDel.intersection(rangeForInsNode);
            DOMWithCharsForAction = rangeWithCharsForAction.cloneContents();
            if ( (DOMWithCharsForAction.textContent != '')
                && (rangeWithCharsForAction.text() == rangeForDel.text()) ) {
                    completeSelectionIsHandled = true;
                }
            // INS-node belongs to the same user and workflow: really remove character(s)
            if (this.isNodesOfSameConditions(insNode,tmpMarkupNode)) {
                if (rangeForDel.containsNodeText(insNode)) {
                    // INS-Node is selected completely: remove the node
                    insNode.parentNode.removeChild(insNode);
                } else {
                    // INS-Node is selected partially: remove the selected chars only
                    // (= we only need what is WITHIN the INS-node AND is selected).
                    if (DOMWithCharsForAction.textContent != ''){
                        if (rangeWithCharsForAction.text() == rangeForDel.text()) {  // rangeWithCharsForAction.containsRange(rangeForDel) does not work eg when the caret has been placed right BEFORE an ins-Node (with DEL-Key)
                            completeSelectionIsHandled = true;
                        }
                        rangeWithCharsForAction.deleteContents();
                    }
                }
            } else {
            // INS-node does NOT belong to the same user and workflow: only mark character(s) as deleted, but don't remove them.
                var contentToMoveFromInsToDel = rangeWithCharsForAction.toHtml(),
                    delNode = this.createNodeForMarkup(this.NODE_NAME_DEL);
                delNode.appendChild(document.createTextNode(contentToMoveFromInsToDel));
                this.docSelRange.insertNode(delNode);
                insNode.parentNode.removeChild(insNode);
            }
            // If the selection is completely removed now, then there is nothing left to do.
            if (completeSelectionIsHandled) {
                return 'completeSelectionIsHandled';
            }
        }
        this.refreshSelectionAndRange(); // We might have changed the DOM quite a bit...
        return delNode;
    },
    /**
     * Handle all images within the selected range for deletion.
     * - If the image had been inserted by the same user in the same workflow, it can really be deleted.
     * - If not, the image just has to be marked as deleted.
     * The last node that gets marked as deleted is returned.
     * @returns {?Object} delNode
     */
    handleImagesInDeletion: function() {
        var me = this,
            completeSelectionIsHandled = false,
            delNode = null,
            tmpMarkupNode = this.createNodeForMarkup(this.getNodeNameAccordingToEvent()),
            rangeForDel = this.getRangeToBeDeleted(),
            imgNodesTotal,
            setOfImgNodesTotal,
            mqmPartnerImgNodes = [],
            imgNode,
            rangeForImgNode = rangy.createRange();
        if (rangeForDel == null) {
            return false;
        }
        // collect INS-nodes within what is to be deleted
        imgNodesTotal = rangeForDel.getNodes([1], function(node) {
            return (node.nodeName == 'IMG' && node.parentNode.nodeName != 'DEL');
        });
        // if the selection is completely that IMG-node:
        if (rangeForDel.commonAncestorContainer.nodeName == 'IMG'
            &&  rangeForDel.commonAncestorContainer.isEqualNode(rangeForDel.startContainer)
            &&  rangeForDel.commonAncestorContainer.isEqualNode(rangeForDel.endContainer) ) {
            imgNodesTotal.push(rangeForDel.commonAncestorContainer);
            completeSelectionIsHandled = true;
        };
        // check for get MQM-partner-tags and add them to the images for changeMarkup
        setOfImgNodesTotal = new Set(imgNodesTotal);
        for (var i = 0; i < imgNodesTotal.length; i++) {
            var mqmImgPartnerNode = this.getMQMPartnerTag(imgNodesTotal[i]);
            if (mqmImgPartnerNode != null) {
                //add imgNode only if it's not included already
                if (!setOfImgNodesTotal.has(mqmImgPartnerNode)) {
                    mqmPartnerImgNodes.unshift(mqmImgPartnerNode); // (unshift: handle partner-tags first, otherwise the caret will move)
                    setOfImgNodesTotal.add(mqmImgPartnerNode);
                }
            }
        }
        imgNodesTotal = imgNodesTotal.concat(mqmPartnerImgNodes);
        // changeMarkup for images
        for (var i = 0; i < imgNodesTotal.length; i++) {
            imgNode = imgNodesTotal[i];
            rangeForImgNode.selectNode(imgNode);
            if(this.rangeMatchesNode(rangeForDel,imgNode)) {
                completeSelectionIsHandled = true;
            }
            delNode = this.createNodeForMarkup(this.NODE_NAME_DEL);
            rangeForDel.collapseBefore(imgNode);
            delNode.appendChild(imgNode);
            rangeForDel.insertNode(delNode);
            setOfImgNodesTotal.delete(imgNode);
            // If the selection is completely marked now (including their partner-tags), 
            // then there is nothing left to do.
            if (completeSelectionIsHandled && setOfImgNodesTotal.size == 0) {
                return 'completeSelectionIsHandled';
            }
        }
        // We might have changed the DOM quite a bit...
        this.refreshSelectionAndRange();
        return delNode;
    },
    /**
     * Mark all unmarked contents within the selected range for deletion.
     * The last node that gets marked as deleted is returned;
     * if nothing gets marked for deletion, we return false.
     * @returns {(?Object|Boolean)} delNode
     */
    addDel: function() {
        var me = this,
            delNode = this.createNodeForMarkup(this.NODE_NAME_DEL),
            tmpMarkupNode = this.createNodeForMarkup(this.getNodeNameAccordingToEvent()),
            rangeForDel = this.getRangeToBeDeleted(),
            rangeForUnmarkedNode = rangy.createRange(),
            rangeWithCharsForAction = rangy.createRange(),
            unmarkedNodesTotal,
            unmarkedNode;
        
        if (rangeForDel == null) {
            return false;
        }
        
        // Is the complete range itself to be marked as deleted? 
        // (= e.g. if a character is to be deleted within other characters)
        if(this.rangeMatchesNode(rangeForDel,delNode)) {
            if (rangeForDel.canSurroundContents(delNode)) {
                rangeForDel.surroundContents(delNode);
                return delNode;
            }
        }
        
        // Otherwise: Collect element-nodes to mark as deleted...
        unmarkedNodesTotal = rangeForDel.getNodes([1,3], function(node) {
            // ... but only when they are not (/in) a DEL-node already
            return ( !me.isNodeOfSameMarkupTypeAsEvent(node) && !me.isNodeOfSameMarkupTypeAsEvent(node.parentNode) );
        });
        
        if(unmarkedNodesTotal.length == 0) {
            return false;
        }
        
        for (var i = 0; i < unmarkedNodesTotal.length; i++) {
            unmarkedNode = unmarkedNodesTotal[i];
            rangeForUnmarkedNode.selectNodeContents(unmarkedNode);
            rangeWithCharsForAction = rangeForDel.intersection(rangeForUnmarkedNode);
            delNode = this.createNodeForMarkup(this.NODE_NAME_DEL);
            if (rangeWithCharsForAction.canSurroundContents()) {
                rangeWithCharsForAction.surroundContents(delNode);
            }
        }
        // We might have changed the DOM quite a bit...
        this.refreshSelectionAndRange();
        return delNode;
    },

    // =========================================================================
    // Insertions
    // =========================================================================
    
    /**
     * Insert an INS-Node.
     * @param {?String} contentForInsNode
     */
    addIns: function(contentForInsNode) {
        var insNode = this.createNodeForMarkup(this.NODE_NAME_INS),
            insInnerNodeContent = contentForInsNode,
            insInnerNode,
            isPlaceholder = false,
            selPositionInfo = this.getSelectionPositionInfo(this.editor.getEditorBody());
        // Do we insert content or just an empty markup-Node?
        if (insInnerNodeContent == null || insInnerNodeContent == '') {
            isPlaceholder = true;
            insInnerNodeContent = this.CHAR_PLACEHOLDER;              // eg Google Chrome gets lost without placeholder
        }
        if(selPositionInfo.atStart || selPositionInfo.atEnd) {
            this.consoleLog("(is atStart / atEnd)");
            insInnerNodeContent = 'x';                                // Workaround: empty placeholders are not recognized at the beginning or the end in the ExtJs-Editor.
        }
        // insert the INS-Node
        insInnerNode = document.createTextNode(insInnerNodeContent);
        insNode.appendChild(insInnerNode);
        this.docSelRange.insertNode(insNode);        
        // position the caret
        this.positionCaretAfterInsertion(insNode,isPlaceholder);
        // "Reset" to initial content except for Workaround
        insNode.nodeValue = insInnerNodeContent;                      // Google Chrome and Workaround; see above...
    },

    // =========================================================================
    // Positioning the caret
    // =========================================================================
    
    /**
     * Position the caret at initial bookmark.
     */
    positionCaretAtBookmark: function() {
        // TODO: moveToBookmark does not work when inserting after deletions!
        //     Here is an example: 
        //          abef 
        //          => select "ab" and insert "c": 
        //          <del>ab</del><ins>c</ins>ef 
        //          => select "abc" and insert "d" results in:
        //          <del>ab</del>e<ins>d</ins>f  (= wrong; correct would be: <del>ab</del><ins>d</ins>ef)
        var rangeForCaret = rangy.createRange();
        rangeForCaret.moveToBookmark(this.initialRangeBookmark);
        rangeForCaret.collapse(false);
        this.docSel.setSingleRange(rangeForCaret);
    },
    /**
     * Position the caret depending on the DEL-node and the Keyboard-Event.
     * @param {?Object} delNode
     */
    positionCaretAfterDeletion: function(delNode) {
        if (delNode == null || !delNode.nodeType || delNode.parentNode == null) {
            return;
        }
        if(this.eventKeyCode == Ext.event.Event.BACKSPACE){
            this.docSelRange.collapseBefore(delNode);
        } else {
            this.docSelRange.collapseAfter(delNode);
        }
        this.docSel.setSingleRange(this.docSelRange);
    },
    /**
     * Position the caret depending on the INS-node.
     * @param {Object} insNode
     * @param {Boolean} isPlaceholder
     */
    positionCaretAfterInsertion: function(insNode,isPlaceholder) {
        var rangeForCaret = rangy.createRange();
        rangeForCaret.selectNodeContents(insNode);
        if (isPlaceholder == false) {
            // if the INS-Node is not used as placeholder, the caret
            // must be positioned BEHIND the already inserted content:
            rangeForCaret.collapse(false);
        }
        this.docSel.setSingleRange(rangeForCaret);
    },
    /**
     * Position the caret at the beginning of the given node (within!!!).
     * @param {?Object} node
     */
    positionCaretInNode:  function(node) {
        var focusNode = document.createElement('div'), // Workaround: temporarily add a "focus-node" at the beginning within the node for using setEndAfter on it (otherwise we will not end up WITHIN the node!)
            rangeForCaret = rangy.createRange();
        focusNode.style.position = "absolute"; // display the temp div out of sight, otherwise the displayed content flickers
        focusNode.style.left = "-1000px";
        node.insertBefore(focusNode,node.childNodes[0]);
        rangeForCaret.setEndAfter(focusNode); // setStartBefore(nextNode) jumps IN FRONT OF the boundary of the node; setStart() using offset is inconvenient, because we don't know what the offeset refers to (does the node start with a textnode or not?)
        rangeForCaret.collapse(false);
        this.docSel.setSingleRange(rangeForCaret);
        // Cleanup
        setTimeout(function() { // removing the focusNode before the insert is done looses the correct position of the caret
            node.removeChild(focusNode);
        }, 1);
    },

    // =========================================================================
    // Check if the current selection is in or at nodes and handle their content
    // =========================================================================
    
    // ----------------- Current Selection & Nodes: Specific (DEL) --------------------------
    
    /**
     * Is eyerything of the given range within a DEL-node?
     * @param {Object} range
     * @returns {Boolean}
     */
    rangeIsWithinDELNode: function(range) {
        return this.rangeIsWithinMarkupNodeOfCertainKind(range,this.NODE_NAME_DEL);
    },
    /**
     * Returns a container (node) with the previous/next "one-space"-content from the current position of the caret
     * (= as if DELETE or BACKSPACE would have been used after positioning the caret somewhere).
     * DELETE:
     * - ab|cd              => 'c'
     * - abc| d             => ' '
     * - abc|<IMG>d         => <IMG>
     * - ab|<ins>cd</ins>   => 'c'
     * - ab|c<del>d</del>   => 'c'
     * - ab<del>|c</del>d   => 'c'
     * BACKSPACE:
     * - abc|d              => 'c'
     * - abc |d             => ' '
     * - abc<IMG>|d         => <IMG>
     * - <ins>abc</ins>|d   => 'c'
     * - abc<del>|d</del>   => 'c'
     * - ab<del>c|</del>d   => 'c'
     * 
     * @returns {Object}
     */
    getContainerWithNeighborOfCurrentCaret: function() {
        var container = this.getContainerNodeForCurrentSelection(),
            containerParent = container.parentNode,
            positionInContainer = this.getSelectionPositionInfo(container),
            positionInParent = this.getSelectionPositionInfo(containerParent),
            positionInEditor = this.getSelectionPositionInfo(this.editor.getEditorBody()),
            containerSibling,
            containerSiblingChild,
            containerParentSibling,
            containerParentSiblingChild,
            isAtFinalPositionInContainer,
            isAtFinalPositionInParent,
            containerNextToCaret;
        // If we are at the very beginning/end of the Editor, we cannot go any further: 
        if( (positionInEditor.atStart && this.eventKeyCode == Ext.event.Event.BACKSPACE)
                || (positionInEditor.atEnd && this.eventKeyCode == Ext.event.Event.DELETE) ){
            return null;
        }
        // Get position and sibling according to the event:
        switch(this.eventKeyCode) {
            case Ext.event.Event.BACKSPACE:                         // BACKSPACE: "deletes" the previous character/image
                containerSibling = container.previousSibling;
                containerSiblingChild = (containerSibling != null) ? containerSibling.lastChild : null;
                containerParentSibling = (containerParent != null) ? containerParent.previousSibling : null;
                containerParentSiblingChild = (containerParentSibling != null) ? containerParentSibling.lastChild : null;
                isAtFinalPositionInContainer = positionInContainer.atStart;
                isAtFinalPositionInParent = positionInParent.atStart;
                break;
            case Ext.event.Event.DELETE:                            // DELETE: "deletes" the next character/image
                containerSibling = container.nextSibling;
                containerSiblingChild = (containerSibling != null) ? containerSibling.firstChild : null;
                containerParentSibling = (containerParent != null) ? containerParent.nextSibling : null;
                containerParentSiblingChild = (containerParentSibling != null) ? containerParentSibling.firstChild : null;
                isAtFinalPositionInContainer = positionInContainer.atEnd;
                isAtFinalPositionInParent = positionInParent.atEnd;
                break;
        }
        // ----- (1) We are NOT at the very beginning/end in our node: ---------------------------------------------------------------
        // -----     (= moving to the left or to the right stays within the container) -----------------------------------------------
        if (!isAtFinalPositionInContainer) {
            // (1.1) 
            if (container.nodeType == 3) {
                // This will happen only for text-nodes (= we can move within our own container then) ...
                return container;
            } else if (this.isNodeOfTypeMarkup(container)) {
                // ... or text-nodes within markupNodes (= we can use the container's sibling then) ...
                return containerSibling;
            } else {
                // ... and that's it, because IMG-Nodes are always recognized within their own range (with both atStart and atEnd are true).
                // Hence, if we get here, I got something wrong.
                debugger;
            }
        }
        // -----  (2) We ARE at the very beginning/end in our node: -------------------------------------------------------------------
        // -----     (= moving to the left or to the right will refer to the previous/next container) ---------------------------------
        
        if (isAtFinalPositionInContainer) {
            // (2.1) We are WITHIN a MarkupNode of any kind (eg. some text within a DEL-Node).
            //      Only if we are at the very beginning/end of the MarkupNode; otherwise we can move within our own container, see (2.3)
            if (this.isNodeOfTypeMarkup(containerParent) && isAtFinalPositionInParent) {
                if (this.isNodeOfTypeMarkup(containerParentSibling)) {
                    return containerParentSiblingChild;         // The parent's sibling is a MarkupNode, so we need to get its first/last child.
                } else {
                    return containerParentSibling;
                }
            }
            // (2.2a) Special case for images, is a bit tricky.
            containerNextToCaret = null;
            if (container.nodeType == 1 && container.nodeName == 'IMG') { 
                containerNextToCaret = this.getContainerNextToCaretForImageNodesInMarkupNodes(container,containerSibling);
            }
            // (2.2b) Special case for images in markup-Nodes, is a bit tricky.
            if (this.isNodeOfTypeMarkup(container) && container.firstChild.nodeType == 1 && container.firstChild.nodeName == 'IMG') { 
                containerNextToCaret = this.getContainerNextToCaretForImageNodesInMarkupNodes(container.firstChild,containerSibling);
            }
            if (containerNextToCaret != null) {
                if (containerNextToCaret.isEqualNode(containerSibling) && this.isNodeOfTypeMarkup(containerSibling)) {
                    return containerSiblingChild;               // The container's sibling is a MarkupNode, so we need to get its first/last child.
                } else {
                    return containerNextToCaret;
                }
            }
            // (2.3) "Default": when we are at the border of our container, we check for our sibling on the same level.
            if (this.isNodeOfTypeMarkup(containerSibling)) {
                return containerSiblingChild;                   // The container's sibling is a MarkupNode, so we need to get its first/last child.
            } else {
                return containerSibling;
            }
        }
        debugger; // If we get here, I missed something.
    },
    /**
     * Returns the content "on the left" or "on the right" (according to the 
     * current event) of the given range.
     * If the given container is an image, we select that one;
     * otherweise we select text by moving the range's boundary.
     * @param {Object} range
     * @param {Object} containerNextToCaret
     * @returns {?Object} range
     */
    selectCharacterInRangeByOne: function(range,containerNextToCaret) {
        var moveOptions  = { includeBlockContentTrailingSpace: true };
        if(!range.collapsed) {
            return range;
        }
        if (containerNextToCaret == null) {
            return null;
        }
        // containerNextToCaret is an IMG => take that one.
        if (containerNextToCaret != null && containerNextToCaret.nodeType == 1 && containerNextToCaret.nodeName == 'IMG') {
            range.selectNodeContents(containerNextToCaret);
        } else {
            // Content in containerNextToCaret will be Text or Markup => moveCharacter.
            switch(this.eventKeyCode) {
                case Ext.event.Event.BACKSPACE:
                    range.moveStart("character", -1, moveOptions);
                    break;
                case Ext.event.Event.DELETE:
                    range.moveEnd("character", 1, moveOptions);
                    break;
            }
        }
        return range;
    },
    /**
     * Returns the content "on the left" or "on the right" for the current selection
     * if the caret is positioned next to an image that is within a markupnode.
     * @param {Object} imgNode
     * @param {Object} containerSibling
     * @returns {Object}
     */
    getContainerNextToCaretForImageNodesInMarkupNodes: function(imgNode,containerSibling) {
        // CAUTION: Images surrounded be Markup-Nodes are seen as "positioned" at the start AND at the end of the node. 
        // The position of the caret  does not matter in these cases! What we can make use of is a difference in the range we get:
        // - If the caret is on the right from the image: the range lands "within" the DEL-Node of the image.
        // - If the caret is in the left from the image: the range stays left from the DEL-Node of the image.
        // TODO: What happens if we are INBETWEEN two image-nodes? (check with markup also!)
        if (this.rangeIsWithinMarkupNodeOfAnyKind(this.docSelRange) && this.eventKeyCode == Ext.event.Event.BACKSPACE) {
            // The caret is behind the image.
            // We are in a BACKSPACE-Event, so get the image:
            return imgNode;
        } else {
            if (this.eventKeyCode == Ext.event.Event.DELETE) {
                if(this.rangeMatchesNode(this.docSelRange,imgNode)) {
                    // The caret is in front of the image.
                    // We are in a DELETE-Event, so get the image:
                    return imgNode;
                } else {
                    // The caret is in behind the image.
                    // We are in a DELETE-Event, so we'll get the next content.
                    return containerSibling;
                }
            } else {
                // The caret is before the image.
                // We are in a BACKSPACE-Event, so we'll get the previous content.
                return containerSibling;
            }
        }
    },
    
    // ----------------- Current Selection & Nodes: Specific (INS) --------------------------
    
    /**
     * Is eyerything of the current selection within an INS-node 
     * that has the same userand workflow as the event?
     * @returns {Boolean}
     */
    selectionIsWithinINSNodeAccordingToEvent: function() {
        var containerNode = this.getMarkupNodeForCurrentSelection(this.NODE_NAME_INS);
        return (containerNode != null && this.isNodeAccordingToEvent(containerNode) );
    },
    /**
     * Get the INS-Node EXACTLY BEFORE the current position of the caret 
     * that has the same user and workflow as the event (if there is one).
     * @returns {?Object}
     */
    getINSNodeForContentBeforeCaretAccordingToEvent: function() {
        var nodeBeforeCaret = this.getMarkupNodeForContentBeforeCaret(this.NODE_NAME_INS);
        if (nodeBeforeCaret != null
                && this.isNodeAccordingToEvent(nodeBeforeCaret) ) {
            return nodeBeforeCaret;
        } else {
            return null;
        }
    },
    /**
     * Get the INS-Node EXACTLY BEHIND the current position of the caret 
     * that has the same user and workflow as the event (if there is one).
     * @returns {?Object}
     */
    getINSNodeForContentBehindCaretAccordingToEvent: function() {
        var nodeBehindCaret = this.getMarkupNodeForContentBehindCaret(this.NODE_NAME_INS);
        if (nodeBehindCaret != null
                && this.isNodeAccordingToEvent(nodeBehindCaret) ) {
            return nodeBehindCaret;
        } else {
            return null;
        }
    },
    /**
     * Get the "surrounding" node of the current selection 
     * that does NOT match the conditions (if there is one).
     * @returns {?Object}
     */
    getForeignMarkupNodeForInsertion: function() {
        var tmpMarkupNodeINS = this.createNodeForMarkup(this.NODE_NAME_INS),
            containerNodeINS = this.getMarkupNodeForCurrentSelection(this.NODE_NAME_INS),
            containerNodeDEL = this.getMarkupNodeForCurrentSelection(this.NODE_NAME_DEL);
        // We are within a DEL-Node of any kind:
        if (containerNodeDEL != null) {
            return containerNodeDEL;
        }
        // We are within an INS-Node that does not match the current user and workflow:
        if (containerNodeINS != null && this.isNodesOfForeignMarkup(containerNodeINS,tmpMarkupNodeINS) ) {
            return containerNodeINS;
        }
        // No foreign node found:
        return null;
    },
    
    // ----------------- Current Selection & Nodes: General(Markup-Nodes) -------------------
    
    /**
     * Checks if the complete given range is within one-and-the-same markup-Node of any kind.
     * @param {Object} range
     * @returns {Boolean}
     */
    rangeIsWithinMarkupNodeOfAnyKind: function(range) {
        var container = range.commonAncestorContainer,
            containerParent = container.parentNode;
        // If we are in the midst of a given node, we return that node:
        if (container.nodeType == 1 && this.isNodeOfTypeMarkup(container)) {
            return true;
        }
        // If we are in the midst of a textNode within a given node, we return its surrounding node:
        if (container.nodeType == 3 && this.isNodeOfTypeMarkup(containerParent)) {
            return true;
        }
        // There is no markup-node surrounding the current selection:
        return false;
    },
    /**
     * Checks is the complete given range is within one-and-the-same markup-Node as given in the nodeName.
     * @param {Object} range
     * @param {String} nodeName
     * @returns {Boolean}
     */
    rangeIsWithinMarkupNodeOfCertainKind: function(range,nodeName) {
        var container = range.commonAncestorContainer,
            containerParent = container.parentNode;
        // If we are in the midst of a given node, we return that node:
        if (container.nodeType == 1 && container.nodeName == nodeName) {
            return true;
        }
        // If we are in the midst of a textNode within a given node, we return its surrounding node:
        if (container.nodeType == 3 && containerParent.nodeName == nodeName) {
            return true;
        }
        // There is no markup-node surrounding the current selection:
        return false;
    },
    /**
     * Get the "surrounding" markup-node of the given type (= nodeName) of the current selection (if there is one).
     * @param {String} nodeName
     * @returns {?Object}
     */
    getMarkupNodeForCurrentSelection: function(nodeName){
        var container = this.getContainerNodeForCurrentSelection(),
            containerParent = container.parentNode;
        // If we are in the midst of a given node, we return that node:
        if (container.nodeType == 1 && container.nodeName == nodeName) {
            return container;
        }
        // If we are in the midst of a textNode within a given node, we return its surrounding node:
        if (container.nodeType == 3 && containerParent.nodeName == nodeName) {
            return containerParent;
        }
        // There is no markup-node surrounding the current selection:
        return null;
    },
    /**
     * Get the Node of the given type (= nodeName) EXACTLY BEFORE the current position of the caret (if there is one).
     * @param {String} nodeName
     * @returns {?Object}
     */
    getMarkupNodeForContentBeforeCaret: function(nodeName) {
        var nodeBeforeCaret,
            container = this.getContainerNodeForCurrentSelection(),
            startContainer = this.docSelRange.startContainer,
            startOffset = this.docSelRange.startOffset,
            endContainer = this.docSelRange.endContainer,
            endOffset = this.docSelRange.endOffset,
            editorBody = this.editor.getEditorBody();
        // If we are right behind an IMAGE within a markup-Node, that node of that image is what we look for.
        // Since these image-nodes always return true for atStart and atEnd, we cannot check for the position and need a workaround:
        if (this.rangeIsWithinDELNode(this.docSelRange)
                && this.isNodeOfTypeMarkup(startContainer)
                && startContainer.firstChild.isEqualNode(endContainer.lastChild)
                && startContainer.firstChild.nodeName == 'IMG') {
            nodeBeforeCaret = startContainer;
        }
        // If we are NOT at the beginning of a node (but e.g. within a DEL-node), then the previous character is within the node we are already in:
        else if (!this.isAtStartOfCurrentSelection()) {
            nodeBeforeCaret = this.getMarkupNodeForCurrentSelection(nodeName);
        }
        // If we ARE right at the beginning of any kind of node (AND not right behind to an image node), 
        // then the previous character belongs to the the previous sibling:
        else {
            nodeBeforeCaret = this.getSiblingNodeForCurrentSelection('previous');
        }
        if (nodeBeforeCaret != null && nodeBeforeCaret.nodeName == nodeName) {
            return nodeBeforeCaret;
        } else {
            return null;
        }
    },
    /**
     * Get the Node of the given type (= nodeName) EXACTLY BEHIND the current position of the caret (if there is one).
     * @param {String} nodeName
     * @returns {?Object}
     */
    getMarkupNodeForContentBehindCaret: function(nodeName) {
        var nodeBehindCaret;
        if (!this.isAtEndOfCurrentSelection()) {                                    // (1) If we are NOT at the end, but e.g. within a DEL-node...  
            nodeBehindCaret = this.getMarkupNodeForCurrentSelection(nodeName);      // ... we must check for that node we are in.
        } else {                                                                    // (2) If we are right at the end of any kind of node,
            nodeBehindCaret = this.getSiblingNodeForCurrentSelection('next');       // ... we must check for the next sibling.
        }
        // TODO: Must also check for already marked images!
        if (nodeBehindCaret != null && nodeBehindCaret.nodeName == nodeName) {
            return nodeBehindCaret;
        } else {
            return null;
        }
    },
    
    // ----------------- Current Selection, Ranges & Nodes: General(Any nodes) ----------------------
    
    /**
     * Get the "surrounding" node of the current selection.
     * @returns {Object}
     */
    getContainerNodeForCurrentSelection: function(){
        var commonAncestorContainer = this.docSelRange.commonAncestorContainer,
            startContainer = this.docSelRange.startContainer,
            startOffset = this.docSelRange.startOffset,
            endContainer = this.docSelRange.endContainer,
            endOffset = this.docSelRange.endOffset;
        // (With Images for example:) If the range boundaries are "outside" of the selection
        // although the caret is positioned within one-and-the-same node, we use that node:
        if (startContainer.childNodes[startOffset] != null
                && endContainer.childNodes[endOffset] != null
                && startContainer.childNodes[startOffset].isEqualNode(endContainer.childNodes[endOffset])) {
            return commonAncestorContainer.childNodes[startOffset];
        } else {
            return commonAncestorContainer;
        }
    },
    /**
     * Get the previous or next node of the current selection.
     * If the selection is in a textNode without a sibling, the sibling of the selection's parentNode is returned.
     * If there is no sibling, we return null.
     * @param {String} direction (previous|next)
     * @returns {?Object}
     */
    getSiblingNodeForCurrentSelection: function(direction){
        var docSel = this.docSelRange,
            lookForPrevious = (direction == "previous") ? true : false,
            currentNode = (lookForPrevious ? docSel.startContainer : docSel.endContainer),
            currentParentNode = currentNode.parentNode,
            siblingNode = null;
        siblingNode = (lookForPrevious ? currentNode.previousSibling : currentNode.nextSibling);
        if ( (siblingNode == null) && (currentNode.nodeType == 3) ) {
            siblingNode = (lookForPrevious ? currentParentNode.previousSibling : currentParentNode.nextSibling);
        }
        return siblingNode;
    },
    /**
     * Does the selection start right at the beginning of its container?
     * If an img is encompassed by a Markup-Node, we cannot check whether we are before or behind the image.
     * @returns {Boolean}
     */
    isAtStartOfCurrentSelection: function() {
        var selRange = this.docSelRange,
            startContainer = selRange.startContainer,
            containingNode,
            selectionPosition;
        if(this.isNodeOfTypeMarkup(startContainer)) {
            containingNode = startContainer.firstChild;
        } else {
            containingNode = startContainer;
        }
        selectionPosition = this.getSelectionPositionInfo(containingNode);
        return selectionPosition.atStart;
    },
    /**
     * Does the selection start right at the beginning of its container?
     * If an img is encompassed by a Markup-Node, we cannot check whether we are before or behind the image.
     * @returns {Boolean}
     */
    isAtEndOfCurrentSelection: function() {
        var selRange = this.docSelRange,
            endContainer = selRange.endContainer,
            containingNode,
            selectionPosition;
        if(this.isNodeOfTypeMarkup(endContainer)) {
            containingNode = endContainer.lastChild;
        } else {
            containingNode = endContainer;
        }
        selectionPosition = this.getSelectionPositionInfo(containingNode);
        return selectionPosition.atEnd;
    },
    /**
     * Determine if the current selection is at the start or end of the given part of content.
     * (https://stackoverflow.com/a/7478420)
     * @param {Object} containingNode
     * @returns {Object}
     */
    getSelectionPositionInfo: function(containingNode) {
        var atStart = false,
            atEnd = false,
            el = containingNode,
            selRange = this.docSelRange,
            testRange = rangy.createRange();
        
        testRange.selectNodeContents(el);
        testRange.setEnd(selRange.startContainer, selRange.startOffset);
        atStart = (testRange.toString() == "");
        
        testRange.selectNodeContents(el);
        testRange.setStart(selRange.endContainer, selRange.endOffset);
        atEnd = (testRange.toString() == "");
        
        return { atStart: atStart, atEnd: atEnd };
    },
    /**
     * Is the (complete) node (somewhere) within given range?
     * @param {Object} range
     * @param {Object} node
     * @returns {Boolean}
     */
    rangeContainsNode: function(range,node) {
        var rangeForNode = rangy.createRange();
        rangeForNode.selectNodeContents(node);
        return range.containsRange(rangeForNode);
    },
    /**
     * Does the node completely and exactly encompass the given range?
     * @param {Object} range
     * @param {Object} node
     * @returns {Boolean}
     */
    rangeMatchesNode: function(range,node){
        var commonAncestorContainer = range.commonAncestorContainer,
            startContainer = range.startContainer,
            startOffset = range.startOffset,
            endContainer = range.endContainer,
            endOffset = range.endOffset,
            rangeForNode = rangy.createRange();
        // If we compare text:
        if(node.nodeType == 3) {
            rangeForNode.selectNodeContents(node);
            var selectionMatchesNode = rangeForNode.text() == this.docSelRange.text();
            return selectionMatchesNode;
        }
        // If the selection encompasses only one node and that one completely:
        if ( startContainer.childNodes[startOffset] != null && endContainer.childNodes[endOffset] != null
             && startContainer.childNodes[startOffset].isEqualNode(endContainer.childNodes[endOffset]) ) {
            var selectionMatchesNode = commonAncestorContainer.childNodes[startOffset].isEqualNode(node);
            return selectionMatchesNode;
        }
        return false;
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
     * Returns an array with all markup-nodes (of any kind: DEL, INS, ...) in the Editor.
     * @returns {Object} 
     */
    getAllMarkupNodesInEditor: function(){
        var allMarkupNodes = [],
            nodeNamesForEvents = this.getMarkupNodeNamesForEvents();
        for (var key in nodeNamesForEvents) {
            // get all nodes of this markup-type:
            var nodeName = nodeNamesForEvents[key],
                nodesForEvent = this.editor.getDoc().getElementsByTagName(nodeName);
            // run the checks for all nodes of this markup-type:
            for (var k = 0; k < nodesForEvent.length; k++){
                allMarkupNodes.push(nodesForEvent[k]);
            }
        }
        return allMarkupNodes;
    },
    /**
     * Create and return a new node for Markup.
     * @param {String} nodeName
     * @returns {Object} 
     */
    createNodeForMarkup: function(nodeName){
        var nodeEl = document.createElement(nodeName),
            allUsers = this.getAllUsers(),
            thisUser = this.editorUsername,
            thisUserCSS,
            segmentWorkflowStepNr = this.segmentWorkflowStepNr,
            timestamp = Ext.Date.format(new Date(), 'time'); // dates are wrong with 'timestamp' although doc states differently: http://docs.sencha.com/extjs/6.2.0/classic/Ext.Date.html 
        
        // set CSS-class for specific colors for each user (in their chronological order)
        if (allUsers[thisUser] != undefined && allUsers[thisUser] != null) {
            thisUserCSS = allUsers[thisUser];
        } else {
            thisUserCSS = 'user' + (Object.keys(allUsers).length + 1).toString();
        }
        
        // (setAttribute: see https://jsperf.com/html5-dataset-vs-native-setattribute)
        nodeEl.setAttribute(this.ATTRIBUTE_USERNAME,thisUser); 
        nodeEl.setAttribute(this.ATTRIBUTE_USER_CSS,thisUserCSS);
        nodeEl.setAttribute(this.ATTRIBUTE_WORKFLOWSTEPNR,segmentWorkflowStepNr);
        nodeEl.setAttribute(this.ATTRIBUTE_TIMESTAMP,timestamp);
        
        // - 'changemarkup': specific selector for CSS
        // - 'ownttip': general selector for delegate in tooltip
        nodeEl.className = 'changemarkup ownttip';
        
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
        // If not both of them are markup-nodes, we don't have to run any check at all.
        if (!this.isNodeOfTypeMarkup(nodeA) || !this.isNodeOfTypeMarkup(nodeB)) {
            return false; // false because: NO, the node isn't a markup-node at all (hence, it's not a foreign markup neither).
        }
        return !this.isNodesOfSameNameAndConditionsAndEvent(nodeA,nodeB);
    },
    /**
     * Checks if a node is of the same markup-type as the event.
     * (Does NOT check any further conditions!)
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeOfSameMarkupTypeAsEvent: function(node) {
        var nodeName = (node == null) ? 'null' : node.nodeName,
            nodeNameOfEvent = this.getNodeNameAccordingToEvent();
        if (!this.isNodeOfTypeMarkup(node)) {
            return false;
        }
        return nodeName == nodeNameOfEvent;
    },
    /**
     * Checks if a markup-node is "empty".
     * @param {Object} node
     * @returns {Boolean}
     */
    isEmptyMarkupNode: function(node) {
        if (node.textContent == '') {
            if (node.childNodes.length == 0) {
                return true;
            }
            if (node.childNodes.length == 1 && node.childNodes[0].nodeType == 3 && node.childNodes[0].textContent == '') {
                return true;
            }
        }
        return false;
    },
    /**
     * Checks if the two given nodes touch each other in the given direction.
     * @param {Object} node
     * @param {Object} siblingNode
     * @param {String} direction (previous|next)
     * @returns {Boolean}
     */
    isNodesTouching: function(node,siblingNode,direction) {
        var hasSiblingInGivenDirection = false,
            isTouchingSibling = false,
            rangeForNode                = rangy.createRange(),
            rangeForNodeContents        = rangy.createRange(),
            rangeForSiblingNode         = rangy.createRange(),
            rangeForSiblingNodeContents = rangy.createRange();
        
        if (node == null || siblingNode == null) {
            return false;   // is not even two nodes, thus it's also not two nodes touching.
        }
        
        if (node.parentNode == null || siblingNode.parentNode == null
                || ( node.parentNode != siblingNode.parentNode) ) {
            return false;   // two sibling nodes will both have a parent (and: the SAME parent).
        }
        
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
        var moveOptions  = { includeBlockContentTrailingSpace: true };
        if (direction == 'previous') {
            rangeForNode.moveStart('character',-1, moveOptions);
        } else {
            rangeForNode.moveEnd('character',1, moveOptions);
        }
        isTouchingSibling = rangeForNode.intersectsRange(rangeForSiblingNode); // intersectsOrTouchesRange(): touching does not apply since the nodes would be "just neighbors".
        
        return hasSiblingInGivenDirection && isTouchingSibling;
    },
    
    // --------------------------------------------------------------------------------------------------------
    // Helpers for grouped checks: same nodeName / same conditions (= user, workflow, ...) / according to event
    // --------------------------------------------------------------------------------------------------------
    /**
     * Do the nodes share the same conditions? (user, workflow....)
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameConditions: function(nodeA,nodeB) {
        if (!this.isNodeOfTypeMarkup(nodeA) || !this.isNodeOfTypeMarkup(nodeB)) {
            return false;
        }
        var isNodesOfSameUser       = this.isNodesOfSameUser(nodeA,nodeB),      // user
            isNodesOfSameWorkflow   = this.isNodesOfSameWorkflow(nodeA,nodeB);  // workflow
        return isNodesOfSameUser && isNodesOfSameWorkflow;
    },
    /**
     * Do the nodes share the same conditions? (nodeName, user, workflow....)
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameNameAndConditions: function(nodeA,nodeB) {
        var isNodesOfSameName       = this.isNodesOfSameName(nodeA,nodeB),       // nodeName
            isNodesOfSameConditions = this.isNodesOfSameConditions(nodeA,nodeB); // user, workflow
        return isNodesOfSameName && isNodesOfSameConditions;
    },
    /**
     * Do the nodes share the same conditions (nodeName, user, workflow....) AND match the event?
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameNameAndConditionsAndEvent: function(nodeA,nodeB) {
        // The conditions of the given nodes must BOTH
        // (1) fit to each other
        // AND
        // (2) fit to the current Event
        var isNodesOfSameNameAndConditions = this.isNodesOfSameNameAndConditions(nodeA,nodeB),
            isNodesAccordingToEvent = ( (this.isNodeAccordingToEvent(nodeA)) && (this.isNodeAccordingToEvent(nodeB)) );
        return isNodesOfSameNameAndConditions && isNodesAccordingToEvent;
    },
    /**
     * Do the given node's conditions match the conditions according to the event? (user, workflow....)
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeAccordingToEvent: function(node) {
        var isNodeOfUserAccordingToEvent     = this.isNodeOfUserAccordingToEvent(node),     // user acc. to event
            isNodeOfWorkflowAccordingToEvent = this.isNodeOfWorkflowAccordingToEvent(node); // workflow acc. to event
        return isNodeOfUserAccordingToEvent && isNodeOfWorkflowAccordingToEvent;
    },
    
    // ------------------------------------------------------------------------------------------------------------
    // Helpers for single checks (= comparing to another node or according to event): nodeName, user, workflow, ...
    // ------------------------------------------------------------------------------------------------------------
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
        if (!this.isNodeOfTypeMarkup(node)) {
            return false;
        }
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
        if (!this.isNodeOfTypeMarkup(nodeA) || !this.isNodeOfTypeMarkup(nodeB)) {
            return false;
        }
        var userOfNodeA = nodeA.getAttribute(this.ATTRIBUTE_USERNAME),
            userOfNodeB = nodeB.getAttribute(this.ATTRIBUTE_USERNAME);
        return userOfNodeA == userOfNodeB;
    },
    /**
     * Does the given node's user match the user according to the event?
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeOfUserAccordingToEvent: function(node) {
        if (!this.isNodeOfTypeMarkup(node)) {
            return false;
        }
        var userOfNode = node.getAttribute(this.ATTRIBUTE_USERNAME),
            userOfEvent = this.editorUsername;
        return userOfNode == userOfEvent;
    },
    /**
     * Do the nodes share the same workflow?
     * @param {Object} nodeA
     * @param {Object} nodeB
     * @returns {Boolean}
     */
    isNodesOfSameWorkflow: function(nodeA,nodeB) {
        if (!this.isNodeOfTypeMarkup(nodeA) || !this.isNodeOfTypeMarkup(nodeB)) {
            return false;
        }
        var workflowOfNodeA = parseInt(nodeA.getAttribute(this.ATTRIBUTE_WORKFLOWSTEPNR)),
            workflowOfNodeB = parseInt(nodeB.getAttribute(this.ATTRIBUTE_WORKFLOWSTEPNR));
        return workflowOfNodeA == workflowOfNodeB;
    },
    /**
     * Does the given node's workflow match the workflow according to the event?
     * @param {Object} node
     * @returns {Boolean}
     */
    isNodeOfWorkflowAccordingToEvent: function(node) {
        if (!this.isNodeOfTypeMarkup(node)) {
            return false;
        }
        var workflowOfNode = parseInt(node.getAttribute(this.ATTRIBUTE_WORKFLOWSTEPNR)),
            workflowOfEvent = this.segmentWorkflowStepNr;
        return workflowOfNode == workflowOfEvent;
    },

    // =========================================================================
    // Cleaning up
    // =========================================================================
    
    // E.g. deleting a lot of content might result in neighbors of the same kind or in a DEL-node with other DEL-nodes and INS-nodes WITHIN,
    // but they must all be without duplication and on the same level, not including children.
    // Examples: 
    // <del>ab<ins>c</ins></del> => <del>ab</del><ins>c</ins>
    // <ins>a</ins><ins>b</ins> => <ins>ab</ins>
    
    /**
     * Check all markup-nodes and arrange them on one level.
     */
    cleanUpMarkupInEditor: function() {
        this.consoleLog("cleanUpMarkupInEditor:");
        this.cleanUpChildren();
        this.cleanUpSiblings();
        this.cleanUpEmptyMarkupNodes();
        this.cleanUpEditor();
    },
    /**
     * Clean up child-nodes (since we check this everytime, we won't have any grandchildren).
     */
    cleanUpChildren: function() {
        this.consoleLog("- cleanUpChildren...");
        var allMarkupNodes = this.getAllMarkupNodesInEditor();
        for (var k = 0; k < allMarkupNodes.length; k++){
            var node = allMarkupNodes[k],
                childrenInNode = node.childNodes;
            for (var i = 0; i < childrenInNode.length; i++){
                var childNode = childrenInNode[i];
                if (this.isNodeOfTypeMarkup(childNode)) { // Check for INS- and DEL-nodes only
                    this.consoleLog("childNode? is: " + childNode.nodeName);
                    // (a) When an INS gets deleted by the same user and in the same workflow, 
                    //     it CAN really be deleted (= we remove the node).
                    if (node.nodeName == this.NODE_NAME_DEL
                            && childNode.nodeName == this.NODE_NAME_INS
                            && this.isNodesOfSameConditions(node,childNode) ) {
                        node.removeChild(childNode);
                        node.normalize();
                        return;
                    }
                    // (b) Otherwise: flatten the hierachy of the nodes:
                    // Set range....
                    var rangeForChildnode = rangy.createRange();
                    rangeForChildnode.setStartBefore(childNode);
                    // ... take out the childNode ...
                    node.parentNode.insertBefore(childNode,node);
                    // ... split the node ...
                    var splittedNodes = this.splitNode(node,rangeForChildnode);
                    // ... and insert childNode again:
                    this.moveNodeInbetweenSplittedNodes(childNode,splittedNodes);
                }
            }
        }
    },
    /**
     * Merge corresponding markup-nodes.
     */
    cleanUpSiblings: function() {
        this.consoleLog("- cleanUpSiblings...");
        var allMarkupNodes = this.getAllMarkupNodesInEditor();
        // We check if the node touches a next node of the same conditions.
        // If the two get merged, we check if the merged node touches a next
        // node of the same kind, and so on.
        for (var k = 0; k < allMarkupNodes.length; k++){
            var node = allMarkupNodes[k],
                nextNode = node.nextElementSibling;
            while ( this.isNodesOfSameNameAndConditions(node,nextNode) 
                    && this.isNodesTouching(node,nextNode,'next') ) {
                this.consoleLog("Do Merge...");
                node = this.mergeNodesFromTo(nextNode,node);
                nextNode = node.nextElementSibling;
            }
        }
    },
    /**
     * Delete all empty markup-nodes (= no textContent AND no childNodes).
     */
    cleanUpEmptyMarkupNodes: function() {
        this.consoleLog("- cleanUpEmptyMarkupNodes...");
        var allMarkupNodes = this.getAllMarkupNodesInEditor();
        for (var k = 0; k < allMarkupNodes.length; k++){
            var node = allMarkupNodes[k];
            if (this.isEmptyMarkupNode(node)) {
                node.parentNode.removeChild(node);
            }
        }
    },
    /**
     * Normalize nodes in the editor.
     */
    cleanUpEditor: function() {
        this.consoleLog("- cleanUpEditor...");
        this.editor.getEditorBody().normalize();
    },

    // =========================================================================
    // Helpers for content related to the Editor, User etc.
    // =========================================================================
    
    /**
     * Checks if an img is an MQM-tag and returns its partner-tag (if there is one).
     * @param {Object} mqmImgNode
     * @returns {?Object} imgNode
     */ 
    getMQMPartnerTag: function(mqmImgNode) {
        if (Ext.fly(mqmImgNode).hasCls('qmflag') && mqmImgNode.hasAttribute('data-seq')) {
            var imgInEditorTotal = this.editor.getDoc().images;
            for(var i = 0; i < imgInEditorTotal.length; i++){
                imgOnCheck = imgInEditorTotal[i];
                if (Ext.fly(imgOnCheck).hasCls('qmflag')
                        && imgOnCheck.hasAttribute('data-seq')
                        && (imgOnCheck.getAttribute('data-seq') == mqmImgNode.getAttribute('data-seq') )
                        && (imgOnCheck.id != mqmImgNode.id ) ) {
                    return imgOnCheck;
                }
            }
        }
        return null;
     },
     /**
      * Get an Object with all the users that have done any editing so far and 
      * their css-selector.
      * allUsers.userName = specificSelectorForThisUser
      * @returns {Object} allUsers
      */ 
     getAllUsers: function() {
         var allUsers = new Object(),
             allMarkupNodes = this.getAllMarkupNodesInEditor();
         for (var i = 0; i < allMarkupNodes.length; i++){
             var node = allMarkupNodes[i],
                 userOfNode = node.getAttribute(this.ATTRIBUTE_USERNAME),
                 userCSS = node.getAttribute(this.ATTRIBUTE_USER_CSS);
             if (allUsers[userOfNode] == undefined){
                 allUsers[userOfNode] = userCSS;
             }
         }
         return allUsers;
      },
      /**
       * Finds the newest timestamp in the given Array of nodes.
       * @param {Object} arrNodes
       * @returns {Object} timestamp
       */
      getNewestTimestampOfNodes: function(arrNodes) {
          var newestTimestamp;
          for (var i = 0; i < arrNodes.length; i++){
              var node = arrNodes[i],
                  timestampOfNode = node.getAttribute(this.ATTRIBUTE_TIMESTAMP);
              if (newestTimestamp == undefined){
                  newestTimestamp = timestampOfNode;
              }
              if (timestampOfNode > newestTimestamp){
                  newestTimestamp = timestampOfNode;
              }
          }
          return newestTimestamp;
      },

    // =========================================================================
    // Helpers for Nodes in general
    // =========================================================================
    
    /**
     * Split a node at the position of the current selection.
     * Returns an array with the two parts of the node afterwards.
     * @param {Object} nodeToSplit
     * @returns {Object} splittedNodes
     */
    splitNode: function(nodeToSplit,rangeForPositionToSplit) {
        this.consoleLog("-----");this.consoleLog("nodeToSplit: "); this.consoleLog(nodeToSplit);this.consoleLog(rangeForPositionToSplit); this.consoleLog("-----");
        var splittedNodes = new Array();
        // extract what's on the left from the caret and insert it before the node as a new node
        var rangeForExtract = rangy.createRange(),
            selectionStartNode = rangeForPositionToSplit.startContainer,
            selectionStartOffset = rangeForPositionToSplit.startOffset,
            parentNode = nodeToSplit.parentNode;
        rangeForExtract.setStartBefore(nodeToSplit);
        rangeForExtract.setEnd(selectionStartNode, selectionStartOffset);
        splittedNodes[0] = rangeForExtract.extractContents();
        splittedNodes[1] = nodeToSplit; // nodeToSplit: contains only the second half of the nodeToSplit after extractContents()
        parentNode.insertBefore(splittedNodes[0], splittedNodes[1]); // TODO: Check if both parts of the splitted node share the same conditions (user, workflow, ...)
        // "reset" position of the user's selection (= where the node was split)
        rangeForPositionToSplit.setEndBefore(splittedNodes[1]);
        rangeForPositionToSplit.collapse(false);
        this.docSel.setSingleRange(rangeForPositionToSplit);
        return splittedNodes;
    },
    /**
     * Move a given node inbetween the two halfs of a formerly splitted node.
     * @param {Object} nodeToMove
     * @param {Object} splittedNodes
     */
    moveNodeInbetweenSplittedNodes: function(nodeToMove,splittedNodes) {
        splittedNodes[1].parentNode.insertBefore(nodeToMove, splittedNodes[1]);
    },
    /**
     * Merge two nodes (= move the content from one node to another and remove it then).
     * Returns the (formerly nodeTo-) node with the merged content.
     * The merged node has the timestamp of the newer node (= was edited in the current step).
     * @param {Object} nodeFrom
     * @param {Object} nodeTo
     * @returns {Object} nodeForMergedContent
     */
    mergeNodesFromTo: function(nodeFrom,nodeTo) {
        var nodeForMergedContent = nodeTo,
            arrNodes = [nodeFrom,nodeTo],
            newestTimestamp = this.getNewestTimestampOfNodes(arrNodes);
        while (nodeFrom.childNodes.length > 0) {
            nodeForMergedContent.appendChild(nodeFrom.childNodes[0]);
        }
        nodeForMergedContent.normalize();
        nodeForMergedContent.setAttribute(this.ATTRIBUTE_TIMESTAMP,newestTimestamp);
        nodeFrom.parentNode.removeChild(nodeFrom);
        this.consoleLog("-> Nodes gemergt.");
        return nodeForMergedContent;
    },

    // =========================================================================
    // Development
    // =========================================================================
    
    /**
     * Write into the browser console depending on the setting of this.USE_CONSOLE.
     * @param {(String|Object)} outputForConsole
     */
    consoleLog: function(outputForConsole) {
        if (this.USE_CONSOLE) {
            if (typeof outputForConsole === 'string' || outputForConsole instanceof String) {
                console.log(outputForConsole);
            } else {
                console.dir(outputForConsole);
            }
        }
    },
    consoleClear: function() {
        if (this.USE_CONSOLE) {
            console.clear();
        }
    }
});
