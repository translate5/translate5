
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
 * General DOM selection Helper
 * It is tailored to work with text-indices and to select/decorate DOM-nodes by text-indices
 * In this process nodes can be ignored (internal-tags) but to keep the code universally usable,
 * this has to be set-up by using the .addIgnored()-API
 * Generally, the tool selects nodes for further processing with the dom-manipulation
 * The Morphology of the selected nodes can be like (sketching the hirarchy levels):
 *
 * __|‾‾ OR ‾‾|__ ‾‾|__|‾‾ OR __|‾‾|__
 *
 * with the left/right ends having more ramifications in the same direction up/down
 *
 */
Ext.define('Editor.util.dom.Selection', {

    /**
     * @var {Element}
     */
    target: null,

    /**
     * @var {Object}
     */
    ignored: {},

    /**
     * @var {string}
     */
    text: '',

    /**
     * @var {Node[]}
     */
    selection: [],

    /**
     * @var {WeakMap}
     */
    selectionMap: null,

    /**
     * @var {string}
     */
    selectedText: '',

    /**
     * Sets the target Element where to select in
     * @param {Element} element
     * @returns {Editor.util.dom.Selection}
     */
    setTarget: function(element){
        this.target = element;
        return this;
    },

    /**
     * Defines the ignored Elements as an array of ignore-entries, e.g.
     *
     * [
     *    { "tag": "div", "classes": ["classA", "classB"], "placeholder": "€" },
     *    ...
     * ]
     *
     * @param {Object[]} ignoredElements
     * @returns {Editor.util.dom.Selection}
     */
    setIgnored: function(ignoredElements){
        for(var item of ignoredElements){
            this.addIgnored(
                item.tag,
                item.hasOwnProperty('classes') ? item.classes : [],
                item.hasOwnProperty('placeholder') ? item.placeholder : ''
            );
        }
        return this;
    },

    /**
     * Adds a selector for Elements to ignore
     * An Element will be ignored, if it has a node-name and all of the classes passed
     * If a placeholder is passed, the ignored tag will count with the length of the placeholder
     * Keep in mind, ignored element do not count for text-indices even if they have content unless a placeholder is given
     * Ignored Elements will be part of a selection if they are within the selected text
     * The order of ignored definitions matters, e.g. if having rule for "classA classB" and just "classA"
     * "classA classB" must come first
     * @param {string} tagName
     * @param {string[]} cssClasses
     * @param {string} placeholder
     * @returns {Editor.util.dom.Selection}
     */
    addIgnored: function(tagName, cssClasses= [], placeHolder = '') {
        tagName = tagName.toLowerCase();
        if(!this.ignored.hasOwnProperty(tagName)){
            this.ignored[tagName] = [];
        }
        this.ignored[tagName].push({ "tag": tagName, "classes": cssClasses, "placeholder": placeHolder });
        return this;
    },

    /**
     * Retrieves the set target
     * @returns {Element}
     */
    getTarget: function() {
        return this.target;
    },

    /**
     * Retrieves te text-content of the target element (ignoring ignored tags!)
     * Can be called without selecting
     * @returns {string}
     */
    getTargetText: function() {
        this.text = '';
        // state object to track several stuff
        var state = {
            chain: [],
            ended: false,
            started: true,
            pos: 0,
            search: false
        };
        // walks the nodes
        this.walk(this.target, state);

        return this.text;
    },

    /**
     * Retrieves the text-content of the selection (ignoring ignored tags!)
     * Can only be called after selecting
     * @returns {string}
     */
    getText: function() {
        return this.selectedText;
    },

    /**
     * Retrieves the chain of selected Nodes
     * Can only be called after selecting
     * @returns {Node[]}
     */
    getNodes: function() {
        return this.selection;
    },

    /**
     * Retrieves the number of selected nodes
     * @returns {int}
     */
    getSize: function() {
        return this.selection.length;
    },

    /**
     * Checks, if a node are completely part of the selection and e.g. shall be wrapped
     * This excludes Elements, that have only a partial branch in them
     * The wrapped-map was evaluated when condensing the chain
     * @param {Node} node
     * @returns {boolean}
     */
    isFullySelected: function(node) {
        if(node.nodeType === Node.ELEMENT_NODE
            && !this.isIgnoredElement(node)
            && !this.selectionMap.has(node)) {
            return false;
        }
        return true;
    },

    /**
     * Selects a chain of nodes by start & end index for the current target
     * @param {int} from
     * @param {int} to
     * @returns {Editor.util.dom.Selection}
     */
    setIndices: function(from, to) {
        if(to <= from){
            throw new Error('setIndices: "from" must be lower than "to"!');
        }
        this.init();
        // state object to track several stuff
        var state = {
            chain: [],
            from: from,
            to : to,
            ended: false,
            started: false,
            pos: 0,
            search: true
        };
        // evaluates the chain of nodes
        this.walk(this.target, state);
        // when parent-nodes of start & end nodes are present in the chain and
        // the start/end elements are the first/last node in their Elements, we prepend/append the parent
        // this way, they will be condensed, and we avoid fragmentation
        if(state.chain.length > 0){
            this.selection = this.simplify(state.chain);
            this.selectedText = this.text.substring(from, to);
        }
        return this;
    },

    /**
     * searches the children of an element for start & end of the selection
     * @param {Element} element
     * @param {Object} state
     */
    walk: function(element, state){
        // add element to chain if it already started
        if(state.started && !state.ended){
            state.chain.push(element);
        }
        // get the placeholder-length of an ignored element (-1, if element is not ignored)
        var placeholderLength = this.getIgnoredLength(element);
        if(placeholderLength > -1){

            // some ignored elements (internal whitespace tags) have a length!
            state.pos += placeholderLength;
            if(state.pos >= state.from){
                state.started = true;
            }
        } else if(element.hasChildNodes()){
            // iterate a tags childNodes to get the full chain
            var i, child;
            for(i = 0; i < element.childNodes.length; i++){
                child = element.childNodes[i];
                if(state.ended){
                    break;
                } else {
                    if(child.nodeType === Node.ELEMENT_NODE){
                        this.walk(child, state);
                    } else if(child.nodeType === Node.TEXT_NODE) {
                        this.text += child.textContent;
                        // search-mode: explore text-nodes
                        if(state.search){
                            i += this.walkText(child, state, child.textContent.length);
                        }
                    }
                }
            }
            // elements with children are added with their end-tag again
            // this way we detect children, that can be excluded from the rendering:
            // we condense tags that are in the chain with start and end ...
            if(state.started && !state.ended){
                state.chain.push(element);
            }
        }
    },

    /**
     * Searches in a text-node for start/end
     * @param {Text} node
     * @param {Object} state
     * @param {int} length: the length of the passed text-node
     * @returns {int} the number of added text-nodes
     */
    walkText: function(node, state, length){
        // when we add nodes, the calling loop needs to skip those for a consistent logic
        var cut, logState, nodeOffset = 0;
        // node starts the decoration
        if(!state.started && (state.pos + length) > state.from){
            state.started = true;
            // if area starts within, cut node, set new part as current node
            if(state.from > state.pos){
                cut = state.from - state.pos;
                // QUIRK: this check should not be neccessary
                if(cut > 0 && cut < length){
                    node = node.splitText(cut);
                    nodeOffset++;
                } else if(window.jslogger){
                    // TODO: remove logging when TRANSLATE-3702 is solved
                    logState = Ext.clone(state);
                    logState.chain = logState.chain.length;
                    window.jslogger.addLogEntry({
                        type: 'info',
                        message: 'selection state: ' + JSON.stringify(logState).replaceAll('"', '~')
                    });
                }
                state.pos = state.from;
                length = node.textContent.length;
            }
        }
        if(state.started){
            // add node to chain
            state.chain.push(node);
            if(state.to < (state.pos + length)){
                // if area ends within, cut node, finish
                cut = state.to - state.pos;
                // QUIRK: this check should not be neccessary
                if(cut > 0 && cut < length){
                    node.splitText(cut);
                    nodeOffset++;
                } else if(window.jslogger){
                    // TODO: remove logging when TRANSLATE-3702 is solved
                    logState = Ext.clone(state);
                    logState.chain = logState.chain.length;
                    window.jslogger.addLogEntry({
                        type: 'info',
                        message: 'selection state: ' + JSON.stringify(logState).replaceAll('"', '~')
                    });
                }
                state.ended = true;
                state.pos = state.to;
            } else if(state.to === (state.pos + length)){
                // if area ends exactly after node just finish
                state.ended = true;
                state.pos = state.to;
            } else {
                state.pos += length;
            }
        } else {
            state.pos += length;
        }
        return nodeOffset;
    },

    /**
     * Selects a chain by using a DOM Range.
     * Obviously this Range must be within our target element, if not, nothing will be selected
     * @param {Range} range
     * @returns {Editor.util.dom.Selection}
     */
    setRange: function(range) {
        this.init();
        var state = {
            chain: [],
            ended: false,
            started: false
        };
        // we normalize the range for our purposes
        // text-nodes will be splitted if neccessary at the defined offset,
        // element-nodes with offset will instead use the referenced child as start/end
        if(range.startContainer.nodeType === Node.ELEMENT_NODE){
            if(range.startContainer.hasChildNodes() && range.startOffset > 0){
                if(range.startOffset < range.startContainer.childNodes.length - 1){
                    // the range starts at an element child. We use this as start
                    // and add it to the fully selected if neccessary
                    state.start = range.startContainer.childNodes[range.startOffset];
                    if(state.start.nodeType === Node.ELEMENT_NODE){
                        this.selectionMap.set(state.start, true);
                    }
                } else {
                    throw new Error('Invalid Range: startOffset of startContainer element is invalid!');
                }
            } else {
                // start-container is fully selected
                state.start = range.startContainer;
                this.selectionMap.set(range.startContainer, true);
            }
        } else if(range.startContainer.nodeType === Node.TEXT_NODE){
            if(range.startOffset === 0){
                // range starts with the full text-node
                state.start = range.startContainer;
            } else {
                // we must split the referenced node
                state.start = range.startContainer.splitText(range.startOffset);
            }
        } else {
            throw new Error('Invalid Range: Range references start-node that cannot be selected!');
        }
        if(range.endContainer.nodeType === Node.ELEMENT_NODE){
            if(range.endContainer.hasChildNodes() && range.endOffset < range.endContainer.childNodes.length - 1){
                // partially selected node: take child before end as end-node
                // and add it to the fully selected if neccessary
                state.end = range.endContainer.childNodes[range.endOffset];
                if(state.end.nodeType === Node.ELEMENT_NODE){
                    this.selectionMap.set(state.end, true);
                }
            } else {
                // end-container is fully selected
                state.end = range.endContainer;
                this.selectionMap.set(range.endContainer, true);
            }
        } else if(range.endContainer.nodeType === Node.TEXT_NODE){
            if(range.endOffset >= range.endContainer.textContent.length - 1){
                // range ends with the full text-node
                state.end = range.endContainer;
            } else {
                // we must split the referenced node and the range end is also our end
                state.end = range.endContainer;
                range.endContainer.splitText(range.endOffset);
            }
        } else {
            throw new Error('Invalid Range: Range references end-node that cannot be selected!');
        }

        // evaluates the chain of nodes
        this.walkRange(this.target, state);

        // when parent-nodes of start & end nodes are present in the chain and
        // the start/end elements are the first/last node in their Elements, we prepend/append the parent
        // this way, they will be condensed, and we avoid fragmentation
        if(state.chain.length > 0){
            this.selection = this.simplify(state.chain);
        }
        return this;
    },

    /**
     * searches the children of an element for start & end of the decoration
     * @param {Node} node
     * @param {Object} state
     */
    walkRange: function(node, state){
        // start chain if element is the start-element
        if(node === state.start){
            state.started = true;
        }
        // add element to chain if it already started
        if(state.started && !state.ended){
            state.chain.push(node);
            // collect selected texts
            if(node.nodeType === Node.TEXT_NODE){
                this.selectedText += node.textContent;
            }
        }
        // element-nodes with children will be iterated
        if(node.nodeType === Node.ELEMENT_NODE && !this.isIgnoredElement(node) && node.hasChildNodes()){
            for(var i = 0; i < node.childNodes.length; i++){
                if(state.ended){
                    break;
                } else {
                    this.walkRange(node.childNodes[i], state);
                }
            }
            // elements with children are added with their end-tag again
            // this way we detect children, that can be excluded from the rendering:
            // we condense tags that are in the chain with start and end ...
            if(state.started && !state.ended){
                state.chain.push(node);
            }
        }
        // we evaluate the end-state for elements at last so that all children are captured
        if(node === state.end){
            state.ended = true;
        }
    },

    /**
     * Simplify a chain, prepends/appends the start/end-node if all children of an element are selected
     * what will then be condensed in the next step
     * @param {Node[]} chain
     * @returns {Node[]}
     */
    simplify: function(chain){
        var first = chain[0],
            last = chain[chain.length - 1];

        if(first !== last){

            // prepend/append first/last parents if they shall be decorated as a whole
            if(first.parentElement.firstChild === first
                && this.chainContains(chain, first.parentElement, 0)){
                chain.unshift(first.parentElement);
            }
            if(last.parentElement.lastChild === last
                && this.chainContains(chain, last.parentElement, 0)){
                chain.push(last.parentElement);
            }
            // now condense the chain. Condensing means, that all nodes, that appear twice in the chain
            // can be reduced to one removing everything in-between
            this.condense(chain);
            return chain;

        } else {
            // rare case: first element equals last element
            // meaning the decoration must only be applied to a full tag
            return [ first ];
        }
    },

    /**
     * Condenses a chain of nodes and marks the completely contained nodes as wrapped in the selectionMap
     * @param {Node[]} chain
     */
    condense: function(chain){
        if(chain.length === 1){
            return;
        }
        var lastIdx, idx = 0;
        while(idx < (chain.length - 1)){
            // if a node has start & end in the chain, we condense it to a single node
            // and mark it as "fully wrapped"
            lastIdx = this.lastPosInChain(chain, idx);
            if(lastIdx > idx){
                chain.splice(idx + 1, lastIdx - idx);
                this.selectionMap.set(chain[idx], true);
            }
            idx++;
        }
    },

    /**
     * checks, if a chain of nodes contains the given element
     * @param {Node[]} chain
     * @param {Node} node
     * @param {int} startIdx
     * @returns {boolean}
     */
    chainContains: function(chain, node, startIdx){
        for(var i = startIdx; i < chain.length; i++){
            if(chain[i] === node){
                return true;
            }
        }
        return false;
    },

    /**
     * retrieves the last position of an element in the chain or -1, if it is not chained multiple times
     * @param {Node[]} chain
     * @param {int} idx
     * @returns {int}
     */
    lastPosInChain: function(chain, idx){
        for(var i = (chain.length - 1); i > idx; i--){
            if(chain[idx] === chain[i] && idx !== i){
                return i;
            }
        }
        return -1;
    },

    /**
     * Checks if an element is ignored
     * @param {Element} element
     * @returns {boolean}
     */
    isIgnoredElement: function(element){
        var tagName = element.nodeName.toLowerCase();
        if(tagName in this.ignored){
            for(var item of this.ignored[tagName]){
                if(this.elementHasClasses(element, item.classes)){
                    return true;
                }
            }
        }
        return false;
    },

    /**
     * Retrives the placeholder text-length of an ignored Element, if the element is not ignored, -1 is returned
     * @param {Element} element
     * @returns {int}
     */
    getIgnoredLength: function(element){
        var tagName = element.nodeName.toLowerCase();
        if(tagName in this.ignored){
            for(var item of this.ignored[tagName]){
                if(this.elementHasClasses(element, item.classes)){
                    if(item.placeholder !== ''){
                        // QUIRK: do we have whitespace tags with an amount greater 1 ? currently not
                        return item.placeholder.length * this.parseLengthAttribute(element);
                    }
                    return 0;
                }
            }
        }
        return -1;
    },

    /**
     * Retrives the value of a potential length-attribute in an Element
     * The length-attribute will only be parsed, if it is a number
     * @param {Element} element
     * @returns {int}
     */
    parseLengthAttribute: function(element){
        var length = (element.dataset && element.dataset.length) ? String(element.dataset.length) : '';
        length = (/^[0-9]+$/.test(length)) ? parseInt(length) : 1;

        // TODO: remove when TRANSLATE-3702 is solved
        if(length > 2 && window.jslogger){
            window.jslogger.addLogEntry({
                type: 'info',
                message: 'Whitespath length: length-attribute: ~' + element.dataset.length + '~, evaluated length: ~' + length + '~'
            });
        }
        return length;
    },

    /**
     * Checks, if an Element has all given classes
     * @param {Element} element
     * @param {string[]} cssClasses
     * @returns {boolean}
     */
    elementHasClasses: function(element, cssClasses) {
        for(var cssClass of cssClasses){
            if(!element.classList.contains(cssClass)){
                return false;
            }
        }
        return true;
    },

    init: function(){
        this.text = '';
        this.selection = [];
        this.selectionMap = new WeakMap();
        this.selectedText = '';
    }
});
