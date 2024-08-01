Ext.define('TMMaintenance.view.main.SelectTmController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.selecttm',

    onTmSelect: function (button) {
        this.getViewModel().set('selectedTm', button.ownerCmp.getRecord().getId());
        this.getView().up('app-main').down('#selectTmDialog').hide();
    },

    onTmDoubleTap: function(grid, location) {
        this.getViewModel().set('selectedTm', location.record.getId());
        this.getView().up('app-main').down('#selectTmDialog').hide();
    }
});