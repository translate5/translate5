Ext.define('TMMaintenance.view.main.EditFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.editform',

    onSavePressed: function () {
        let me = this;
        let form = this.view;

        if (!form.isValid()) {
            return;
        }

        let record = form.getRecord();

        if (!record) {
            record = Ext.create('TMMaintenance.model.Segment', {});
        }

        record.set(form.getValues());
        record.set({tm: this.getViewModel().get('selectedTm')});
        record.save({
            success: () => {
                this.getView().up('app-main').controller.hideForm()
            }
        });
    },

    onCancelPressed: function () {
        this.getView().up('app-main').controller.hideForm()
    },
});
