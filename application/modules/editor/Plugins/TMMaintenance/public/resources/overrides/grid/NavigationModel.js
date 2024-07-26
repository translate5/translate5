Ext.define('TMMaintenance.override.grid.NavigationModel', {
    override: 'Ext.grid.NavigationModel',
    privates: {
        triggerActionable: function(actionable, e) {
            if (e.getTarget('.x-scrollbar')) {
                return;
            }
            this.callParent(arguments);
        },
    }
});