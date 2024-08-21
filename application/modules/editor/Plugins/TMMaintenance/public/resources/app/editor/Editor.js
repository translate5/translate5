Ext.define('Ext.translate5.Editor', {
    extend: 'Ext.grid.CellEditor',
    xtype: 't5editor',

    editor: null,
    currentEditingRecord: null,

    config: {
        field: {
            xtype: 'textfield',
            clearable: false,
        },
    },

    defaultListenerScope: true,

    listeners: {
        beforecomplete: 'onBeforeComplete',
    },

    onBeforeComplete: function () {
        const data = this.getData();
        const controller = Ext.ComponentQuery.query('app-main')[0].getController();
        const l10n = controller.getViewModel().data.l10n;

        if (!data.checkResult.tagsOrderCorrect) {
            controller.showGeneralError(l10n.error.wrongTagsOrdering);

            return false;
        }

        return true;
    },

    onEditComplete: function (remainVisible, cancelling) {
        const location = this.getLocation();
        let result = this.callParent([remainVisible, cancelling]);

        if (!location || undefined === this.config.editingDataIndex) {
            return result;
        }

        location.record.set('isEditing', false);

        if (cancelling) {
            return result;
        }

        const data = this.getData();

        if (location.record.get(this.config.editingDataIndex) === data.data) {
            return result;
        }

        location.record.set('isSaving', true);
        location.record.set(this.config.dataIndex, data.data);
        location.record.set(this.config.editingDataIndex, data.data);
        location.record.save({
            success: function () {
                location.record.set('isSaving', false);
                location.view.refresh();
                this.currentEditingRecord = null;
            },
            error: function (record, operation) {
                Ext.ComponentQuery.query('app-main')[0].getController().showServerError(operation.getError());
                location.record.set('isSaving', false);
            },
            failure: function (record, operation) {
                Ext.ComponentQuery.query('app-main')[0].getController().showServerError(operation.getError());
                location.record.set('isSaving', false);
            }
        });

        return result;
    },

    beforeEdit: function () {
        const location = this.getLocation();
        if (!location.record.get('metaData').internalKey) {
            return false;
        }
        this.currentEditingRecord = location.record;
        location.record.set('isEditing', true);

        const value = location.record.get(this.config.editingDataIndex);
        const referenceData = location.record.get('source');

        if (this.editor) {
            this.setData(value, referenceData);

            return;
        }

        const element = document.querySelector('#' + this.getField().inputElement.id);
        const TagsModeProvider = class {
            isFullTagMode() {
                return false;
            }
        }

        new RichTextEditor.EditorWrapper(
            element,
            new TagsModeProvider(),
            true,
            true
        ).then((editor) => {
            this.editor = editor;

            this.setData(value, referenceData);

            return editor;
        });
    },

    onFocusLeave: function () {
        // Prevent editor from closing when clicking outside
    },

    getEditor: function () {
        return this.editor;
    },

    getData: function () {
        return this.editor.getDataT5Format();
    },

    setData: function (data, referenceData) {
        this.editor.setDataT5Format(data, referenceData);
    },
});
