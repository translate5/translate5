
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

/**
 * Mixin with common Helpers regarding ranges
 * @class Editor.util.Range
 */
Ext.define('Editor.util.Range', {
    mixins: ['Editor.util.DevelopmentTools',
             'Editor.util.SegmentEditor'],

    // =========================================================================
    // Container
    // =========================================================================
    
    /**
     * Get the deepest common node of the given range.
     * If we find a text-Node, we take that one.
     * @param {Object} range
     * @returns {Object} node
     */
    getContainerForRange: function(range){
        var me = this,
            commonStartAndEndContainer,
            commonBoundaryContainer,
            commonBoundaryOffset,
            positionInEditor,
            isTextContainer = function(nodeToCheck){
                return (nodeToCheck.nodeName.toLowerCase() == '#text');
            };
        
        // Examples:
        // <body>a|<img>b</body>                    Text    (here: atEnd)
        // <body>a<del>b</del>|c</body>             Text    (here: atStart)
        // <body>a<del>|b</del>c</body>             Text    (here: atStart)
        // <body>a<img><del>|b</del>cd</body>       Text    (here: atStart)
        // <body><span>|ab</span></body>            Text    (here: atStart)
        // <body>a<img>|<img>bc</body>              Element
        // <body>a<ins><img>|</ins></body>          Element
        // <body>a<img><del>|<img></del>bc</body>   Element
        
        if (me.rangeStartsAndEndsInTheSameNode(range)){
            commonBoundaryContainer = range.startContainer; // startContainer == endContainer in here
            commonBoundaryOffset = range.startOffset;       // startOffset == endOffset in here
            if (commonBoundaryContainer.nodeType == 1) {
                commonBoundaryContainer = commonBoundaryContainer.childNodes[commonBoundaryOffset]; // might be null, that's ok
            }
            // Bubble down until the common container is text
            while (commonBoundaryContainer) {
                if (isTextContainer(commonBoundaryContainer)) {
                    return commonBoundaryContainer;
                }
                // after re-opening a segment and not changing the postion of the cursor,
                // the range might suddenly refer to the body, don't know why.
                // => in this case: use firstChild, no matter if there are many childNodes or not.
                positionInEditor = me.getPositionInfoForRange(range,me.getEditorBody());
                if (commonBoundaryContainer.childNodes.length == 1 || positionInEditor.atStart) {
                    commonBoundaryContainer = commonBoundaryContainer.firstChild;
                } else {
                    commonBoundaryContainer = null;
                }
            }
        }
        // "default":
        return range.commonAncestorContainer;
    },
    /**
     * Does the range start and end in one-and-the-same node?
     * @param {Object} range
     * @returns {Boolean}
     */
    rangeStartsAndEndsInTheSameNode: function(range) {
        var startContainer          = range.startContainer,
            startOffset             = range.startOffset,
            endContainer            = range.endContainer,
            endOffset               = range.endOffset,
            startContainerNodeType = startContainer.nodeType,
            endContainerNodeType = endContainer.nodeType;
        if (startContainer.isSameNode(endContainer)) {
            // Common node is a text-node:
            if (startContainerNodeType == 3 && endContainerNodeType == 3) {
                return true;
            }
            // Common node is an image:
            if (startContainerNodeType == 1 && endContainerNodeType == 1
                    && startContainer.nodeName.toLowerCase() == 'img' && endContainer.nodeName.toLowerCase() == 'img') {
                return true;
            }
            // Common node is an element-node => their offset must refer to the same children:
            if (startOffset == endOffset) {
                return true;
            }
        }
        return false;
    },
    
    // =========================================================================
    // Siblings
    // =========================================================================
    
    /**
     * Get the previous or next node of the given range.
     * @param {Object} range
     * @param {String} direction (previous|next)
     * @returns {?Object} node
     */
    getSiblingNodeForRange: function(range,direction){
        var me = this,
            node = (direction == "previous") ? range.startContainer : range.endContainer;
        return me.getSiblingNodeForNode(node,direction);
    },
    /**
     * Get the previous or next node of the given node.
     * If we are in a text node, the sibling node of the (parent's....) parentNode is returned.
     * @param {Object} node
     * @param {String} direction (previous|next)
     * @returns {?Object} node
     */
    getSiblingNodeForNode: function(node,direction){
        var lookForPrevious = (direction == "previous") ? true : false,
            siblingNode = null;
        while (node) {
            siblingNode = (lookForPrevious ? node.previousSibling : node.nextSibling);
            if (siblingNode != null) {
                return siblingNode;
            }
            node = node.parentNode;
        }
        return null;
    },
    /**
     * Returns a node that includes the previous/next "one-space"-content from the current position of the caret
     * (= as if DELETE or BACKSPACE would have been used after positioning the caret somewhere).
     * - within text (Example: BACKSPACE at <ins>a|bc</ins>): we'll move within it
     * - at the beginning/end of text (Example: BACKSPACE at <ins>abc|</ins>de or <ins>abc|</ins><del>de<del> or <ins>abc|</ins><img>de): we'll move to the next (text-)node
     * - otherwise (Example: BACKSPACE at <ins>abc<img>|</ins> or <ins>abc</ins>|<del>de<del> or <ins><img></ins>|<del>abc<del>): we'll move into/to the next node
     * @param {Object} range
     * @param {String} direction
     * @returns {?Object} node
     */
    getContainerForCharacterNextToCaret: function(range,direction) {
        var me = this,
            positionInEditor = me.getPositionInfoForRange(range,me.getEditorBody()),
            container,
            positionInContainer,
            isAtFinalPositionInContainer,
            containerSibling,
            isContentContainer = function(nodeToCheck){
                return (nodeToCheck.nodeType == 3 || nodeToCheck.nodeName.toLowerCase() == 'img');
            };
        // If we are at the very beginning/end of the Editor, we cannot go any further:
        if( (positionInEditor.atStart && direction == 'previous')
                || (positionInEditor.atEnd && direction == 'next') ){
            return null;
        }
        container = me.getContainerForRange(range);
        // (1) We are in a text node, but NOT at the very beginning/end
        //     => moving to the left or to the right stays within the text.
        if (container.nodeType == 3) {
            positionInContainer = me.getPositionInfoForRange(range,container);
            switch(direction) {
                case 'previous':
                    isAtFinalPositionInContainer = positionInContainer.atStart;
                    break;
                case 'next':
                    isAtFinalPositionInContainer = positionInContainer.atEnd;
                    break;
            }
            if (!isAtFinalPositionInContainer) {
                return container;
            }
        }
        // (2) We are right before/after a node that is or contains image/text => DELETE or BACKSPACE will mean that one
        if (container.nodeType == 1) {
            switch(direction) {
                case 'previous':
                    containerSibling = range.startContainer.childNodes[range.startOffset-1];
                    break;
                case 'next':
                    containerSibling = range.endContainer.childNodes[range.endOffset];
                    break;
            }
            if (containerSibling != null && isContentContainer(containerSibling)) {
                return containerSibling;
            }
        }
        // (3) Moving to the left or to the right will refer to the (content in the) previous/next container.
        // 
        //            ~~~~~~~~~~~~~~~~~~                ~~~~~~~~~~~~~~               
        //     -----> {I have a sibling} --YES--------> {is text||IMG} --YES-----> OK
        //        ^   ~~~~~~~~~~~~~~~~~~           ^    ~~~~~~~~~~~~~~               
        //        |            |                   |          |                      
        //        |            | NO                |          | NO                   
        //        |            |                   |          |                      
        //        |            |                   |          v                      
        //        |            |                   |   ~~~~~~~~~~~~~~~~              
        //        |            |               YES |-- {    has a     }              
        //        |            |                       { (last) child }              
        //        |            |                       ~~~~~~~~~~~~~~~~              
        //        |            |                              |                      
        //        |            |                              | NO                   
        //        |            |                              |                      
        //        |            |  < --------------------------                       
        //    YES |            v                                                     
        //        |   ~~~~~~~~~~~~~~~~~                                              
        //        --- {I have a parent}                                              
        //            ~~~~~~~~~~~~~~~~~                                              
        //                     |                                                     
        //                     | NO                                                  
        //                     |                                                     
        //                     v                                                     
        //                                                                           
        //                    NULL                                                   
        //
        while (container && container.nodeName.toLowerCase() != 'body') {
            var containerSibling = me.getSiblingNodeForNode(container, direction);
            while (containerSibling) {
                if (isContentContainer(containerSibling)) {
                    if (!me.isContainerToIgnore(containerSibling)) { // e.g. don't return the duplicatesavecheck-image
                        return containerSibling;
                    }
                }
                containerSibling = (direction == "previous") ? containerSibling.lastChild : containerSibling.firstChild;
            }
            container = container.parentNode;
        }
        // (4) If we didn't find any text or image so far, we just return the container next to the caret in the given direction.
        if (container.nodeName.toLowerCase() == 'body' && range.commonAncestorContainer.nodeName.toLowerCase() == 'body') {
            switch(direction) {
                case 'previous':
                    return range.startContainer.childNodes[range.startOffset-1];
                    break;
                case 'next':
                    return range.endContainer.childNodes[range.endOffset];
                    break;
            }
        }
        return null;
    },
    /**
     * Returns the range for the content "on the left" or "on the right" (according to the 
     * current event) of the given range.
     * If the given container is an image, we select that one;
     * otherweise we select text by moving the range's boundary.
     * @param {Object} range
     * @param {String} direction
     * @returns {?Object} range
     */
    selectCharacterNextToCaret: function(range,direction) {
        var me = this,
            containerNextToCaret = me.getContainerForCharacterNextToCaret(range,direction),
            containerNextToCaretNodeType,
            rangeEndContainer = range.endContainer,
            rangeEndContainerIsOurContainer,
            moveOptions  = { includeBlockContentTrailingSpace: true };
        if(!range.collapsed) {
            return range;
        }
        if (containerNextToCaret == null) {
            return null;
        }
        rangeEndContainerIsOurContainer = rangeEndContainer.isSameNode(containerNextToCaret);
        containerNextToCaretNodeType = containerNextToCaret.nodeType;
        // containerNextToCaret is an IMG => take that one.
        if (containerNextToCaretNodeType == 1 && containerNextToCaret.nodeName.toLowerCase() == 'img') {
            range.selectNodeContents(containerNextToCaret);
        }
        // containerNextToCaret is one single character of text => take that one.
        else if (containerNextToCaretNodeType == 3 && containerNextToCaret.length == 1) {
            range.selectNodeContents(containerNextToCaret);
        }
        // content in containerNextToCaret will be many characters of text => moveCharacter.
        else {
            //e.g. 'next' at <del>a<img>|<del>bcd => jump into containerNextToCaret (= 'bcd') first
            if(!rangeEndContainerIsOurContainer) {
                range.selectNodeContents(containerNextToCaret);
                switch(direction) {
                    case 'previous':
                        range.collapse(false);
                        break;
                    case 'next':
                        range.collapse(true);
                        break;
                }
            }
            // moveCharacter (checking for atStart / atEnd  is not necessary here because we moved into the container into the given direction already)
            switch(direction) {
                case 'previous':
                    range.moveStart("character", -1, moveOptions);
                    break;
                case 'next':
                    range.moveEnd("character", 1, moveOptions);
                    break;
            }
        }
        return range;
    },

    // =========================================================================
    // Positions
    // =========================================================================
    
    /**
     * Determine if the given range is at the start or end of the given part of content.
     * (https://stackoverflow.com/a/7478420)
     * @param {Object} range
     * @param {Object} containingNode
     * @returns {Object}
     */
    getPositionInfoForRange: function(range,containingNode) {
        var me = this,
            atStart = false,
            atEnd = false,
            testRange = rangy.createRange(),
            rangeToString,
            rangeToHtml,
            rangeWrapper,
            rangeAsDom,
            imagesInNode,
            i,
            arrLength,
            rangeIsEmpty = function(rangeToCheck){
                rangeToString = rangeToCheck.toString();
                if(rangeToString == "") {
                    if(rangeToCheck.commonAncestorContainer.parentNode != null) { // toHtml() would fail then
                        rangeToHtml = rangeToCheck.toHtml();
                        if(rangeToHtml != "") {
                            // We might have an image in here, thus we are NOT empty ...
                            rangeWrapper = document.createElement('div');
                            rangeWrapper.innerHTML = rangeToHtml;
                            rangeAsDom = Ext.fly(rangeWrapper).dom;
                            imagesInNode = rangeAsDom.getElementsByTagName("IMG");
                            arrLength = imagesInNode.length;
                            for (i = 0; i < arrLength; i++){
                                if(!me.isContainerToIgnore(imagesInNode[i])) { // ignore nodes to ignore
                                    return false;
                                }
                            }
                        }
                    }
                }
                return (rangeToString == "");
            };
        
        testRange.selectNodeContents(containingNode);
        testRange.setEnd(range.startContainer, range.startOffset);
        atStart = rangeIsEmpty(testRange);
        
        testRange.selectNodeContents(containingNode);
        testRange.setStart(range.endContainer, range.endOffset);
        atEnd = rangeIsEmpty(testRange);
        
        return { atStart: atStart, atEnd: atEnd };
    },
    
    // =========================================================================
    // Helpers for the content of/in a range
    // =========================================================================
    
    /** 
     * Replace whitespace-images in given range with whitespace-text. Returns the new html.
     * @params {Object} range
     * @returns {String} html 
     */
    getContentWithWhitespaceImagesAsText: function(range) {
        var allWhitespaceImages,
            htmlForImage,
            html = range.toHtml(),
            rangeForWhitespace = rangy.createRange();
        allWhitespaceImages = range.getNodes([1], function(node) {
            return (node.nodeName == 'IMG' && node.classList.contains('whitespace'));
        });
        Ext.Array.each(allWhitespaceImages, function(imgNode) {
            rangeForWhitespace.selectNode(imgNode);
            htmlForImage = rangeForWhitespace.toHtml();
            html = html.replace(imgNode.outerHTML, ' ');
        });
        return html;
    },
    /**
     * Fix selections when they start or end in/at an internal tag. 
     * = Workaround because selected tags might not be fully fetched for ranges,
     * which will cause errors in getData() of Editor.view.segments.HtmlEditor.
     * Example:
     * <div class="close"><span class="short">&lt;/1&gt;</span><span class="full">&lt;/span&gt;</span></div>
     * Example 1: the selections covers the tag fully, but the range will only fetch:
     * <div class="close"><span class="short">&lt;/1&gt;</span>
     * Example 2: user might only select "<1"
     * @param {Object} selRange
     * @returns {Object} selRange
     */
    getRangeWithFullInternalTags: function(selRange) {
        var me = this,
            startContainer = selRange.startContainer,
            endContainer = selRange.endContainer,
            commonContainer = me.getContainerForRange(selRange),
            commonDivTag,
            allDivTags,
            getCommonDivTag = function(node){
                while (node) {
                    if (node.nodeName.toLowerCase() === 'div' && 
                            ( node.classList.contains('open') || node.classList.contains('close') || node.classList.contains('single') ) ) {
                        return node;
                    }
                    node = node.parentNode;
                }
                return null;
            };
        commonDivTag = getCommonDivTag(commonContainer);
        if (commonDivTag != null) {
            // Is the selection completely within a divTag?
            selRange.selectNode(commonDivTag);
        } else if (commonContainer.nodeType === 1) {
            // Does the selection start or end within a divTag?
            allDivTags = commonContainer.getElementsByTagName('div');
            Ext.Array.each(allDivTags, function(divTag) {
                if (rangy.dom.isOrIsAncestorOf(divTag, startContainer)) {
                    selRange.setStartBefore(divTag);
                }
                if (rangy.dom.isOrIsAncestorOf(divTag, endContainer)) {
                    selRange.setEndAfter(divTag);
                }
            });
        }
        return selRange;
    },
    /**
     * Returns true if all the relevant content in the editor is selected.
     * @returns {Boolean} 
     */
    isSelectedAllRelevantNodesInEditor: function() {
        var me = this,
            selectionInEditor,
            rangeForSelection,
            el,
            selectedText,
            selectedContent,
            editorContentAsText,
            internalTags = [],
            tagsOpen,
            tagsClose,
            tagsSingle,
            internalTagIsNotSelected;
        // if editor is empty, there is nothing to select
        if (me.isEmptyEditor()) {
            return false;
        }
        selectionInEditor = rangy.getSelection(me.getEditorBody());
        rangeForSelection = selectionInEditor.rangeCount ? selectionInEditor.getRangeAt(0) : null;
        if (rangeForSelection == null || rangeForSelection.collapsed){
            return false; // might be true, but we couldn't check with the current code.
        }
        el = me.getEditorBodyExtDomElement();
        el.select('.deleted').setStyle('visibility', 'hidden');
        selectedText = rangeForSelection.text();
        selectedContent = rangeForSelection.toHtml();
        editorContentAsText = me.getEditorContentAsText(false);
        el.select('.deleted').setStyle('visibility', 'visible');
        // if the text is not the same in the selection as in the Editor, not everything is selected 
        if (selectedText !== editorContentAsText) {
            return false;
        }
        // internal tags are relevant content, too!
        // example: <1>German</1>  => check if <1> and/or </1> are in the selection, too
        el = me.getEditorBodyExtDomElement();
        tagsOpen = el.select('.open');
        tagsClose = el.select('.close');
        tagsSingle = el.select('.single');
        internalTags = internalTags.concat(tagsOpen.elements).concat(tagsClose.elements).concat(tagsSingle.elements);
        internalTagIsNotSelected = false;
        Ext.Array.each(internalTags, function(internalTag) {
            if (selectedContent.indexOf(internalTag.id) === -1) {
                internalTagIsNotSelected = true;
                return false; // break here
            }
        });
        return !internalTagIsNotSelected;
    },
    
    /**
     * If there are empty text-Nodes or non-text-content-Nodes (MQM-Tag, Content-Tag) 
     * at the beginning or end of a range that is based on characters (eg from SpellCheck),
     * then we move these Nodes OUT of the given range. (Otherwise the context eg for 
     * replacements would include these nodes, which would be wrong.)
     * Example (range marked from "[" to "]"):
     * - Content is:                                            ab<MQM>cd</MQM>ef
     * - SpellCheck checks:                                     abcdef
     * - A match in the SpllCheck means:                        cd
     * - Finding this range based on the characters results in: ab[<MQM>cd]</MQM>ef
     * - But correct is:                                        ab<MQM>[cd]</MQM>ef
     * @param {Object} range
     * @param {String} direction (optional)
     * @returns {Object} range
     */
    cleanBordersOfCharacterbasedRange: function(range,direction) {
        var me = this,
            documentFragmentForRange,
            tagNodesInRange,
            nodeAtBorder,
            nodeToSeperate,
            i,
            iMax,
            isTagNodeInRange = function(nodeToCheck){
                var nodeFound = false;
                Ext.Array.each(tagNodesInRange, function(node) {
                    if (node.id === nodeToCheck.id) {
                        nodeFound = true;
                    }
                });
                return nodeFound;
            },
            isEmptyTextNode = function(nodeToCheck){
                if (nodeToCheck.nodeType === 3 && nodeToCheck.data === '') {
                    return true;
                }
                return false;
            },
            findNodeInEditor = function(nodeToFind){
                var nodeFound = null,
                    allNodesInEditor = me.getEditorBodyExtDomElement().query('*');
                Ext.Array.each(allNodesInEditor, function(node) {
                    if ((node.id != null && node.id !== '' && node.id === nodeToFind.id) || node.isEqualNode(nodeToFind)) {
                        nodeFound = node;
                    }
                });
                return nodeFound;
            },
            getNextBorderNode = function(dir){
                return (dir === 'fromEnd') ? documentFragmentForRange.lastChild : documentFragmentForRange.firstChild;
            },
            seperateNodeFromRange = function(seperatorNode, dir){
                if (dir === 'fromEnd') {
                    range.setEndBefore(seperatorNode);
                } else {
                    range.setStartAfter(seperatorNode);
                }
            },
            cleanBorderNodesFromRange = function(dir){
                i = 0;
                nodeAtBorder = getNextBorderNode(dir);
                while (i<iMax && nodeAtBorder != null && (isEmptyTextNode(nodeAtBorder) || isTagNodeInRange(nodeAtBorder)) ) {
                    switch(true) {
                        case isEmptyTextNode(nodeAtBorder):
                            // empty text-nodes are irrelevant, skip them
                            documentFragmentForRange.removeChild(nodeAtBorder);
                            break;
                        case isTagNodeInRange(nodeAtBorder):
                            nodeToSeperate = findNodeInEditor(nodeAtBorder);
                            if(nodeToSeperate != null) {
                                seperateNodeFromRange(nodeToSeperate, dir);
                                documentFragmentForRange = range.cloneContents();
                            } else {
                                nodeAtBorder = null; // stop iteration
                            }
                            break;
                    }
                    if (nodeAtBorder != null) {
                        nodeAtBorder = getNextBorderNode(direction);
                    }
                    i++;
                }
            };
        
        documentFragmentForRange = range.cloneContents();
        tagNodesInRange = range.getNodes([1], function(node) {
            return ( me.isMQMTag(node) || me.isContentTag(node) );
        });
        iMax = documentFragmentForRange.childNodes.length;
        
        if (direction == null) {
            direction = 'fromBothEnds';
        }
        switch(direction) {
            case 'fromStart':
                cleanBorderNodesFromRange('fromStart');
                break;
            case 'fromEnd':
                cleanBorderNodesFromRange('fromEnd');
                break;
            case 'fromBothEnds':
            default:
                cleanBorderNodesFromRange('fromStart');
                cleanBorderNodesFromRange('fromEnd');
                break;
        }
        
        return range;
    },
    
    // =========================================================================
    // Workarounds
    // =========================================================================
    
    /**
     * Surrounds the given range with the given node.
     * Usually easy, but surroundContents() does not work 
     * as long as the node is not recognized in the same document.
     */
    surroundRangeWithNode: function (range, node) {
        var me = this;
        
        // Problem: see https://stackoverflow.com/a/17282924
        // console.dir(range.commonAncestorContainer.ownerDocument);
        // console.dir(node.ownerDocument);
        
        // => Hack for identifying trouble that would cause Wrong-Document-Errors (eg in IE 11):
        if (!me.getEditorBodyExtDomElement().contains(node)) {
            // (here:) me.getEditorBody().contains(node) => WrongDocumentError
            // Workaround: add the node manually:
            me.getEditorBody().appendChild(node);
        }
        
        range.surroundContents(node);
    },
    
    // =========================================================================
    // Workarounds for buggy Bookmarks of Rangy
    // =========================================================================
    // Rangy is buggy (eg when the cursor is right BEHIND an MQM-Tag, 
    // the bookmark will place the cursor right IN FRONT of the MQM-Tag) 
    // => workaround: return and apply node.
    // =========================================================================
    
    // -------------------------------------------------------------------------
    // When to use the workaround 
    // -------------------------------------------------------------------------
    
    /**
     * On getting the bookmark:
     * Check if the workaround as described above is needed to be applied
     * (= when the cursor is placed after an MQM-Tag or a content-node).
     * @param {Object} range
     * @returns {Boolean}
     */
    useWorkaroundForBookmark: function(range) {
        var me = this,
            contentBeforeRange;
        contentBeforeRange = me.getContainerForCharacterNextToCaret(range,'previous');
        if (contentBeforeRange === null || contentBeforeRange === undefined) {
            return false;
        }
        if (typeof contentBeforeRange !== 'object') {
            return false;
        }
        return ('nodeType' in contentBeforeRange && contentBeforeRange.nodeType === 1 
                && ( me.isMQMTag(contentBeforeRange) || me.isContentTag(contentBeforeRange) ) );
    },
    /**
     * On applying the bookmark:
     * Check if the workaround as described above is needed to be applied
     * (= when the bookmark is a node).
     * @param {Object} bookmark
     * @returns {Boolean}
     */
    isBookmarkOfWorkaround: function(bookmark) {
        if (typeof bookmark !== 'object') {
            return false;
        }
        return ('nodeType' in bookmark && bookmark.nodeType === 1);
    },
    
    // -------------------------------------------------------------------------
    // What to use as workaround
    // -------------------------------------------------------------------------
    
    /**
     * Get the bookmark using the workaround; optionally handles character-based ranges, too.
     * @param {Object} range
     * @param {Boolean} isCharacterbased
     * @returns {Boolean}
     */
    getBookmarkUsingTheWorkaround: function(range,isCharacterbased) {
        var me = this;
        // Use the workaround only for collapsed ranges!
        if (range.collapsed) {
            return me.getContainerForCharacterNextToCaret(range,'previous');
        }
        // If we work with a (non-collapsed) character-based range: clean the borders first.
        if (isCharacterbased) {
            range = me.cleanBordersOfCharacterbasedRange(range);
        }
        // Fallback for non-collapsed ranges: Use rangy's bookmark as usual.
        return range.getBookmark();
    },
    /**
     * Apply the bookmark using the workaround.
     * If the node that we used for the workaround does no longer exist, we are lost.
     * @param {Object} range
     * @param {Object} bookmark
     * @returns {Object} range
     */
    applyBookmarkUsingTheWorkaround: function(range,bookmark) {
        var me = this,
            nodeForBookmark;
        nodeForBookmark = Ext.get(me.getEditorBody()).getById(bookmark.id,true);
        if (nodeForBookmark != null) {
            range.setStartAfter(nodeForBookmark);
            range.collapse(true);
        }
        return range;
    },
    
    // -------------------------------------------------------------------------
    // Bookmarks for Ranges in translate5
    // -------------------------------------------------------------------------
    
    /**
     * Get the bookmark:
     * - use workaround for buggy bookmarks when necesssary
     * - optional: extra handling of characterbased ranges
     * @param {Object} range
     * @param {Boolean} isCharacterbased
     * @returns {Object} rangy-bookmark|node
     */
    getBookmarkForRangeInTranslate5: function(range,isCharacterbased) {
        var me = this;
        if (me.useWorkaroundForBookmark(range)) {
            return me.getBookmarkUsingTheWorkaround(range,isCharacterbased);
        } else {
            return range.getBookmark();
        }
    },
    /**
     * Apply a bookmark:
     * @param {Object} range
     * @param {Object} rangy-bookmark|node
     * @param {Boolean} isCharacterbased
     * @returns {Object} range
     */
    moveRangeToBookmarkInTranslate5: function(range,bookmark,isCharacterbased) {
        var me = this,
            nodeForBookmark;
        if (me.isBookmarkOfWorkaround(bookmark)){
            range = me.applyBookmarkUsingTheWorkaround(range,bookmark);
        } else {
            range.moveToBookmark(bookmark);
        }
        if (isCharacterbased) {
            range = me.cleanBordersOfCharacterbasedRange(range);
        }
        return range;
    },

    // -------------------------------------------------------------------------
    // Get and set the position of the caret in the Editor
    // -------------------------------------------------------------------------
    
    /**
     * Returns a bookmark for the current position of the cursor in the Editor.
    // (Use rangy's bookmark if workaround is not applied).
     * @returns {Object|null} rangy-bookmark|node|null
     */
    getPositionOfCaret: function() {
        var me = this,
            selectionForCaret = rangy.getSelection(me.getEditorBody()),
            rangeForCaret = selectionForCaret.rangeCount ? selectionForCaret.getRangeAt(0) : null;
        if (rangeForCaret == null) { // eg. after the push-event when newly opening a segment: editor is opened, but user is not in there yet.
            return null;
        } else if (me.useWorkaroundForBookmark(rangeForCaret)) {
            return me.getBookmarkUsingTheWorkaround(rangeForCaret);
        } else {
            return rangeForCaret.getBookmark();
        }
    },
    /**
     * Set the position of the cursor according to the given bookmark or node.
     * (Use rangy's bookmark if workaround is not applied).
     * @param {Object|null} rangy-bookmark|node|null
     */
    setPositionOfCaret: function(bookmarkForCaret) {
        var me = this,
            selectionForCaret,
            startNodeOfSelection,
            rangeForCaret,
            nodeForBookmark;
        if (bookmarkForCaret != null) {
            selectionForCaret = rangy.getSelection(me.getEditorBody());
            startNodeOfSelection = (selectionForCaret.isBackwards()) ? selectionForCaret.focusNode : selectionForCaret.anchorNode;
            rangeForCaret = rangy.createRange();
            if(me.isBookmarkOfWorkaround(bookmarkForCaret)){
                rangeForCaret = me.applyBookmarkUsingTheWorkaround(rangeForCaret,bookmarkForCaret);
            } else {
                rangeForCaret.moveToBookmark(bookmarkForCaret);
            }
            if (!rangeForCaret.collapsed && startNodeOfSelection.nodeType == 1) {
                // rangy bug: if the selection starts between a tag and text, the tag will be included even it was not selected
                // (does not happen at the end of the selection)
                rangeForCaret = me.cleanBordersOfCharacterbasedRange(rangeForCaret,"fromStart");
            }
            selectionForCaret.setSingleRange(rangeForCaret);
        }
    }
});