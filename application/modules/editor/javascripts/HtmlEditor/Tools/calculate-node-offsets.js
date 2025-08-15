'use strict';

/**
 * @param {HTMLElement} root
 * @param {HTMLElement} target
 *
 * @returns {number}
 */
export default function calculateNodeOffsets(root, target) {
    if (!target) {
        return null;
    }

    let currentOffset = 0;
    let result = null;
    let _this = this;

    function traverse(nodeList) {
        for (const node of nodeList) {
            if (result) {
                return;
            }

            if (node === target || node.outerHTML === target.outerHTML) {
                const start = currentOffset;
                const length = RichTextEditor.calculateNodeLength(node);
                result = { start, end: start + length };

                return;
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

    return result;
}