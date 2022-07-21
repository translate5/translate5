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
        if (!this.validateTagsOrdering(this.getData())) {
            Ext.Msg.show({
                title: 'Validation error',
                message: 'Some of the tags used in the segment are in the wrong order',
            });

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

        let data = this.getData();

        let tagHelper = Ext.create('TMMaintenance.helper.Tag');
        data = tagHelper.reverseTransform(data);

        if (location.record.get(this.config.editingDataIndex) === data) {
            return result;
        }

        location.record.set('isSaving', true);
        location.record.set(this.config.dataIndex, data);
        location.record.set(this.config.editingDataIndex, data);
        location.record.save({
            success: function () {
                location.record.set('isSaving', false);
            },
            error: function () {
                // TODO show error
                location.record.set('isSaving', false);
            }
        });

        location.view.refresh();

        this.currentEditingRecord = null;

        return result;
    },

    beforeEdit: function () {
        let me = this;
        let location = me.getLocation();
        let tagHelper = Ext.create('TMMaintenance.helper.Tag');
        let value = tagHelper.transform(location.record.get(this.config.editingDataIndex));
        this.currentEditingRecord = location.record;
        location.record.set('isEditing', true);

        if (null !== me.editor) {
            me.editor.setData(value);

            return;
        }

        Editor.create(document.querySelector('#' + me.getField().inputElement.id))
            .then((newEditor) => {
                me.editor = newEditor;
                me.editor.setData(value);

                me.addListeners(me.editor);
            })
            .catch(error => {
                console.error(error);
            });
    },

    onFocusLeave: function () {
        // Prevent editor from closing when clicking outside
    },

    getEditor: function () {
        return this.editor;
    },

    addListeners: function (editor) {
        editor.editing.view.document.on(
            'enter',
            (evt, data) => {
                //change enter to shift+enter to prevent ckeditor from inserting a new p tag
                this.editor.execute('shiftEnter');
                //Cancel existing event
                data.preventDefault();
                evt.stop();
            }
        );

        editor.editing.view.document.on(
            'clipboardInput',
            (evt, data) => {
                console.log('Past from clipboard');

                // Prevent the default listener from being executed.
                // evt.stop();
            }
        );
    },

    validateTagsOrdering: function (data) {
        let dom = document.createElement('html');
        dom.innerHTML = data;

        let isTagsOrderingRight = true;
        let tags = [];
        dom.getElementsByTagName('body')[0].childNodes.forEach(function (node) {
            if (undefined === node.dataset) {
                return;
            }

            if (undefined === node.dataset.tagType || 'single' === node.dataset.tagType) {
                return;
            }

            if ('open' === node.dataset.tagType) {
                tags.push(node.dataset.tagId);

                return;
            }

            if (node.dataset.tagId !== tags[tags.length - 1]) {
                isTagsOrderingRight = false;
            }

            tags.pop();
        });

        return isTagsOrderingRight && tags.length === 0;
    },

    getData: function () {
        let dom = document.createElement('html');
        dom.innerHTML = this.editor.getData();

        return dom.getElementsByTagName('p')[0].innerHTML;
    },
});
