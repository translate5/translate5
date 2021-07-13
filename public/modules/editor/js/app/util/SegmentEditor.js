
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Mixin with Helpers regarding the (content in the) Segment-Editor.
 * @class Editor.util.SegmentEditor
 */
Ext.define('Editor.util.SegmentEditor', {
    mixins: [
        'Editor.util.DevelopmentTools',
        'Editor.util.Node'
    ],
    
    editor: null, // = the segment's Editor (Editor.view.segments.HtmlEditor)
    
    // =========================================================================
    // Avoid problems due to not initialized Editor.
    // =========================================================================
    
    /***
     * Use this function to get the editor body.
     * @returns {HTMLBodyElement}
     */
    getEditorBody:function(){
        var me = this;
        if(!me.editor){
        	me.consoleLog('ERROR: getEditorBody cannot find me.editor!');
            return false;
        }
        if(me.editor.editorBody){
            return me.editor.editorBody;
        }
        return me.editor.getEditorBody();
    },
    /***
     * Use this function to get the editor ext document element.
     * @returns {Ext.dom.Element}
     */
    getEditorBodyExtDomElement:function(){
        var me = this;
        return Ext.get(me.getEditorBody());
    },
    /***
     * Use this function to get the editor HTML-document.
     * @returns {HTMLDocument (#document)}
     */
    getEditorDoc:function(){
        var me = this;
        if(!me.editor){
        	me.consoleLog('ERROR: getEditorDoc cannot find me.editor!');
            return false;
        }
        return me.editor.getDoc();
    },
    
    // =========================================================================
    // Helpers regarding the Segment-Editor
    // =========================================================================
    
    /***
     * Enable/disable the Segment-Editor.
     * @param {Boolean} disabled
     */
    setEditorDisabled:function(disabled){
        var me = this;
        me.editor.setDisabled(disabled);
    },
    
    // =========================================================================
    // Helpers regarding the (content in the) Segment-Editor
    // =========================================================================
    
    /**
     * Returns true if the Segment-Editor is "empty" (= includes nothing but isContainerToIgnore or Selection-Boundary), e.g.:
     * - true: <body><img class="duplicatesavecheck"></body>
     * - true: <body><del>abc</del><img class="duplicatesavecheck"></body>
     * - true: <body><ins><span id="selectionBoundary"></span></ins><img class="duplicatesavecheck"></body>
     * @returns {Boolean}
     */
    isEmptyEditor: function() {
        var me = this,
            rangeForEditor = rangy.createRange(),
            relevantNodesInEditor;
        rangeForEditor.selectNodeContents(me.getEditorBody());
        relevantNodesInEditor = rangeForEditor.getNodes([1,3], function(node) {
            if (node.nodeType == 1) {
                if (node.nodeValue == null) {
                    return false;
                }
                if (!me.isContainerToIgnore(node)) {
                    return false;
                }
                if (node.nodeName === 'DEL') {
                    return false;
                }
                return true;
            } else {
                // selection-boundary-spans do include #text (data: \ufeff)
                if (node.parentNode && me.isContainerToIgnore(node.parentNode)) {
                    return false;
                }
                //its unclear if to use here textContainsEmptyIns or textIsEmptyIns, according to the implementation date the first one makes sense
                if (Editor.plugins.TrackChanges && me.textContainsEmptyIns(node.data)) {
                    return false;
                }
                // (checking parent is enough; del-Nodes should never be nested:)
                if (node.parentNode && node.parentNode.nodeName === 'DEL') {
                    return false;
                }
                return node.data != "";
            }
        });
        if (relevantNodesInEditor.length == 0) {
            return true;
        }
        return false;
    },
    /**
     * Adds a placeholder in the Editor if necessary (TRANSLATE-1042).
     */
    addPlaceholderIfEditorIsEmpty: function() {
        var me = this, 
            content = me.getEditorBody().innerHTML,
            emptySpace = "&#8203;",
            check = /^&#8203;|&#8203;$/;
        
        //add emptySpace only once
        if ((Ext.isGecko || Ext.isWebKit) && me.isEmptyEditor() && check.test(content.trim())) {
            me.getEditorBody().innerHTML = emptySpace;
        }
    },
    /**
     * E.g. invisible containers in the Editor.
     * @param {Object} node
     * @returns {Boolean} 
     */
    isContainerToIgnore: function(node) {
        if (node.nodeName.toLowerCase() == 'img' && /duplicatesavecheck/.test(node.className)) {
            return true;
        }
        if (node.nodeName.toLowerCase() == 'span' && /rangySelectionBoundary/.test(node.className)) {
            return true;
        }
        return false;
    },
    /**
     * Is the given node an MQM-Tag?
     * @param {Object} node
     * @returns {Boolean}
     */ 
    isMQMTag: function(node) {
        return (Ext.fly(node).hasCls('qmflag') && this.hasQualityId(node));
    },
    /**
     * Is the given node an img-content-tag?
     * @param {Object} node
     * @returns {Boolean}
     */ 
    isContentTag: function(node) {
        var me = this,
            idPrefix = me.editor.idPrefix; // s. Editor.view.segments.HtmlEditor
        return Ext.String.startsWith(node.id, idPrefix);
    },
    /**
     * Returns the content in the Editor taking into account its tags:
     * - whitespace-images are replaced with whitespace
     * - content in delNodes is ignored
     * Does NOT change anything in the content of the Editor.
     * @param {Boolean} collapseWhitespace
     * @returns {String}
     */
    getEditorContentAsText: function(collapseWhitespace) {
        var me = this,
            rangeForEditor = rangy.createRange(),
            elBody = me.getEditorBody(),
            el,
            elContentOriginal,
            invisibleElements = [],
            invisibleElementsSearchReplace,
            invisibleElementsRangy,
            editorContentAsText,
            bookmarkForCaret,
            htmlWithWhitespaceImagesAsText,
            docSelSaved = rangy.saveSelection(elBody);
        
        el = me.getEditorBodyExtDomElement();
        elContentOriginal = el.getHtml();
        bookmarkForCaret = me.getPositionOfCaret();
        rangeForEditor.selectNodeContents(elBody);
        
        // replace whitespace-images with whitespace...
        htmlWithWhitespaceImagesAsText = me.getContentWithWhitespaceImagesAsText(rangeForEditor);
        el.setHtml(htmlWithWhitespaceImagesAsText);
        // ...and update the range:
        rangeForEditor.selectNodeContents(elBody);
        
        // ignore delNodes
        me.prepareDelNodeForSearch(true);   // SearchReplaceUtils.js (add display none to all del nodes, with this they are ignored in rangeForEditor.text())
        
        if (collapseWhitespace) {
            // CAUTION: rangy.innerText collapses whitespace! (https://github.com/timdown/rangy/wiki/Text-Range-Module#visible-text)
            editorContentAsText = rangy.innerText(el);
        } else {
            // Do NOT collapse multiple whitespace: Remove invisible content and keep ALL of the rest.
            // = Collect all invisible elements; add selectors as needed:
            invisibleElementsSearchReplace = el.select('.searchreplace-hide-element'); // SearchReplaceUtils.js
            invisibleElementsRangy = el.select('.rangySelectionBoundary');             // Rangy
            invisibleElements = invisibleElements.concat(invisibleElementsSearchReplace).concat(invisibleElementsRangy);
            Ext.Array.each(invisibleElements, function(invisibleEl) {
                invisibleEl.destroy();
            });
            editorContentAsText = rangeForEditor.toString();
        }
        
        el.setHtml(elContentOriginal);
        me.setPositionOfCaret(bookmarkForCaret);
        rangy.restoreSelection(docSelSaved);
        rangy.removeMarkers(docSelSaved);
        
        me.prepareDelNodeForSearch(false);  // SearchReplaceUtils.js
        return editorContentAsText;
    },
    /**
     * Returns the first/last node in the editor that is not of the kind to be ignored.
     * @param {String} direction
     * @returns {Boolean} 
     */
    getLastRelevantNodeInEditor: function(direction) {
        var me = this,
            node = (direction == 'fromEnd') ? me.getEditorBody().lastChild : me.getEditorBody().firstChild;
        while (node) {
            if (!me.isContainerToIgnore(node)) {
                return node;
            }
            node = (direction == 'fromEnd') ? node.previousSibling : node.nextSibling;
        }
        return null;
    },
    /**
     * Returns the first/last node in the editor.
     * @param {String} direction
     * @returns {Boolean} 
     */
    getLastNodeInEditor: function(direction) {
        var me = this,
            node = (direction == 'fromEnd') ? me.getEditorBody().lastChild : me.getEditorBody().firstChild;
        return node;
    },
    
    /**
     * Get the TermTag-Node for a node (= the node itself or loop its parents until we find it).
     * @param {Object} node
     * @returns {?Object} termTag-node
     */
    getTermTagNodeOfNode: function(node){
        var me = this;
        while (node) {
            if (/term/.test(node.className)) {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    },
    /**
     * Collects all kinds of partner-Tags we need to check against.
     * @param {Object} node
     * @returns {?Object} node
     */ 
    getPartnerTag: function(node) {
        var me = this,
            partnerTag = null;
        if (node.nodeType == 3) {
            return null;
        }
        // (can only match one type of partner-Tag)
        partnerTag = me.getMQMPartnerTag(node);            // MQM-Tags
        if (partnerTag == null) {
            partnerTag = me.getContentPartnerTag(node);    // Content-Tags
        }
        return partnerTag;
    },
    /**
     * Checks if an img is an MQM-tag and returns its partner-tag (if there is one).
     * @param {Object} mqmImgNode
     * @returns {?Object} imgNode
     */ 
    getMQMPartnerTag: function(mqmImgNode) {
        var me = this,
            imgInEditorTotal,
            imgOnCheck,
            i,
            arrLength;
        if (me.isMQMTag(mqmImgNode)) {
            imgInEditorTotal = me.getEditorDoc().images;
            arrLength = imgInEditorTotal.length;
            for(i = 0; i < arrLength; i++){
                imgOnCheck = imgInEditorTotal[i];
                if (Ext.fly(imgOnCheck).hasCls('qmflag')
                        && me.hasQualityId(imgOnCheck)
                        && (me.fetchQualityId(imgOnCheck) == me.fetchQualityId(mqmImgNode))
                        && (imgOnCheck.id != mqmImgNode.id ) ) {
                    return imgOnCheck;
                }
            }
        }
        return null;
    },
    /**
     * Checks if an img is a Content-tag (<i>, <b>, ...) and returns its partner-tag (if there is one).
     * @param {Object} contentTagImgNode
     * @returns {?Object} imgNode
     */ 
    getContentPartnerTag: function(contentTagImgNode) {
        var me = this,
            idPrefix = me.editor.idPrefix, // s. Editor.view.segments.HtmlEditor
            contentTagImgClass = contentTagImgNode.className,
            contentTagImgId = contentTagImgNode.id,
            partnerTagImgId = null;
        if (me.isContentTag(contentTagImgNode)) {
            // "Toggle" id
            switch(true){
              case /open/.test(contentTagImgClass):
                  partnerTagImgId = contentTagImgId.replace('open', 'close');
                break;
              case /close/.test(contentTagImgClass):
                  partnerTagImgId = contentTagImgId.replace('close', 'open');
                break;
            }
            if(partnerTagImgId != null) {
                return me.getEditorDoc().getElementById(partnerTagImgId); // getElementById() returns null if it doesn't exist
            }
        };
        return null;
    },
    /**
     * Collapse all multiple whitespaces with nothing between them (we delete them anyway on save).
     */
    collapseMultipleWhitespaceInEditor: function() {
        var me = this,
            el = me.getEditorBodyExtDomElement(),
            elContent,
            bookmarkForCaret = me.getPositionOfCaret();
        elContent = el.getHtml();
        elContent = elContent.replace(/&nbsp;+/gi,'  ').replace(/\s\s+/g, '&nbsp;');
        el.setHtml(elContent);
        me.getEditorBodyExtDomElement().dom.normalize();
        me.setPositionOfCaret(bookmarkForCaret);
    },
    /***
     * Remove SpellCheck-Markup in the Editor but keep their content.
     */
    cleanSpellCheckMarkupInEditor:function(){
        var me = this,
            el = me.getEditorBodyExtDomElement(),
            allSpellCheckNodes = Ext.fly(el).query('.spellcheck');
        Ext.Array.each(allSpellCheckNodes, function(spellCheckNode, index) {
            me.removeMarkupAroundNode(spellCheckNode);
        });
    },
    /***
     * Remove SpellCheck-Markup at a range (e.g. the position of the cursor) but keep the content.
     */
    cleanSpellCheckMarkupAtRange:function(range){
        var me = this,
            allSpellCheckNodes = [],
            spellCheckNode,
            getSpellCheckNode = function(nodeToCheck){
                while (nodeToCheck) {
                    if (/\bspellcheck\b/i.test(nodeToCheck.className)) {
                        return nodeToCheck;
                    }
                    nodeToCheck = nodeToCheck.parentNode;
                }
                return null;
            },
            bookmarkForCaret = me.getPositionOfCaret();
        allSpellCheckNodes.push(range.commonAncestorContainer);
        allSpellCheckNodes.push(range.startContainer);
        allSpellCheckNodes.push(range.endContainer);
        Ext.Array.each(allSpellCheckNodes, function(node) {
            spellCheckNode = getSpellCheckNode(node);
            if (spellCheckNode != null) {
                me.removeMarkupAroundNode(spellCheckNode);
            }
        });
        me.setPositionOfCaret(bookmarkForCaret);
    },
    /***
     * Clean up Nodes, e.g. remove empty TrackChange-Nodes.
     */
    cleanUpNode:function(node){
        var me = this,
            allTrackChangeNodes,
            isEmptyNode = function(nodeToCheck){
                if (nodeToCheck.nodeValue == null) {
                    if (nodeToCheck.childNodes.length == 0) {
                        return true;
                    }
                    if (nodeToCheck.childNodes.length == 1 && nodeToCheck.firstChild.nodeType == 3) {
                        if (nodeToCheck.firstChild.data == "") {
                            return true;
                        }
                    }
                }
                return false;
            };
            allTrackChangeNodes = Ext.fly(node).query('.trackchanges');
        Ext.Array.each(allTrackChangeNodes, function(trackChangeNode, index) {
            if (isEmptyNode(trackChangeNode) && trackChangeNode.parentNode != null) {
                trackChangeNode.parentNode.removeChild(trackChangeNode);
            }
        });
    },
    /**
     * Comapatibility function to retrieve the quality id from a DOM node
     * NOTE: historically the quality-id was encoded as "data-seq"
     */
    fetchQualityId: function(ele){
        if(ele.hasAttribute('data-t5qid')){
            return ele.getAttribute('data-t5qid');
        }
        if(ele.hasAttribute('data-seq')){
            return ele.getAttribute('data-seq');
        }
        return null;
    },
    /**
     * Comapatibility function to check if the quality id is set on a DOM node
     * NOTE: historically the quality-id was encoded as "data-seq"
     */
    hasQualityId: function(ele){
        return ele.hasAttribute('data-t5qid') || ele.hasAttribute('data-seq');
    }
});