import Editor from "../Source";
import TagsConversion from "../TagsTransform/tags-conversion";
import PixelMapping from "../TagsTransform/pixel-mapping";
import stringToDom from "../Tools/string-to-dom";
import DataTransformer from "../DataTransform/data-transformer";
import CallbacksQueue from "./callbacks-queue";
import DeletedElement from "./deleted-element";
import DocumentFragment from "../Mixin/document-fragment";
import InsertPreprocessor from "../DataCleanup/insert-preprocessor";

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
    };

    #font = null;
    #tagsModeProvider = null;

    #isProcessingDrop = false;
    #isProcessingPaste = false;

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
        this.dataTransformer = new DataTransformer(
            this._tagsConversion,
            stringToDom(data).childNodes,
            stringToDom(referenceData).childNodes,
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
        const pixelMapping = new PixelMapping(this.#font);
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
                || stringToDom(this.getContentInRange(position - 1, position)).innerHTML === '&nbsp;'
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
            }
        ).then((editor) => {
            this._editor = editor;
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
        viewDocument.on('enter', (event, data) => {
            this.#onPressEnter(event, data, editor);
        }, {priority: 'high'});
        viewDocument.on('paste', (event, data) => {
            this.#onClipboardInput(event, data, editor);
        });
        viewDocument.on('drop', (event, data) => {
            this.#onDrop(event, data, editor);
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

        if (!data.isTyping && !this.#isProcessingDrop && !this.#isProcessingPaste) {
            return;
        }

        // Immediately reset the flag to prevent multiple calls
        this.#isProcessingDrop = false;
        this.#isProcessingPaste = false;

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
                actions.push(this.#createActionFromOperation(operation));
            }
        });

        this.#runModifiers(actions);
    }

    #createActionFromOperation(operation) {
        const position = operation.position?.path[1] || operation.sourcePosition?.path[1];

        if (operation.type === 'remove') {
            const content = this.#getDeletedContent(operation);

            return {type: EditorWrapper.ACTION_TYPE.REMOVE, content, position, correction: 0};
        }

        let content = operation.nodes?.getNode(0).data || '';
        // Use Unicode for non-breaking space
        content = content === ' ' ? '\u00A0' : content;
        const correction = content.length;

        return {type: EditorWrapper.ACTION_TYPE.INSERT, content, position, correction};
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

        // const content = editor.data.processor.toView('Hello world');
        // editor.plugins.get('Clipboard').insertContent(content, data);

        this.#isProcessingPaste = true;

        // Prevent the default listener from being executed.
        // event.stop();
    }

    #onInputTransformation(event, data, editor)
    {
        const content =  data.content;
        Object.assign(content, DocumentFragment);
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
        const attributes = change.getAttributes().toArray();

        // simple text has no attributes, so adding early return
        if (attributes.length === 0) {
            return new DeletedElement(data, {}, DeletedElement.TYPE.TEXT);
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

            return new DeletedElement(
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

        return new DeletedElement(data, attrs, change.name || 'text', parent);
    }

    //region Probably for moving to another class

    #runModifiers(actions) {
        const originalText = this.getRawData();
        let text = originalText;
        let position;
        let forceUpdate = false;

        for (const modifier of this._modifiers[EditorWrapper.EDITOR_EVENTS.DATA_CHANGED]) {
            // TODO use position
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
        const doc = stringToDom(data);
        const cleaned = new InsertPreprocessor(this._tagsConversion).cleanup(doc);

        return this.dataTransformer.transformPartial(cleaned.childNodes);
    }
    //endregion
}
