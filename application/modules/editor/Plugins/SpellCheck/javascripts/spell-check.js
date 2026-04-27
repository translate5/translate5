import stringToDom from '../../../javascripts/HtmlEditor/Tools/string-to-dom.js';
import calculateNodeOffsets from "../../../javascripts/HtmlEditor/Tools/calculate-node-offsets.js";
import calculateNodeLength from "../../../javascripts/HtmlEditor/Tools/calculate-node-length.js";
import unescapeHtml from "../../../javascripts/HtmlEditor/Tools/unescape-html.js";

export default class SpellCheck {
    static NODE_NAME_MATCH = 'span';
    static ATTRIBUTE_ACTIVEMATCHINDEX = 'data-spellCheck-activeMatchIndex';
    static ATTRIBUTE_QTIP = 'data-qtip';
    static ATTRIBUTE_FALSE_POSITIVE = 'data-t5qfp';
    static ATTRIBUTE_QUALITY_ID = 'data-t5qid';

    //region cleanup on typing
    cleanSpellcheckOnTypingInside(rawData, actions, previousPosition, tagsConversion) {
        if (!actions.length) {
            return [rawData, previousPosition];
        }

        const doc = stringToDom(rawData);

        let position = previousPosition;

        for (const action of actions) {
            if (!action.type) {
                continue;
            }

            const calculatedPosition = this.#processNodes(doc, action, tagsConversion);

            position = calculatedPosition !== null ? calculatedPosition : position;
        }

        return [doc.innerHTML, position];
    }

    #processNodes(doc, action, tagsConversion) {
        const _this = this;
        const position = action.position;
        let pointer = 0;

        function traverseNodes(node) {
            if (node.nodeType === Node.TEXT_NODE || tagsConversion.isTag(node)) {
                const nodeLength = calculateNodeLength(node);

                const isWithinNode = pointer <= position && pointer + nodeLength >= position;

                if (!isWithinNode) {
                    pointer += nodeLength;

                    return null;
                }

                const isInserting = action.type === RichTextEditor.EditorWrapper.ACTION_TYPE.INSERT;
                const dom = !isInserting && action.content.length ? action.content[0].toDom() : null;
                const isDeletingSpellCheck =
                    !isInserting &&
                    dom &&
                    (
                        tagsConversion.isSpellcheckNode(dom) ||
                        // If one of its children is a spellcheck node
                        (dom.nodeType === Node.ELEMENT_NODE && !!dom.querySelectorAll('.t5spellcheck').length)
                    );

                // if we are inserting or deleting inside a spellcheck node, unwrap it
                if (
                    tagsConversion.isSpellcheckNode(node.parentNode) &&
                    (isInserting || isDeletingSpellCheck)
                ) {
                    _this.#unwrapNodesWithMatchIndex(
                        doc,
                        node.parentNode.getAttribute(SpellCheck.ATTRIBUTE_ACTIVEMATCHINDEX)
                    );

                    return isInserting ? position + action.correction : position;
                }

                // if we are deleted at the beginning of the spellcheck node with the del or backspace key
                // and due to calculation of the position of a change current node can be right before the
                // spellcheck node that we need to unwrap
                let siblingToUnwrap = node.nextSibling &&
                tagsConversion.isSpellcheckNode(node.nextSibling) ? node.nextSibling : null;

                // sometimes next sibling should be checked on a parent node
                if (!siblingToUnwrap && node.parentNode.lastChild === node) {
                    siblingToUnwrap = node.parentNode.nextSibling &&
                    tagsConversion.isSpellcheckNode(node.parentNode.nextSibling) ? node.parentNode.nextSibling : null;
                }

                if (siblingToUnwrap && isDeletingSpellCheck) {
                    _this.#unwrapNodesWithMatchIndex(
                        doc,
                        siblingToUnwrap.getAttribute(SpellCheck.ATTRIBUTE_ACTIVEMATCHINDEX)
                    );

                    return isInserting ? position + action.correction : position;
                }

