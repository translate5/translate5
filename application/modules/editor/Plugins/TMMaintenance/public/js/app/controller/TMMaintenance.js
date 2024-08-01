Ext.define('Editor.plugins.TMMaintenance.app.controller.TMMaintenance', {
    extend: 'Ext.app.Controller',

    listen: {
        component: {
            'viewport > #adminMainSection > tabbar': {
                afterrender: 'onMainSectionAfterRender'
            },
            '#adminMainSection #btnTMMaintenance': {
                click: 'onTMMaintenanceButtonClick'
            }
        }
    },

    strings: {
        TMMaintenance: 'TMMaintenance'
    },

    /**
     * On head panel after render handler
     */
    onMainSectionAfterRender: function (tabbar) {
        //if we are in edit task mode or are not allow to use the plugin, we do not add the button
        if (Ext.ComponentQuery.query('#segmentgrid')[0] || !this.isTMMaintenanceAllowed()){
            return;
        }

        tabbar.add({
            xtype: 'tab',
            closable: false,
            itemId: 'btnTMMaintenance',
            glyph: 'xf7d9@FontAwesome5FreeSolid',
            text: this.strings.TMMaintenance
        });
    },

    /***
     * TM Maintenance button handler
     */
    onTMMaintenanceButtonClick: function () {
        if (this.isTMMaintenanceAllowed()) {
            let url = Editor.data.restpath + 'tmmaintenance';

            //reset the window name, since the user can use this window to open the translate5
            //this can happen after the user logout login again in translate5
            window.name = '';
            window.open(url, 'tmmaintenance').focus();
        }
    },

    /**
     * Check if the user has right to use the tm maintenance
     */
    isTMMaintenanceAllowed: function () {
        return Editor.app.authenticatedUser.isAllowed('accessTMMaintenance');
    },
})
