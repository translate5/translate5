import TagsConversion from "./tags-conversion";
import CheckResult from "./check-result";

export default class TagCheck {
    #userCanModifyWhitespaceTags;
    #userCanInsertWhitespaceTags;

    constructor(
        referenceTags,
        tagsConversion,
        userCanModifyWhitespaceTags,
        userCanInsertWhitespaceTags
    ) {
        this.referenceTags = referenceTags;
        this.tagsConversion = tagsConversion;
        this.#userCanModifyWhitespaceTags = userCanModifyWhitespaceTags;
        this.#userCanInsertWhitespaceTags = userCanInsertWhitespaceTags;
    }

    /**
     * check and fix tags
     *
     * @param {HTMLElement} node
     */
    checkTags(node) {
        let nodeList = node.getElementsByTagName('img');

        this._fixDuplicateImgIds(nodeList);
        let checkResult = this._checkContentTags(nodeList);

        this._removeOrphanedTags(nodeList);

        if (!checkResult.isSuccessful()) {
            console.log('Check result is not successful');
            console.log(checkResult);

            // Save anyway mode
            // this.disableErrorCheck
            //no more checks if missing tags found
            return checkResult;
        }

        checkResult.tagsOrderCorrect = this._checkTagOrder(nodeList);
        console.log('Tags order correct: ' + checkResult.tagsOrderCorrect);

        return checkResult;
    }

    _checkContentTags(nodeList) {
        let foundIds = [],
            ignoreWhitespace = this._shouldIgnoreWhitespaceTags();

        let duplicatedTags = [];
        let excessTags = [];

        for (let node of nodeList) {
            let matches = node.id.match(/\d+$/);
            let id = matches ? matches[0] : null;

            let isQaTag = /qmflag/.test(node.className);

            if (isQaTag) {
                continue;
            }

            let tagType = this.tagsConversion.getInternalTagType(node);
            let isWhitespaceTag = tagType === TagsConversion.TYPE.WHITESPACE;

            //ignore whitespace and nodes without ids
            if ((isWhitespaceTag && ignoreWhitespace) || null === id) {
                continue;
            }

            if (!this.referenceTags[tagType][id]) {
                if (isWhitespaceTag && this._isAllowedAddingWhitespaceTags()) {
                    continue;
                }

                excessTags.push(node);
            }

            if (foundIds.includes(tagType + id) && node.parentNode.nodeName.toLowerCase() !== "del") {
                duplicatedTags.push(node);
            } else {
                if (node.parentNode.nodeName.toLowerCase() !== "del") {
                    foundIds.push(tagType + id);
                }
            }
        }

        let missingTags = [];
        for (const [type, items] of Object.entries(this.referenceTags)) {
            if (ignoreWhitespace && type === TagsConversion.TYPE.WHITESPACE) {
                continue;
            }

            for (const [id, item] of Object.entries(items)) {
                if (!foundIds.includes(type + id)) {
                    missingTags.push(item._transformed);
                }
            }
        }

        return new CheckResult(missingTags, duplicatedTags, excessTags);
    }

