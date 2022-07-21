Ext.define('TMMaintenance.view.main.CreateFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.createform',

    onSavePress: function () {
        let form = this.view;

        if (!form.isValid()) {
            return;
        }

        let record = Ext.create('TMMaintenance.model.Segment', {});

        record.set(form.getValues());
        record.set({tm: this.getViewModel().get('selectedTm')});
        record.save({
            success: () => {
                this.getView().up('app-main').controller.hideForm();
            }
        });
    },

    onCancelPress: function () {
        this.view.setValues({
            rawSource: '',
            rawTarget: '',
        });
        this.getView().up('app-main').controller.hideForm()
    },
});
