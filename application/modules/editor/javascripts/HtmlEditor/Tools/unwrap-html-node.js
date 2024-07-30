'use strict';

import escapeHtml from './escape-html';

/**
 * @param {ChildNode} node
 * @returns {string}
 */
export default function unwrapHtmlNodeToText(node) {
    let result = '';

    for (const child of node.childNodes) {
        result += escapeHtml(child.data);
    }

    return result;
}
