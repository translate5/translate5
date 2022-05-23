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
        record.set(form.getValues());
        //TODO get from base/main/global
        record.set({tm: Ext.getCmp('searchform').getValues().tm});
        record.save({
            success: () => {
                me.onSuccess();
            }
        });
    },

    onCancelPressed: function () {
        this.view.hide();
        this.view.reset();
    },

    onSuccess: function () {
        this.view.hide();
        this.view.reset();
    }
});
