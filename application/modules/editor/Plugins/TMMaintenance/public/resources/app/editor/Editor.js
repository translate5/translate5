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
        // TODO validate tags ordering
        // if (!this.validateTagsOrdering(this.getData())) {
        //     Ext.Msg.show({
        //         title: 'Validation error',
        //         message: 'Some of the tags used in the segment are in the wrong order',
        //     });
        //
        //     return false;
        // }

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

        let data = this.getData();

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
            error: function () {
                debugger;
                // TODO show error
                location.record.set('isSaving', false);
            }
        });

        return result;
    },

    beforeEdit: function () {
        const location = this.getLocation();

        this.currentEditingRecord = location.record;
        location.record.set('isEditing', true);

        const value = location.record.get(this.config.editingDataIndex);

        if (this.editor) {
            this.setData(value);

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

            this.setData(value);

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

    setData: function (data) {
        this.editor.setDataT5Format(data, data);
    },
});
