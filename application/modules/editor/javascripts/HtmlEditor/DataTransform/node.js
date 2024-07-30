export default class Node {
    constructor(original, transformed = null, children = []) {
        this._original = original;
        this._transformed = transformed;
        this._children = children;
    }

    toString() {
        // if (!this._children.length) {
        //     return this._transformed.outerHTML;
        // }

        let result = '';

        for (const node of this._children) {
            result += node.toString();
        }

        return result;
    }

    // get length() {
    //     // if (!this._children.length) {
    //     //     return 0;
    //     // }
    //
    //     let result = 0;
    //
    //     // this._children.forEach((node) => {
    //     //     result += node.length;
    //     // });
    //
    //     return result;
    // }
}