    _checkTagOrder(nodeList) {
        let open = {},
            clean = true;

        for (let img of nodeList) {
            // crucial: for the tag-order, we only have to check tags that are not already deleted
            if (!this._isDeletedTag(img)) {
                if (TagsConversion.isDuplicateSaveTag(img) || /^remove/.test(img.id) || /(-single|-whitespace)/.test(img.id)) {
                    //ignore tags marked to remove
                    continue;
                }

                if (/open/.test(img.id)) {
                    open[img.id] = true;

                    continue;
                }

                let replaced = img.id.replace(/close/, 'open');

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
    _isDeletedTag(node) {
        while (node.parentElement && node.parentElement.tagName.toLowerCase() !== 'body') {
            if (node.parentElement.tagName.toLowerCase() === 'del') {
                return true;
            }

            node = node.parentElement;
        }

        return false;
    }

    /**
     * @returns {Boolean}
     */
    _shouldIgnoreWhitespaceTags() {
        return this.#userCanModifyWhitespaceTags;
    }

    /**
     * @returns {boolean}
     */
    _isAllowedAddingWhitespaceTags() {
        return this.#userCanInsertWhitespaceTags;
    }

    /**
     * Fixes duplicate img ids in the opened editor on unmarkup (MQM tags)
     * Works with <img> tags with the following specifications:
     * IMG needs an id Attribute. Assuming that the id contains the strings "-open" or "-close". The rest of the id string is identical.
     * Needs also an attribute "data-t5qid" which is containing the plain ID of the tag pair.
     * If a duplicated img tag is found, the "123" of the id will be replaced with a generated Ext.id()
     *
     * example, tag with needed infos:
     * <img id="foo-open-123" data-t5qid="123"/> open tag
     * <img id="foo-close-123" data-t5qid="123"/> close tag
     *
     * copying this tags will result in
     * <img id="foo-open-ext-456" data-t5qid="ext-456"/>
     * <img id="foo-close-ext-456" data-t5qid="ext-456"/>
     *
     * Warning:
     * fixing IDs means that existing ids are wandering forward:
     * Before duplicating:
     * This is the [X 1]testtext[/X 1].
     * after duplicating, before fixing:
     * This [X 1]is[/X 1] the [X 1]testtext[/X 1].
     * after fixing:
     * This [X 1]is[/X 1] the [X 2]testtext[/X 2].
     *
     * @param {HTMLCollection} nodeList
     */
    _fixDuplicateImgIds(nodeList) {
        let ids = {},
            stackList = {},
            updateId = function (img, newQid, oldQid) {
                img.id = img.id.replace(new RegExp(oldQid + '$'), newQid);
                img.setAttribute('data-t5qid', newQid);
            };

        for (let img of nodeList) {
            let newQid,
                oldQid = TagsConversion.getElementsQualityId(img),
                id = img.id,
                pid,
                open;

            if (!id || TagsConversion.isDuplicateSaveTag(img)) {
                continue;
            }

            if (!ids[id]) {
                //id does not yet exist, then it is not duplicated => out
                ids[id] = true;

                continue;
            }

            if (stackList[id] && stackList[id].length > 0) {
                newQid = stackList[id].shift();
                updateId(img, newQid, oldQid);

                continue;
            }

            open = new RegExp("-open");

            if (open.test(id)) {
                pid = id.replace(open, '-close');
            } else {
                pid = id.replace(/-close/, '-open');
            }

            if (!stackList[pid]) {
                stackList[pid] = [];
            }

            // TODO replace Ext dependency
            newQid = Ext.id();
            stackList[pid].push(newQid);

            updateId(img, newQid, oldQid);
        }
    }

    /**
     * removes orphaned tags (MQM only)
     * assuming same id for open and close tag. Each Tag ID contains the string "-open" or "-close"
     * prepends "remove-" to the id of an orphaned tag
     *
     * @param {HTMLCollection} nodeList
     */
    _removeOrphanedTags(nodeList) {
        let openers = {},
            closers = {},
            hasRemoves = false;

        for (let img of nodeList) {
            if (TagsConversion.isDuplicateSaveTag(img)) {
                return;
            }

            if (/-open/.test(img.id)) {
                openers[img.id] = img;
            }

            if (/-close/.test(img.id)) {
                closers[img.id] = img;
            }
        }

        for (const [id, img] of Object.entries(openers)) {
            let closeId = img.id.replace(/-open/, '-close');

            if (closers[closeId]) {
                //closer zum opener => aus "closer entfern" liste raus
                delete closers[closeId];
            } else {
                //kein closer zum opener => opener zum entfernen markieren
                hasRemoves = true;
                img.id = 'remove-' + img.id;
            }
        }

        for (const [id, img] of Object.entries(closers)) {
            hasRemoves = true;
            img.id = 'remove-' + img.id;
        }

        if (hasRemoves) {
            // TODO fix this
            Editor.MessageBox.addInfo(this.strings.tagRemovedText);
        }
    }
}
