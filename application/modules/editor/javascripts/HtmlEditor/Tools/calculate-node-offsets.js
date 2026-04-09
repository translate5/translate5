import calculateNodeLength from "./calculate-node-length.js";

'use strict';

/**
 * @param {HTMLElement} root
 * @param {HTMLElement} target
 *
 * @returns {{start: number, end: number}}
 */
export default function calculateNodeOffsets(root, target) {
    if (!target) {
        return { start: 0, end: 0 };
    }

    let currentOffset = 0;
    let result = null;

    function traverse(nodeList) {
        for (const node of nodeList) {
            if (result) {
                break;
            }

            if (node === target) {
                const start = currentOffset;
                const length = calculateNodeLength(node);
                result = { start: start, end: start + length };

                break;
            }

            if (node.nodeType === Node.TEXT_NODE) {
                currentOffset += node.nodeValue.length;
            }

            // Simplifid check if node is an internal tag
            if (node.tagName === 'IMG') {
                currentOffset += 1;

                continue;
            }

            if (node.nodeType === Node.ELEMENT_NODE) {
                traverse(node.childNodes);
            }
        }
    }

    traverse(root.childNodes);

    return result || { start: 0, end: 0 };
}