'use strict';

/**
 * @param {ChildNode} node
 * @returns {number}
 */
export default function calculateNodeLength(node) {
    let length = 0;

    if (node.nodeType === Node.TEXT_NODE) {
        return node.length;
    }

    if (node.tagName === 'IMG') {
        return 1;
    }

    for (const child of node.childNodes) {
        // Simplified check if node is an internal tag
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
