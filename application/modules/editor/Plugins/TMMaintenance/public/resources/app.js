/*
 * This file launches the application by asking Ext JS to create
 * and launch() the Application class.
 */
Ext.application({
    extend: 'TMMaintenance.Application',

    name: 'TMMaintenance',

    requires: [
        // This will automatically load all classes in the TMMaintenance namespace
        // so that application classes do not need to require each other.
        'TMMaintenance.*'
    ],

    // The name of the initial view to create.
    mainView: 'TMMaintenance.view.main.Main',

    init: function () {
        //enable json in our REST interface
        Ext.Ajax.setDefaultHeaders({
            'Accept': 'application/json',
            'CsrfToken': window.csrfToken
        });

        this.callParent(arguments);
    },

    /**
     * Launch
     */
    launch: function () {
        Ext.GlobalEvents.fireEvent('onApplicationLoad');
    },
});
