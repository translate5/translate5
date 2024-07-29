'use strict';

/**
 * @param {ChildNode} node
 * @returns {number}
 */
export default function calculateNodeLength(node) {
    let length = 0;

    for (const child of node.childNodes) {
        if (child.nodeType === Node.ELEMENT_NODE) {
            length += calculateNodeLength(child);

            continue;
        }

        length += child.length;
    }

    return length;
}
