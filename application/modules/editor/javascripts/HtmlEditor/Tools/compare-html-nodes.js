'use strict';

/**
 *
 * @param {HTMLElement} node1
 * @param {HTMLElement} node2
 * @returns {boolean}
 */
export default function nodesAreSame(node1, node2) {
    if (node1.nodeType === Node.TEXT_NODE) {
        if (node2.nodeType !== Node.TEXT_NODE) {
            return false;
        }

        return node1.textContent === node2.textContent;
    }

    if (node2.nodeType === Node.TEXT_NODE) {
        if (node1.nodeType !== Node.TEXT_NODE) {
            return false;
        }

        return node1.textContent === node2.textContent;
    }

    if (node1.tagName !== node2.tagName) {
        return false;
    }

    const classes1 = Array.from(node1.classList).sort().join(' ');
    const classes2 = Array.from(node2.classList).sort().join(' ');

    if (classes1 !== classes2) {
        return false;
    }

    // Compare all other attributes (excluding "class").
    const getAttrs = (node) => {
        const map = {};

        for (const attr of node.attributes) {
            if (attr.name.toLowerCase() === 'class') {
                continue;
            }

            map[attr.name] = attr.value;
        }

        return map;
    };

    const attrs1 = getAttrs(node1);
    const attrs2 = getAttrs(node2);

    const keys1 = Object.keys(attrs1).sort();
    const keys2 = Object.keys(attrs2).sort();

    if (keys1.length !== keys2.length) {
        return false;
    }

    for (let i = 0; i < keys1.length; i++) {
        if (keys1[i] !== keys2[i] || attrs1[keys1[i]] !== attrs2[keys2[i]]) {
            return false;
        }
    }

    return true;
};