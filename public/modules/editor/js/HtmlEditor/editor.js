(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["RichTextEditor"] = factory();
	else
		root["RichTextEditor"] = factory();
})(self, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./DataCleanup/insert-preprocessor.js":
/*!********************************************!*\
  !*** ./DataCleanup/insert-preprocessor.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ InsertPreprocessor)
/* harmony export */ });
/* harmony import */ var _Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../Tools/string-to-dom */ "./Tools/string-to-dom.js");


class InsertPreprocessor {
    #tagsConversion = null;

    constructor(tagsTransform) {
        this.#tagsConversion = tagsTransform;
    }

    cleanup(doc) {
        const result = (0,_Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_0__["default"])('');
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

/***/ }),

/***/ "./DataTransform/data-transformer.js":
/*!*******************************************!*\
  !*** ./DataTransform/data-transformer.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ DataTransformer)
/* harmony export */ });
/* harmony import */ var _node__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./node */ "./DataTransform/node.js");
/* harmony import */ var _TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../TagsTransform/tags-conversion */ "./TagsTransform/tags-conversion.js");
/* harmony import */ var _TagsTransform_tag_check__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../TagsTransform/tag-check */ "./TagsTransform/tag-check.js");




class DataTransformer {
    #userCanModifyWhitespaceTags;
    #userCanInsertWhitespaceTags;

    /**
     * @param {TagsConversion} tagsConversion
     * @param {NodeListOf<ChildNode>} items
     * @param {NodeListOf<ChildNode>|Array} referenceItems
     * @param {Boolean} userCanModifyWhitespaceTags
     * @param {Boolean} userCanInsertWhitespaceTags
     */
    constructor(
        tagsConversion,
        items,
        referenceItems,
        userCanModifyWhitespaceTags,
        userCanInsertWhitespaceTags
    ) {
        this._tagsConversion = tagsConversion;
        this._referenceTags = {
            [_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.SINGLE]: {},
            [_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.OPEN]: {},
            [_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.CLOSE]: {},
            [_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.WHITESPACE]: {},
        };
        this._transformedTags = {
            [_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.SINGLE]: {},
            [_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.OPEN]: {},
            [_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.CLOSE]: {},
            [_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.WHITESPACE]: {},
        };
        this._transformedNodes = [];
        this.#userCanModifyWhitespaceTags = userCanModifyWhitespaceTags;
        this.#userCanInsertWhitespaceTags = userCanInsertWhitespaceTags;

        this.#transform(items, referenceItems);
    }

    #transform(items, referenceItems = []) {
        this.#parseReferenceItems(this.#transformItems(referenceItems));
        this._tagCheck = new _TagsTransform_tag_check__WEBPACK_IMPORTED_MODULE_2__["default"](
            this._referenceTags,
            this._tagsConversion,
            this.#userCanModifyWhitespaceTags,
            this.#userCanInsertWhitespaceTags
        );
        this._transformedNodes = this.#transformItems(items, true);
    }
    
    transformPartial(items) {
        const nodes = this.#transformItems(items, true);

        let result = '';
        for (const node of nodes) {
            result += node._transformed.outerHTML !== undefined ? node._transformed.outerHTML : node._transformed.textContent;
        }

        return result;
    }

    /**
     * Transform data from editor format to t5 internal format (replace images to div-span tags structure)
     *
     * @param data
     * @returns {{data: string, checkResult: CheckResult}}
     */
    reverseTransform(data) {
        let checkResult = this._tagCheck.checkTags(data);

        return {"data": this.#reverseTransformItems(data), "checkResult": checkResult};
    }

    toString() {
        let result = "";

        for (const node of this._transformedNodes) {
            result += node._transformed.outerHTML !== undefined ? node._transformed.outerHTML : node._transformed.textContent;
        }

        return result;
    }

    #transformItems(items, useReference = false) {
        let result = [];

        for (const item of items) {
            let node;

            if (this._tagsConversion.isInternalTagNode(item) && useReference) {
                const type = this._tagsConversion.getInternalTagType(item);
                const tagNumber = this._tagsConversion.getInternalTagNumber(item);

                switch (type) {
                    case _TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.OPEN:
                        node = this.#getOpeningReferenceTagAtIndex(tagNumber);

                        if (!node) {
                            continue;
                        }

                        break;

                    case _TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.CLOSE:
                        node = this.#getClosingReferenceTagAtIndex(tagNumber);

                        if (!node) {
                            continue;
                        }

                        break;

                    case _TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.WHITESPACE:
                        node = this.#getWhitespaceReferenceTagAtIndex(tagNumber);

                        if (!node && this._tagCheck.isAllowedAddingWhitespaceTags()) {
                            node = new _node__WEBPACK_IMPORTED_MODULE_0__["default"](item, this._tagsConversion.transform(item));
                        }

                        if (!node) {
                            continue;
                        }

                        break;

                    case _TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.SINGLE:
                        node = this.#getSingleReferenceTagAtIndex(tagNumber);

                        if (!node) {
                            continue;
                        }

                        break;
                }

                if (!this._transformedTags[type][tagNumber]) {
                    this._transformedTags[type][tagNumber] = node;
                }
            } else {
                node = new _node__WEBPACK_IMPORTED_MODULE_0__["default"](item, this._tagsConversion.transform(item));
            }

            if (node) {
                result.push(node);

                if (item.childNodes.length) {
                    node._children = this.#transformItems(item.childNodes, useReference);
                }
            }
        }

        return result;
    }

    #reverseTransformItems(data) {
        let result = '';

        for (const item of data.childNodes) {
            if (this._tagsConversion.isInternalTagNode(item)) {
                const tagType = this._tagsConversion.getInternalTagType(item);
                const tagNumber = this._tagsConversion.getInternalTagNumber(item);
                result += this._transformedTags[tagType][tagNumber]._original.outerHTML;

                continue;
            }

            if (this._tagsConversion.isTermNode(item)) {
                result += this.#reverseTransformItems(item);

                continue;
            }

            if (this._tagsConversion.isTrackChangesNode(item)) {
                const node = item.cloneNode(true);
                node.innerHTML = this.#reverseTransformItems(node);
                result += node.outerHTML;

                continue;
            }

            // other elements like spellcheck nodes etc.
            if (item.childNodes.length) {
                result += this.#reverseTransformItems(item);

                continue;
            }

            result += this.#htmlEncode(item.data);
        }

        return result;
    }

    #parseReferenceItems(items) {
        for (const item of items) {
            if (
                this._tagsConversion.isInternalTagNode(item._original)
                || this._tagsConversion.isWhitespaceNode(item._original)
            ) {
                const tagType = this._tagsConversion.getInternalTagType(item._original);
                const tagNumber = this._tagsConversion.getInternalTagNumber(item._original);
                this._referenceTags[tagType][tagNumber] = item;
            }

            if (item._children.length) {
                this.#parseReferenceItems(item._children);
            }
        }
    }

    #getReferenceTagAtIndex(type, index) {
        return this._referenceTags[type][index] ?? null;
    }

    #getOpeningReferenceTagAtIndex(index) {
        return this.#getReferenceTagAtIndex(_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.OPEN, index);
    }

    #getClosingReferenceTagAtIndex(index) {
        return this.#getReferenceTagAtIndex(_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.CLOSE, index);
    }

    #getSingleReferenceTagAtIndex(index) {
        return this.#getReferenceTagAtIndex(_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.SINGLE, index);
    }

    #getWhitespaceReferenceTagAtIndex(index) {
        return this.#getReferenceTagAtIndex(_TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"].TYPE.WHITESPACE, index);
    }

    #htmlEncode(string) {
        return string.replace(/[\u00A0-\u9999<>&]/g, i => '&#'+i.charCodeAt(0)+';');
    }
}


/***/ }),

/***/ "./DataTransform/node.js":
/*!*******************************!*\
  !*** ./DataTransform/node.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Node)
/* harmony export */ });
class Node {
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


/***/ }),

/***/ "./Editor/callbacks-queue.js":
/*!***********************************!*\
  !*** ./Editor/callbacks-queue.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ CallbacksQueue)
/* harmony export */ });
class CallbacksQueue {
    #items = [];
    #defaultAppendPriority = 10;  // Default priority for append
    #defaultPrependPriority = -10; // Default priority for prepend

    constructor() {
    }

    // Add element with priority
    add(callback, priority) {
        const newItem = {callback, priority};
        let contains = false;

        for (let i = 0; i < this.#items.length; i++) {
            if (this.#items[i].priority > newItem.priority) {
                this.#items.splice(i, 0, newItem);
                contains = true;

                break;
            }
        }

        if (!contains) {
            this.#items.push(newItem);
        }
    }

    // Append element at the end based on default append priority
    append(element) {
        this.add(element, this.#defaultAppendPriority);
    }

    // Prepend element at the beginning based on default prepend priority
    prepend(element) {
        this.add(element, this.#defaultPrependPriority);
    }

    /**
     * Iterable interface.
     *
     * @returns {Iterable.<Node>}
     */
    [Symbol.iterator]() {
        let index = 0;

        return {
            // Note: using an arrow function allows `this` to point to the
            // one of `[@@iterator]()` instead of `next()`
            next: () => {
                if (index < this.#items.length) {
                    return {value: this.#items[index++].callback, done: false};
                } else {
                    return {done: true};
                }
            },
        };
    }
}


/***/ }),

/***/ "./Editor/deleted-element.js":
/*!***********************************!*\
  !*** ./Editor/deleted-element.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ DeletedElement)
/* harmony export */ });
class DeletedElement {
    static TYPE = {
        INTERNAL_TAG: 'internal-tag',
        TEXT: 'text',
        INSERT_TAG: 'insert-tag',
        DELETE_TAG: 'delete-tag',
    };

    #data;
    #attributes;
    #type;
    #parent;

    constructor(data, attributes, elementName, parent = null) {
        this.#data = data;
        this.#attributes = attributes;
        this.#type = this.#getType(elementName);
        this.#parent = parent;
    }

    get data() {
        return this.#data;
    }

    get length() {
        return this.#data?.length || 1;
    }

    isInternalTag() {
        return this.#type === DeletedElement.TYPE.INTERNAL_TAG;
    }

    isText() {
        return this.#type === DeletedElement.TYPE.TEXT;
    }

    isInsertDeleteTag() {
        return this.#type === DeletedElement.TYPE.INSERT_DELETE_TAG;
    }

    toDom(child) {
        let element;

        if (this.#type === DeletedElement.TYPE.TEXT) {
            element = document.createTextNode('');
        } else {
            element = document.createElement(this.#getTag());
        }

        for (const [key, value] of Object.entries(this.#attributes)) {
            element.setAttribute(key, value);
        }

        // Set text content if #data is not null
        if (this.#data) {
            element.textContent = this.#data;
        }

        if (child) {
            element.appendChild(child);
        }

        if (this.#parent) {
            return this.#parent.toDom(element);
        }

        return element;
    }

    #getTag() {
        switch (this.#type) {
            case DeletedElement.TYPE.INTERNAL_TAG:
                return 'img';
            case DeletedElement.TYPE.INSERT_TAG:
                return 'ins';
            case DeletedElement.TYPE.DELETE_TAG:
                return 'del';
            default:
                return null;
        }
    }

    #getType(name) {
        switch (name) {
            case 'imageInline':
                return DeletedElement.TYPE.INTERNAL_TAG;
            case 'htmlIns':
                return DeletedElement.TYPE.INSERT_TAG;
            case 'htmlDel':
                return DeletedElement.TYPE.DELETE_TAG;
            default:
                return DeletedElement.TYPE.TEXT;
        }
    }
}


/***/ }),

