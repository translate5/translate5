import stringToDom from "../Tools/string-to-dom.js";

/**
 *
 *
 * @param {string} html
 * @param {TagsConversion} tagsConversion
 * @param {DataTransformer} dataTransformer
 * @returns {string}
 */
export default function copyPreprocessor(html, tagsConversion, dataTransformer) {
    const dom = stringToDom(html);

    const tags = dom.querySelectorAll('img');

    if (! tags.length) {
        return html;
    }

    for (const tag of tags) {
        if (! tagsConversion.isWhitespaceNode(tag)) {
            continue;
        }

        let div = document.createElement('div');
        div.appendChild(tag.cloneNode(true));

        const reversed = dataTransformer.reverseTransform(div)?.data;

        if (! reversed) {
            tag.parentNode.removeChild(tag);

            continue;
        }

        tag.parentNode.insertBefore(stringToDom(reversed).firstChild, tag);
        tag.parentNode.removeChild(tag);
    }

    return dom.innerHTML;
}