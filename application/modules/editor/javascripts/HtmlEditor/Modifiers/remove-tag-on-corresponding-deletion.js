import stringToDom from "../Tools/string-to-dom.js";
import nodesAreSame from "../Tools/compare-html-nodes.js";

export default function removeTagOnCorrespondingDeletion(rawData, actions, position, tagsConversion) {
    const doc = stringToDom(rawData);

    for (const action of actions) {
        if (!action.type) {
            continue;
        }

        if (!action.correspondingDeletion) {
            continue;
        }

        const deletion = action.content[0];

        let tag = deletion.toDom();
        let parentNode;

        if (! tagsConversion.isInternalTagNode(tag) && ! tagsConversion.isMQMNode(tag)) {
            parentNode = tag;
            tag = tag.querySelector('img');
        }

        const allTags = doc.querySelectorAll('img');

        for (const candidate of allTags) {
            if (tagsConversion.isTrackChangesDelNode(candidate.parentNode)) {
                continue;
            }

            if (nodesAreSame(candidate, tag)) {
                candidate.parentNode.removeChild(candidate);

                break;
            }
        }
    }

    return [doc.innerHTML, position];
}
