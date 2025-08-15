'use strict';

import stringToDom from './string-to-dom';

/**
 * Insert HTML at a specific position in the given HTML string.
 *
 * @param {string} htmlString
 * @param {int} position
 * @param {string} htmlToInsert
 * @returns {string}
 */
export default function insertHtmlAt(htmlString, position, htmlToInsert) {
    const dom = stringToDom(htmlString);
    const walker = document.createTreeWalker(dom, NodeFilter.SHOW_TEXT, null, false);
    let node;
    let accumulated = 0;

    while ((node = walker.nextNode())) {
        const textLen = node.nodeValue.length;

        if (accumulated + textLen >= position) {
            const offsetInNode = position - accumulated;
            const parent = node.parentNode;

            const beforeText = document.createTextNode(node.nodeValue.slice(0, offsetInNode));
            const afterText  = document.createTextNode(node.nodeValue.slice(offsetInNode));

            parent.insertBefore(beforeText, node);

            const frag = document.createRange().createContextualFragment(htmlToInsert);
            parent.insertBefore(frag, node);

            parent.insertBefore(afterText, node);
            parent.removeChild(node);

            break;
        }

        accumulated += textLen;
    }

    return dom.innerHTML;
}
