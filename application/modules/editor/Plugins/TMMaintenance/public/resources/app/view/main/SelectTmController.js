Ext.define('TMMaintenance.view.main.SelectTmController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.selecttm',

    onTmSelect: function (button) {
        const data = button.ownerCmp.getRecord().data;
        this.getViewModel().set('selectedTm', data.id);

        this.getView().up('app-main').down('#selectTmDialog').hide();
    },
});