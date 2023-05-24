class TagsCheck {
    constructor(referenceTags, idPrefix) {
        this.referenceTags = referenceTags;
        this.idPrefix = idPrefix;
    }

    checkContentTags(nodeList, markupImagesCache) {
        let foundIds = [],
            ignoreWhitespace = Editor.app.getTaskConfig('segments.userCanModifyWhitespaceTags');

        let duplicatedTags = [];
        let excessTags = [];

        for (let node of nodeList) {
            let id = node.id.replace(new RegExp('^' + this.idPrefix), '');

            //ignore whitespace and nodes without ids
            if (ignoreWhitespace && /whitespace/.test(node.className) || /^\s*$/.test(node.id)) {
                continue;
            }

            if (!this.referenceTags.hasOwnProperty(id)) {
                excessTags.push(markupImagesCache[id]);
            }

            if (foundIds.includes(id) && node.parentNode.nodeName.toLowerCase() !== "del") {
                duplicatedTags.push(markupImagesCache[id]);
            } else {
                if (node.parentNode.nodeName.toLowerCase() !== "del") {
                    foundIds.push(id);
                }
            }
        }

        let missingTags = [];
        for (const [key, item] of Object.entries(this.referenceTags)) {
            if (ignoreWhitespace && item.whitespaceTag) {
                continue;
            }

            if (!foundIds.includes(key)) {
                missingTags.push(item);
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
}