/***/ "./Editor/editor-wrapper.js":
/*!**********************************!*\
  !*** ./Editor/editor-wrapper.js ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ EditorWrapper)
/* harmony export */ });
/* harmony import */ var _Source__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../Source */ "./Source/build/ckeditor.js");
/* harmony import */ var _Source__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_Source__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../TagsTransform/tags-conversion */ "./TagsTransform/tags-conversion.js");
/* harmony import */ var _TagsTransform_pixel_mapping__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../TagsTransform/pixel-mapping */ "./TagsTransform/pixel-mapping.js");
/* harmony import */ var _Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Tools/string-to-dom */ "./Tools/string-to-dom.js");
/* harmony import */ var _DataTransform_data_transformer__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../DataTransform/data-transformer */ "./DataTransform/data-transformer.js");
/* harmony import */ var _callbacks_queue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./callbacks-queue */ "./Editor/callbacks-queue.js");
/* harmony import */ var _deleted_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./deleted-element */ "./Editor/deleted-element.js");
/* harmony import */ var _Mixin_document_fragment__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../Mixin/document-fragment */ "./Mixin/document-fragment.js");
/* harmony import */ var _DataCleanup_insert_preprocessor__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../DataCleanup/insert-preprocessor */ "./DataCleanup/insert-preprocessor.js");










class EditorWrapper {
    static REFERENCE_FIELDS = {
        SOURCE: 'source',
        TARGET: 'target',
    };

    static ACTION_TYPE = {
        REMOVE: 'remove',
        INSERT: 'insert',
    };

    static EDITOR_EVENTS = {
        DATA_CHANGED: 'dataChanged',
    };

    #font = null;
    #tagsModeProvider = null;

    #isProcessingDrop = false;
    #isProcessingPaste = false;
    #isProcessingCut = false;
    #lastKeyPressed = null;

    #userCanModifyWhitespaceTags;
    #userCanInsertWhitespaceTags;

    constructor(
        element,
        tagsModeProvider,
        userCanModifyWhitespaceTags,
        userCanInsertWhitespaceTags
    ) {
        this._element = element;
        this._editor = null;
        this._tagsConversion = null;
        this.#tagsModeProvider = tagsModeProvider;
        this.#userCanModifyWhitespaceTags = userCanModifyWhitespaceTags;
        this.#userCanInsertWhitespaceTags = userCanInsertWhitespaceTags;

        this._modifiers = {
            [EditorWrapper.EDITOR_EVENTS.DATA_CHANGED]: new _callbacks_queue__WEBPACK_IMPORTED_MODULE_5__["default"](),
        };

        this._asyncModifiers = {
            [EditorWrapper.EDITOR_EVENTS.DATA_CHANGED]: [],
        };

        // this._cachedSelection = {content: null, position: null};

        return this.#create();
    }

    get font() {
        return this.#font;
    }

    /**
     * Reset editor state to avoid having history of editing from the previous instance
     */
    resetEditor() {
        this._editor.model.change(writer => {
            const root = this._editor.model.document.getRoot();
            writer.remove(writer.createRangeIn(root));
        });
    }

    /**
     * Returns object containing data in t5 internal format and content
     *
     * @returns {{data: string, checkResult: CheckResult}}
     */
    getDataT5Format() {
        // TODO Add length check
        // this.checkSegmentLength(source || "");
        // TODO add check for contentEdited
        // me.contentEdited = me.plainContent.join('') !== result.replace(/<img[^>]+>/g, '');

        return this.dataTransformer.reverseTransform(this.#getRawDataNode());
    }

    /**
     * Set data into editor, data should be in t5 format (div-span tags structure)
     *
     * @param {string} data
     * @param {string} referenceData
     * @param {Font} font
     */
    setDataT5Format(data, referenceData, font) {
        this.#font = font;
        this.dataTransformer = new _DataTransform_data_transformer__WEBPACK_IMPORTED_MODULE_4__["default"](
            this._tagsConversion,
            (0,_Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__["default"])(data).childNodes,
            (0,_Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__["default"])(referenceData).childNodes,
            this.#userCanModifyWhitespaceTags,
            this.#userCanInsertWhitespaceTags
        );
        this.#setRawData(this.dataTransformer.toString());
        this.#triggerDataChanged();
    }

    /**
     * Insert whitespace tag into editor
     *
     * @param {String} whitespaceType
     * @param {integer} position
     * @param {boolean} replaceWhitespaceBeforePosition
     */
    insertWhitespace(whitespaceType, position = null, replaceWhitespaceBeforePosition = false) {
        const tagNumber = this._tagsConversion.getNextWhitespaceTagNumber(this.#getRawDataNode().getElementsByTagName('img'));
        const divSpanHtml = this._tagsConversion.generateWhitespaceTag(whitespaceType, tagNumber);
        const pixelMapping = new _TagsTransform_pixel_mapping__WEBPACK_IMPORTED_MODULE_2__["default"](this.#font);
        const image = this._tagsConversion.transform(divSpanHtml, pixelMapping).outerHTML;

        if (!position) {
            const viewFragment = this._editor.data.processor.toView(image);
            const modelFragment = this._editor.data.toModel(viewFragment);
            this._editor.model.insertContent(modelFragment);
            this.#triggerDataChanged();

            return;
        }

        let start = position;
        const end = position;

        if (replaceWhitespaceBeforePosition
            && (
                this.getContentInRange(position - 1, position) === '&nbsp;'
                // This is because just added whitespace can be in trackchanges tag
                || (0,_Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__["default"])(this.getContentInRange(position - 1, position)).innerHTML === '&nbsp;'
            )
        ) {
            start = position - 1;
        }

        this.replaceContentInRange(start, end, image);
    }

    /**
     * Insert a symbol into editor
     *
     * @param {String} symbol
     */
    insertSymbol(symbol) {
        this._editor.model.change(writer => {
            this._editor.model.insertContent(writer.createText(symbol));
        });

        this.#triggerDataChanged();
    }

    /**
     * Register a callback which will be called on editor data change
     *
     * @param {String} event
     * @param {function} modifier
     * @param {int} priority
     */
    registerModifier(event, modifier, priority) {
        if (typeof modifier !== 'function') {
            // TODO error?
            return;
        }

        this._modifiers[event].add(modifier, priority);
    }

    /**
     * Register an async modifier which will be called on editor data change
     *
     * @param {String} event
     * @param {function} modifier
     */
    registerAsyncModifier(event, modifier) {
        if (typeof modifier !== 'function') {
            // TODO error?
            return;
        }

        this._asyncModifiers[event].push(modifier);
    }

    /**
     * Returns current selection in an editor
     *
     * @returns {{start: (*|number), end: (*|number)}}
     */
    getSelection() {
        const selection = this._editor.model.document.selection.getFirstRange();

        return {
            start: selection.start.path[1] ?? 0,
            end: selection.end.path[1] ?? 0,
        };
    }

    /**
     * Returns content of an editor in a range from-to
     *
     * @param {integer} from
     * @param {integer} to
     * @returns {string}
     */
    getContentInRange(from, to) {
        let result = '';

        this._editor.model.change((writer) => {
            const preservedSelection = this._editor.model.document.selection.getFirstRange();

            const root = writer.model.document.getRoot();

            const rootChild = root.getChild(0);
            const maxOffset = rootChild ? rootChild.maxOffset : 0;

            // Use the smallest of `to` or `maxOffset`
            const effectiveTo = Math.min(to, maxOffset);

            const start = writer.model.createPositionFromPath(root, [0, from]);
            const end = writer.model.createPositionFromPath(root, [0, effectiveTo]);
            const range = writer.model.createRange(start, end);

            writer.setSelection(range);

            const content = writer.model.getSelectedContent(writer.model.document.selection);
            const viewFragment = this._editor.data.toView(content);

            writer.setSelection(preservedSelection);

            result = this._editor.data.processor.toData(viewFragment);
        });

        return result;
    }

    /**
     * Replace part of a content in a range from-to
     *
     * @param {integer} rangeStart
     * @param {integer} rangeEnd
     * @param {String} content
     */
    replaceContentInRange(rangeStart, rangeEnd, content) {
        this._editor.model.change((writer) => {
            const preservedSelection = this._editor.model.document.selection.getFirstRange();

            const root = writer.model.document.getRoot();
            const start = writer.model.createPositionFromPath(root, [0, rangeStart]);
            const end = writer.model.createPositionFromPath(root, [0, rangeEnd]);
            const range = writer.model.createRange(start, end);

            writer.setSelection(range);

            const viewFragment = this._editor.data.processor.toView(content);
            const modelFragment = this._editor.data.toModel(viewFragment);

            this._editor.model.insertContent(modelFragment, range);

            writer.setSelection(preservedSelection);
        });

        // Create delete/insert actions based on content
        this.#triggerDataChanged();
    }

    /**
     * Mark content in a range from-to with a predefined set of markers
     *
     * @param rangeStart
     * @param rangeEnd
     * @param options - Marker name should be passed in next format {value: 'yellowMarker'} <br>
     *                  As for now yellowMarker, greenMarker, redMarker are available
     */
    markContentInRange(rangeStart, rangeEnd, options) {
        this._editor.model.change((writer) => {
            const preservedSelection = this._editor.model.document.selection.getFirstRange();

            const root = writer.model.document.getRoot();
            const start = writer.model.createPositionFromPath(root, [0, rangeStart]);
            const end = writer.model.createPositionFromPath(root, [0, rangeEnd]);
            const range = writer.model.createRange(start, end);

            writer.setSelection(range);

            this._editor.execute('highlight', options);

            writer.setSelection(preservedSelection);
        });
    }

    unmarkAll() {
        const doc = this._editor.model.document;
        const root = doc.getRoot();

        this._editor.model.change((writer) => {
            const preservedSelection = this._editor.model.document.selection.getFirstRange();

            writer.setSelection(root, 'in');

            this._editor.execute('highlight');

            writer.setSelection(preservedSelection);
        });
    }

    /**
     * Returns an array of internal tags positions
     *
     * @returns {Object<string, string>}
     */
    getInternalTagsPositions() {
        const model = this._editor.model;

        let imagesWithPositions;

        model.change(writer => {
            imagesWithPositions = this.#getInternalTagsPositions(model.document.getRoot(), writer);
        });

        return imagesWithPositions;
    }

    addEditorCssClass(classToToggle) {
        this.getEditorViewNode().classList.add(classToToggle);
    }

    removeEditorCssClass(classToToggle) {
        this.getEditorViewNode().classList.remove(classToToggle);
    }

    /**
     * Returns HTML node of an editor
     *
     * @returns {HTMLDocument}
     * @private
     */
    getEditorViewNode() {
        return this._editor.ui.view.element;
    }

    getTagsConversion() {
        return this._tagsConversion;
    }

    #triggerDataChanged() {
        const position = this._editor.model.document.selection.getFirstPosition().path[1];
        this.#runModifiers([{position: position, correction: 0}]);
    }

    /**
     * @param element
     * @param writer
     * @returns {Array<int, HTMLElement>}
     * @private
     */
    #getInternalTagsPositions(element, writer) {
        let imagesWithPositions = {};

        for (const node of element.getChildren()) {
            if (node.is('element')) {
                if (node.name === 'imageInline'
                    && node.getAttribute('htmlImgAttributes').classes.includes('internal-tag')
                ) {
                    const imagePosition = writer.createPositionBefore(node);
                    imagesWithPositions[imagePosition.path[1]] = this._tagsConversion.getInternalTagTypeByClass(
                        node.getAttribute('htmlImgAttributes').classes.join(' ')
                    );
                }

                // Recursively check the children of the node
                imagesWithPositions = Object.assign({}, imagesWithPositions, this.#getInternalTagsPositions(node, writer));
            }
        }

        return imagesWithPositions;
    }

    /**
     * Create an editor instance
     *
     * @returns {Promise<EditorWrapper>}
     * @private
     */
    #create() {
        return _Source__WEBPACK_IMPORTED_MODULE_0___default().create(
            this._element,
            {
                toolbar: [],
                htmlSupport: {
                    allow: [
                        {
                            name: /.*/,
                            attributes: true,
                            classes: true,
                            styles: true
                        }
                    ],
                },
                highlight: {
                    options: [
                        {model: 'greenMarker', class: 'search-replace_marker-green', type: 'marker'},
                        {model: 'yellowMarker', class: 'search-replace_marker-yellow', type: 'marker'},
                        {model: 'redMarker', class: 'search-replace_marker-red', type: 'marker'},
                    ],
                },
            }
        ).then((editor) => {
            this._editor = editor;
            this._tagsConversion = new _TagsTransform_tags_conversion__WEBPACK_IMPORTED_MODULE_1__["default"](this.getEditorViewNode(), this.#tagsModeProvider);
            this.#addListeners(editor);

            return this;
        }).catch((error) => {
            // TODO: handle error
            console.error(error);
        });
    }

    /**
     * Returns content of an editor as is, without any modifications
     *
     * @returns {string}
     * @private
     */
    getRawData() {
        return this.#getRawDataNode().innerHTML;
    }

    /**
     * Set provided data to an editor as is, without any modifications
     *
     * @param {string} data
     * @private
     */
    #setRawData(data) {
        this._editor.setData(data);
    }

    /**
     * Returns HTML node containing editor's data
     *
     * @returns {HTMLParagraphElement}
     * @private
     */
    #getRawDataNode() {
        if (this._editor.getData() === '') {
            return document.createElement('p');
        }

        const dom = document.createElement('html');
        dom.innerHTML = this._editor.getData();

        return dom.getElementsByTagName('p')[0];
    }

