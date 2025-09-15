import Node from "./node";
import TagsConversion from "../TagsTransform/tags-conversion";
import TagCheck from "../TagsTransform/tag-check";
import PixelMapping from "../TagsTransform/pixel-mapping";

const htmlEncode = require('js-htmlencode').htmlEncode;

export default class DataTransformer {
    #userCanModifyWhitespaceTags;
    #userCanInsertWhitespaceTags;
    #font;

    /**
     * @param {TagsConversion} tagsConversion
     * @param {Font} font
     * @param {NodeListOf<HTMLElement>} items
     * @param {NodeListOf<HTMLElement>|Array<HTMLElement>} referenceItems
     * @param {Boolean} userCanModifyWhitespaceTags
     * @param {Boolean} userCanInsertWhitespaceTags
     */
    constructor(
        tagsConversion,
        font,
        items,
        referenceItems,
        userCanModifyWhitespaceTags,
        userCanInsertWhitespaceTags
    ) {
        this._tagsConversion = tagsConversion;
        this.#font = font;
        this._referenceTags = {
            [TagsConversion.TYPE.SINGLE]: {},
            [TagsConversion.TYPE.OPEN]: {},
            [TagsConversion.TYPE.CLOSE]: {},
            [TagsConversion.TYPE.WHITESPACE]: {},
        };
        this._transformedTags = {
            [TagsConversion.TYPE.SINGLE]: {},
            [TagsConversion.TYPE.OPEN]: {},
            [TagsConversion.TYPE.CLOSE]: {},
            [TagsConversion.TYPE.WHITESPACE]: {},
        };
        this._transformedNodes = [];
        this.#userCanModifyWhitespaceTags = userCanModifyWhitespaceTags;
        this.#userCanInsertWhitespaceTags = userCanInsertWhitespaceTags;

        this.#transform(items, referenceItems);
    }

