Ext.define('Ext.translate5.Editor', {
    extend: 'Ext.grid.CellEditor',
    xtype: 't5editor',

    editor: null,

    config: {},

    onEditComplete: function(remainVisible, cancelling) {
        const location = this.getLocation();

        let result = this.callParent([remainVisible, cancelling]);

        if (!location && undefined === this.config.editingDataIndex) {
            return result;
        }

        let tagHelper = Ext.create('TMMaintenance.helper.Tag');
        let data = tagHelper.reverseTransform(this.editor.getData());

        // TODO move to a separate method
        let dom = document.createElement('html');
        dom.innerHTML = this.editor.getData(data);
        let rawData = dom.getElementsByTagName('p')[0].innerHTML;

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
});