    // region editor event listeners
    #addListeners(editor) {
        const viewDocument = editor.editing.view.document;
        viewDocument.on('enter', (event, data) => {
            this.#onPressEnter(event, data, editor);
        }, {priority: 'high'});
        viewDocument.on('paste', (event, data) => {
            this.#onClipboardInput(event, data, editor);
        });
        viewDocument.on('drop', (event, data) => {
            this.#onDrop(event, data, editor);
        });
        viewDocument.on('keydown', (event, data) => {
            this.#onKeyDown(event, data, editor);
        });
        viewDocument.on('clipboardOutput', (event, data) => {
            this.#onClipboardOutput(event, data, editor);
        });

        const modelDocument = editor.model.document;
        modelDocument.on('change:data', (event, data) => {
            this.#onDataChange(event, data, editor);
        });
        modelDocument.on('change', (event, data) => {
            this.#onDocumentChange(event, data, editor);
        });

        editor.plugins.get('ClipboardPipeline').on('inputTransformation', (event, data) => {
            this.#onInputTransformation(event, data, editor);
        });
    }

    #onDataChange(event, data, editor) {
        console.log('The data has changed!');
        this.modifiersLastRunId = null;

        if (data.isUndo) {
            this.#triggerDataChanged();

            return;
        }

        if (!data.isTyping && !this.#isProcessingDrop && !this.#isProcessingPaste && !this.#isProcessingCut) {
            return;
        }

        // Immediately reset the flags to prevent multiple calls
        this.#isProcessingDrop = false;
        this.#isProcessingPaste = false;
        const lastKeyPressed = this.#lastKeyPressed;
        this.#lastKeyPressed = null;

        const actions = [];

        const operations = data.operations
            // Filter out all operations except 'insert' and 'remove'
            .filter(operation => operation.type === 'insert' || operation.type === 'remove')
            .reduce((_operations, operation) => {
                // For 'insert' operations, pick the one with the highest 'baseVersion' as we don't need history
                if (operation.type === 'insert') {
                    if (!_operations.insert || _operations.insert.baseVersion < operation.baseVersion) {
                        _operations.insert = operation;
                    }
                } else if (operation.type === 'remove' && !_operations.remove) {
                    // We only need the last 'remove' operation
                    // (usually it is the only one in the array, but just in case)
                    _operations.remove = operation;
                }

                return _operations;
            }, {insert: null, remove: null});

        ['remove', 'insert'].forEach(type => {
            const operation = operations[type];

            if (!operation) {
                return;
            }

            const path = operation.position?.path || operation.sourcePosition?.path;

            if (path && path[1] !== undefined) {
                actions.push(this.#createActionFromOperation(operation, lastKeyPressed));
            }
        });

        this.#runModifiers(actions);
    }

    #createActionFromOperation(operation, lastKeyPressed) {
        const position = operation.position?.path[1] || operation.sourcePosition?.path[1];

        if (operation.type === 'remove') {
            const content = this.#getDeletedContent(operation);

            return {type: EditorWrapper.ACTION_TYPE.REMOVE, content, position, correction: 0, lastKeyPressed};
        }

        let content = operation.nodes?.getNode(0).data || '';
        // Use Unicode for non-breaking space for further processing
        // ckeditor mixes spaces, so need to manually replace them
        content = content === ' ' ? '\u00A0' : content;
        const correction = content.length;

        return {type: EditorWrapper.ACTION_TYPE.INSERT, content, position, correction, lastKeyPressed};
    }

    #onClipboardOutput(event, data, editor) {
        this.#isProcessingCut = data.method === 'cut';
    }

    #onDocumentChange(event, data, editor) {
        // console.log('The Document has changed!');
        console.log('Position ' + editor.model.document.selection.getFirstPosition().path[1]);
    }

    #onClipboardInput(event, data, editor) {
        console.log('Paste from clipboard');
        if (this.#isProcessingDrop) {
            return;
        }

        this.#isProcessingPaste = true;
    }

    #onInputTransformation(event, data, editor) {
        const content = data.content;
        Object.assign(content, _Mixin_document_fragment__WEBPACK_IMPORTED_MODULE_7__["default"]);
        const cleaned = this.#cleanupDataOnInsertOrDrop(content.toHTMLString());
        data.content = editor.data.htmlProcessor.toView(cleaned);
    }

    #onDrop(event, data, editor) {
        console.log('Drop event');
        this.#isProcessingDrop = true;
    }

    #onPressEnter(event, data, editor) {
        //change enter to shift+enter to prevent ckeditor from inserting a new p tag
        data.preventDefault();
        event.stop();
        editor.execute('shiftEnter');
        editor.editing.view.scrollToTheSelection();
    }

    #onKeyDown(event, data, editor) {
        this.#lastKeyPressed = data.domEvent.code;
    }

    // endregion

    #getDeletedContent(operation) {
        const changes = [];

        for (const change of operation.getMovedRangeStart().root.getChildren()) {
            if (change.name === 'paragraph') {
                break;
            }

            changes.push(this.#createDeletedElement(change));
        }

        return changes;
    }

    #createDeletedElement(change) {
        const data = change.data || null;
        const iterator = change.getAttributes();
        const attributes = Array.from(iterator);

        // simple text has no attributes, so adding early return
        if (attributes.length === 0) {
            return new _deleted_element__WEBPACK_IMPORTED_MODULE_6__["default"](data, {}, _deleted_element__WEBPACK_IMPORTED_MODULE_6__["default"].TYPE.TEXT);
        }

        const createParents = function (parents) {
            if (parents.length === 0) {
                return null;
            }

            const [name, attributes] = parents.shift();

            if (
                name === 'htmlSpan'
                && (
                    // Here we open implementation from other modules, need to rethink this approach
                    attributes.classes.includes('t5spellcheck') || attributes.classes.includes('term')
                )
            ) {
                return null;
            }

            return new _deleted_element__WEBPACK_IMPORTED_MODULE_6__["default"](
                null,
                {...attributes.attributes, class: attributes.classes.join(' ')},
                name,
                createParents(parents)
            );
        };

        let attrs = {};
        let parent = null;

        for (const [key, [name, value]] of attributes.entries()) {
            if (name === 'htmlImgAttributes') {
                attrs = {
                    ...attrs,
                    ...value.attributes,
                    class: value.classes.join(' '),
                }

                continue;
            }

            if (['htmlIns', 'htmlDel', 'htmlSpan'].includes(name)) {
                parent = createParents(attributes.slice(key));

                continue;
            }

            attrs[name] = value;
        }

        return new _deleted_element__WEBPACK_IMPORTED_MODULE_6__["default"](data, attrs, change.name || 'text', parent);
    }

    //region Probably for moving to another class

    #runModifiers(actions) {
        const originalText = this.getRawData();
        let text = originalText;
        let position;
        let forceUpdate = false;

        for (const modifier of this._modifiers[EditorWrapper.EDITOR_EVENTS.DATA_CHANGED]) {
            // TODO position can be modified by modifier, need to pass it to the next one
            [text, position, forceUpdate] = modifier(text, actions);
        }

        if (text !== originalText || forceUpdate) {
            this.#replaceDataInEditor(text, position);
        }

        this.#runAsyncModifiers(position);

        const event = new CustomEvent(EditorWrapper.EDITOR_EVENTS.DATA_CHANGED, {
            detail: 'Data changed',
            bubbles: true,
        });

        this.getEditorViewNode().dispatchEvent(event);
    }

    #runAsyncModifiers() {
        this.modifiersLastRunId = Math.random().toString(36).substring(2, 16);

        const originalText = this.getRawData();
        // Now async modifiers can be executed in any order, need to change this to promises sequence
        for (const modifier of this._asyncModifiers[EditorWrapper.EDITOR_EVENTS.DATA_CHANGED]) {
            modifier(originalText, this.modifiersLastRunId).then((result) => {
                let position = this._editor.model.document.selection.getFirstPosition().path[1]

                const [modifiedText, runId] = result;

                if (runId === this.modifiersLastRunId && modifiedText !== originalText) {
                    this.#replaceDataInEditor(modifiedText, position);
                }
            }).catch((error) => {
                console.log('Error in async modifier');
                console.log(error);
            });
        }
    }

    #replaceDataInEditor(data, position) {
        const doc = this._editor.model.document;
        const root = doc.getRoot();

        this._editor.model.change(writer => {
            writer.setSelection(root, 'in');

            const selection = this._editor.model.document.selection;
            this._editor.model.deleteContent(selection);

            // TODO clear graveyard
        });

        this._editor.model.change(writer => {
            const viewFragment = this._editor.data.processor.toView(data);
            const modelFragment = this._editor.data.toModel(viewFragment);

            this._editor.model.insertContent(modelFragment);

            const maxOffset = root.getChild(0).maxOffset;

            // Use the smallest of `to` or `maxOffset`
            const effectiveTo = Math.min(position, maxOffset);

            const selection = this._editor.model.createPositionFromPath(this._editor.model.document.getRoot(), [0, effectiveTo]);
            writer.setSelection(selection);
        });
    };

    //endregion


    //region data cleanup on insert or drop
    #cleanupDataOnInsertOrDrop(data) {
        const doc = (0,_Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__["default"])(data);
        const cleaned = new _DataCleanup_insert_preprocessor__WEBPACK_IMPORTED_MODULE_8__["default"](this._tagsConversion).cleanup(doc);

        return this.dataTransformer.transformPartial(cleaned.childNodes);
    }

    //endregion
}


/***/ }),

/***/ "./Mixin/document-fragment.js":
/*!************************************!*\
  !*** ./Mixin/document-fragment.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DocumentFragment = {
    toHTMLString() {
        let html = '',
            fragment = this;

        // Function to process a node and its children recursively
        function processNode(node) {
            if (node.is('element')) {
                // Opening tag
                html += `<${node.name}`;

                // Attributes
                if (node.getAttributes()) {
                    for (const [key, value] of node.getAttributes()) {
                        html += ` ${key}="${value}"`;
                    }
                }

                html += '>';

                // Children
                for (const child of node.getChildren()) {
                    processNode(child);
                }

                // Closing tag
                html += `</${node.name}>`;
            } else if (node.is('text')) {
                // Text node
                html += node.data;
            }
        }

        // Start processing from the root children of the DocumentFragment
        for (const child of fragment.getChildren()) {
            processNode(child);
        }

        return html;
    }
});

/***/ }),

/***/ "./Source/build/ckeditor.js":
/*!**********************************!*\
  !*** ./Source/build/ckeditor.js ***!
  \**********************************/
