import Editor from "../Source";
import TagsConversion from "../TagsTransform/tags-conversion";
import stringToDom from "../Tools/string-to-dom";
import DataTransformer from "../DataTransform/data-transformer";
import CallbacksQueue from "./callbacks-queue";
import ModelNode from "./model-node";
import DocumentFragment from "../Mixin/document-fragment";
import InsertPreprocessor from "../DataCleanup/insert-preprocessor";
import calculateNodeLength from "../Tools/calculate-node-length";
// import CKEditorInspector from '@ckeditor/ckeditor5-inspector';

export default class EditorWrapper {
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
        ON_CLICK: 'onclick',
        ON_ARROW_KEY: 'onArrowKey',
        ON_SELECTION_CHANGE_COMPLETED: 'onSelectionChangeCompleted',
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
            [EditorWrapper.EDITOR_EVENTS.DATA_CHANGED]: new CallbacksQueue(),
        };

        this._asyncModifiers = {
            [EditorWrapper.EDITOR_EVENTS.DATA_CHANGED]: [],
        };

        this.registerModifier(
            EditorWrapper.EDITOR_EVENTS.DATA_CHANGED,
            (text, actions, position) => this.#removeTagOnCorrespondingDeletion(text, actions, position),
            0
        );

        this.registerModifier(
            EditorWrapper.EDITOR_EVENTS.DATA_CHANGED,
            (text, actions, position) => this.#preserveOriginalTextIfNoModifications(text, actions, position),
            9999
        );

        return this.#create();
    }

    get font() {
        return this.#font;
    }

    /**
     * Reset editor state to avoid having history of editing from the previous instance
     */
    resetEditor() {
        this.modifiersLastRunId = null;
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
        this.dataTransformer = new DataTransformer(
            this._tagsConversion,
            stringToDom(data).childNodes,
            stringToDom(referenceData).childNodes,
            this.#userCanModifyWhitespaceTags,
            this.#userCanInsertWhitespaceTags
        );
        this.#setRawData(this.dataTransformer.toString());
        this._editor.editing.view.focus();
        this.setCursorToEnd();
        this.#triggerDataChanged();
    }

    addDataT5Format(data) {
        const items = RichTextEditor.stringToDom(data).childNodes;
        const transformed = this.dataTransformer.transformPartial(items);

        const selection = this.getSelection();
        const start = selection.start;
        const end = selection.end < selection.start ? selection.start : selection.end;

        this.replaceContentInRange(start, end, transformed, false, true);
    }

    replaceDataT5Format(data) {
        const rangeStart = 0;
        const rangeEnd = this._editor.model.document.getRoot().getChild(0).maxOffset;

        const contentInRange = this.#getModelInRange(rangeStart, rangeEnd);
        const actions = [];

        if (contentInRange) {
            const changes = [];

            for (const child of contentInRange.getChildren()) {
                changes.push(this.#createModelNode(child));
            }

            actions.push({type: EditorWrapper.ACTION_TYPE.REMOVE, content: changes, position: 0, correction: 0});
        }

        const items = RichTextEditor.stringToDom(data).childNodes;
        const transformed = this.dataTransformer.transformPartial(items);
        actions.push({type: EditorWrapper.ACTION_TYPE.INSERT, content: transformed, position: 0, correction: 0});

        this.#setRawData('');
        this._editor.editing.view.focus();
        this.setCursorToEnd();

        this.#triggerDataChanged(actions);
    }

    /**
     * Insert whitespace tag into editor
     *
     * @param {String} whitespaceType
     * @param {integer} position
     * @param {boolean} replaceWhitespaceBeforePosition
     */
    insertWhitespace(whitespaceType, position = null, replaceWhitespaceBeforePosition = false) {
        const tagNumber = this._tagsConversion
            .getNextWhitespaceTagNumber(this.#getRawDataNode().getElementsByTagName('img'));
        const divSpanHtml = this._tagsConversion.generateWhitespaceTag(whitespaceType, tagNumber);
        const image = this.dataTransformer.transformWhitespace(divSpanHtml)._transformed.outerHTML;

        if (!position) {
            const position = this._editor.model.document.selection.getFirstPosition().path[1];

            const viewFragment = this._editor.data.processor.toView(image);
            const modelFragment = this._editor.data.toModel(viewFragment);
            this._editor.model.insertContent(modelFragment);

            const action = {type: EditorWrapper.ACTION_TYPE.INSERT, content: image, position: position, correction: 0};
            this.#triggerDataChanged([action]);

            return;
        }

        let start = position;
        const end = position;

        if (replaceWhitespaceBeforePosition
            && (
                this.getContentInRange(position - 1, position) === '&nbsp;'
                // This is because just added whitespace can be in trackchanges tag
                || stringToDom(this.getContentInRange(position - 1, position)).innerHTML === '&nbsp;'
            )
        ) {
            start = position - 1;
        }

        this.replaceContentInRange(start, end, image, false, true);
    }

    /**
     * Insert a symbol into editor
     *
     * @param {String} symbol
     */
    insertSymbol(symbol) {
        const position = this._editor.model.document.selection.getFirstPosition().path[1];

        this._editor.model.change(writer => {
            this._editor.model.insertContent(writer.createText(symbol));
            const action = {type: EditorWrapper.ACTION_TYPE.INSERT, content: symbol, position: position, correction: 0};
            this.#triggerDataChanged([action]);
        });
    }

    /**
     * @param {int} tagNumber
     */
    insertTagFromReference(tagNumber) {
        const selection = this.getSelection();

        if (selection.isCollapsed()) {
            if (this.dataTransformer.hasSingleReferenceTag(tagNumber)) {
                const tag = this.dataTransformer.getSingleReferenceTag(tagNumber);
                this.replaceContentInRange(selection.start, selection.start, tag._transformed.outerHTML, false, true);

                return;
            }

            if (this.dataTransformer.hasPairedReferenceTag(tagNumber)) {
                const positions = this.getInternalTagsPositions();
                const leftSiblings = Object.keys(positions).filter((position) => position < selection.start);
                const leftSibling = leftSiblings.length ? positions[Math.max(...leftSiblings)] : null;

                let type = TagsConversion.TYPE.OPEN;
                if (leftSibling && leftSibling.type === TagsConversion.TYPE.OPEN) {
                    type = TagsConversion.TYPE.CLOSE;
                }

                const tags = this.dataTransformer.getPairedReferenceTag(tagNumber);
                this.replaceContentInRange(selection.start, selection.start, tags[type]._transformed.outerHTML, false, true);
            }
        } else {
            if (this.dataTransformer.hasPairedReferenceTag(tagNumber)) {
                const tags = this.dataTransformer.getPairedReferenceTag(tagNumber);

                this.replaceContentInRange(selection.end, selection.end, tags.close._transformed.outerHTML, false, true);
                this.replaceContentInRange(selection.start, selection.start, tags.open._transformed.outerHTML);

                return;
            }

            if (this.dataTransformer.hasSingleReferenceTag(tagNumber)) {
                const tag = this.dataTransformer.getSingleReferenceTag(tagNumber);
                this.replaceContentInRange(selection.start, selection.end, tag._transformed.outerHTML, false, true);
            }
        }
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
     * @returns {{start: (*|number), end: (*|number), internalSelection: *, isCollapsed: (function(): boolean)}}
     */
    getSelection() {
        const selection = this._editor.model.document.selection.getFirstRange();

        return {
            start: selection.start.path[1] ?? 0,
            end: selection.end.path[1] ?? 0,
            internalSelection: selection,
            isCollapsed: function () { return this.start === this.end },
        };
    }

    selectRange(startPosition, endPosition) {
        const root = this._editor.model.document.getRoot();
        const rootChild = root.getChild(0);
        const maxOffset = rootChild ? rootChild.maxOffset : 0;

        if (
            startPosition < 0 ||
            endPosition < 0 ||
            startPosition > endPosition ||
            startPosition > maxOffset ||
            endPosition > maxOffset
        ) {
            return;
        }

        this._editor.model.change((writer) => {
            const start = writer.model.createPositionFromPath(root, [0, startPosition]);
            const end = writer.model.createPositionFromPath(root, [0,  endPosition]);
            const range = writer.model.createRange(start, end);

            writer.setSelection(range);

            this._editor.editing.view.focus();
        });
    }

    setCursorPosition(position) {
        const root = this._editor.model.document.getRoot();
        const rootChild = root.getChild(0);
        const maxOffset = rootChild ? rootChild.maxOffset : 0;
        // Use the smallest of `to` or `maxOffset`
        const effectiveTo = Math.min(position, maxOffset);

        this._editor.model.change(writer => {
            const start = writer.model.createPositionFromPath(root, [0, effectiveTo]);
            const end = writer.model.createPositionFromPath(root, [0, effectiveTo]);
            const range = writer.model.createRange(start, end);

            writer.setSelection(range);
        });
    }

    getCursorPosition() {
        const selection = this._editor.model.document.selection.getFirstPosition();

        return selection.path[1] ?? 0;
    }

    setCursorToEnd() {
        const root = this._editor.model.document.getRoot();
        const rootChild = root.getChild(0);
        const maxOffset = rootChild ? rootChild.maxOffset : 0;
        this.setCursorPosition(maxOffset);
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

        if (to < from) {
            return result;
        }

        this._editor.model.change(() => {
            const content = this.#getModelInRange(from, to);

            if (!content) {
                return;
            }

            const viewFragment = this._editor.data.toView(content);
            result = this._editor.data.processor.toData(viewFragment);
        });

        return result;
    }

    getContentLength() {
        const root = this._editor.model.document.getRoot();
        const rootChild = root.getChild(0);
        return rootChild ? rootChild.maxOffset : 0;
    }

    /**
     * Replace part of a content in a range from-to
     *
     * @param {integer} rangeStart
     * @param {integer} rangeEnd
     * @param {String} content
     * @param {Boolean} skipDataChangeEvent
     * @param {Boolean} moveCarret - if true, move caret to the end of the inserted content
     */
    replaceContentInRange(rangeStart, rangeEnd, content, skipDataChangeEvent = false, moveCarret = false) {
        this._editor.model.change((writer) => {
            const preservedSelection = this._editor.model.document.selection.getFirstRange();

            const contentInRange = this.#getModelInRange(rangeStart, rangeEnd);
            const changes = [];

            // contentInRange can be null if rangeStart is at the very start of the document
            for (const child of (contentInRange ? contentInRange.getChildren() : [])) {
                changes.push(this.#createModelNode(child));
            }

            const root = writer.model.document.getRoot();
            const start = writer.model.createPositionFromPath(root, [0, rangeStart]);
            const end = writer.model.createPositionFromPath(root, [0, rangeEnd]);
            const range = writer.model.createRange(start, end);

            writer.setSelection(range);

            const viewFragment = this._editor.data.processor.toView(content);
            const modelFragment = this._editor.data.toModel(viewFragment);

            this._editor.model.insertContent(modelFragment, range);

            if (moveCarret) {
                const length = calculateNodeLength(stringToDom(content));
                preservedSelection.start.path[1] += length;
                preservedSelection.end.path[1] += length;
            }

            writer.setSelection(preservedSelection);

            if (!skipDataChangeEvent) {
                const actions = [];

                if (rangeStart !== rangeEnd) {
                    actions.push(
                        {type: EditorWrapper.ACTION_TYPE.REMOVE, content: changes, position: rangeStart, correction: 0}
                    );
                }

                actions.push(
                    {type: EditorWrapper.ACTION_TYPE.INSERT, content: content, position: rangeStart, correction: 0}
                );

                this.#triggerDataChanged(actions);
            }
        });
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
     * Please note the position is calculated based on the real position of a tag in the editor including
     * track changes, whitespaces, etc.
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

    getDomNodeUnderCursor() {
        if (!this.getSelection().isCollapsed()) {
            return null;
        }

        const domConverter = this._editor.editing.view.domConverter;
        const selection = this._editor.model.document.selection;
        const position = selection.getFirstPosition();
        let viewPosition = this._editor.editing.mapper.toViewPosition(position);
        let node;

        while (!node && viewPosition.parent) {
            viewPosition = viewPosition.parent;
            node = domConverter.mapViewToDom(viewPosition.parent);
        }

        // P is a root element, so we need to return null
        if (node && node.tagName !== 'P') {
            return node;
        }

        return null;
    }

    getTagsConversion() {
        return this._tagsConversion;
    }

    triggerDataChanged() {
        this.#triggerDataChanged();
    }

    #triggerDataChanged(actions = null) {
        const position = this._editor.model.document.selection.getFirstPosition().path[1];
        this.#runModifiers(actions || [{position: position, correction: 0}]);
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
                if (
                    node.name === 'imageInline'
                    && node.getAttribute('htmlImgAttributes').classes.includes('internal-tag')
                    && ! node.getAttribute('htmlDel')
                ) {
                    const imagePosition = writer.createPositionBefore(node);
                    imagesWithPositions[imagePosition.path[1]] = {
                        type: this._tagsConversion.getInternalTagTypeByClass(
                            node.getAttribute('htmlImgAttributes').classes.join(' ')
                        ),
                        number: node.getAttribute('htmlImgAttributes').attributes['data-tag-number'],
                    };
                }

                // Recursively check the children of the node
                imagesWithPositions = Object.assign(
                    {},
                    imagesWithPositions,
                    this.#getInternalTagsPositions(node, writer)
                );
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
        return Editor.create(
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
                licenseKey: 'GPL',
            }
        ).then((editor) => {
            this._editor = editor;
            // CKEditorInspector.attach(editor);
            this._tagsConversion = new TagsConversion(this.getEditorViewNode(), this.#tagsModeProvider);
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
        viewDocument.on(
            'enter',
            (event, data) => {
                return this.#onPressEnter(event, data, editor);
            },
            {
                priority: 'high'
            }
        );
        viewDocument.on('paste', () => {
            this.#onClipboardInput();
        });
        viewDocument.on('drop', () => {
            this.#onDrop();
        });
        viewDocument.on('keydown', (event, data) => {
            this.#onKeyDown(event, data);
        });
        viewDocument.on('clipboardOutput', (event, data) => {
            this.#onClipboardOutput(event, data, editor);
        });
        viewDocument.on('dragstart', () => {
            this.#onDragStart();
        });
        viewDocument.on('arrowKey', (event, data) => {
            this.#onArrowKey(event, data, editor);
        });

        // viewDocument.on('selectionChange', (evt, data) => {
        //     console.log('Selection changed (view level)', data);
        // });

        viewDocument.on('selectionChangeDone', (event, data) => {
            this.#onSelectionChangeCompleted(event, data, editor);
        });

        // This does not work, need to find a way to trigger it
        // viewDocument.on('click', (event, data) => {
        //     this.#onClick(event, data);
        // });

        // This is an inappropriate way of adding reaction to click event
        // Need to find the reason why standard way does not work (see above)
        this.getEditorViewNode().addEventListener('click', (event) => {
            this.#onClick(event, {}, editor);
        });

        const modelDocument = editor.model.document;
        modelDocument.on('change:data', (event, data) => {
            this.#onDataChange(event, data);
        });
        modelDocument.on('change', (event, data) => {
            this.#onDocumentChange(event, data, editor);
        });

        editor.plugins.get('ClipboardPipeline').on('inputTransformation', (event, data) => {
            this.#onInputTransformation(event, data, editor);
        });
    }

    #onDataChange(event, data) {
        // console.log('The data has changed!');
        this.modifiersLastRunId = null;

        if (data.isUndo) {
            this.#triggerDataChanged();

            return;
        }

        if (!data.isTyping && !this.#isProcessingDrop && !this.#isProcessingPaste && !this.#isProcessingCut) {
            return;
        }

        const isProcessingDrop = this.#isProcessingDrop;
        const isProcessingCut = this.#isProcessingCut;

        // Immediately reset the flags to prevent multiple calls
        this.#isProcessingDrop = false;
        this.#isProcessingPaste = false;
        this.#isProcessingCut = false;
        const lastKeyPressed = this.#lastKeyPressed;
        this.#lastKeyPressed = null;

        const actions = [];

        const operations = data.operations
            // Filter out all operations except 'insert' and 'remove'
            .filter(operation => {
                return operation.type === EditorWrapper.ACTION_TYPE.INSERT
                || operation.type === EditorWrapper.ACTION_TYPE.REMOVE
            })
            .reduce((_operations, operation) => {
                // For 'insert' operations, pick the one with the highest 'baseVersion' as we don't need history here
                if (operation.type === EditorWrapper.ACTION_TYPE.INSERT && operation.baseVersion) {
                    _operations.insert ? _operations.insert.push(operation) : _operations.insert = [operation];
                } else if (operation.type === EditorWrapper.ACTION_TYPE.REMOVE) {
                    // We only need the last 'remove' operation
                    // (usually it is the only one in the array, but just in case)
                    _operations.remove = [operation];
                }

                return _operations;
            }, {insert: null, remove: null});

        [EditorWrapper.ACTION_TYPE.REMOVE, EditorWrapper.ACTION_TYPE.INSERT].forEach(type => {
            if (!operations[type]) {
                return;
            }

            for (const operation of operations[type]) {
                if (!operation) {
                    return;
                }

                const path = operation.position?.path || operation.sourcePosition?.path;

                if (!path || path[1] === undefined) {
                    continue;
                }

                const action = this.#createActionFromOperation(operation, lastKeyPressed);

                // If the last action is 'insert' and the current one is 'insert' as well, merge them
                if (
                    type === EditorWrapper.ACTION_TYPE.INSERT
                    && actions[actions.length - 1]?.type === EditorWrapper.ACTION_TYPE.INSERT
                ) {
                    actions[actions.length - 1].content += action.content;
                    actions[actions.length - 1].correction += action.correction;

                    continue;
                }

                // Check deleted content for tags and delete corresponding tags
                // (if open tag is deleted - delete also paired closing one)
                // Move this if to a separate method(s) after testing
                if (type === EditorWrapper.ACTION_TYPE.REMOVE && !isProcessingDrop && !isProcessingCut) {
                    const tagsRemoved = [];

                    // Check if there are tags removed and remove corresponding tags also if any
                    for (const modelNode of action.content) {
                        const deletedDom = modelNode.toDom();

                        if (
                            this._tagsConversion.isTrackChangesDelNode(deletedDom) ||
                            deletedDom.nodeType === Node.TEXT_NODE
                        ) {
                            continue;
                        }

                        if (
                            this._tagsConversion.isInternalTagNode(deletedDom) &&
                            !this._tagsConversion.isWhitespaceNode(deletedDom)
                        ) {
                            tagsRemoved.push({
                                type: this._tagsConversion.getInternalTagType(deletedDom),
                                number: this._tagsConversion.getInternalTagNumber(deletedDom),
                                tag: deletedDom,
                            });

                            continue;
                        }

                        const tag = deletedDom.querySelector('img');

                        if (
                            tag &&
                            !this._tagsConversion.isWhitespaceNode(deletedDom)
                        ) {
                            tagsRemoved.push({
                                type: this._tagsConversion.getInternalTagType(tag),
                                number: this._tagsConversion.getInternalTagNumber(tag),
                                tag: tag,
                            });
                        }
                    }

                    if (tagsRemoved.length) {
                        const contentInEditor = RichTextEditor.stringToDom(this.getRawData());

                        for (const tag of tagsRemoved) {
                            let correspondingTags;

                            if (this._tagsConversion.isMQMNode(tag.tag)) {
                                const classes = Array.from(tag.tag.classList);
                                correspondingTags = contentInEditor.querySelectorAll(
                                    `img.${classes.splice(classes.indexOf(tag.type, 1)).join('.')}`
                                );
                            } else {
                                const expectedType = tag.type === TagsConversion.TYPE.OPEN ?
                                    TagsConversion.TYPE.CLOSE :
                                    TagsConversion.TYPE.OPEN;

                                correspondingTags = contentInEditor.querySelectorAll(
                                    `img.${expectedType}[data-tag-number="${tag.number}"]`
                                );
                            }

                            const correspondingTag = Array.from(correspondingTags).find(tag => {
                                return !tag.parentNode || !this._tagsConversion.isTrackChangesDelNode(tag.parentNode);
                            });

                            if (correspondingTag) {
                                const offsets = RichTextEditor.calculateNodeOffsets(contentInEditor, correspondingTag);
                                const modelNode = this.#createModelNode(
                                    Array.from(this.#getModelInRange(offsets.start, offsets.end + 1).getChildren())[0],
                                    true
                                );

                                actions.unshift({
                                    type: EditorWrapper.ACTION_TYPE.REMOVE,
                                    content: [modelNode],
                                    position: offsets.start,
                                    correction: 1,
                                    correspondingDeletion: true,
                                });
                            }
                        }
                    }
                }

                actions.push(action);
            }
        });

        this.#runModifiers(actions);
    }

    #onClipboardOutput(event, data) {
        this.#isProcessingCut = data.method === 'cut';
    }

    #onDragStart() {
        console.log('Drag start');
        this.modifiersLastRunId = null;
    }

    #onDocumentChange(event, data, editor) {
        // console.log('The Document has changed!');
        console.log('Position ' + editor.model.document.selection.getFirstPosition().path[1]);
    }

    #onClipboardInput() {
        console.log('Paste from clipboard');
        if (this.#isProcessingDrop) {
            return;
        }

        this.#isProcessingPaste = true;
    }

    #onInputTransformation(event, data, editor) {
        const content = data.content;
        Object.assign(content, DocumentFragment);
        const cleaned = this.#cleanupDataOnInsertOrDrop(content.toHTMLString());
        data.content = editor.data.htmlProcessor.toView(cleaned);
    }

    #onDrop() {
        console.log('Drop event');
        this.#isProcessingDrop = true;
    }

    #onPressEnter(event, data, editor) {
        // suppress inserting a new line on Enter press
        // because it should be handled by the controller
        data.preventDefault();
        event.stop();

        return false;
    }

    #onKeyDown(event, data) {
        this.#lastKeyPressed = data.domEvent.code;
    }

    #onArrowKey(event, data, editor) {
        const position = editor.model.document.selection.getFirstPosition().path[1];

        const customEvent = new CustomEvent(EditorWrapper.EDITOR_EVENTS.ON_ARROW_KEY, {
            detail: {
                position: position
            },
            bubbles: true,
        });

        this.getEditorViewNode().dispatchEvent(customEvent);
    }

    #onClick(event, data, editor) {
        const position = editor.model.document.selection.getFirstPosition().path[1];

        const customEvent = new CustomEvent(EditorWrapper.EDITOR_EVENTS.ON_CLICK, {
            detail: {
                position: position
            },
            bubbles: true,
        });

        this.getEditorViewNode().dispatchEvent(customEvent);
    }

    #onSelectionChangeCompleted(event, data, editor) {
        const customEvent = new CustomEvent(EditorWrapper.EDITOR_EVENTS.ON_SELECTION_CHANGE_COMPLETED, {
            detail: {
                selection: data.domSelection
            },
            bubbles: true,
        });

        this.getEditorViewNode().dispatchEvent(customEvent);
    }

    // endregion

    /**
     * @param {Object} operation
     * @param {String} lastKeyPressed
     * @returns {
     *  {position: number, type: 'insert', correction: number, content: string, lastKeyPressed}
     *  |{position: number, type: 'remove', correction: number, content: ModelNode[], lastKeyPressed}
     * }
     */
    #createActionFromOperation(operation, lastKeyPressed) {
        let position = operation.position?.path[1] || operation.sourcePosition?.path[1];
        // Position can be null or NaN, so we need to set it to 0 in this case
        position = position || 0;

        if (operation.type === 'remove') {
            const content = this.#getDeletedContent(operation);

            return {type: EditorWrapper.ACTION_TYPE.REMOVE, content, position, correction: 0, lastKeyPressed};
        }

        let content = '';
        for (const node of operation.nodes) {
            const dom = this.#createModelNode(node).toDom();
            content += dom.data ?? dom.outerHTML;
        }

        // Use Unicode for non-breaking space for further processing
        // ckeditor mixes spaces, so need to manually replace them
        content = content === ' ' ? '\u00A0' : content;
        const correction = content.length;

        return {type: EditorWrapper.ACTION_TYPE.INSERT, content, position, correction, lastKeyPressed};
    }

    #getDeletedContent(operation) {
        const changes = [];

        for (const change of operation.getMovedRangeStart().root.getChildren()) {
            if (change.name === 'paragraph') {
                break;
            }

            changes.push(this.#createModelNode(change, true));
        }

        return changes;
    }

    #createModelNode(modelPart, deletion = false) {
        const data = modelPart.data || null;
        const iterator = modelPart.getAttributes();
        const attributes = Array.from(iterator);

        // simple text has no attributes, so adding early return
        if (attributes.length === 0) {
            return new ModelNode(data, {}, ModelNode.TYPE.TEXT);
        }

        const createParents = function (parents) {
            if (parents.length === 0) {
                return null;
            }

            const [name, attributes] = parents.shift();

            // if (
            //     name === 'htmlSpan'
            //     && (
            //         // Here we open implementation from other modules, need to rethink this approach
            //         attributes.classes.includes('t5spellcheck') || attributes.classes.includes('term')
            //     )
            // ) {
            //     return null;
            // }

            return new ModelNode(
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
                };

                continue;
            }

            if (['htmlIns', 'htmlDel', 'htmlSpan'].includes(name)) {
                if (deletion) {
                    parent = parent || createParents(attributes.slice(key));
                }

                continue;
            }

            attrs[name] = value;
        }

        return new ModelNode(data, attrs, modelPart.name || 'text', parent);
    }

    #getModelInRange(from, to) {
        let result;

        this._editor.model.change((writer) => {
            const preservedSelection = this._editor.model.document.selection.getFirstRange();

            const root = writer.model.document.getRoot();

            const rootChild = root.getChild(0);
            const maxOffset = rootChild ? rootChild.maxOffset : 0;

            // Use the smallest of `to` or `maxOffset`
            const effectiveTo = Math.min(to, maxOffset);

            if (effectiveTo === 0) {
                result = null;

                return;
            }

            const start = writer.model.createPositionFromPath(root, [0, from]);
            const end = writer.model.createPositionFromPath(root, [0, effectiveTo]);
            const range = writer.model.createRange(start, end);

            writer.setSelection(range);

            const content = writer.model.getSelectedContent(writer.model.document.selection);

            writer.setSelection(preservedSelection);

            result = content;
        });

        return result;
    }

    #runModifiers(actions) {
        const originalText = this.getRawData();
        let text = originalText;
        let position = actions[0]?.position || 0;
        let forceUpdate = false;

        for (const modifier of this._modifiers[EditorWrapper.EDITOR_EVENTS.DATA_CHANGED]) {
            [text, position, forceUpdate] = modifier(text, actions, position);
        }

        if (text !== originalText || forceUpdate) {
            this.#replaceDataInEditor(text, position);
        }

        this.#runAsyncModifiers();

        const event = new CustomEvent(EditorWrapper.EDITOR_EVENTS.DATA_CHANGED, {
            detail: 'Data changed',
            bubbles: true,
        });

        this.getEditorViewNode().dispatchEvent(event);
    }

    #runAsyncModifiers() {
        this.modifiersLastRunId = Math.random().toString(36).substring(2, 16);
        const preservedSelection = this.getSelection();

        const originalText = this.getRawData();
        // Now async modifiers can be executed in any order, need to change this to promises sequence
        for (const modifier of this._asyncModifiers[EditorWrapper.EDITOR_EVENTS.DATA_CHANGED]) {
            modifier(originalText, this.modifiersLastRunId).then((result) => {
                const currentSelection = this.getSelection();

                if (
                    preservedSelection.start !== currentSelection.start ||
                    preservedSelection.end !== currentSelection.end
                ) {
                    // If the selection has changed, we should not replace the data
                    // because it can lead to unexpected behavior
                    return;
                }

                let position = this._editor.model.document.selection.getFirstPosition().path[1];

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

    #replaceDataInEditor(data, positionAfterReplace) {
        const doc = this._editor.model.document;
        const root = doc.getRoot();
        const currentSelection = this.getSelection();

        this._editor.model.change(writer => {
            const entireSelection = writer.createSelection(root, 'in');

            const viewFragment = this._editor.data.processor.toView(data);
            const modelFragment = this._editor.data.toModel(viewFragment);

            this._editor.model.insertContent(modelFragment, entireSelection);

            // If we have a selection that is not collapsed, replacing the data within the async operation
            // in this case we need to restore the selection
            if (!currentSelection.isCollapsed()) {
                writer.setSelection(currentSelection.internalSelection);

                return;
            }

            this.setCursorPosition(positionAfterReplace);
        });
    };

    //region data cleanup on insert or drop
    #cleanupDataOnInsertOrDrop(data) {
        const doc = stringToDom(data);
        const cleaned = new InsertPreprocessor(this._tagsConversion).cleanup(doc);

        return this.dataTransformer.transformPartial(cleaned.childNodes);
    }
    //endregion

    #removeTagOnCorrespondingDeletion(rawData, actions, position) {
        const doc = RichTextEditor.stringToDom(rawData);

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

            if (!this._tagsConversion.isInternalTagNode(tag) && !this._tagsConversion.isMQMNode(tag)) {
                parentNode = tag;
                tag = tag.querySelector('img');
            }

            const allTags = doc.querySelectorAll('img');

            for (const candidate of allTags) {
                if (this._tagsConversion.isTrackChangesDelNode(candidate.parentNode)) {
                    continue;
                }

                if (RichTextEditor.nodesAreSame(candidate, tag)) {
                    candidate.parentNode.removeChild(candidate);
                    break;
                }
            }
        }

        return [doc.innerHTML, position];
    }

    #preserveOriginalTextIfNoModifications(text, actions, position) {
        const insertion = actions.find(action => action.type === 'insert');

        if (text === '' && insertion !== undefined) {
            return [insertion.content, Infinity];
        }

        return [text, position];
    }
}
