'use strict';

/**
 * @param {ChildNode} node
 * @returns {number}
 */
export default function calculateNodeLength(node) {
    let length = 0;

    for (const child of node.childNodes) {
        // Simplifid check if node is an internal tag
        if (child.tagName === 'IMG') {
            length += 1;

            continue;
        }

        if (child.nodeType === Node.ELEMENT_NODE) {
            length += calculateNodeLength(child);

            continue;
        }

        length += child.length;
    }

    return length;
}
