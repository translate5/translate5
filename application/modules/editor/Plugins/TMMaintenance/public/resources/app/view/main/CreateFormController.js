Ext.define('TMMaintenance.view.main.CreateFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.createform',

    onSavePress: function () {
        let form = this.view;

        if (!form.isValid()) {
            return;
        }

        let record = new TMMaintenance.model.Segment();

        const values = form.getValues();
        values.source = Ext.htmlEncode(values.source);
        values.target = Ext.htmlEncode(values.target);

        record.set(values);
        record.set({tm: this.getViewModel().get('selectedTm')});
        record.save({
            success: () => {
                this.getView().up('app-main').controller.hideForm();
            },
            failure: (record, operation) => {
                this.getView().up('app-main').controller.showServerError(operation.getError());
            },
        });

        // record.getStore().add(record);
        // record.getStore().sync({
        //     success: () => {
        //         this.getView().up('app-main').controller.hideForm();
        //     }
        // });
    },

    onCancelPress: function () {
        this.view.setValues({
            rawSource: '',
            rawTarget: '',
        });
        this.getView().up('app-main').controller.hideForm()
    },
});
