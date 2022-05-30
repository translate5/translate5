Ext.define('TMMaintenance.view.main.MainController', {
    extend: 'Ext.app.ViewController',

    alias: 'controller.main',

    onContainerScrollEnd: function () {
        if (!this.getViewModel().get('hasMoreRecords')) {
            return;
        }

        let values = Ext.getCmp('searchform').getValues();
        let store = Ext.getCmp('mainlist').store;
        let me = this;

        store.load({
            params: {
                tm: values.tm,
                searchField: values.searchField,
                searchCriteria: values.searchCriteria,
                offset: this.getViewModel().get('lastOffset'),
            },
            addRecords: true,
            callback: function(records, operation) {
                let offset = operation.getProxy().getReader().metaData.offset;

                me.getViewModel().set('lastOffset', offset);
                me.getViewModel().set('hasMoreRecords', null !== offset);
            },
        });
    },

    /**
     * @param {TMMaintenance.view.main.List} sender
     * @param {Array} record
     */
    onItemSelected: function (sender, record) {
        let form = this.getForm();

        form.reset();
        form.setRecord(record[0]);
        form.getItems().each(function (item) {
            if (typeof(item.resetOriginalValue) === 'function') {
                item.resetOriginalValue();
            }
        });
        form.show();
    },

    onCreatePressed: function () {
        let form = this.getForm();
        let segment = Ext.create('TMMaintenance.model.Segment', {});

        form.setRecord(segment);
        form.show();
    },

    onDeletePressed: function (sender, record) {
        Ext.Msg.confirm(
            'Confirm',
            'Do you really want to delete a segment?',
            (buttonPressed) => {
                if ('no' === buttonPressed) {
                    return;
                }

                record.record.set({tm: this.getViewModel().get('selectedTm')});
                record.record.erase({
                    success: () => {
                        // TODO what to do here?
                    }
                });
            },
            this
        );
    },

    /**
     * @param {TMMaintenance.view.main.List} grid
     * @param {Ext.dataview.Location} gridLocation
     */
    onEditPressed: function (grid, gridLocation) {
        let rowedit = grid.getPlugin('rowedit');
        rowedit.startEdit(gridLocation.record, grid.down('[dataIndex=target]'));

        let editor = rowedit.editor.down('textfield');
        editor.setCaretPos(editor.getValue().length);
    },

    getForm: function () {
        return Ext.getCmp('editform');
    },


    /**
     * @param {TMMaintenance.view.main.List} grid
     * @param {Ext.dataview.Location} gridLocation
     */
    onRowEdit: function (grid, gridLocation) {
        let record = gridLocation.record;
        record.set({tm: this.getViewModel().get('selectedTm')});
        record.save({
            success: () => {
                console.log('Saved');
            }
        });
    },
});
