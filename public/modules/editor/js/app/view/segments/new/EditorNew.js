Ext.define('Editor.view.segments.new.EditorNew', {
    extend: 'Ext.form.FieldContainer',
    mixins: {
        field: 'Ext.form.field.Field'
    },

    xtype: 't5editor',
    itemId: 't5Editor',
    isFormField: false,
    editor: null,
    viewModesController: null,

    referenceField: null,
    currentlyEditingRecord: null,
    currentlyEditingColumnToEdit: null,

    requires: [
        'Editor.view.segments.StatusStrip',
    ],

    layout: 'form',
    style: {
        overflow: 'visible'
    },
    focusable: true,
    componentTpl: [
        '<textarea id="{id}-textareaEl" data-ref="textareaEl" name="{name}" tabindex="-1" autocomplete="off">',
        '{[Ext.util.Format.htmlEncode(values.value)]}',
        '</textarea>',
        {
            disableFormats: true
        }
    ],

    initComponent: function () {
        let me = this;

        // Prepare items
        me.items = [
            me.createInputCmp(),
            me.createStatusStrip()
        ];

        // Call parent
        me.callParent(arguments);
    },

    createStatusStrip: function() {
        return this.statusStrip = Ext.widget({
            xtype: 'segments.statusstrip',
            htmlEditor: this,
        });
    },

    createInputCmp: function(){
        return this.inputCmp = Ext.widget({
            xtype: 'component',
            flex: 1,
            tpl: this.lookupTpl('componentTpl'),
            childEls: ['textareaEl'],
            id: this.id + '-inputCmp',
            cls: Ext.baseCSSPrefix + 'html-editor-input',
            data: {
                id: this.id + '-inputCmp',
                name: this.name,
                value: this.value,
            }
        });
    },

    afterRender: function() {
        let me = this;
        me.callParent(arguments);
        me.textareaEl = me.inputEl = me.inputCmp.textareaEl;
    },

    onFocusLeave: function () {
        // Prevent editor from closing when clicking outside
    },

    getData: function () {
        return this.editor.getData();
    },

    setData: function (data, isReplacing = false, isAdding = false) {
        if (!this.currentlyEditingRecord) {
            return;
        }
        const metaCache = this.currentlyEditingRecord.get('metaCache');
        const referenceField = this.getReferenceField(
            this.currentlyEditingRecord.get('target'),
            this.currentlyEditingRecord.get('pretrans'),
            this.currentlyEditingRecord.get('matchRateType')
        );

        if (isReplacing) {
            this.editor.replaceDataT5Format(data);

            return;
        }

        if (isAdding) {
            this.editor.addDataT5Format(data);

            return;
        }

        this.editor.setDataT5Format(
            data,
            this.currentlyEditingRecord.get(referenceField),
            new RichTextEditor.Font(
                metaCache.sizeUnit,
                metaCache.font.toLowerCase(),
                metaCache.fontSize,
                this.currentlyEditingRecord.get('fileId')
            )
        );
    },

    insertWhitespace: function(whitespaceType, position = null, replaceWhitespaceBeforePosition = false) {
        this.editor.insertWhitespace(whitespaceType, position, replaceWhitespaceBeforePosition);
    },

    insertSymbol: function(symbol) {
        this.editor.insertSymbol(symbol);
    },

    setValue: function(value, record, columnToEdit, isReplacing = false, isAdding = false) {
        let me = this,
            textarea = me.textareaEl;

        if (value === null || value === undefined) {
            value = '';
        }

        // Only update the field if the value has changed
        if (me.value !== value) {
            if (textarea) {
                textarea.dom.value = value;
            }
            me.pushValue();

            if (!me.rendered && me.inputCmp) {
                me.inputCmp.data.value = value;
            }
            me.mixins.field.setValue.call(me, value);
        }
        // TODO think how to get rid of it
        if (record) {
            me.currentlyEditingRecord = record;
        }

        if (columnToEdit) {
            me.currentlyEditingColumnToEdit = columnToEdit;
        }

        if (null === me.editor) {
            me._instantiateEditor(me.textareaEl.id).then(() => {
                me.fireEvent('afterInstantiateEditor', me);
                me.editor.getEditorViewNode().addEventListener(
                    RichTextEditor.EditorWrapper.EDITOR_EVENTS.DATA_CHANGED,
                    () => {
                        me.fitSize();
                        me.fireEvent('editorDataChanged', me);
                    }
                );

                me.setData(value);
                me.fireEvent('afterStartEdit', me);
            });

            return me;
        }

        me.setData(value, isReplacing, isAdding);
        me.fireEvent('afterStartEdit', me);

        return me;
    },

    fitSize: function() {
        let me = this, wu_was = me.getWidthUsage();

        Ext.defer(() => {
            me.fitHeight();
            me.fitWidth(wu_was);
        }, 10);
    },
    fitWidth: function(wasWidthUsage) {
        let me = this,
            nowWidthUsage = me.getWidthUsage(),
            diff = nowWidthUsage - wasWidthUsage,
            rowEditorInnerCt, width;

        // Here we only handle case when ckeditor width became smaller, because
        // 'became bigger' case is handled by ckeditor and/or extjs by themselves
        if (diff < 0) {

            // Get row editor inner container
            rowEditorInnerCt = me.up().el.down('[data-ref=innerCt]');

            // Get it's width
            width = rowEditorInnerCt.getWidth();

            // Prevent setting row editor inner container width to be less than row editor general width
            if (width > me.up().getWidth()) {

                // Decrease width
                rowEditorInnerCt.setWidth(width - Math.abs(diff));
            }
        }
    },
    getWidthUsage: function() {
        return this.editor._editor.ui.view.element.clientWidth;
    },
    fitHeight: function() {
        let me = this,
            recordHeight = me.getRecordHeightUsage(),
            inputMinHeight = recordHeight - me.statusStrip.getHeight() - 2;

        // Set min height for both as well
        me.setMinHeight(recordHeight).up().setMinHeight(recordHeight);

        // Setup min height for input extjs component and input of the editor component
        me.inputCmp.setMinHeight(inputMinHeight);
        me.inputCmp.el.down('.ck-editor__main').dom.setAttribute('style', '--height:' + inputMinHeight + 'px');

        // Get target height usage
        let targetHeight = me.getTargetHeightUsage();

        // Set height for both editor container and editor row panel
        me.setHeight(targetHeight).up().setHeight(targetHeight);
    },
    getTargetHeightUsage: function() {
        return this.editor._editor.ui.view.element.clientHeight + this.statusStrip.getHeight() + 2;
    },
    getRecordHeightUsage: function() {
        return this.up('[rowToEditOrigHeight]').rowToEditOrigHeight + 2;
    },

    pushValue: function() {
        let me = this,
            v;
        if(me.initialized){
            v = me.textareaEl.dom.value || '';
            if (!me.activated && v.length < 1) {
                v = me.defaultValue;
            }
            if (me.fireEvent('beforepush', me, v) !== false) {
                me.fireEvent('push', me, v);
            }
        }
    },

    cancelEdit: function() {
        this.currentlyEditingRecord = null;
        this.currentlyEditingColumnToEdit = null;
        this.editor.resetEditor();
    },

    getEditorBody: function() {
        return this.editor.getEditorViewNode();
    },

    /**
     * Distinguish which field should be used for reference tags
     *
     * @param {String} targetContent
     * @param {number} pretrans
     * @param {number} matchRateType
     * @returns {string}
     */
    getReferenceField: function (targetContent, pretrans, matchRateType) {
        const useSourceAsReference = Editor.app.getTaskConfig('editor.frontend.reviewTask.useSourceForReference');

        if (useSourceAsReference) {
            return 'source';
        }

        if (targetContent.trim() === '') {
            return 'source';
        }

        // If target was filled during pretranslation, use source as reference
        if (this.isExternalTranslation(matchRateType) === false && 0 !== pretrans) {
            return 'source';
        }

        return 'target';
    },

    /**
     * Is external translation when the match type is "import" and the match source is not empty
     * @param {string} matchRateType
     * @returns {boolean}
     */
    isExternalTranslation: function (matchRateType) {

        if (matchRateType.startsWith("import;")) {
            const remainingValue = matchRateType.substring(7);

            const parts = remainingValue.split(";");

            if(parts.length <=1) {
                return false;
            }

            const isFromMtOrTm =  ['tm', 'mt'].some(
                element =>
                    parts.some(item =>
                        item.toLowerCase() === element.toLowerCase()
                    )
            );

            // ensure the second part exists (tm or mt) and there's a name following it
            if (isFromMtOrTm) {
                return true;
            }
        }
        return false;
    },

    showFullTags: function () {
        if (!this.editor) {
            return;
        }

        this.setData(this.editor.getDataT5Format().data);
    },
    showShortTags: function () {
        if (!this.editor) {
            return;
        }

        this.setData(this.editor.getDataT5Format().data);
    },

    _instantiateEditor: function (inputId) {
        let element = document.querySelector('#' + inputId);

        if (null === element) {
            // TODO handle error
            return;
        }

        const TagsModeProvider = class {
            constructor() {
                this.viewModesController = Editor.app.getController('ViewModes');
            }

            isFullTagMode() {
                return this.viewModesController.isFullTag();
            }
        }

        return new RichTextEditor.EditorWrapper(
            element,
            new TagsModeProvider(),
            !!Editor.app.getTaskConfig('segments.userCanModifyWhitespaceTags'),
            !!Editor.app.getTaskConfig('segments.userCanInsertWhitespaceTags')
        ).then((editor) => {
            this.editor = editor;

            return editor;
        });
    },

    /**
     * Small fix for editor insufficient width problem, possible caused by roweditor fields sizing in ExtJS
     *
     * @param width
     */
    setWidth: function(width) {
        this.callParent([width + 3]);
    }
});
