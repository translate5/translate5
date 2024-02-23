class TagCheck {
    constructor(referenceTags, idPrefix) {
        this.referenceTags = referenceTags;
        this.idPrefix = idPrefix;
    }

    checkContentTags(nodeList, markupImagesCache) {
        let foundIds = [],
            ignoreWhitespace = this.shouldIgnoreWhitespaceTags();

        let duplicatedTags = [];
        let excessTags = [];

        for (let node of nodeList) {
            if (node.parentNode && node.parentNode.nodeName.toLowerCase() === "del") {
                // Ignore deleted nodes
                continue;
            }

            // node id can have word locked followed by number, so we need to use negative lookbehind
            const match = node.id.match(new RegExp(this.idPrefix + '([a-zA-Z]+)(\\d+)'));

            if (!match) {
                continue;
            }

            let type = match[1];

            if (type.includes('locked')) {
                type = type.replace('locked', '');
            }

            const number = parseInt(match[2], 10);
            const id = type + number;
            const originalId = match[1] + match[2];

            let isQaTag = /qmflag/.test(node.className);

            if (isQaTag) {
                continue;
            }

            let isWhitespaceTag = /whitespace/.test(node.className);

            //ignore whitespace and nodes without ids
            if ((isWhitespaceTag && ignoreWhitespace)
                || /^\s*$/.test(node.id)
            ) {
                continue;
            }

            // data.nr can be prefixed by the word locked, so we need to remove that to get the number of the tag
            if (!this.referenceTags[type].find(t => parseInt(t.data.nr.replace('locked', '')) === number)) {
                if (isWhitespaceTag && this.isAllowedAddingWhitespaceTags()) {
                    continue;
                }

                excessTags.push(markupImagesCache[originalId]);
            }

            if (foundIds.includes(id) && node.parentNode.nodeName.toLowerCase() !== "del") {
                duplicatedTags.push(markupImagesCache[originalId]);
            } else {
                if (node.parentNode.nodeName.toLowerCase() !== "del") {
                    foundIds.push(id);
                }
            }
        }

        let missingTags = [];
        for (const [type, tags] of Object.entries(this.referenceTags)) {
            for (const tag of tags) {
                if (ignoreWhitespace && type === 'whitespace') {
                    continue;
                }

                // Check if tag is deleted keeping in mind that data.nr can be prefixed by the word locked
                if (!foundIds.includes(type + tag.data.nr.replace('locked', ''))) {
                    missingTags.push(tag);
                }
            }
        }

        return new CheckResult(missingTags, duplicatedTags, excessTags);
    }

    checkTagOrder(nodeList) {
        let me = this,
            open = {},
            clean = true;

        for (let img of nodeList) {
            // crucial: for the tag-order, we only have to check tags that are not already deleted
            if (!me.isDeletedTag(img)) {
                if (me.isDuplicateSaveTag(img) || /^remove/.test(img.id) || /(-single|-whitespace)/.test(img.id)) {
                    //ignore tags marked to remove
                    continue;
                }

                if (/-open/.test(img.id)) {
                    open[img.id] = true;

                    continue;
                }

                let replaced = img.id.replace(/-close/, '-open');

                if (!open[replaced]) {
                    clean = false;

                    break;
                }
            }
        }

        return clean;
    }

    /**
     * Checks if a tag is inside a del-tag and thus can be regarded as a deleted tag
     * @param {Node} node
     */
    isDeletedTag(node) {
        while (node.parentElement && node.parentElement.tagName.toLowerCase() !== 'body') {
            if (node.parentElement.tagName.toLowerCase() === 'del') {
                return true;
            }

            node = node.parentElement;
        }

        return false;
    }

    /**
     * returns true if given html node is a duplicatesavecheck img tag
     * @param {} img
     * @return {Boolean}
     */
    isDuplicateSaveTag(img) {
        return img.tagName === 'IMG' && img.className && /duplicatesavecheck/.test(img.className);
    }

    /**
     * @returns {Boolean}
     */
    shouldIgnoreWhitespaceTags() {
        return !!Editor.app.getTaskConfig('segments.userCanModifyWhitespaceTags');
    }

    /**
     * @returns {boolean}
     */
    isAllowedAddingWhitespaceTags() {
        return !!Editor.app.getTaskConfig('segments.userCanInsertWhitespaceTags');
    }

    getReferenceTagAtIndex(type, index) {
        return this.referenceTags[type][index] !== undefined ? this.referenceTags[type][index] : null;
    }

    // Since tags ordering is not always in order, we need to check the next tag
    getReferenceTagAtIndexOrNext(type, index) {
        for (let i = index; i <= index + 10; i++) {
            let tag = this.getReferenceTagAtIndex(type, i);

            if (tag) {
                return tag;
            }
        }

        return null;
    }

    getOpeningReferenceTagAtIndexOrNext(index) {
        return this.getReferenceTagAtIndexOrNext('open', index);
    }

    getClosingReferenceTagAtIndexOrNext(index) {
        return this.getReferenceTagAtIndexOrNext('close', index);
    }

    getSingleReferenceTagAtIndexOrNext(index) {
        return this.getReferenceTagAtIndexOrNext('single', index);
    }

    getWhitespaceReferenceTagAtIndex(index) {
        return this.getReferenceTagAtIndex('whitespace', index);
    }
}
