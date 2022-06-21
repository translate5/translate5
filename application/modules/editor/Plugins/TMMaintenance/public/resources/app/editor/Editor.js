Ext.define('Ext.translate5.Editor', {
    extend: 'Ext.grid.CellEditor',
    xtype: 't5editor',

    editor: null,

    config: {},

    startEdit: function(location, value, doFocus) {
        if (location && location.column.config.editor.editingDataIndex !== undefined) {
            value = location.record.get(location.column.config.editor.editingDataIndex);
        }

        return this.callParent([location, value, doFocus]);
    },

    onEditComplete: function(remainVisible, cancelling) {
        const location = this.getLocation();

        let result = this.callParent([remainVisible, cancelling]);

        if (!location && undefined === this.config.editingDataIndex) {
            return result;
        }

        if (location.record.get(this.config.editingDataIndex) === this.editor.getData()) {
            return result;
        }

        location.record.set(this.config.editingDataIndex, this.editor.getData());
        location.record.save();

        return result;
    },

    beforeEdit: function() {
        let me = this;
        let value = me.getLocation().record.get(this.config.editingDataIndex);

        if (null !== me.editor) {
            me.editor.setData(value);

            return;
        }

        Editor.create(document.querySelector('#' + me.getField().inputElement.id))
            .then((newEditor) => {
                me.editor = newEditor;
                me.editor.setData(value);

                // TODO move to a separate method
                me.editor.editing.view.document.on(
                    'enter',
                    (evt, data) => {
                        me.editor.execute('shiftEnter');
                        //Cancel existing event
                        data.preventDefault();
                        evt.stop();
                    }
                );

                me.editor.editing.view.document.on(
                    'clipboardInput',
                    (evt, data) => {
                        console.log('Past from clipboard');

                        // Prevent the default listener from being executed.
                        // evt.stop();
                    }
                );
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
});
