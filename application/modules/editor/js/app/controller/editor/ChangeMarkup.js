
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
 * MetaPanel Controller
 * @class Editor.controller.editor.ChangeMarkup
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.editor.ChangeMarkup', {
    extend : 'Ext.app.Controller',

    TAG_TYPE_DEL: 'delete',
    TAG_TYPE_INS: 'insert',
    TAG_TYPE_REMOVE: 'remove',
    TAG_TYPE_IGNORE: 'ignore',
    TAG_TYPE_BODY: 'body',
    TRACK_NODES: ['INS', 'DEL'],

    requires: [
      'Editor.view.segments.EditorKeyMap'
    ],
    messages: {
    },
    refs : [{
        ref : 'segmentGrid',
        selector : '#segmentgrid'
    }],
    listen: {
        component: {
            'segmentsHtmleditor': {
//FIXME add a "weight" here to ensure that this method is called after Editor.initEditor
                initialize: 'initEditor'
            }
        }
    },
    init : function() {
        var me = this;
    },
    initEditor: function(editor) {
        var me = this;

        me.editor = editor;
        me.editorDoc = Ext.get(editor.getDoc());

//FIXME Fix interaction with our "System Keys" → change invocation?
//FIXME demo only with keydown, implement also keypress!

        me.editorDoc.on('keydown', me.handleKeyDown, me, {priority: 9999, delegated: false});
    },
    handleKeyDown: function(event) {
        var me = this, 
            prevent;
        if(me.handleCutCopy(event)) {
            return;
        }
        preventEvent = this.handleKey(event);
        if (preventEvent) {
            event.stopEvent();
            event.preventDefault();
        }
    },
    /**
     * return true to cancel event
     * @returns {Boolean}
     */
    handleKey: function(e) {
        var char;
        if (e.ctrlKey) {
            return false;
        }
	
//FIXME on delete elements "normalize" node is needed um mehrere parallele text nodes zu einer zusammenzufassen

        switch (e.keyCode) {
            //case 32: //ckeditor does funny stuff with spaces, so insert it ourselves
                //return this.insert({ text: ' ' });
            case e.ENTER:
                this._handleEnter(); //FIXME
                return false;
            case e.DELETE:
            case e.UP:
            case e.DOWN:
            case e.LEFT:
            case e.RIGHT:
                return this.handleAuxiliary(e.keyCode);
            default:
                if (this.ignoreKey(e))  {
                    return false;
                }
                char = String.fromCharCode(e.keyCode)
                if (char) {
                    return this.insert(char);
                }
                // empty strings and null
                return false;
        }
    },
    insert: function(char) {
        console.log(this.editor.iframeEl.dom);
        var me = this,
            selection = rangy.getSelection(me.editor.iframeEl.dom),
            selNode = selection.focusNode,
            isCollapsed = selection.isCollapsed,
            parentType = me.getParentNodeType(selection.focusNode),
            range;

        if(isCollapsed) {
            //handle just a insert of content
            //check where we are
            switch (parentType) {
                case me.TAG_TYPE_IGNORE:
                    //in a div#term node → delete the term node first //FIXME same for delete in a term node
                    break;
                case me.TAG_TYPE_INS:                    
                    // if it is my INS node add text directly
                    if(me.isMyTrackNode(selNode)) {
                        return false; //process normally
                    }
                    // if it is a foreign INS node break it apart here and insert my INS node with the text

                    //ctNode, range.endContainer, range.endOffset
                    range = selection.getRangeAt(0);
                    selNode = me.splitNode(me.getTrackNode(selNode), range.endContainer, range.endOffset);
                    if (selNode) {
					    range.setStartAfter(selNode);
					    range.collapse(true);
                    }
                    selection.setSingleRange(range);
                    me.insertNode(selection, char);
                    return true;
                    break;
            
                default:
// adding first insert:
                    me.insertNode(selection, char);

                    break;
            }
            //in a del node
                // if next sibling is a text node add INS node containing the text after the DEL node before the sibling
                // if next sibling is a INS node of me, add text to this INS node
                // if next sibling is a INS node not of me, add a new INS after the DEL
            //in no node / body
                // add a INS with the text
        }
        else {
            //Handle a whole selection
            // get the nodes in the selection → rangy lib?
            //TODO
        }

        return true; //true cancels event!
    },
    insertNode: function(selection, char) {
        var range = selection.getRangeAt(0),
            node = Ext.DomHelper.createDom({
                tag: 'ins',
                "data-userguid": Editor.app.authenticatedUser.get('userGuid'),
                html: char
            }),
            text = node.lastChild;
        //FIXME node.lastChild can also be some other node type not only text! use setEndAfter then
        range.collapse(false);
        range.insertNode(node);
        range.setEnd(text, (text.nodeValue && text.nodeValue.length) || 0);
        range.collapse();
        selection.addRange(range);
    },
    /**
     * @param node {Node} node to split
     * @param inNode {Node} in which subnode the split should be done
     * @param offset {Integer} offset in the subnode
     * @returns {Node}
     */
    splitNode: function(node, inNode, offset) {

//FIXME change the order of the lines and variable names here!

        var parent = node.parentNode,
            parentOffset = rangy.dom.getNodeIndex(node),
            doc = inNode.ownerDocument, 
            leftRange = doc.createRange(),
            left;
        leftRange.setStart(parent, parentOffset);
        leftRange.setEnd(inNode, offset);
        left = leftRange.extractContents();
        parent.insertBefore(left, node);
        return node.previousSibling;
    },
    getParentNodeType: function(node) {
        var me = this;
        switch(node.nodeName) {
            case '#text': 
                //use the method recursivly to get the type of the parentNode
                return this.getParentNodeType(node.parentNode);
            case 'BODY': 
                return me.TAG_TYPE_BODY;
            case 'DEL': 
                return me.TAG_TYPE_DEL;
            case 'INS': 
                return me.TAG_TYPE_INS;
            case 'SPAN': 
                if(Ext.fly(node).hasCls('term')){
                    //remove term nodes, since when a string was edited it is not the term anymore
                    // and it makes it easier to add the change markup
                    return me.TAG_TYPE_REMOVE;
                }
        }
        return me.TAG_TYPE_IGNORE;
    },
    /**
     * returns true if key should be ignored
     * @returns {Boolean}
     */
    ignoreKey: function(e){
        var key = e.keyCode;
        switch (key) {
            case e.SPACE: //dont ignore space (space is <= DOWN)
                return false;
            case e.INSERT:
            case 91:  // left window key
            case 92:  // right window key
            case 93:  // select key
            case 144:  // num lock
            case 145:  // scroll lock
                return true;
            default:
                if(key <= e.DOWN || (e.F1 <= key && key <= e.F12)){
                    return true; //F keys, special chars and nav keys handled otherwise
                }
        }
        return false;
    },
    /**
     * handles cut and copy shortcuts
     * returns true if the event was handled
     * @returns {Boolean}
     */
    handleCutCopy: function (e) {
        if(!e.ctrlKey) {
            return false;
        }
        switch (e.keyCode) {
            case e.C: //keyCode 99???
                //FIXME do copy
                break;
            case e.X: //keyCode 120???
                //FIXME do cut 
                break;      
        }
        return false;
    },
    handleAuxiliary: function(e) {
        return false;
    },
    /**
     * returns true if the given track node belongs to the current user
     * returns also true if the given node is NO track node at all
     * @returns {Boolean}
     */
    isMyTrackNode: function(trackNode){
        var trackNode = this.getTrackNode(trackNode);
        if(!trackNode) {
            return true;//when it is no trackNode it is considered as my node!
        }
        return trackNode.getAttribute('data-userguid') === Editor.app.authenticatedUser.get('userGuid');
    },
    getTrackNode: function(node) {
        if(!node) {
            return null;
        }
        //if the given node is a text node we want to check the parent one
        if(node.nodeName == '#text') {
            return this.getTrackNode(node.parentNode);
        }
        if(this.TRACK_NODES.indexOf(node.nodeName) < 0) {
            return null;
        }
        return node;
    }
});
