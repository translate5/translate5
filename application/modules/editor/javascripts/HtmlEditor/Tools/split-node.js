'use strict';

import calculateNodeLength from './calculate-node-length.js';
import nodesAreSame from './compare-html-nodes.js';

/**
 * @param {HTMLElement} node
 * @param {int} offset
 *
 * @returns {Node[]}
 */
export function splitNode(node, offset) {
    if (node.nodeType === Node.TEXT_NODE) {
        const left = node.cloneNode();
        const right = node.cloneNode();

        left.textContent = node.textContent.slice(0, offset);
        right.textContent = node.textContent.slice(offset);

        return [left, right];
    }

    const total = calculateNodeLength(node);
    let remaining = Math.max(0, Math.min(offset, total)); // clamp [0..total]

    const leftEl = node.cloneNode(false);
    const rightEl = node.cloneNode(false);

    for (const child of node.childNodes) {
        if (remaining <= 0) {
            rightEl.appendChild(child.cloneNode(true));

            continue;
        }

        if (child.nodeType === Node.TEXT_NODE) {
            const text = /** @type {Text} */ (child).data;
            const length = text.length;

            if (remaining >= length) {
                leftEl.appendChild(child.cloneNode(true));
                remaining -= length;
            } else {
                const leftText = document.createTextNode(text.slice(0, remaining));
                const rightText = document.createTextNode(text.slice(remaining));

                if (leftText.data.length > 0) {
                    leftEl.appendChild(leftText);
                }

                if (rightText.data.length > 0) {
                    rightEl.appendChild(rightText);
                }

                remaining = 0;
            }

            continue;
        }

        if (child.nodeType === Node.ELEMENT_NODE) {
            const element = /** @type {Element} */ (child);

            if (element.tagName.toLowerCase() === 'img') {
                if (remaining >= 1) {
                    leftEl.appendChild(element.cloneNode(true));
                    remaining -= 1;
                } else {
                    rightEl.appendChild(element.cloneNode(true));
                }

                continue;
            }

            const length = calculateNodeLength(element);

            if (remaining >= length) {
                leftEl.appendChild(element.cloneNode(true));
                remaining -= length;
            } else {
                const [partLeft, partRight] = splitNode(element, remaining);

                if (partLeft.childNodes.length > 0) {
                    leftEl.appendChild(partLeft);
                }

                if (partRight.childNodes.length > 0) {
                    rightEl.appendChild(partRight);
                }

                remaining = 0;
            }

            continue;
        }

        if (remaining > 0) {
            leftEl.appendChild(child.cloneNode(true));
        } else {
            rightEl.appendChild(child.cloneNode(true));
        }
    }

    return [leftEl, rightEl.childNodes.length > 0 ? rightEl : null];
}

/**
 * Split a parent node at the position of a child node ignoring the child
 *
 * @param {HTMLElement} parentNode - The parent node to split
 * @param {HTMLElement} childNode - The child node to extract
 */
export function splitNodeByChild(parentNode, childNode) {
    // Clone the parent node to work with
    const parentClone = parentNode.cloneNode(true);

    // Find the child in the cloned parent
    const childInClone = findChildInClone(parentClone, childNode);

    if (! childInClone) {
        console.warn('Could not find child node in clone');

        return [null, null];
    }

    // Create three fragments: before, child, after
    const beforeNode = parentNode.cloneNode(false);
    const afterNode = parentNode.cloneNode(false);

    // Split the parent's content into before and after sections
    let beforeChild = true;

    for (let i = 0; i < parentClone.childNodes.length; i++) {
        const child = parentClone.childNodes[i];

        if (child === childInClone) {
            beforeChild = false;

            continue;
        }

        if (beforeChild) {
            beforeNode.appendChild(child.cloneNode(true));

            continue;
        }

        afterNode.appendChild(child.cloneNode(true));
    }

    return [
        beforeNode.childNodes.length > 0 ? beforeNode : null,
        afterNode.childNodes.length > 0 ? afterNode : null,
    ];
}

/**
 * Find a child node in a cloned parent by comparing structure
 * @param {HTMLElement} clonedParent - The cloned parent node
 * @param {HTMLElement} originalChild - The original child node to find
 * @returns {HTMLElement|null}
 */
function findChildInClone(clonedParent, originalChild) {
    const allChildren = clonedParent.querySelectorAll('*');

    for (const child of allChildren) {
        if (nodesAreSame(child, originalChild)) {
            return child;
        }
    }

    return null;
}
