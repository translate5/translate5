Ext.define('TMMaintenance.view.main.EditFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.editform',

    onSave: function () {
        let form = this.view;

        if (form.isValid()) {
            let values = form.getValues();
            let segment = Ext.create('TMMaintenance.model.Segment', values);

            segment.save({});
        }
    },
});