/***/ ((module, exports, __webpack_require__) => {

/* module decorator */ module = __webpack_require__.nmd(module);
(function(t){const e=t["en"]=t["en"]||{};e.dictionary=Object.assign(e.dictionary||{},{"(may require <kbd>Fn</kbd>)":"(may require <kbd>Fn</kbd>)","%0 of %1":"%0 of %1",Accept:"Accept",Accessibility:"Accessibility","Accessibility help":"Accessibility help",Aquamarine:"Aquamarine","Below, you can find a list of keyboard shortcuts that can be used in the editor.":"Below, you can find a list of keyboard shortcuts that can be used in the editor.",Black:"Black",Blue:"Blue","Blue marker":"Blue marker","Break text":"Break text",Cancel:"Cancel","Caption for image: %0":"Caption for image: %0","Caption for the image":"Caption for the image","Centered image":"Centered image","Change image text alternative":"Change image text alternative",Clear:"Clear","Click to edit block":"Click to edit block",Close:"Close","Close contextual balloons, dropdowns, and dialogs":"Close contextual balloons, dropdowns, and dialogs","Content editing keystrokes":"Content editing keystrokes","Copy selected content":"Copy selected content","Dim grey":"Dim grey","Drag to move":"Drag to move","Dropdown toolbar":"Dropdown toolbar","Edit block":"Edit block","Editor block content toolbar":"Editor block content toolbar","Editor contextual toolbar":"Editor contextual toolbar","Editor dialog":"Editor dialog","Editor editing area: %0":"Editor editing area: %0","Editor menu bar":"Editor menu bar","Editor toolbar":"Editor toolbar","Enter image caption":"Enter image caption","Execute the currently focused button. Executing buttons that interact with the editor content moves the focus back to the content.":"Execute the currently focused button. Executing buttons that interact with the editor content moves the focus back to the content.","Full size image":"Full size image",Green:"Green","Green marker":"Green marker","Green pen":"Green pen",Grey:"Grey","Help Contents. To close this dialog press ESC.":"Help Contents. To close this dialog press ESC.",HEX:"HEX",Highlight:"Highlight","HTML object":"HTML object","Image from computer":"Image from computer","Image resize list":"Image resize list","Image toolbar":"Image toolbar","image widget":"image widget","In line":"In line",Insert:"Insert","Insert a hard break (a new paragraph)":"Insert a hard break (a new paragraph)","Insert a new paragraph directly after a widget":"Insert a new paragraph directly after a widget","Insert a new paragraph directly before a widget":"Insert a new paragraph directly before a widget","Insert a soft break (a <code>&lt;br&gt;</code> element)":"Insert a soft break (a <code>&lt;br&gt;</code> element)","Insert image":"Insert image","Insert image via URL":"Insert image via URL","Insert paragraph after block":"Insert paragraph after block","Insert paragraph before block":"Insert paragraph before block","Keystrokes that can be used when a widget is selected (for example: image, table, etc.)":"Keystrokes that can be used when a widget is selected (for example: image, table, etc.)","Left aligned image":"Left aligned image","Light blue":"Light blue","Light green":"Light green","Light grey":"Light grey",MENU_BAR_MENU_EDIT:"Edit",MENU_BAR_MENU_FILE:"File",MENU_BAR_MENU_FONT:"Font",MENU_BAR_MENU_FORMAT:"Format",MENU_BAR_MENU_HELP:"Help",MENU_BAR_MENU_INSERT:"Insert",MENU_BAR_MENU_TEXT:"Text",MENU_BAR_MENU_TOOLS:"Tools",MENU_BAR_MENU_VIEW:"View","Move focus between form fields (inputs, buttons, etc.)":"Move focus between form fields (inputs, buttons, etc.)","Move focus in and out of an active dialog window":"Move focus in and out of an active dialog window","Move focus to the menu bar, navigate between menu bars":"Move focus to the menu bar, navigate between menu bars","Move focus to the toolbar, navigate between toolbars":"Move focus to the toolbar, navigate between toolbars","Move the caret to allow typing directly after a widget":"Move the caret to allow typing directly after a widget","Move the caret to allow typing directly before a widget":"Move the caret to allow typing directly before a widget","Navigate through the toolbar or menu bar":"Navigate through the toolbar or menu bar",Next:"Next","No results found":"No results found","No searchable items":"No searchable items","Open the accessibility help dialog":"Open the accessibility help dialog",Orange:"Orange",Original:"Original","Paste content":"Paste content","Paste content as plain text":"Paste content as plain text","Pink marker":"Pink marker","Press %0 for help.":"Press %0 for help.","Press Enter to type after or press Shift + Enter to type before the widget":"Press Enter to type after or press Shift + Enter to type before the widget",Previous:"Previous",Purple:"Purple",Red:"Red","Red pen":"Red pen",Redo:"Redo","Remove highlight":"Remove highlight","Replace from computer":"Replace from computer","Replace image":"Replace image","Replace image from computer":"Replace image from computer","Resize image":"Resize image","Resize image to %0":"Resize image to %0","Resize image to the original size":"Resize image to the original size","Rich Text Editor":"Rich Text Editor","Right aligned image":"Right aligned image",Save:"Save","Select all":"Select all","Show more items":"Show more items","Side image":"Side image","Text alternative":"Text alternative","Text highlight toolbar":"Text highlight toolbar","These keyboard shortcuts allow for quick access to content editing features.":"These keyboard shortcuts allow for quick access to content editing features.","Toggle caption off":"Toggle caption off","Toggle caption on":"Toggle caption on",Turquoise:"Turquoise",Undo:"Undo",Update:"Update","Update image URL":"Update image URL","Upload failed":"Upload failed","Upload from computer":"Upload from computer","Upload image from computer":"Upload image from computer","Upload in progress":"Upload in progress","Use the following keystrokes for more efficient navigation in the CKEditor 5 user interface.":"Use the following keystrokes for more efficient navigation in the CKEditor 5 user interface.","User interface and content navigation keystrokes":"User interface and content navigation keystrokes",White:"White","Widget toolbar":"Widget toolbar","Wrap text":"Wrap text",Yellow:"Yellow","Yellow marker":"Yellow marker"})})(window.CKEDITOR_TRANSLATIONS||(window.CKEDITOR_TRANSLATIONS={}));
/*!
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md.
//# sourceMappingURL=ckeditor.js.map

/***/ }),

/***/ "./TagsTransform/check-result.js":
/*!***************************************!*\
  !*** ./TagsTransform/check-result.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ CheckResult)
/* harmony export */ });
class CheckResult {
    constructor(missingTags, duplicatedTags, excessTags, tagsOrderCorrect) {
        this.missingTags = missingTags;
        this.duplicatedTags = duplicatedTags;
        this.excessTags = excessTags;
        this.tagsOrderCorrect = tagsOrderCorrect;
    }

    isSuccessful() {
        return this.missingTags.length === 0
            && this.duplicatedTags.length === 0
            && this.excessTags.length === 0
            && this.tagsOrderCorrect === true;
    }
}


/***/ }),

/***/ "./TagsTransform/font.js":
/*!*******************************!*\
  !*** ./TagsTransform/font.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Font)
/* harmony export */ });
class Font {
    constructor(sizeUnit, fontFamily, fontSize, fieldId) {
        this.sizeUnit = sizeUnit;
        this.fontFamily = fontFamily;
        this.fontSize = fontSize;
        this.fieldId = fieldId;
    }
}


/***/ }),

/***/ "./TagsTransform/pixel-mapping.js":
/*!****************************************!*\
  !*** ./TagsTransform/pixel-mapping.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ PixelMapping)
/* harmony export */ });
class PixelMapping {
    // Size-unit used for pixel-mapping
    static SIZE_UNIT_FOR_PIXEL_MAPPING = 'pixel';

    /**
     * @param {Font} font
     */
    constructor(font) {
        this.font = font;
    }

    /**
     * What's the length of the given internal tag according to the pixelMapping?
     * @param {HTMLElement} tagNode
     * @return {int}
     */
    getPixelLengthFromTag(tagNode) {
        if (!this.getPixelMappingSettings()
            || (this.font.sizeUnit !== PixelMapping.SIZE_UNIT_FOR_PIXEL_MAPPING)
        ) {
            return 0;
        }

        const matches = tagNode.className.match(/^([a-z]*)\s+([xA-Fa-g0-9]*)/),
            returns = {
                "hardReturn": "\r\n",
                "softReturn": "\n",
                "macReturn": "\r"
            };

        if (!matches) {
            return 0;
        }

        //convert stored data back to plain tag
        let tag = this.hexStreamToString(matches[2]);
        let plainTag = tag.replace(/[^a-zA-Z]*$/, '');

        //if it is a return, use the hardcoded replacements
        if (returns[plainTag]) {
            return this.getPixelLength(returns[plainTag]);
        }

        //get the real payload from the tag
        if (!(tag = tag.match(/ ts="([^"]+)"/))) {
            return 0;
        }

        //count the length of the real payload
        return this.getPixelLength(this.hexStreamToString(tag[1]));
    }

    /**
     * What's the length of the text according to the pixelMapping?
     * @param {String} text
     * @return {int}
     */
    getPixelLength(text) {
        const allCharsInText = this.stringToArray(text),
            pixelMapping = this.getPixelMappingForSegment();

        let unicodeCharNumeric,
            pixelLength = 0,
            pixelMappingForCharacter,
            charWidth;

        const getCharWidth = function (unicodeCharNumeric) {
            if (pixelMapping[unicodeCharNumeric] !== undefined) {
                pixelMappingForCharacter = pixelMapping[unicodeCharNumeric];
                if (pixelMappingForCharacter[this.font.fieldId] !== undefined) {
                    return pixelMappingForCharacter[this.font.fieldId];
                }
                if (pixelMappingForCharacter['default'] !== undefined) {
                    return pixelMappingForCharacter['default'];
                }
            }
            return pixelMapping['default'];
        };

        //console.dir(pixelMapping);
        //console.log(text);
        //console.dir(allCharsInText);
        let key = 0;
        allCharsInText.forEach(function (char) {
            unicodeCharNumeric = char.codePointAt(0);
            charWidth = getCharWidth(unicodeCharNumeric);
            key++;
            pixelLength += parseInt(charWidth);
            //console.log('['+key+'] ' + char + ' ('+ unicodeCharNumeric + '): ' + charWidth + ' => pixelLength: ' + pixelLength);
        });

        return pixelLength;
    }

    /**
     * Return the pixelMapping for a specific segment as already loaded for the task
     * (= the item from the array with all fonts for the task that matches the segment's
     * font-family and font-size).
     *
     * @return {Array}
     */
    getPixelMappingForSegment() {
        const pixelMapping = this.getPixelMappingSettings();

        return pixelMapping[this.font.fontFamily][this.font.fontSize];
    }

    getPixelMappingSettings() {
        // TODO think how we will handle global settings
        return Editor.data.task.get('pixelMapping');
    }

    // region Helpers

    // ---------------------------------------------------------------------------------------
    // tag content - Helpers
    // ---------------------------------------------------------------------------------------

    /**
     * implementation of PHPs pack('H*', data) function to get the tags real content
     */
    hexStreamToString(data) {
        return decodeURIComponent(data.replace(/(..)/g, '%$1'));
    }

    // ---------------------------------------------------------------------------------------
    // Unicode-Helpers
    // ---------------------------------------------------------------------------------------

    /**
     * https://stackoverflow.com/a/38901550
     */
    stringToArray(str) {
        const me = this,
            arr = [];
        let i = 0,
            codePoint;

        while (!isNaN(codePoint = me.knownCharCodeAt(str, i))) {
            arr.push(String.fromCodePoint(codePoint));
            i++;
        }

        return arr;
    }