                return isInserting ? position + action.correction : position;
            }

            if (tagsConversion.isTag(node)) {
                pointer++;

                return null;
            }

            if (node.nodeType === Node.ELEMENT_NODE) {
                for (let child of node.childNodes) {
                    const changed = traverseNodes(child);

                    // This is done to prevent endless recursion
                    if (changed) {
                        return changed;
                    }
                }
            }

            return null;
        }

        return traverseNodes(doc);
    }

    #unwrapNodesWithMatchIndex(doc, matchIndex) {
        const nodes = doc.querySelectorAll(`span[${SpellCheck.ATTRIBUTE_ACTIVEMATCHINDEX}*="${matchIndex}"]`);

        for (const node of nodes) {
            this.#unwrapSpellcheckNode(node);
        }
    }

    #unwrapSpellcheckNode(spellCheckNode) {
        const spellCheckNodeParent = spellCheckNode.parentNode;
        const insertFragment = document.createRange().createContextualFragment(spellCheckNode.innerHTML);
        spellCheckNodeParent.insertBefore(insertFragment, spellCheckNode);
        spellCheckNodeParent.removeChild(spellCheckNode);
    }

    //endregion

    //region transform matches
    transformMatches(matches, originalText, existingQualities = []) {
        const internalTagsPositions = [];

        const deletionsPositions = [];
        const doc = stringToDom(originalText);
        const tags = doc.querySelectorAll('img.newline, img.whitespace');

        for (const tag of tags) {
            const offsets = calculateNodeOffsets(doc, tag);
            internalTagsPositions.push(offsets.start);
        }

        const deletions = doc.querySelectorAll('del');

        for (const deletion of deletions) {
            const offsets = calculateNodeOffsets(doc, deletion);
            deletionsPositions.push(offsets);
        }

        const posMap = this.#buildPositionMap(doc);
        const transformed = [];

        for (const [index, match] of matches.entries()) {
            const matchStart = match.offset;
            const matchEnd = matchStart + match.context.length;
            const transformedMatch = {
                matchIndex: index,
                range: this.#getRangeForMatch(matchStart, matchEnd, internalTagsPositions, deletionsPositions),
                textRange: {start: matchStart, end: matchEnd},
                message: match.message,
                replacements: match.replacements.map(replacement => replacement.value),
                infoURLs: match.rule.urls ? match.rule.urls.map(url => url.value) : [],
                cssClassErrorType: Editor.data.plugins.SpellCheck.cssMap[match.rule.issueType] || '',
            };

            const matchContent = this.prepareTextForSpellCheck(
                this.#replaceSpace(
                    this.#sliceHtml(posMap, transformedMatch.range.start, transformedMatch.range.end)
                )
            );
            const quality = this.#lookForMatchingQuality(match, matchContent, existingQualities);

            if (quality) {
                transformedMatch.id = quality.id;
                transformedMatch.falsePositive = quality.falsePositive;
            }

            transformed.push(transformedMatch);
        }

        return transformed;
    }

    /**
     * Get the range of a match in the Editor using the offsets of the text-only version.
     * @param {int} matchStart
     * @param {int} matchEnd
     * @param {int[]} internalTagsPositions
     * @param {Object<start: int, end: int>[]} deletionsPositions
     * @returns {Object}
     */
    #getRangeForMatch(
        matchStart,
        matchEnd,
        internalTagsPositions,
        deletionsPositions
    ) {
        let deletionsBeforeStartLength = 0;
        let deletionsBeforeEndLength = 0;

        for (const deletion of deletionsPositions) {
            const deletionLength = deletion.end - deletion.start;
            const biasedStart = deletion.start - deletionsBeforeStartLength;
            const biasedEnd = deletion.end - deletionsBeforeEndLength - deletionLength;

            if (biasedStart < matchStart) {
                deletionsBeforeStartLength += deletionLength;
            }

            if (biasedEnd < matchEnd) {
                deletionsBeforeEndLength += deletionLength;
            }
        }

        let start = matchStart
            // Since internal tag length is 1 we can calculate amount of filtered tags
            + internalTagsPositions.filter((el) => el < matchStart + deletionsBeforeStartLength).length
            + deletionsBeforeStartLength;

        let end = matchEnd
            + internalTagsPositions.filter((el) => el < matchEnd + deletionsBeforeEndLength).length
            + deletionsBeforeEndLength;

        return {
            start: start,
            end: end,
        };
    }

    //endregion

    //region apply matches

    applyMatches(originalText, matches) {
        const dom = stringToDom(originalText);
        const posMap = this.#buildPositionMap(dom);
        const contentLength = posMap.filter(e => e.type === 'char' || e.type === 'img').length;

        let result = '';
        let previousRangeEnd = 0;

        for (const match of matches) {
            if (this.#isInsideTermNode(posMap, match.range.start, match.range.end)) {
                continue;
            }

            if (previousRangeEnd !== match.range.start) {
                result += this.#replaceSpace(this.#sliceHtml(posMap, previousRangeEnd, match.range.start));
            }

            const matchContent = this.#replaceSpace(this.#sliceHtml(posMap, match.range.start, match.range.end));
            const [leadingDeletions, remainder] = this.#splitLeadingDeletions(matchContent);

            result += leadingDeletions;

            if (remainder) {
                result += this.#createSpellcheckNode(remainder, match);
            }

            previousRangeEnd = match.range.end;
        }

        const contentLeft = this.#sliceHtml(posMap, previousRangeEnd, contentLength);

        if (contentLeft === '&nbsp;') {
            // If this is the latest nbsp in the whole content
            // do not replace it with space because it will be trimmed on applying it to editor
            result += contentLeft;
        } else {
            result += this.#replaceSpace(contentLeft, true, false);
        }

        return result;
    }

    /**
     * Checks whether a given match corresponds to a false-positive in the existing persisted qualities.
     * Matching is done by plain-text range (textRange) first, with content as a secondary check.
     *
     * @param {Object} match
     * @param {string} renderedContent  – the HTML slice that will be wrapped (inner HTML of the span)
     * @param {Array} existingQualities
     * @returns {Object|null}  the matched quality if it exists, or null if it doesn't
     */
    #lookForMatchingQuality(match, renderedContent, existingQualities) {
        if (!existingQualities.length) {
            return null;
        }

        const textStart = match.textRange?.start;
        const textEnd = match.textRange?.end;

        // Strip HTML tags from the rendered slice to get comparable plain text
        const plainContent = renderedContent.replace(/<[^>]*>/g, '');

        for (const quality of existingQualities) {
            if (!quality) {
                continue;
            }

            const rangeMatches =
                quality.range &&
                quality.range.start === textStart &&
                quality.range.end === textEnd;

            const contentMatches =
                quality.content !== undefined &&
                quality.content === plainContent;

            if (rangeMatches || contentMatches) {
                return quality;
            }
        }

        return null;
    }

    /**
     * Builds a flat position-map from a DOM node.
     * Logical position rules (matching CKEditor model / calculateNodeLength):
     *   - text characters each count as 1  → type 'char'
     *   - <img> elements count as 1        → type 'img'
     *   - all other elements are transparent containers → type 'open' / 'close'
     *
     * @param {HTMLElement} root
     * @returns {Array}
     */
    #buildPositionMap(root) {
        const entries = [];

        function traverse(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                for (const char of node.nodeValue) {
                    entries.push({type: 'char', value: char});
                }
                return;
            }

            if (node.nodeName === 'IMG') {
                entries.push({type: 'img', node});
                return;
            }

            if (node.nodeType === Node.ELEMENT_NODE) {
                entries.push({type: 'open', node});
                for (const child of node.childNodes) {
                    traverse(child);
                }
                entries.push({type: 'close', node});
            }
        }

        for (const child of root.childNodes) {
            traverse(child);
        }

        return entries;
    }

    /**
     * Reconstructs the HTML for the logical range [from, to) from a position map.
     * Transparent container tags are included when their content falls within the range.
     *
     * @param {Array}  posMap
     * @param {number} from
     * @param {number} to
     * @returns {string}
     */
    #sliceHtml(posMap, from, to) {
        let pos = 0;
        let result = '';
        // Each frame: { node, emitted }
        // "emitted" is true when the open-tag has been written to `result`.
        const openStack = [];

        const VOID = new Set(['BR', 'HR', 'INPUT', 'LINK', 'META']);

        // Emit opening tags for any ancestor frames not yet emitted.
        // Called whenever we are about to emit content inside the range.
        const ensureAncestorsOpen = () => {
            for (const frame of openStack) {
                if (!frame.emitted) {
                    const el = frame.node;
                    result += el.outerHTML.slice(0, el.outerHTML.indexOf('>') + 1);
                    frame.emitted = true;
                }
            }
        };

        for (const entry of posMap) {
            if (entry.type === 'open') {
                const inRange = pos >= from && pos < to;
                const frame = {node: entry.node, emitted: false};

                if (inRange) {
                    // Tag opens inside the range — ensure ancestors are written first, then open this tag.
                    ensureAncestorsOpen();
                    result += entry.node.outerHTML.slice(0, entry.node.outerHTML.indexOf('>') + 1);
                    frame.emitted = true;
                }
                // If not yet in range, push with emitted=false; it will be lazily opened
                // by ensureAncestorsOpen() if the range starts inside this element.
                openStack.push(frame);
                continue;
            }

            if (entry.type === 'close') {
                const frame = openStack.pop();
                if (frame && frame.emitted && !VOID.has(entry.node.nodeName)) {
                    result += `</${entry.node.nodeName.toLowerCase()}>`;
                }
                continue;
            }

            // 'char' or 'img' — advance logical position
            if (pos >= from && pos < to) {
                ensureAncestorsOpen();
                if (entry.type === 'char') {
                    result += entry.value === '\u00a0' ? '&nbsp;' : entry.value;
                } else {
                    result += entry.node.outerHTML;
                }
            }

            pos++;
        }

        return result;
    }

    /**
     * Returns true when any content in the logical range [from, to) is nested
     * inside a <span class="term"> element, meaning the match should not be
     * wrapped in a spellcheck node.
     *
     * @param {Array}  posMap
     * @param {number} from
     * @param {number} to
     * @returns {boolean}
     */
    #isInsideTermNode(posMap, from, to) {
        let pos = 0;
        const openStack = [];

        for (const entry of posMap) {
            if (entry.type === 'open') {
                openStack.push(entry.node);
                continue;
            }

            if (entry.type === 'close') {
                openStack.pop();
                continue;
            }

            // 'char' or 'img' — check logical position against the range
            if (pos >= from && pos < to) {
                if (openStack.some(node => node.nodeName === 'SPAN' && node.classList.contains('term'))) {
                    return true;
                }
            }

            pos++;
        }

        return false;
    }

    /**
     * Splits the leading <del> nodes from the beginning of an HTML string.
     * Returns [leadingDeletionsHtml, remainderHtml].
     * If there are no leading deletions, leadingDeletionsHtml is an empty string.
     *
     * @param {string} html
     * @returns {[string, string]}
     */
    #splitLeadingDeletions(html) {
        const dom = stringToDom(html);
        let leadingDeletions = '';
        let remainder = '';
        let foundNonDeletion = false;

        for (const node of dom.childNodes) {
            if (!foundNonDeletion && node.nodeName === 'DEL') {
                leadingDeletions += node.outerHTML;
            } else {
                foundNonDeletion = true;
                remainder += node.nodeType === Node.TEXT_NODE ? node.nodeValue : node.outerHTML;
            }
        }

        return [leadingDeletions, remainder];
    }


    /**
     * Create and return a new node for SpellCheck-Match of the given index.
     * For match-specific data, get the data from the tool.
     *
     * @param {string}  text
     * @param {Object}  match
     *
     * @returns {string}
     */
    #createSpellcheckNode(text, match) {
        const node = document.createElement(SpellCheck.NODE_NAME_MATCH);
        node.className = `${Editor.util.HtmlClasses.CSS_CLASSNAME_SPELLCHECK} ${match.cssClassErrorType} ownttip`;
        node.setAttribute(SpellCheck.ATTRIBUTE_ACTIVEMATCHINDEX, match.matchIndex);
        node.setAttribute(SpellCheck.ATTRIBUTE_QTIP, Editor.data.l10n.SpellCheck.nodeTitle);

        const falsePositive = !! match?.falsePositive;
        node.setAttribute(SpellCheck.ATTRIBUTE_FALSE_POSITIVE, falsePositive ? 'true' : 'false');

        if (match.id) {
            node.setAttribute(SpellCheck.ATTRIBUTE_QUALITY_ID, match.id);
        }

        node.innerHTML = text;

        return node.outerHTML;
    }


    #replaceSpace(text, start = true, end = true) {
        let result = text;

        if (start) {
            result = result.replace(/^&nbsp;/, ' ');
        }

        if (end) {
            result = result.replace(/&nbsp;$/, ' ');
        }

        return result;
    }

    //endregion

    //region prepare text for spellcheck

    /**
     * @param {String} text
     * @return {String}
     */
    prepareTextForSpellCheck(text) {
        let editorContentAsText = this.#getContentWithWhitespaceImagesAsText(text);
        // Replace all new line tags to actual new line
        editorContentAsText = editorContentAsText.replaceAll('<br>', '\n');
        editorContentAsText = this.#cleanupForSpellchecking(stringToDom(editorContentAsText));
        editorContentAsText = unescapeHtml(editorContentAsText);

        return editorContentAsText.trim();
    }

    #cleanupForSpellchecking(dom) {
        let result = '';

        for (const node of dom.childNodes) {
            if (node.nodeName === '#text') {
                result += node.data;

                continue;
            }

            if (node.nodeName === 'SPAN') {
                result += this.#cleanupForSpellchecking(node);

                continue;
            }

            if (node.nodeName === 'IMG') {
                continue;
            }

            if (node.nodeName === 'DEL') {
                continue;
            }

            if (node.childNodes.length > 0) {
                result += this.#cleanupForSpellchecking(node);

                continue;
            }

            result += node.outerHTML;
        }

        return result;
    }

    cleanupSpellcheckNodes(text) {
        const dom = stringToDom(text);
        let result = dom.innerHTML;
        const spellcheckNodes = dom.querySelectorAll(SpellCheck.NODE_NAME_MATCH);

        for (const node of spellcheckNodes) {
            result = result.replace(node.outerHTML, node.innerHTML);
        }

        return result;
    }

    /**
     * Replace whitespace-images with whitespace-text.
     *
     * @params {String} text
     * @returns {String} html
     */
    #getContentWithWhitespaceImagesAsText(text) {
        let html = text;

        const dom = stringToDom(html);

        for (const node of dom.childNodes) {
            if (node.nodeName === 'IMG' && node.classList.contains('whitespace') && !node.classList.contains('newline')) {
                html = html.replace(node.outerHTML, ' ');
            }
        }

        return html;
    }

    //endregion
}
