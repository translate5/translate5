import stringToDom from "../Tools/string-to-dom.js";

/**
 * Add line breaks <br> after images with the "newline" class in the given HTML
 * text to make the new line actually look like a new line.
 *
 * @param {string} text
 * @param {Array} actions
 * @param {int} position
 * @returns {[string|*,undefined]}
 */
export default function addLineBreaks(text, actions, position) {
    text = text.replaceAll(/<br>/gi, '');

    // Check if the only action is inserting a single new line symbol is inserted
    if (actions.length === 1 && actions[0].type === 'insert') {
        const content = actions[0].content;

        if (/^<img[^>]*class="[^"]*\bnewline\b[^"]*"[^>]*><br>$/.test(content)) {
            position++;
        }
    }

    const dom = stringToDom(text);
    const brs = dom.querySelectorAll('img.newline');
    let changed = false;

    for (const br of brs) {
        if (br.closest('del') !== null) {
            continue;
        }

        const nextNode = br.nextSibling;

        br.parentNode.insertBefore(document.createElement('br'), nextNode);
        changed = true;

        if (nextNode && nextNode.nodeType === Node.TEXT_NODE && nextNode.textContent.startsWith(' ')) {
            nextNode.textContent = '\u00A0' + nextNode.textContent.substring(1);
        }
    }

    return [changed ? dom.innerHTML : text, position];
}