    /**
     * @param {NodeListOf<HTMLElement>} items
     * @param {NodeListOf<HTMLElement>} referenceItems
     */
    #transform(items, referenceItems = []) {
        this.#retrieveTags(this.#transformItems(referenceItems), this._referenceTags);
        this._tagCheck = new TagCheck(
            this._referenceTags,
            this._tagsConversion,
            this.#userCanModifyWhitespaceTags,
            this.#userCanInsertWhitespaceTags
        );
        this._transformedNodes = this.#transformItems(items);
        this.#retrieveTags(this._transformedNodes, this._transformedTags);
    }

    /**
     * @param {NodeListOf<HTMLElement>} items
     * @returns {string}
     */
    transformPartial(items) {
        const nodes = this.#transformItems(items);
        this.#retrieveTags(nodes, this._transformedTags);

        let result = '';
        for (const node of nodes) {
            result += node._transformed.outerHTML !== undefined ? node._transformed.outerHTML : htmlEncode(node._transformed.textContent);
        }

        return result;
    }

    /**
     * @param {HTMLElement} whitespce
     * @returns {*}
     */
    transformWhitespace(whitespce) {
        const items = this.#transformItems([whitespce], true);
        this.#retrieveTags(items, this._transformedTags);

        return items.pop();
    }

    /**
     * Transform data from editor format to t5 internal format (replace images to div-span tags structure)
     *
     * @param data
     * @returns {{data: string, checkResult: CheckResult}}
     */
    reverseTransform(data) {
        let checkResult = this._tagCheck.checkTags(data);

        // Replace encoded spaces is done here because of the processing of the data on the server side
        // The editor is mixing normal spaces with encoded spaces, so we need to replace them back
        return {"data": this.#replaceEncodedSpaces(this.#reverseTransformItems(data)), "checkResult": checkResult};
    }

    toString() {
        let result = "";

        for (const node of this._transformedNodes) {
            result += node._transformed.outerHTML !== undefined ? node._transformed.outerHTML : htmlEncode(node._transformed.textContent);
        }

        return result;
    }

    /**
     * @param {int} tagNumber
     * @returns {boolean}
     */
    hasSingleReferenceTag(tagNumber) {
        return this._referenceTags[TagsConversion.TYPE.SINGLE][tagNumber] !== undefined;
    }

    getSingleReferenceTag(tagNumber) {
        return this.#getSingleReferenceTagAtIndex(tagNumber);
    }

    /**
     * @param {int} tagNumber
     * @returns {boolean}
     */
    hasPairedReferenceTag(tagNumber) {
        return this._referenceTags[TagsConversion.TYPE.OPEN][tagNumber] !== undefined
            && this._referenceTags[TagsConversion.TYPE.CLOSE][tagNumber] !== undefined;
    }

    getPairedReferenceTag(tagNumber) {
        return {
            "open": this.#getOpeningReferenceTagAtIndex(tagNumber),
            "close": this.#getClosingReferenceTagAtIndex(tagNumber),
        };
    }

    /**
     * @param {NodeListOf<HTMLElement>} items
     * @param {Boolean} reference
     * @returns {Array<Node>}
     */
    #transformItems(items, reference = false) {
        let result = [];
        const pixelMapping = new PixelMapping(this.#font);

        for (const item of items) {
            const transformed = this._tagsConversion.transform(item, pixelMapping);

            if (! transformed) {
                continue;
            }

            let node = new Node(item, transformed);

            result.push(node);

            if (item.childNodes.length) {
                node._children = this.#transformItems(item.childNodes);
            }
        }

        return result;
    }

    #reverseTransformItems(data) {
        let result = '';

        for (const item of data.childNodes) {
            if (this._tagsConversion.isInternalTagNode(item)) {
                const tagType = this._tagsConversion.getInternalTagType(item);
                const tagNumber = this._tagsConversion.getInternalTagNumber(item);
                result += this._referenceTags[tagType][tagNumber]?._original.outerHTML
                    ?? this._transformedTags[tagType][tagNumber]?._original.outerHTML
                    ?? '';

                continue;
            }

            if (this._tagsConversion.isTermNode(item)) {
                result += this.#reverseTransformItems(item);

                continue;
            }

            if (this._tagsConversion.isTrackChangesNode(item)) {
                const node = item.cloneNode(true);
                node.innerHTML = this.#reverseTransformItems(node);
                result += node.outerHTML;

                continue;
            }

            if (this._tagsConversion.isMQMNode(item)) {
                result += item.outerHTML;

                continue;
            }

            // other elements like spellcheck nodes etc.
            if (item.childNodes.length) {
                result += this.#reverseTransformItems(item);

                continue;
            }

            try {
                result += this.#htmlEncode(item.data);
            } catch (e) {
                // item is supposed to be a text node, but it is not
                // This issue is not reproducible, so adding a log info here for debugging purposes
                // TRANSLATE-4895
                console.warn("DataTransformer: Unable to encode data", e);
                console.log(this.#stringifyNodeForLogging(item));

                if (jslogger) {
                    jslogger.logException(e);
                }
            }
        }

        return result;
    }

    #retrieveTags(items, tags) {
        for (const item of items) {
            if (
                this._tagsConversion.isInternalTagNode(item._original)
                || this._tagsConversion.isWhitespaceNode(item._original)
            ) {
                const tagType = this._tagsConversion.getInternalTagType(item._original);
                const tagNumber = this._tagsConversion.getInternalTagNumber(item._original);
                tags[tagType][tagNumber] = tags[tagType][tagNumber] || item;

                continue;
            }

            if (item._children.length) {
                this.#retrieveTags(item._children, tags);
            }
        }
    }

    #getReferenceTagAtIndex(type, index) {
        return this._referenceTags[type][index] ?? null;
    }

    #getOpeningReferenceTagAtIndex(index) {
        return this.#getReferenceTagAtIndex(TagsConversion.TYPE.OPEN, index);
    }

    #getClosingReferenceTagAtIndex(index) {
        return this.#getReferenceTagAtIndex(TagsConversion.TYPE.CLOSE, index);
    }

    #getSingleReferenceTagAtIndex(index) {
        return this.#getReferenceTagAtIndex(TagsConversion.TYPE.SINGLE, index);
    }

    #getWhitespaceReferenceTagAtIndex(index) {
        return this.#getReferenceTagAtIndex(TagsConversion.TYPE.WHITESPACE, index);
    }

    #htmlEncode(string) {
        return string.replace(/[\u00A0-\u9999<>&]/g, i => '&#'+i.charCodeAt(0)+';');
    }

    #replaceEncodedSpaces(string) {
        return string.replace(/&nbsp;/g, ' ');
    }

    /**
     * @param {HTMLElement} node
     * @returns {string}
     */
    #stringifyNodeForLogging(item) {
        if (! item) {
            return 'The item is empty';
        }

        if (item.nodeType === 3) {
            return 'The item is a text: ' + item.textContent;
        }

        return 'The item is a dom node: ' + item.outerHTML;
    }
}
