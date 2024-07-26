import stringToDom from "../Tools/string-to-dom";

export default class InsertPreprocessor {
    #tagsConversion = null;

    constructor(tagsTransform) {
        this.#tagsConversion = tagsTransform;
    }

    cleanup(doc) {
        const result = stringToDom('');
        const _this = this;

        const traverseNodes = function (nodes) {
            for (let node of nodes) {
                if (_this.#tagsConversion.isTrackChangesDelNode(node)) {
                    continue;
                }

                if (_this.#tagsConversion.isTextNode(node)) {
                    result.appendChild(node.cloneNode(true));

                    continue;
                }

                if (_this.#tagsConversion.isInternalTagNode(node)) {
                    result.appendChild(node.cloneNode(true));

                    continue;
                }

                if (node.childNodes.length > 0) {
                    traverseNodes(node.childNodes);
                }
            }
        }

        traverseNodes(doc.childNodes);

        return result;
    }
}