    /**
     * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/charCodeAt#Fixing_charCodeAt()_to_handle_non-Basic-Multilingual-Plane_characters_if_their_presence_earlier_in_the_string_is_known
     */
    knownCharCodeAt(str, idx) {
        str += '';

        const end = str.length;
        const surrogatePairs = /[\uD800-\uDBFF][\uDC00-\uDFFF]/g;

        while ((surrogatePairs.exec(str)) != null) {
            const li = surrogatePairs.lastIndex;
            if (li - 2 < idx) {
                idx++;
            } else {
                break;
            }
        }

        if (idx >= end || idx < 0) {
            return NaN;
        }

        let code = str.charCodeAt(idx);

        let hi,
            low;

        if (0xD800 <= code && code <= 0xDBFF) {
            hi = code;
            low = str.charCodeAt(idx + 1);
            // Go one further, since one of the "characters"
            // is part of a surrogate pair
            return ((hi - 0xD800) * 0x400) + (low - 0xDC00) + 0x10000;
        }

        return code;
    }

    // endregion Helpers
}


/***/ }),

/***/ "./TagsTransform/ruler.js":
/*!********************************!*\
  !*** ./TagsTransform/ruler.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Ruler)
/* harmony export */ });
class Ruler {
    constructor() {
        this.rulerElement = null;
        this.createRuler();
    }

    /**
     * Creates the hidden "ruler" div that is used to measure text length
     */
    createRuler() {
        this.rulerElement = document.createElement('div');
        this.rulerElement.classList.add('textmetrics');
        this.rulerElement.setAttribute('role', 'presentation');
        this.rulerElement.dataset.sticky = true;
        this.rulerElement.style.position = 'absolute';
        this.rulerElement.style.left = '-1000px';
        this.rulerElement.style.top = '-1000px';
        this.rulerElement.style.visibility = 'hidden';

        document.body.appendChild(this.rulerElement);
    }

    /**
     * Measures the passed internal tag's data evaluating the width of the span
     *
     * @param {String} text
     */
    measureWidth(text) {
        this.rulerElement.innerHTML = text;

        return Math.ceil(this.rulerElement.getBoundingClientRect().width);
    }
}


/***/ }),

/***/ "./TagsTransform/tag-check.js":
/*!************************************!*\
  !*** ./TagsTransform/tag-check.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagCheck)
/* harmony export */ });
/* harmony import */ var _tags_conversion__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./tags-conversion */ "./TagsTransform/tags-conversion.js");
/* harmony import */ var _check_result__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./check-result */ "./TagsTransform/check-result.js");



class TagCheck {
    #userCanModifyWhitespaceTags;
    #userCanInsertWhitespaceTags;

    constructor(
        referenceTags,
        tagsConversion,
        userCanModifyWhitespaceTags,
        userCanInsertWhitespaceTags
    ) {
        this.referenceTags = referenceTags;
        this.tagsConversion = tagsConversion;
        this.#userCanModifyWhitespaceTags = userCanModifyWhitespaceTags;
        this.#userCanInsertWhitespaceTags = userCanInsertWhitespaceTags;
    }

    /**
     * check and fix tags
     *
     * @param {HTMLElement} node
     */
    checkTags(node) {
        const tags = node.getElementsByTagName('img');

        this.#fixDuplicateImgIds(tags);
        const checkResult = this.#validateTags(tags);
        this.#removeOrphanedTags(tags);

        if (!checkResult.isSuccessful()) {
            console.log('Check result is not successful');
            console.log(checkResult);
        }

        return checkResult;
    }

    #validateTags(tags) {
        const _this = this;

        // Extract tags from HTML
        const tagList = Array.from(tags)
            // Filter out deleted tags and tags with the qmflag class as we don't need to check them
            .filter(tag => !this.#isDeletedTag(tag) || /qmflag/.test(tag.className));

        const ignoreWhitespace = this.#shouldIgnoreWhitespaceTags();
        const tagStack = [];
        const seenTags = new Set();
        const errors = {
            missingTags: [],
            excessTags: [],
            duplicatedTags: [],
            wrongNesting: []
        };

        for (const tag of tagList) {
            const tagType = _this.tagsConversion.getInternalTagType(tag);
            const tagId = _this.tagsConversion.getInternalTagNumber(tag);

            let isWhitespaceTag = tagType === _tags_conversion__WEBPACK_IMPORTED_MODULE_0__["default"].TYPE.WHITESPACE;

            //ignore whitespace and nodes without ids
            if ((isWhitespaceTag && ignoreWhitespace) || null === tagId) {
                continue;
            }

            const tagKey = `${tagType}-${tagId}`;
            if (seenTags.has(tagKey)) {
                errors.duplicatedTags.push(tag);
            } else {
                seenTags.add(tagKey);
            }

            if (!this.referenceTags[tagType][tagId]) {
                if (isWhitespaceTag && this.isAllowedAddingWhitespaceTags()) {
                    continue;
                }

                errors.excessTags.push(tag);
            }

            if (tagType === _tags_conversion__WEBPACK_IMPORTED_MODULE_0__["default"].TYPE.OPEN) {
                tagStack.push(tagId);
            } else if (tagType === _tags_conversion__WEBPACK_IMPORTED_MODULE_0__["default"].TYPE.CLOSE) {
                if (tagStack.length === 0 || tagStack.pop() !== tagId) {
                    errors.wrongNesting.push(tag);
                }
            }
        }

        // Ensure no unclosed tags remain
        if (tagStack.length > 0) {
            errors.wrongNesting.push(...tagStack);
        }

        for (const [referenceTagType, referenceTags] of Object.entries(this.referenceTags)) {
            if (ignoreWhitespace && referenceTagType === _tags_conversion__WEBPACK_IMPORTED_MODULE_0__["default"].TYPE.WHITESPACE) {
                continue;
            }

            for (const [referenceTagId, referenceTag] of Object.entries(referenceTags)) {
                if (!seenTags.has(`${referenceTagType}-${referenceTagId}`)) {
                    errors.missingTags.push(referenceTag._transformed);
                }
            }
        }

        return new _check_result__WEBPACK_IMPORTED_MODULE_1__["default"](
            errors.missingTags,
            errors.duplicatedTags,
            errors.excessTags,
            errors.wrongNesting.length === 0
        );
    }

    /**
     * Checks if a tag is inside a del-tag and thus can be regarded as a deleted tag
     * @param {Node} node
     */
    #isDeletedTag(node) {
        while (node.parentElement && node.parentElement.tagName.toLowerCase() !== 'body') {
            if (node.parentElement.tagName.toLowerCase() === 'del') {
                return true;
            }

            node = node.parentElement;
        }

        return false;
    }

    /**
     * @returns {Boolean}
     */
    #shouldIgnoreWhitespaceTags() {
        return this.#userCanModifyWhitespaceTags;
    }

    /**
     * @returns {boolean}
     */
    isAllowedAddingWhitespaceTags() {
        return this.#userCanInsertWhitespaceTags;
    }

    /**
     * Fixes duplicate img ids in the opened editor on unmarkup (MQM tags)
     * Works with <img> tags with the following specifications:
     * IMG needs an id Attribute. Assuming that the id contains the strings "-open" or "-close".
     * The rest of the id string is identical.
     * Needs also an attribute "data-t5qid" which is containing the plain ID of the tag pair.
     * If a duplicated img tag is found, the "123" of the id will be replaced with a generated Ext.id()
     *
     * example, tag with needed infos:
     * <img id="foo-open-123" data-t5qid="123"/> open tag
     * <img id="foo-close-123" data-t5qid="123"/> close tag
     *
     * copying this tags will result in
     * <img id="foo-open-ext-456" data-t5qid="ext-456"/>
     * <img id="foo-close-ext-456" data-t5qid="ext-456"/>
     *
     * Warning:
     * fixing IDs means that existing ids are wandering forward:
     * Before duplicating:
     * This is the [X 1]testtext[/X 1].
     * after duplicating, before fixing:
     * This [X 1]is[/X 1] the [X 1]testtext[/X 1].
     * after fixing:
     * This [X 1]is[/X 1] the [X 2]testtext[/X 2].
     *
     * @param {HTMLCollection} tags
     */
    #fixDuplicateImgIds(tags) {
        let ids = {},
            stackList = {},
            updateId = function (img, newQid, oldQid) {
                img.id = img.id.replace(new RegExp(oldQid + '$'), newQid);
                img.setAttribute('data-t5qid', newQid);
            };

        for (let img of tags) {
            let newQid,
                oldQid = _tags_conversion__WEBPACK_IMPORTED_MODULE_0__["default"].getElementsQualityId(img),
                id = img.id,
                pid,
                open;

            if (!id || _tags_conversion__WEBPACK_IMPORTED_MODULE_0__["default"].isDuplicateSaveTag(img)) {
                continue;
            }

            if (!ids[id]) {
                //id does not yet exist, then it is not duplicated => out
                ids[id] = true;

                continue;
            }

            if (stackList[id] && stackList[id].length > 0) {
                newQid = stackList[id].shift();
                updateId(img, newQid, oldQid);

                continue;
            }

            open = new RegExp("-open");

            if (open.test(id)) {
                pid = id.replace(open, '-close');
            } else {
                pid = id.replace(/-close/, '-open');
            }

            if (!stackList[pid]) {
                stackList[pid] = [];
            }

            // TODO replace Ext dependency
            newQid = Ext.id();
            stackList[pid].push(newQid);

            updateId(img, newQid, oldQid);
        }
    }

    /**
     * removes orphaned tags (MQM only)
     * assuming same id for open and close tag. Each Tag ID contains the string "-open" or "-close"
     * prepends "remove-" to the id of an orphaned tag
     *
     * @param {HTMLCollection} nodeList
     */
    #removeOrphanedTags(nodeList) {
        let openers = {},
            closers = {},
            hasRemoves = false;

        for (let img of nodeList) {
            if (_tags_conversion__WEBPACK_IMPORTED_MODULE_0__["default"].isDuplicateSaveTag(img)) {
                return;
            }

            if (/-open/.test(img.id)) {
                openers[img.id] = img;
            }

            if (/-close/.test(img.id)) {
                closers[img.id] = img;
            }
        }

        for (const [id, img] of Object.entries(openers)) {
            let closeId = img.id.replace(/-open/, '-close');

            if (closers[closeId]) {
                //closer zum opener => aus "closer entfern" liste raus
                delete closers[closeId];
            } else {
                //kein closer zum opener => opener zum entfernen markieren
                hasRemoves = true;
                img.id = 'remove-' + img.id;
            }
        }

        for (const [id, img] of Object.entries(closers)) {
            hasRemoves = true;
            img.id = 'remove-' + img.id;
        }

        if (hasRemoves) {
            // TODO fix this
            Editor.MessageBox.addInfo(this.strings.tagRemovedText);
        }
    }
}


/***/ }),

/***/ "./TagsTransform/tags-conversion.js":
/*!******************************************!*\
  !*** ./TagsTransform/tags-conversion.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagsConversion)
/* harmony export */ });
/* harmony import */ var _ruler__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ruler */ "./TagsTransform/ruler.js");
/* harmony import */ var _tags_mode_provider__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./tags-mode-provider */ "./TagsTransform/tags-mode-provider.js");
/* harmony import */ var _templating__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./templating */ "./TagsTransform/templating.js");
/* harmony import */ var _Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Tools/string-to-dom */ "./Tools/string-to-dom.js");





const htmlEncode = (__webpack_require__(/*! js-htmlencode */ "./node_modules/js-htmlencode/src/htmlencode.js").htmlEncode);

class TagsConversion {
    static TYPE = {
        SINGLE: 'single',
        OPEN: 'open',
        CLOSE: 'close',
        WHITESPACE: 'whitespace',
    };

    constructor(editorElement, tagsModeProvider) {
        this._editorElement = editorElement;
        this._idPrefix = 'tag-image-';
        this._ruler = new _ruler__WEBPACK_IMPORTED_MODULE_0__["default"]();
        this._tagModeProvider = tagsModeProvider;
        this._templating = new _templating__WEBPACK_IMPORTED_MODULE_2__["default"](this._idPrefix);
    };

