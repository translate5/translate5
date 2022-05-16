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
        TMMaintenance: '#UT#TMMaintenance'
    },

    /**
     * On head panel after render handler
     */
    onMainSectionAfterRender: function (tabbar) {
        let _this = this;

        //if we are in edit task mode or are not allow to use the termportal, we do not add the portal button
        if (Ext.ComponentQuery.query('#segmentgrid')[0]) { // || !me.isTMMaintenanceAllowed()){
            return;
        }

        tabbar.add({
            xtype: 'tab',
            closable: false,
            itemId: 'btnTMMaintenance',
            glyph: 'xf002@FontAwesome5FreeSolid',
            text: _this.strings.TMMaintenance
        });
    },

    /***
     * TM Maintenance button handler
     */
    onTMMaintenanceButtonClick: function () {
        // if (this.isTMMaintenanceAllowed()) {
            let url = Editor.data.restpath + 'tmmaintenance';

            //reset the window name, since the user can use this window to open the translate5
            //this can happen after the user logout from termportal and login again in translate5
            window.name = '';
            window.open(url, 'tmmaintenance').focus();
            // Yet, this still does not always re-focus an already existing Termportal-Tab:
            // - "Firefox (51.) gets the handle but cannot run any Element.focus() "
            //   (https://developer.mozilla.org/en-US/docs/Web/API/Window/open#Note_on_use_of_window_open)
            // - "It may fail due to user settings and the window isn't guaranteed to be frontmost before this method returns."
            //   (https://developer.mozilla.org/en-US/docs/Web/API/Window/focus)
        // }
    },

    // /**
    //  * Check if the user has right to use the term portal
    //  */
    // isTMMaintenanceAllowed:function(){
    //     var userRoles=Editor.data.app.user.roles.split(",");
    //
    //     //FIXME must check the rights, not the roles! a new frontend right must be added and distributed over the roles
    //     return (Ext.Array.indexOf(userRoles, "termCustomerSearch") >= 0) || (Ext.Array.indexOf(userRoles, "termProposer") >= 0) ;
    // },
})
