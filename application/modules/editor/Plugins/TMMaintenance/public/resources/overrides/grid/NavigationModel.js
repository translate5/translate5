Ext.define('TMMaintenance.override.grid.NavigationModel', {
    override: 'Ext.grid.NavigationModel',
    privates: {
        triggerActionable: function(actionable, e) {
            if (!e.getTarget('.x-gridrow')) {
                return;
            }
            this.callParent(arguments);
        },
    }
});