    transform(item, pixelMapping = null) {
        if (this.isTextNode(item)) {
            let text = item.cloneNode();
            text.data = (htmlEncode(item.data));

            return text;
        }

        // INS- & DEL-nodes
        if (this.isTrackChangesNode(item)) {
            let regExOpening = new RegExp('<\s*' + item.tagName.toLowerCase() + '.*?>'), // Example: /<\s*ins.*?>/g
                regExClosing = new RegExp('<\s*\/\s*' + item.tagName.toLowerCase() + '\s*.*?>'), // Example: /<\s*\/\s*ins\s*.*?>/g
                openingTag = item.outerHTML.match(regExOpening)[0],
                closingTag = item.outerHTML.match(regExClosing)[0];

            let result = null;

            switch (true) {
                case /(^|\s)trackchanges(\s|$)/.test(item.className):
                    // Keep nodes from TrackChanges, but run replaceTagToImage for them as well
                    result = (0,_Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__["default"])(openingTag + closingTag).childNodes[0];
                    for (const child of item.childNodes) {
                        result.appendChild(this.transform(child, pixelMapping));
                    }

                    break;

                case /(^|\s)tmMatchGridResultTooltip(\s|$)/.test(item.className):
                    // diffTagger-markups in Fuzzy Matches: keep the text from ins-Tags, remove del-Tags completely
                    if (item.tagName.toLowerCase() === 'ins') {
                        result = item.cloneNode();
                        result.data = htmlEncode(item.textContent);
                    }

                    if (item.tagName.toLowerCase() === 'del') {
                        // -
                    }

                    break;
            }

            return result;
        }

        if (this._isImageNode(item)) {
            return 'image FIXME';
            let result = this._imgNodeToString(item, true);
        }

        // Span for terminology
        if (this.isTermNode(item)) {
            let termData = {
                className: item.className,
                title: item.title,
                qualityId: TagsConversion.getElementsQualityId(item)
            };

            // TODO fix this
            if (this.fieldTypeToEdit) {
                let replacement = this.fieldTypeToEdit + '-$1';
                termData.className = termData.className.replace(/(transFound|transNotFound|transNotDefined)/, replacement);
            }

            let result = this._applyTemplate('termspan', termData) + '</span>';

            let term = (0,_Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__["default"])(result).childNodes[0];

            item.childNodes.forEach((child) => {
                term.appendChild(this.transform(child, pixelMapping));
            });

            return term;
        }

        //some tags are marked as to be ignored in the editor, so we ignore them
        if (this._isIgnoredNode(item)) {
            return null;
        }

        //if we copy and paste content there could be other divs, so we allow only internal-tag divs:
        if (this.isInternalTagNode(item)) {
            return (0,_Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__["default"])(this._replaceInternalTagToImage(item, this._editorElement, pixelMapping)).childNodes[0];
        }

        return null;
    }

    /**
     * Generate a new whitespace internal tag
     *
     * @param {string} whitespaceType - the type of the whitespace tag (nbsp, newline, tab)
     * @param {int} tagNr - the number of the tag
     * @returns {Node}
     */
    generateWhitespaceTag(whitespaceType, tagNr) {
        let classNameForTagType,
            className;

        let data = this._getInitialData();
        data.nr = tagNr;

        switch (whitespaceType) {
            case 'nbsp':
                classNameForTagType = 'single 636861722074733d226332613022206c656e6774683d2231222f nbsp whitespace';
                data.title = '&lt;' + data.nr + '/&gt;: No-Break Space (NBSP)';
                data.id = 'char';
                data.length = '1';
                data.text = '⎵';
                break;

            case 'newline':
                classNameForTagType = 'single 736f667452657475726e2f newline whitespace';
                data.title = '&lt;' + data.nr + '/&gt;: Newline';
                //previously here was a hardReturn which makes mostly no sense, since just a \n (called here softreturn) is used in most data formats
                data.id = 'softReturn';
                data.length = '1';
                data.text = '↵';
                break;

            case 'tab':
                classNameForTagType = 'single 7461622074733d22303922206c656e6774683d2231222f tab whitespace';
                data.title = '&lt;' + data.nr + '/&gt;: 1 tab character';
                data.id = 'tab';
                data.length = '1';
                data.text = '→';
                break;
        }

        className = classNameForTagType + ' internal-tag ownttip';
        data = this._addTagType(className, data);

        return (0,_Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__["default"])(this._renderInternalTags(className, data)).childNodes[0];
    }

    /**
     * What's the number for the next Whitespace-Tag?
     *
     * @return number nextTagNr
     */
    getNextWhitespaceTagNumber(imgInTarget) {
        let collectedIds = ['0'];

        // target
        for (const imgNode of imgInTarget) {
            let imgClassList = imgNode.classList;
            if (imgClassList.contains('single') || imgClassList.contains('open')) {
                collectedIds.push(imgNode.id);
            }
        }

        // use the highest
        return Math.max.apply(null, collectedIds.map(function (val) {
            return parseInt(val.replace(/[^0-9]*/, ''));
        })) + 1;
    }

    isTermNode(item) {
        return /(^|[\s])term([\s]|$)/.test(item.className);
    }

    isInternalTagNode(item) {
        return /(^|[\s])internal-tag([\s]|$)/.test(item.className);
    }

    isWhitespaceNode(item) {
        return this._isWhitespaceTag(item.className);
    }

    getInternalTagType(item) {
        return this.getInternalTagTypeByClass(item.className);
    }

    getInternalTagTypeByClass(className) {
        if (this._isOpenTag(className)) {
            return TagsConversion.TYPE.OPEN;
        }

        if (this._isCloseTag(className)) {
            return TagsConversion.TYPE.CLOSE;
        }

        if (this._isWhitespaceTag(className)) {
            return TagsConversion.TYPE.WHITESPACE;
        }

        if (this._isSingleTag(className)) {
            return TagsConversion.TYPE.SINGLE;
        }

        throw new Error('Unknown internal tag type');
    }

    /**
     *
     * @param {HTMLDivElement} item
     * @returns {string}
     */
    getInternalTagNumber(item) {
        if (item.tagName === 'IMG') {
            return item.getAttribute('data-tag-number');
        }

        const spanShort = item.querySelector('span.short');
        let number;

        let shortTagContent = spanShort.innerHTML;
        number = shortTagContent.replace(/[^0-9]/g, '');

        if (shortTagContent.search(/locked/) !== -1) {
            number = 'locked' + data.nr;
        }

        return number;
    }

    _replaceInternalTagToImage(item, editorElement, pixelMapping) {
        let data = this._extractInternalTagsData(item, pixelMapping);

        if (this._tagModeProvider.isFullTagMode() || data.whitespaceTag) {
            data.path = this._getSvg(data.text, data.fullWidth, editorElement);
        } else {
            data.path = this._getSvg(data.shortTag, data.shortWidth, editorElement);
        }

        return this._applyTemplate('internalimg', data);
    }

    /**
     * returns true if given html node is a duplicatesavecheck img tag
     *
     * @param {HTMLElement} img
     * @return {Boolean}
     */
    static isDuplicateSaveTag(img) {
        return img.tagName === 'IMG' && img.className && /duplicatesavecheck/.test(img.className);
    }

    /**
     * Convert HTML image node to string
     *
     * @param {HTMLElement} imgNode
     * @param {boolean} markup
     * @returns {string|string|string|string|*}
     * @private
     */
    _imgNodeToString(imgNode, markup) {
        //it may happen that internal tags already converted to img are tried to be markuped again. In that case, just return the tag:
        if (/^tag-image-/.test(imgNode.id)) {
            return imgNode.outerHTML;
        }

        let id = '',
            src = imgNode.src.replace(/^.*\/\/[^\/]+/, ''),
            // TODO get rid of Ext dependency
            img = Ext.fly(imgNode),
            comment = img.getAttribute('data-comment'),
            qualityId = TagsConversion.getElementsQualityId(img);

        if (markup) {
            //on markup an id is needed for remove orphaned tags
            //qm-image-open-#
            //qm-image-close-#
            id = (/open/.test(imgNode.className) ? 'open' : 'close');
            id = ' id="qm-image-' + id + '-' + (qualityId ? qualityId : '') + '"';
        }

        return `<img${id} class="${imgNode.className}" data-t5qid="${(qualityId ?? '')}" data-comment="${comment ?? ''}" src="${src}" />`;
    }

