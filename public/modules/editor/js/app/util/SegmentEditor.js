
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
     * - markers from rangy, t5caret etc are ignored
     * Does NOT change anything in the content of the Editor.
     * @returns {String}
     */
    getEditorContentAsText: function() {
        var txt = this.getInnerTextFiltered(this.getEditorBody(), true, true);
        // TODO/QUIRK: the original implementation of this function used rangy, which uses a "visible text" approach, reducing multiple blanks to one.
        // As long as we use rangy to retrieve the text of selections we have to reflect that but later on it may be neccessary to remove this in an unified codebase
        return txt.replace(/ +/g, ' ');
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
        }
        return null;
    },
    /**
     * Collapse all multiple whitespaces with nothing between them (we delete them anyway on save).
     */
    collapseMultipleWhitespaceInEditor: function() {
        var body = this.getEditorBody(),
            html = body.innerHTML;
        body.innerHTML = html.replace(/&nbsp;+/gi, '  ').replace(/\s\s+/g, '&nbsp;');
        body.normalize();
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