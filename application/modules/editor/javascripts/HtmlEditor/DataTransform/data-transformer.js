import Node from "./node";
import TagsConversion from "../TagsTransform/tags-conversion";
import TagCheck from "../TagsTransform/tag-check";

export default class DataTransformer {
    #userCanModifyWhitespaceTags;
    #userCanInsertWhitespaceTags;

    /**
     * @param {TagsConversion} tagsConversion
     * @param {NodeListOf<ChildNode>} items
     * @param {NodeListOf<ChildNode>|Array} referenceItems
     * @param {Boolean} userCanModifyWhitespaceTags
     * @param {Boolean} userCanInsertWhitespaceTags
     */
    constructor(
        tagsConversion,
        items,
        referenceItems,
        userCanModifyWhitespaceTags,
        userCanInsertWhitespaceTags
    ) {
        this._tagsConversion = tagsConversion;
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

    #transform(items, referenceItems = []) {
        this.#parseReferenceItems(this.#transformItems(referenceItems));
        this._tagCheck = new TagCheck(
            this._referenceTags,
            this._tagsConversion,
            this.#userCanModifyWhitespaceTags,
            this.#userCanInsertWhitespaceTags
        );
        this._transformedNodes = this.#transformItems(items, true);
    }
    
    transformPartial(items) {
        const nodes = this.#transformItems(items, true);

        let result = '';
        for (const node of nodes) {
            result += node._transformed.outerHTML !== undefined ? node._transformed.outerHTML : node._transformed.textContent;
        }

        return result;
    }

    /**
     * Transform data from editor format to t5 internal format (replace images to div-span tags structure)
     *
     * @param data
     * @returns {{data: string, checkResult: CheckResult}}
     */
    reverseTransform(data) {
        let checkResult = this._tagCheck.checkTags(data);

        return {"data": this.#reverseTransformItems(data), "checkResult": checkResult};
    }

    toString() {
        let result = "";

        for (const node of this._transformedNodes) {
            result += node._transformed.outerHTML !== undefined ? node._transformed.outerHTML : node._transformed.textContent;
        }

        return result;
    }

    #transformItems(items, useReference = false) {
        let result = [];

        for (const item of items) {
            let node;

            if (this._tagsConversion.isInternalTagNode(item) && useReference) {
                const type = this._tagsConversion.getInternalTagType(item);
                const tagNumber = this._tagsConversion.getInternalTagNumber(item);

                switch (type) {
                    case TagsConversion.TYPE.OPEN:
                        node = this.#getOpeningReferenceTagAtIndex(tagNumber);

                        if (!node) {
                            continue;
                        }

                        break;

                    case TagsConversion.TYPE.CLOSE:
                        node = this.#getClosingReferenceTagAtIndex(tagNumber);

                        if (!node) {
                            continue;
                        }

                        break;

                    case TagsConversion.TYPE.WHITESPACE:
                        node = this.#getWhitespaceReferenceTagAtIndex(tagNumber);

                        if (!node && this._tagCheck.isAllowedAddingWhitespaceTags()) {
                            node = new Node(item, this._tagsConversion.transform(item));
                        }

                        if (!node) {
                            continue;
                        }

                        break;

                    case TagsConversion.TYPE.SINGLE:
                        node = this.#getSingleReferenceTagAtIndex(tagNumber);

                        if (!node) {
                            continue;
                        }

                        break;
                }

                if (!this._transformedTags[type][tagNumber]) {
                    this._transformedTags[type][tagNumber] = node;
                }
            } else {
                node = new Node(item, this._tagsConversion.transform(item));
            }

            if (node) {
                result.push(node);

                if (item.childNodes.length) {
                    node._children = this.#transformItems(item.childNodes, useReference);
                }
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
                result += this._transformedTags[tagType][tagNumber]._original.outerHTML;

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

            // other elements like spellcheck nodes etc.
            if (item.childNodes.length) {
                result += this.#reverseTransformItems(item);

                continue;
            }

            result += item.data;
        }

        return result;
    }

    #parseReferenceItems(items) {
        for (const item of items) {
            if (
                this._tagsConversion.isInternalTagNode(item._original)
                || this._tagsConversion.isWhitespaceNode(item._original)
            ) {
                const tagType = this._tagsConversion.getInternalTagType(item._original);
                const tagNumber = this._tagsConversion.getInternalTagNumber(item._original);
                this._referenceTags[tagType][tagNumber] = item;
            }

            if (item._children.length) {
                this.#parseReferenceItems(item._children);
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
}