    /**
     * Get data from the tags
     *
     * @param {HTMLDivElement} item
     * @param pixelMapping
     */
    _extractInternalTagsData(item, pixelMapping) {
        let data = this._getInitialData();

        const spanFull = item.querySelector('span.full');
        const spanShort = item.querySelector('span.short');

        data.text = spanFull.innerHTML.replace(/"/g, '&quot;');
        data.id = spanFull.getAttribute('data-originalid');
        data.qualityId = TagsConversion.getElementsQualityId(item);
        data.title = htmlEncode(spanShort.getAttribute('title'));
        data.length = spanFull.getAttribute('data-length');

        //old way is to use only the id attribute, new way is to use separate data fields
        // both way are currently used!
        if (!data.id) {
            let split = spanFull.getAttribute('id').split('-');
            data.id = split.shift();
        }

        data.nr = this.getInternalTagNumber(item);
        data = this._addTagType(item.className, data);

        // if it is a whitespace tag we have to precalculate the pixel width of the tag (if possible)
        if (data.whitespaceTag && pixelMapping) {
            data.pixellength = pixelMapping.getPixelLengthFromTag(item);
        } else {
            data.pixellength = 0;
        }

        // get the dimensions of the inner spans
        data.fullWidth = this._ruler.measureWidth(data.text);
        data.shortWidth = this._ruler.measureWidth(data.shortTag);

        return data;
    }

    /**
     * Comapatibility function to retrieve the quality id from a DOM node
     * NOTE: historically the quality-id was encoded as "data-seq"
     *
     * @param {HTMLElement} element
     */
    static getElementsQualityId(element) {
        if (element.hasAttribute('data-t5qid')) {
            return element.getAttribute('data-t5qid');
        }

        if (element.hasAttribute('data-seq')) {
            return element.getAttribute('data-seq');
        }

        return null;
    }

    /**
     * Add type etc. to data according to tag-type.
     *
     * @param {string} className
     * @param {object} data
     *
     * @return {object} data
     */
    _addTagType(className, data) {
        data.type = 'internal-tag';

        //Fallunterscheidung Tag Typ
        switch (true) {
            case /open/.test(className):
                data.type += ' open';
                data.suffix = '-left';
                data.shortTag = data.nr;
                break;

            case /close/.test(className):
                data.type += ' close';
                data.suffix = '-right';
                data.shortTag = '/' + data.nr;
                break;

            case /single/.test(className):
                data.type += ' single';
                data.suffix = '-single';
                data.shortTag = data.nr + '/';
                break;
        }

        data.key = data.type + data.nr;
        data.shortTag = '&lt;' + data.shortTag + '&gt;';
        data.whitespaceTag = /nbsp|tab|space|newline|char|whitespace/.test(className);

        if (data.whitespaceTag) {
            data.type += ' whitespace';

            if (/newline/.test(className)) {
                data.type += ' newline';
            }

            data.key = 'whitespace' + data.nr;
        } else {
            data.key = data.type + data.nr;
        }

        return data;
    }

    _getInitialData() {
        return {
            fullPath: Editor.data.segments.fullTagPath,
            shortPath: Editor.data.segments.shortTagPath
        };
    }

    _getSvg(text, width, editorElement) {
        let prefix = 'data:image/svg+xml;charset=utf-8,',
            svg = '',
            styles = this._getStyle(editorElement, ['font-size', 'font-style', 'font-weight', 'font-family', 'line-height', 'text-transform', 'letter-spacing', 'word-break']),
            lineHeight = styles['line-height'].replace(/px/, '');

        // TODO get rid of Ext dependency
        if (!Ext.isNumber(lineHeight)) {
            lineHeight = Math.round(styles['font-size'].replace(/px/, '') * 1.3);
        }

        // padding left 1px and right 1px by adding x+1 and width + 2
        // svg += '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
        svg += '<svg xmlns="http://www.w3.org/2000/svg" height="' + lineHeight + '" width="' + (width + 2) + '">';
        svg += '<rect width="100%" height="100%" fill="rgb(207,207,207)" rx="3" ry="3"/>';
        svg += '<text x="1" y="' + (lineHeight - 5) + '" font-size="' + styles['font-size'] + '" font-weight="' + styles['font-weight'] + '" font-family="' + styles['font-family'].replace(/"/g, "'") + '">'
        svg += htmlEncode(text) + '</text></svg>';

        return prefix + encodeURI(svg);
    }

    _getStyle(element, styleProps) {
        let out = [],
            defaultView = (element.ownerDocument || document).defaultView,
            computedStyle = defaultView.getComputedStyle(element, null);

        styleProps.forEach((prop) => {
            if (defaultView && defaultView.getComputedStyle) {
                // sanitize property name to css notation
                // (hyphen separated words eg. font-size)
                prop = prop.replace(/([A-Z])/g, "-$1").toLowerCase();

                out[prop] = computedStyle.getPropertyValue(prop);
            }
        });

        return out;
    }

    // getSingleCharacter(tagMatches) {
    //     let character = '';
    //
    //     switch (tagMatches[1]) {
    //         case 'hardReturn':
    //             character = '↵';
    //             break;
    //         case 'softReturn':
    //             character = '↵';
    //             break;
    //         case 'char':
    //             if (tagMatches[5] === 'c2a0') {
    //                 character = '⎵';
    //             }
    //             break;
    //         case 'space':
    //             character = '⎵';
    //             break;
    //         case 'tab':
    //             character = '→';
    //             break;
    //     }
    //
    //     return character;
    // }

    /**
     * Applies our templates to the given data by type
     *
     * @returns {string}
     */

    _applyTemplate(type, data) {
        switch (type) {
            case 'internalimg':
                return (this._hasQIdProp(data) ? this._templating.intImgTplQid.apply(data) : this._templating.intImgTpl.apply(data));

            case 'internalspans':
                return this._templating.intSpansTpl.apply(data);

            case 'termspan':
                return (this._hasQIdProp(data) ? this._templating.termSpanTplQid.apply(data) : this._templating.termSpanTpl.apply(data));

            default:
                console.log('Invalid type "' + type + '" when using compileTemplate!');

                return '';
        }
    }

    _hasQIdProp(data) {
        return (data.qualityId && data.qualityId !== '');
    }

    /**
     * Render html for internal Tags displayed as div-Tags.
     * In case of changes, also check $htmlTagTpl in ImageTag.php
     *
     * @param {string} className
     * @param {object} data
     *
     * @return String
     */
    _renderInternalTags(className, data) {
        return '<div class="' + className + '">' + this._applyTemplate('internalspans', data) + '</div>';
    }

    /**
     * returns a IMG tag with a segment identifier for "checkplausibilityofput" check in PHP
     *
     * @param {integer} segmentId
     * @param {String} fieldName
     * @return {String}
     */
    _getDuplicateCheckImg(segmentId, fieldName) {
        // TODO get rid of Ext dependency
        return '<img src="' + Ext.BLANK_IMAGE_URL + '" class="duplicatesavecheck" data-segmentid="' + segmentId + '" data-fieldname="' + fieldName + '">';
    }

    isTextNode(item) {
        return item.nodeName === "#text"
    }

    isTrackChangesNode(item) {
        return item.tagName === 'INS' || this.isTrackChangesDelNode(item);
    }

    isTrackChangesDelNode(item) {
        return item.tagName === 'DEL';
    }

    _isImageNode(item) {
        return item.tagName === 'IMG' && !TagsConversion.isDuplicateSaveTag(item);
    }

    _isIgnoredNode(item) {
        return /(^|[\s])ignoreInEditor([\s]|$)/.test(item.className);
    }

    _isSingleTagNode(item) {
        return this._isSingleTag(item.className);
    }

    _isOpenTagNode(item) {
        return this._isOpenTag(item.className);
    }

    _isCloseTagNode(item) {
        return this._isCloseTag(item.className);
    }

    _isSingleTag(className) {
        return /(^|[\s])single([\s]|$)/.test(className);
    }

    _isOpenTag(className) {
        return /(^|[\s])open([\s]|$)/.test(className);
    }

    _isCloseTag(className) {
        return /(^|[\s])close([\s]|$)/.test(className);
    }

    _isWhitespaceTag(className) {
        return /whitespace|nbsp|tab|space|newline|char/.test(className);
    }
}


/***/ }),

/***/ "./TagsTransform/tags-mode-provider.js":
/*!*********************************************!*\
  !*** ./TagsTransform/tags-mode-provider.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagsModeProvider)
/* harmony export */ });
class TagsModeProvider {
    constructor() {
        // TODO change this to get rid of Ext dependency (app state?)
        this.viewModesController = Editor.app.getController('ViewModes');
    }

    isFullTagMode() {
        return this.viewModesController.isFullTag();
    }
}


/***/ }),

/***/ "./TagsTransform/templating.js":
/*!*************************************!*\
  !*** ./TagsTransform/templating.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Templating)
/* harmony export */ });
class Templating {
    /**
     * @param {string} idPrefix
     */
    constructor(idPrefix) {
        this.idPrefix = idPrefix;

        // TODO remove dependency on EXTJS
        this.intImgTpl = new Ext.Template([
            '<img id="' + this.idPrefix + '{key}" class="{type}" title="{title}" alt="{text}" src="{path}" data-length="{length}" data-pixellength="{pixellength}" data-tag-number="{nr}"/>'
        ]);
        this.intImgTplQid = new Ext.Template([
            '<img id="' + this.idPrefix + '{key}" class="{type}" title="{title}" alt="{text}" src="{path}" data-length="{length}" data-pixellength="{pixellength}" data-t5qid="{qualityId}" />'
        ]);
        this.intSpansTpl = new Ext.Template([
            '<span title="{title}" class="short">{shortTag}</span>',
            '<span data-originalid="{id}" data-length="{length}" class="full">{text}</span>'
        ]);
        this.termSpanTpl = new Ext.Template([
            '<span class="{className}" title="{title}"></span>'
        ]);
        this.termSpanTplQid = new Ext.Template([
            '<span class="{className}" title="{title}" data-t5qid="{qualityId}"></span>'
        ]);
        this.intImgTpl.compile();
        this.intImgTplQid.compile();
        this.intSpansTpl.compile();
        this.termSpanTpl.compile();
        this.termSpanTplQid.compile();
    }


}


/***/ }),

/***/ "./Tools/calculate-node-length.js":
/*!****************************************!*\
  !*** ./Tools/calculate-node-length.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ calculateNodeLength)
/* harmony export */ });


/**
 * @param {ChildNode} node
 * @returns {number}
 */
function calculateNodeLength(node) {
    let length = 0;

    for (const child of node.childNodes) {
        if (child.nodeType === Node.ELEMENT_NODE) {
            length += calculateNodeLength(child);

            continue;
        }

        length += child.length;
    }

    return length;
}


/***/ }),

/***/ "./Tools/escape-html.js":
/*!******************************!*\
  !*** ./Tools/escape-html.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ escapeHtml)
/* harmony export */ });


/**
 * @param {string} text
 * @returns {string}
 */
function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


/***/ }),

/***/ "./Tools/string-to-dom.js":
/*!********************************!*\
  !*** ./Tools/string-to-dom.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ stringToDom)
/* harmony export */ });


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
function stringToDom (str) {
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



/***/ }),

/***/ "./Tools/unescape-html.js":
/*!********************************!*\
  !*** ./Tools/unescape-html.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ unescapeHtml)
/* harmony export */ });


/**
 * @param {String} text
 * @returns {String}
 */
function unescapeHtml(text) {
    return text
        .replace(/&amp;/g, "&")
        .replace(/&lt;/g, "<")
        .replace(/&gt;/g, ">")
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'");
}


/***/ }),

/***/ "./Tools/unwrap-html-node.js":
/*!***********************************!*\
  !*** ./Tools/unwrap-html-node.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ unwrapHtmlNodeToText)
/* harmony export */ });
/* harmony import */ var _escape_html__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./escape-html */ "./Tools/escape-html.js");




/**
 * @param {ChildNode} node
 * @returns {string}
 */
function unwrapHtmlNodeToText(node) {
    let result = '';

    for (const child of node.childNodes) {
        result += (0,_escape_html__WEBPACK_IMPORTED_MODULE_0__["default"])(child.data);
    }

    return result;
}


/***/ }),

/***/ "./node_modules/js-htmlencode/src/htmlencode.js":
/*!******************************************************!*\
  !*** ./node_modules/js-htmlencode/src/htmlencode.js ***!
  \******************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_RESULT__;/**
 * [js-htmlencode]{@link https://github.com/emn178/js-htmlencode}
 *
 * @version 0.3.0
 * @author Chen, Yi-Cyuan [emn178@gmail.com]
 * @copyright Chen, Yi-Cyuan 2014-2017
 * @license MIT
 */
