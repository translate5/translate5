'use strict';

import stringToDom from "./string-to-dom";

export default function extractHtmlAttributes(html) {
    let p = stringToDom(html);

    let data = {};
    for (const attribute of p.children[0].attributes) {
        data[attribute.nodeName] = attribute.nodeValue;
    }

    return data;
}
