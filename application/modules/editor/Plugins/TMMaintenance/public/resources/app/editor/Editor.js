Ext.define('Ext.translate5.Editor', {
    extend: 'Ext.grid.CellEditor',
    xtype: 't5editor',

    editor: null,

    config: {
        field: {
            xtype: 'textfield',
            clearable: false,
        },
    },

    onEditComplete: function(remainVisible, cancelling) {
        const location = this.getLocation();
        let result = this.callParent([remainVisible, cancelling]);

        if (cancelling) {
            return result;

        }

        if (!location || undefined === this.config.editingDataIndex) {
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

        return result;
    },

    beforeEdit: function() {
        let me = this;
        let tagHelper = Ext.create('TMMaintenance.helper.Tag');
        let value = tagHelper.transform(me.getLocation().record.get(this.config.editingDataIndex));

        if (null !== me.editor) {
            me.editor.setData(value);

            return;
        }

        Editor.create(document.querySelector('#' + me.getField().inputElement.id))
            .then((newEditor) => {
                me.editor = newEditor;
                me.editor.setData(value);

                me.addListeners(me.editor);
                me.addEditorButtons(me);
            })
            .catch(error => {
                console.error(error);
            });
    },

    onFocusLeave: function() {
        // Prevent editor from closing when clicking outside
    },

    realign: function () {
        console.log('realign');

        return this.callParent([]);
    },

    afterShow: function () {
        console.log('afterShow');

        return this.callParent([]);
    },

    getEditor: function () {
        return this.editor;
    },

    addListeners: function (editor) {
        editor.editing.view.document.on(
            'enter',
            (evt, data) => {
                me.editor.execute('shiftEnter');
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

    addEditorButtons: function (me) {
        let buttons = document.createElement('div');
        buttons.classList.value = 'x-trigger x-interactive x-cleartrigger-celleditor x-trigger-celleditor f-column';
        let ok = document.createElement('i');
        ok.classList.value = 'x-fa fa-check mb-5';
        let cancel = document.createElement('i');
        cancel.classList.value = 'x-fa fa-window-close';
        buttons.appendChild(ok);
        buttons.appendChild(cancel);

        me.getField().afterInputElement.appendChild(buttons);

        ok.addEventListener('click', function () {
            me.completeEdit();
        });

        cancel.addEventListener('click', function () {
            me.cancelEdit();
        });
    },
});