/*jslint bitwise: true */
(function () {
  'use strict';

  var root = typeof window === 'object' ? window : {};
  var NODE_JS = !root.JS_HTMLENCODE_NO_NODE_JS && typeof process === 'object' && process.versions && process.versions.node;
  if (NODE_JS) {
    root = __webpack_require__.g;
  }
  var COMMON_JS = !root.JS_HTMLENCODE_NO_COMMON_JS && "object" === 'object' && module.exports;
  var AMD =  true && __webpack_require__.amdO;

  var HTML_ENTITIES = {
    '&nbsp;' : '\u00A0',
    '&iexcl;' : '\u00A1',
    '&cent;' : '\u00A2',
    '&pound;' : '\u00A3',
    '&curren;' : '\u00A4',
    '&yen;' : '\u00A5',
    '&brvbar;' : '\u00A6',
    '&sect;' : '\u00A7',
    '&uml;' : '\u00A8',
    '&copy;' : '\u00A9',
    '&ordf;' : '\u00AA',
    '&laquo;' : '\u00AB',
    '&not;' : '\u00AC',
    '&shy;' : '\u00AD',
    '&reg;' : '\u00AE',
    '&macr;' : '\u00AF',
    '&deg;' : '\u00B0',
    '&plusmn;' : '\u00B1',
    '&sup2;' : '\u00B2',
    '&sup3;' : '\u00B3',
    '&acute;' : '\u00B4',
    '&micro;' : '\u00B5',
    '&para;' : '\u00B6',
    '&middot;' : '\u00B7',
    '&cedil;' : '\u00B8',
    '&sup1;' : '\u00B9',
    '&ordm;' : '\u00BA',
    '&raquo;' : '\u00BB',
    '&frac14;' : '\u00BC',
    '&frac12;' : '\u00BD',
    '&frac34;' : '\u00BE',
    '&iquest;' : '\u00BF',
    '&Agrave;' : '\u00C0',
    '&Aacute;' : '\u00C1',
    '&Acirc;' : '\u00C2',
    '&Atilde;' : '\u00C3',
    '&Auml;' : '\u00C4',
    '&Aring;' : '\u00C5',
    '&AElig;' : '\u00C6',
    '&Ccedil;' : '\u00C7',
    '&Egrave;' : '\u00C8',
    '&Eacute;' : '\u00C9',
    '&Ecirc;' : '\u00CA',
    '&Euml;' : '\u00CB',
    '&Igrave;' : '\u00CC',
    '&Iacute;' : '\u00CD',
    '&Icirc;' : '\u00CE',
    '&Iuml;' : '\u00CF',
    '&ETH;' : '\u00D0',
    '&Ntilde;' : '\u00D1',
    '&Ograve;' : '\u00D2',
    '&Oacute;' : '\u00D3',
    '&Ocirc;' : '\u00D4',
    '&Otilde;' : '\u00D5',
    '&Ouml;' : '\u00D6',
    '&times;' : '\u00D7',
    '&Oslash;' : '\u00D8',
    '&Ugrave;' : '\u00D9',
    '&Uacute;' : '\u00DA',
    '&Ucirc;' : '\u00DB',
    '&Uuml;' : '\u00DC',
    '&Yacute;' : '\u00DD',
    '&THORN;' : '\u00DE',
    '&szlig;' : '\u00DF',
    '&agrave;' : '\u00E0',
    '&aacute;' : '\u00E1',
    '&acirc;' : '\u00E2',
    '&atilde;' : '\u00E3',
    '&auml;' : '\u00E4',
    '&aring;' : '\u00E5',
    '&aelig;' : '\u00E6',
    '&ccedil;' : '\u00E7',
    '&egrave;' : '\u00E8',
    '&eacute;' : '\u00E9',
    '&ecirc;' : '\u00EA',
    '&euml;' : '\u00EB',
    '&igrave;' : '\u00EC',
    '&iacute;' : '\u00ED',
    '&icirc;' : '\u00EE',
    '&iuml;' : '\u00EF',
    '&eth;' : '\u00F0',
    '&ntilde;' : '\u00F1',
    '&ograve;' : '\u00F2',
    '&oacute;' : '\u00F3',
    '&ocirc;' : '\u00F4',
    '&otilde;' : '\u00F5',
    '&ouml;' : '\u00F6',
    '&divide;' : '\u00F7',
    '&oslash;' : '\u00F8',
    '&ugrave;' : '\u00F9',
    '&uacute;' : '\u00FA',
    '&ucirc;' : '\u00FB',
    '&uuml;' : '\u00FC',
    '&yacute;' : '\u00FD',
    '&thorn;' : '\u00FE',
    '&yuml;' : '\u00FF',
    '&quot;' : '\u0022',
    '&amp;' : '\u0026',
    '&lt;' : '\u003C',
    '&gt;' : '\u003E',
    '&apos;' : '\u0027',
    '&OElig;' : '\u0152',
    '&oelig;' : '\u0153',
    '&Scaron;' : '\u0160',
    '&scaron;' : '\u0161',
    '&Yuml;' : '\u0178',
    '&circ;' : '\u02C6',
    '&tilde;' : '\u02DC',
    '&ensp;' : '\u2002',
    '&emsp;' : '\u2003',
    '&thinsp;' : '\u2009',
    '&zwnj;' : '\u200C',
    '&zwj;' : '\u200D',
    '&lrm;' : '\u200E',
    '&rlm;' : '\u200F',
    '&ndash;' : '\u2013',
    '&mdash;' : '\u2014',
    '&lsquo;' : '\u2018',
    '&rsquo;' : '\u2019',
    '&sbquo;' : '\u201A',
    '&ldquo;' : '\u201C',
    '&rdquo;' : '\u201D',
    '&bdquo;' : '\u201E',
    '&dagger;' : '\u2020',
    '&Dagger;' : '\u2021',
    '&permil;' : '\u2030',
    '&lsaquo;' : '\u2039',
    '&rsaquo;' : '\u203A',
    '&euro;' : '\u20AC',
    '&fnof;' : '\u0192',
    '&Alpha;' : '\u0391',
    '&Beta;' : '\u0392',
    '&Gamma;' : '\u0393',
    '&Delta;' : '\u0394',
    '&Epsilon;' : '\u0395',
    '&Zeta;' : '\u0396',
    '&Eta;' : '\u0397',
    '&Theta;' : '\u0398',
    '&Iota;' : '\u0399',
    '&Kappa;' : '\u039A',
    '&Lambda;' : '\u039B',
    '&Mu;' : '\u039C',
    '&Nu;' : '\u039D',
    '&Xi;' : '\u039E',
    '&Omicron;' : '\u039F',
    '&Pi;' : '\u03A0',
    '&Rho;' : '\u03A1',
    '&Sigma;' : '\u03A3',
    '&Tau;' : '\u03A4',
    '&Upsilon;' : '\u03A5',
    '&Phi;' : '\u03A6',
    '&Chi;' : '\u03A7',
    '&Psi;' : '\u03A8',
    '&Omega;' : '\u03A9',
    '&alpha;' : '\u03B1',
    '&beta;' : '\u03B2',
    '&gamma;' : '\u03B3',
    '&delta;' : '\u03B4',
    '&epsilon;' : '\u03B5',
    '&zeta;' : '\u03B6',
    '&eta;' : '\u03B7',
    '&theta;' : '\u03B8',
    '&iota;' : '\u03B9',
    '&kappa;' : '\u03BA',
    '&lambda;' : '\u03BB',
    '&mu;' : '\u03BC',
    '&nu;' : '\u03BD',
    '&xi;' : '\u03BE',
    '&omicron;' : '\u03BF',
    '&pi;' : '\u03C0',
    '&rho;' : '\u03C1',
    '&sigmaf;' : '\u03C2',
    '&sigma;' : '\u03C3',
    '&tau;' : '\u03C4',
    '&upsilon;' : '\u03C5',
    '&phi;' : '\u03C6',
    '&chi;' : '\u03C7',
    '&psi;' : '\u03C8',
    '&omega;' : '\u03C9',
    '&thetasym;' : '\u03D1',
    '&upsih;' : '\u03D2',
    '&piv;' : '\u03D6',
    '&bull;' : '\u2022',
    '&hellip;' : '\u2026',
    '&prime;' : '\u2032',
    '&Prime;' : '\u2033',
    '&oline;' : '\u203E',
    '&frasl;' : '\u2044',
    '&weierp;' : '\u2118',
    '&image;' : '\u2111',
    '&real;' : '\u211C',
    '&trade;' : '\u2122',
    '&alefsym;' : '\u2135',
    '&larr;' : '\u2190',
    '&uarr;' : '\u2191',
    '&rarr;' : '\u2192',
    '&darr;' : '\u2193',
    '&harr;' : '\u2194',
    '&crarr;' : '\u21B5',
    '&lArr;' : '\u21D0',
    '&uArr;' : '\u21D1',
    '&rArr;' : '\u21D2',
    '&dArr;' : '\u21D3',
    '&hArr;' : '\u21D4',
    '&forall;' : '\u2200',
    '&part;' : '\u2202',
    '&exist;' : '\u2203',
    '&empty;' : '\u2205',
    '&nabla;' : '\u2207',
    '&isin;' : '\u2208',
    '&notin;' : '\u2209',
    '&ni;' : '\u220B',
    '&prod;' : '\u220F',
    '&sum;' : '\u2211',
    '&minus;' : '\u2212',
    '&lowast;' : '\u2217',
    '&radic;' : '\u221A',
    '&prop;' : '\u221D',
    '&infin;' : '\u221E',
    '&ang;' : '\u2220',
    '&and;' : '\u2227',
    '&or;' : '\u2228',
    '&cap;' : '\u2229',
    '&cup;' : '\u222A',
    '&int;' : '\u222B',
    '&there4;' : '\u2234',
    '&sim;' : '\u223C',
    '&cong;' : '\u2245',
    '&asymp;' : '\u2248',
    '&ne;' : '\u2260',
    '&equiv;' : '\u2261',
    '&le;' : '\u2264',
    '&ge;' : '\u2265',
    '&sub;' : '\u2282',
    '&sup;' : '\u2283',
    '&nsub;' : '\u2284',
    '&sube;' : '\u2286',
    '&supe;' : '\u2287',
    '&oplus;' : '\u2295',
    '&otimes;' : '\u2297',
    '&perp;' : '\u22A5',
    '&sdot;' : '\u22C5',
    '&lceil;' : '\u2308',
    '&rceil;' : '\u2309',
    '&lfloor;' : '\u230A',
    '&rfloor;' : '\u230B',
    '&lang;' : '\u2329',
    '&rang;' : '\u232A',
    '&loz;' : '\u25CA',
    '&spades;' : '\u2660',
    '&clubs;' : '\u2663',
    '&hearts;' : '\u2665',
    '&diams;' : '\u2666'
  };

  var decodeEntity = function (code) {
    // name type
    if (code.charAt(1) !== '#') {
      return HTML_ENTITIES[code] || code;
    }

    var n, c = code.charAt(2);
    // hex number
    if (c === 'x' || c === 'X') {
      c = code.substring(3, code.length - 1);
      n = parseInt(c, 16);
    } else {
      c = code.substring(2, code.length - 1);
      n = parseInt(c);
    }
    return isNaN(n) ? code : String.fromCharCode(n);
  };

  var htmlEncode = function (str) {
    return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')
      .replace(/</g, '&lt;').replace(/>/g, '&gt;');
  };

  var htmlDecode = function (str) {
    return str.replace(/&#?\w+;/g, decodeEntity);
  };

  var exports = htmlEncode;
  htmlEncode.htmlEncode = htmlEncode;
  htmlEncode.htmlDecode = htmlDecode;
  if (COMMON_JS) {
    module.exports = exports;
  } else {
    root.htmlEncode = htmlEncode;
    root.htmlDecode = htmlDecode;
    if (AMD) {
      !(__WEBPACK_AMD_DEFINE_RESULT__ = (function() {
        return exports;
      }).call(exports, __webpack_require__, exports, module),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    }
  }
})();


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			id: moduleId,
/******/ 			loaded: false,
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/amd options */
/******/ 	(() => {
/******/ 		__webpack_require__.amdO = {};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	(() => {
/******/ 		__webpack_require__.nmd = (module) => {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!*************************!*\
  !*** ./Editor/index.js ***!
  \*************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   EditorWrapper: () => (/* reexport safe */ _editor_wrapper_js__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   Font: () => (/* reexport safe */ _TagsTransform_font_js__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   PixelMapping: () => (/* reexport safe */ _TagsTransform_pixel_mapping_js__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   calculateNodeLength: () => (/* reexport safe */ _Tools_calculate_node_length__WEBPACK_IMPORTED_MODULE_6__["default"]),
/* harmony export */   escapeHtml: () => (/* reexport safe */ _Tools_escape_html__WEBPACK_IMPORTED_MODULE_4__["default"]),
/* harmony export */   stringToDom: () => (/* reexport safe */ _Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__["default"]),
/* harmony export */   unescapeHtml: () => (/* reexport safe */ _Tools_unescape_html__WEBPACK_IMPORTED_MODULE_5__["default"]),
/* harmony export */   unwrapHtmlNodeToText: () => (/* reexport safe */ _Tools_unwrap_html_node__WEBPACK_IMPORTED_MODULE_7__["default"])
/* harmony export */ });
/* harmony import */ var _editor_wrapper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./editor-wrapper.js */ "./Editor/editor-wrapper.js");
/* harmony import */ var _TagsTransform_font_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../TagsTransform/font.js */ "./TagsTransform/font.js");
/* harmony import */ var _TagsTransform_pixel_mapping_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../TagsTransform/pixel-mapping.js */ "./TagsTransform/pixel-mapping.js");
/* harmony import */ var _Tools_string_to_dom__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Tools/string-to-dom */ "./Tools/string-to-dom.js");
/* harmony import */ var _Tools_escape_html__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Tools/escape-html */ "./Tools/escape-html.js");
/* harmony import */ var _Tools_unescape_html__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../Tools/unescape-html */ "./Tools/unescape-html.js");
/* harmony import */ var _Tools_calculate_node_length__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../Tools/calculate-node-length */ "./Tools/calculate-node-length.js");
/* harmony import */ var _Tools_unwrap_html_node__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../Tools/unwrap-html-node */ "./Tools/unwrap-html-node.js");











})();

/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=editor.js.map