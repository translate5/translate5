
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
 * Mixin with general Helpers regarding nodes.
 * @class Editor.util.Node
 */
Ext.define('Editor.util.Node', {
    mixins: ['Editor.util.DevelopmentTools'],
    
    /**
     * Split a node at the position of the current range.
     * Returns an array with the two parts of the node afterwards.
     * @param {Object} nodeToSplit
     * @param {Object} rangeForPositionToSplit
     * @returns {Object} splittedNodes
     */
    splitNode: function(nodeToSplit, rangeForPositionToSplit) {
        var me = this,
            splittedNodes = [],
            rangeForExtract = rangy.createRange(),
            selectionStartNode = rangeForPositionToSplit.startContainer,
            selectionStartOffset = rangeForPositionToSplit.startOffset,
            parentNode = nodeToSplit.parentNode;
        // extract what's on the left from the caret and insert it before the node as a new node
        if(parentNode){
            rangeForExtract.setStartBefore(nodeToSplit);
            rangeForExtract.setEnd(selectionStartNode, selectionStartOffset);
            splittedNodes[0] = rangeForExtract.extractContents();
            splittedNodes[1] = nodeToSplit; // nodeToSplit: contains only the second half of the nodeToSplit after extractContents()
            parentNode.insertBefore(splittedNodes[0], splittedNodes[1]); // TODO: Check if both parts of the splitted node share the same conditions (user, workflow, ...)
        }
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
     * Remove the surrounding Markup and keep only the content of the given node.
     */
    removeMarkupAroundNode: function(node) {
        while(node.firstChild) {
            node.parentNode.insertBefore(node.firstChild,node);
        }
        node.parentNode.removeChild(node);
    },
    /**
     * Move the given childNode to the same level of it's (parent-)Node
     */
    moveNodeOneLevelUp: function(childNode) {
        var me = this,
            node = childNode.parentNode,
            rangeForChildnode = rangy.createRange(),
            splittedNodes;
        // Set range....
        rangeForChildnode.setStartBefore(childNode);
        // ... take out the childNode ...
        node.parentNode.insertBefore(childNode,node);
        // ... split the node ...
        splittedNodes = me.splitNode(node,rangeForChildnode);
        // ... and insert childNode again:
        me.moveNodeInbetweenSplittedNodes(childNode,splittedNodes);
    }
});
