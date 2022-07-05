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

    onEditComplete: function(remainVisible, cancelling) {
        const location = this.getLocation();
        let result = this.callParent([remainVisible, cancelling]);

        if (!location || undefined === this.config.editingDataIndex) {
            return result;
        }

        location.record.set('isEditing', false);

        if (cancelling) {
            return result;
        }
        let data = this.editor.getData();

        // TODO move to a separate method
        let dom = document.createElement('html');
        dom.innerHTML = this.editor.getData(data);
        let rawData = dom.getElementsByTagName('p')[0].innerHTML;

        let tagHelper = Ext.create('TMMaintenance.helper.Tag');
        rawData = tagHelper.reverseTransform(rawData);

        if (location.record.get(this.config.editingDataIndex) === rawData) {
            return result;
        }

        location.record.set('isSaving', true);
        location.record.set(this.config.dataIndex, rawData);
        location.record.set(this.config.editingDataIndex, rawData);
        location.record.save({
            success: function() {
                location.record.set('isSaving', false);
            },
        });

        location.view.refresh();

        this.currentEditingRecord = null;

        return result;
    },

    beforeEdit: function() {
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

    onFocusLeave: function() {
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
});
