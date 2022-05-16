Ext.define('TMMaintenance.view.main.MainController', {
    extend: 'Ext.app.ViewController',

    alias: 'controller.main',

    /**
     * @param {TMMaintenance.view.main.List} sender
     * @param {Array} record
     */
    onItemSelected: function (sender, record) {
        let form = Ext.getCmp('editform');

        if (form.isDirty()) {
            Ext.Msg.confirm(
                'Confirm',
                'Form has unsaved changes, do you want to save them before proceed?',
                '',
                this
            );
        }

        form.setRecord(record[0]);
        form.getItems().each(function (item) {
            if (typeof(item.resetOriginalValue) === 'function') {
                item.resetOriginalValue();
            }
        });
    },
});
