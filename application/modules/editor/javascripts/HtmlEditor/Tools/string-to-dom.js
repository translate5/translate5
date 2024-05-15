'use strict';

const support = (function () {
    if (!window.DOMParser) {
        return false;
    }

    let parser = new DOMParser();

    try {
        parser.parseFromString('x', 'text/html');
    } catch (error) {
        return false;
    }

    return true;
})();

/**
 * Convert a template string into HTML DOM nodes
 * @param  {String} str The template string
 * @return {Node}       The template HTML
 */
export default function stringToDom (str) {
    // If DOMParser is supported, use it
    if (support) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(str, 'text/html');

        return doc.body;
    }

    // Otherwise, fallback to old-school method
    let dom = document.createElement('div');
    dom.innerHTML = str;

    return dom;
};

