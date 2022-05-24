Ext.define('TMMaintenance.view.main.MainController', {
    extend: 'Ext.app.ViewController',

    alias: 'controller.main',

    /**
     * @param {TMMaintenance.view.main.List} sender
     * @param {Array} record
     */
    onItemSelected: function (sender, record) {
        let form = this.getForm();

        if (form.isDirty()) {
            Ext.Msg.confirm(
                'Confirm',
                'Form has unsaved changes, do you want to save them before proceed?',
                '',
                this
            );
        }

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
        let me = this;
        Ext.Msg.confirm(
            'Confirm',
            'Do you really want to delete a segment?',
            (buttonPressed) => {
                if ('no' === buttonPressed) {
                    return;
                }

                me.onDeleteConfirm(record.record);
            },
            this
        );
    },

    /**
     * @param {TMMaintenance.model.Segment} record
     */
    onDeleteConfirm: function (record) {
        record.set({tm: this.getViewModel().get('selectedTm')});
        record.erase({
            success: () => {
                // TODO what to do here?
            }
        });
    },

    getForm: function () {
        return Ext.getCmp('editform');
    }
});
