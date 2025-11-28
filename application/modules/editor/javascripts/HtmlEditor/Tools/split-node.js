'use strict';

import calculateNodeLength from './calculate-node-length.js';

/**
 * @param {HTMLElement} node
 * @param {int} offset
 *
 * @returns {Node[]}
 */
export default function splitNode(node, offset) {
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
