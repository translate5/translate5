
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
 * General DOM manipulation Helper
 * Can insert (-> decorate) and remove nodes by text-indices or ranges
 * It is tailored to work with Editor.util.dom.Selection to select nodes 
 */
Ext.define('Editor.util.dom.Manipulation', {

    requires:[
        'Editor.util.dom.Selection'
    ],

    /**
     * @var {Object}
     */
    decoration: {},

    /**
     * @var {Element[]}
     */
    created: [],

    /**
     * @var {Editor.util.dom.Selection}
     */
    selection: null,

    /**
     * Retrieves the underlying dom-selection
     * @returns {Editor.util.dom.Selection}
     */
    getSelection: function() {
        return this.selection;
    },

    /**
     * Sets the Dom-Selection to use
     * @param {Editor.util.dom.Selection} domSelection
     */
    setSelection: function(domSelection) {
        this.selection = domSelection;
    },

    /**
     * Selects a chain of nodes in the given target by text-indices
     * The ignoredElements param defines the tags to ignore, see Editor.util.dom.Selection setIgnored
     * @param {Element} target
     * @param {int} from
     * @param {int} to
     * @param {Object[]} ignoredElements
     * @returns {Editor.util.dom.Manipulation}
     */
    selectIndices: function(target, from, to, ignoredElements) {
        this.selection = Ext.create('Editor.util.dom.Selection');
        this.selection
            .setTarget(target)
            .setIgnored(ignoredElements)
            .setIndices(from, to);
        return this;
    },

    /**
     * Selects a chain of nodes in the given target with a DOM-range
     * The ignoredElements param defines the tags to ignore, Editor.util.dom.Selection setIgnored
     * @param {Element} target
     * @param {Range} range
     * @param {Object[]} ignoredElements
     * @returns {Editor.util.dom.Manipulation}
     */
    selectRange: function(target, range, ignoredElements) {
        this.selection = Ext.create('Editor.util.dom.Selection');
        this.selection
            .setTarget(target)
            .setIgnored(ignoredElements)
            .setRange(range);
        return this;
    },

    /**
     * Adds the specified Element to decorate the given range defined by text-indices
     * Returns the created/added Elements (which may be multiple depending on the structure!)
     * @param {string} tagName
     * @param {Array} classes
     * @param {Object} attributes
     * @param {string} textDataName
     * @returns {Element[]}
     */
    decorate: function(tagName, classes = [], attributes = null, textDataName = null){
        this.decoration.tagName = tagName;
        this.decoration.classes = classes;
        this.decoration.attributes = attributes;
        this.created = [];

        // now insert the decorator nodes
        if(this.selection.getSize() > 0){
            this.insert();
        }

        // if wanted, add the decorated text-content as a special data-attribute for all nodes
        if(textDataName !== null){
            for(var element of this.created){
                element.setAttribute(textDataName, this.selection.getText());
            }
        }
        return this.created;
    },

    /**
     * Removes the complete selection from the DOM
     */
    remove: function(){
        for(var node of this.selection.getNodes()){ /* @var {Node} node */
            if(node.nodeType === Node.TEXT_NODE){

                node.parentNode.removeChild(node);

            } else if(node.nodeType === Node.ELEMENT_NODE && this.selection.isFullySelected(node)) {

                node.remove();
            }
        }
    },

    /**
     * Decocarates the selected chain by inserting decorator-nodes
     * the created chain on this point can have only the following morphology (with maybe more levels)
     * "__|‾‾" OR "‾‾|__", constantly ascending or descending (sketching the hirarchy level)
     */
    insert: function(){
        var selectedNodes = this.selection.getNodes();
        if(selectedNodes.length === 1){
            // a single element can be wrapped directly
            if(this.selection.isFullySelected(selectedNodes[0])){
                this.wrap(selectedNodes);
            }
        } else {
            var idx = 0,
                nodes = [],
                parent = selectedNodes[0].parentNode,
                ended = false;
            while(!ended){
                // create sub-chain with all nodes in the same parent
                // only when the next element is a level down, we do not include it, as
                // this is a partial rendering of the next element and we must not decorate it completely
                while(idx < selectedNodes.length && selectedNodes[idx].parentNode === parent
                && this.selection.isFullySelected(selectedNodes[idx])){
                    nodes.push(selectedNodes[idx]);
                    idx++;
                }
                if(nodes.length > 0){
                    // decorate the sub-chain
                    this.wrap(nodes);
                } else {
                    // an empty sub-chain usually hints at a partly rendered tag wich therefore will not be wrapped ...
                    idx++; // crucial: otherwise we get an endless loop!
                }

                if(idx >= selectedNodes.length){
                    // we reached the end!
                    ended = true;
                } else {
                    // we reached the point where we move a level up or down in the node hierarchy
                    // we restart the sub-chain and set the current parent to the new levels parent
                    // if the element before is a level down, we do not include the current as it is
                    // only decorated partially and was already decorated on the inside
                    if(!this.selection.isFullySelected(selectedNodes[idx])){
                        idx++;
                    }
                    nodes = [];
                    parent = selectedNodes[idx].parentNode;
                }
            }
        }
    },

    /**
     * Wraps the given nodes into an element
     * The passed nodes must have the identical parent !!
     * @param {Node[]} nodes
     */
    wrap: function(nodes){
        if(nodes.length > 0){
            var wrapper = this.createDocorator(),
                node = nodes[0];
            // insert wrapper before el in the DOM tree
            node.parentNode.insertBefore(wrapper, node);
            // move nodes into wrapper
            for(node of nodes){
                wrapper.appendChild(node);
            }
            this.created.push(wrapper);
        }
    },

    /**
     * Creates a DOM Element with the current decoration props
     * @returns {Element}
     */
    createDocorator: function(){
        var item, ele = this.selection.getTarget().ownerDocument.createElement(this.decoration.tagName);
        for(item of this.decoration.classes){
            ele.classList.add(item);
        }
        if(this.decoration.attributes !== null){
            for(item in this.decoration.attributes){
                ele.setAttribute(item, this.decoration.attributes[item]);
            }
        }
        return ele;
    }
});
