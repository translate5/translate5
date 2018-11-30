
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class Editor.controller.admin.Customer
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.admin.Customer', {
    extend : 'Ext.app.Controller',

    views: [
        'Editor.view.admin.customer.Panel',
        'Editor.view.admin.customer.TagField',
    ],
    models:['Editor.model.admin.Customer'],
    stores:['Editor.store.admin.Customers','Editor.store.admin.UserCustomers'],

    refs:[{
        ref: 'customerPanel',
        selector: 'customerPanel'
    },{
        ref: 'centerRegion',
        selector: 'viewport container[region="center"]'
    },{
        ref: 'headToolBar',
        selector: 'headPanel toolbar#top-menu'
    },{
        ref: 'customerSwitch',
        selector: '#customerSwitch'
    }],

    listen: {
        component: {
            'headPanel toolbar#top-menu': {
                afterrender: 'onHeadPanelAfterRender'
            },
            '#btnCustomerOverviewWindow': {
                click: 'onCustomerOverviewClick'
            },
            'customerPanel':{
                show: 'onCustomerPanelShow'
            },
            'viewport container[region="center"] panel':{
                hide:'onCentarPanelComponentAfterLayout'
            },
            '#adminUserAddWindow':{
                afterrender:'onAdminUserAddWindowAfterRender',
                afterlayout: 'setFilteredCustomerForUserAdd', // Multitenancy
            },
            '#adminUserGrid': {
                beforerender:'onAdminUserGridBeforeRender'
            },
            '#taskMainCard':{
                afterrender:'setFilteredCustomerForTaskAdd' // Multitenancy
            },
            '#addTmWindow':{
                afterrender: 'setFilteredCustomerForLanguageResourceAdd', // Multitenancy
            },
            '#customerSwitch': {
                change: 'onCustomerSwitchChange' // Multitenancy
            },

        },
        controller:{
            '#Editor.$application': {
                editorViewportOpen: 'onEditorViewportOpen'
            }
        },
        store:{
            '#admin.Users':{
                load:'onUserStoreLoad'
            }
        }
    },

    strings:{
        customer:'#UT#Kunden',
        allCustomers:'#UT#Alle Kunden'
    },
    
    hasStoreUsersCustomers: false,
    
    /***
     * hide the customers button when editor is opened
     */
    onEditorViewportOpen:function(){
        this.getHeadToolBar().down('#btnCustomerOverviewWindow').setHidden(true);
    },

    /**
     * On head panel after render handler
     */
    onHeadPanelAfterRender: function(toolbar) {
        //if we are in edit task mode, do not add the customer button
        if(Ext.ComponentQuery.query('#segmentgrid')[0]){
            return;
        }
        
        if(!this.isCustomerOverviewAllowed()){
            return;
        }
        var pos = toolbar.items.length - 2;
        toolbar.insert(pos, {
            xtype: 'button',
            itemId: 'btnCustomerOverviewWindow',
            text:this.strings.customer
        });
        
        // multitenancy: add the drop-down "Switch client"
        this.addCustomerSwitch(toolbar);
    },

    /***
     * Fires when the components in this container are arranged by the associated layout manager.
     */
    onCentarPanelComponentAfterLayout:function(component){
        if(!this.isCustomerOverviewAllowed()){
            return;
        }
        //set the component to visible on each centar panel element hide
        this.setCustomerOverviewButtonHidden(false);
    },

    /**
     * Customer overview button click handler
     */
    onCustomerOverviewClick: function(window) {
        var me = this,
            panel = me.getCustomerPanel();
        
        me.actualTask = window.actualTask;
        
        //hide all components in the cennter region
        me.getCenterRegion().items.each(function(item){
            item.hide();
        });

        if(!panel) {
            //add the customer panel to the center region
            panel = me.getCenterRegion().add({xtype: 'customerPanel'});
        }

        panel.show();

        me.onCustomerPanelShow(panel);
    },

    /**
     * Admin add window after render handler
     */
    onAdminUserAddWindowAfterRender:function(adminWindow){
        if(!this.isCustomerOverviewAllowed()){
            return;
        }
        var me=this,
            loginFieldset=adminWindow.down('#loginDetailsFieldset');
        
        loginFieldset.add({
            xtype:'customers'
        });
    },

    /***
     * On admin user grid before render handler.
     */
    onAdminUserGridBeforeRender:function(taskgrid){
        if(!this.isCustomerOverviewAllowed()){
            return;
        }

        //insert the customer column in the user grid
        var me = this,
            grid = taskgrid.getView().grid,
            column;

        if(grid.down('gridcolumn[dataIndex=customers]')){
            return;
        }
        
        column = Ext.create('Ext.grid.column.Column', {
            xtype: 'gridcolumn',
            width: 250,
            dataIndex:'customers',
            tdCls: 'customers',
            sortable: false,
            cls: 'customers',
            text:this.strings.customer,
            filter: {
                type: 'string'
            },
            renderer: function(v, meta, rec){
                var names = [];
                if(!v || v.length == 0){
                    return '';
                }
                var v = v.replace(/(^,)|(,$)/g, ''),
                    customersStore=Ext.StoreManager.get('customersStore');
                v=v.split(',');
                for(i=0;i<v.length;i++){
                    var tmpRec=customersStore.findRecord('id',v[i],0,false,false,true);
                    tmpRec && names.push(tmpRec.get('name'));
                }
                
                return names.join(',');
            }
        });
        //insert the column after the locale column
        grid.headerCt.insert((grid.down('gridcolumn[dataIndex=locale]').fullColumnIndex + 1), column);
        grid.getView().refresh();
    },

    /***
     * On customer pannel show handler
     */
    onCustomerPanelShow: function(panel) {
        //set the help button data
        Editor.data.helpSection = 'customeroverview';
        Editor.data.helpSectionTitle = panel.getTitle();

        //hide the customerOverview button
        this.setCustomerOverviewButtonHidden(true);
    },

    /**
     * Set the customer overview button hidden property
     */
    setCustomerOverviewButtonHidden:function(isHidden){
        if(!this.getHeadToolBar() || !this.getHeadToolBar().down('#btnCustomerOverviewWindow')){
            return;
        }
        this.getHeadToolBar().down('#btnCustomerOverviewWindow').setHidden(isHidden);
    },

    /**
     * Check if the user has a frontend right to see the customer overview 
     */
    isCustomerOverviewAllowed:function(){
        return Editor.app.authenticatedUser.isAllowed('customerAdministration');
    },

    /**
     * Users store load handler
     */
    onUserStoreLoad:function(){
        var me = this;
        if (!me.hasStoreUsersCustomers) { // avoid ongoing reloads eg. after multitenancy-filtering via "Switch Client"
            //after the users store is loaded, load the usersCustomers store
            Ext.StoreManager.get('userCustomers').loadCustom();
            me.hasStoreUsersCustomers = true;
        }
    },

    // --------------------------- Multitenancy ----------------------------
    
    /**
     * [Multitenancy:] Add the drop-down "Switch client"
     */
    addCustomerSwitch: function(toolbar) {
       var me = this,
           pos = toolbar.items.length - 1,
           storeForSwitch = Ext.create(Editor.store.admin.UserCustomers, {storeId:'userCustomersSwitch'}),
           allCustomers = this.strings.allCustomers;
        toolbar.insert(pos, {
            xtype: 'usercustomerscombo',
            allowBlank: true,
            itemId: 'customerSwitch',
            fieldLabel: '',
            store: storeForSwitch
        });
        storeForSwitch.on("load", function(store, items){
            store.insert(0, [{
                name: allCustomers,
                id: 0
            }]);
            me.setCustomerSwitchValue(0);
        });
    },

    /**
     * [Multitenancy:] "Switch client" drop-down change handler (filter all affected grids)
     */
    onCustomerSwitchChange: function(combo, customerId) {
        var customerName,
            customersStore,
            tasks = Ext.StoreMgr.get('admin.Tasks'),
            users = Ext.StoreMgr.get('admin.Users');
            languageResources = Ext.StoreManager.get('Editor.store.LanguageResources.LanguageResource');
        tasks.clearFilter();
        users.clearFilter();
        languageResources.clearFilter();
        if(customerId == 0) {
            return;
        }
        customersStore = Ext.StoreManager.get('customersStore');
        customerName = customersStore.findRecord('id',customerId,0,false,false,true).get('name');
        tasks.filter([{property: 'customerId', operator:'eq', value: customerId}]);
        users.filter([{property: 'customers', operator:'like', value: customerName}]);
        languageResources.filter([{property: 'resourcesCustomers', operator:'like', value: customerName}]);
    },

    /**
     * [Multitenancy:] Add task: preselect filtered customer
     */
    setFilteredCustomerForTaskAdd: function(taskMainCard) {
        var customerId = this.getCustomerSwitchValue(),
            customerIdField,
            customerNameField,
            customersStore,
            customerName;
        if (customerId == '0') {
            return;
        }
        // id
        customerIdField = taskMainCard.down('#customerId');
        customerIdField.setValue(customerId);
        // name
        customerNameField = taskMainCard.down('#customerNameField');
        if (customerNameField) {
            customersStore = Ext.StoreManager.get('customersStore');
            customerName = customersStore.findRecord('id',customerId,0,false,false,true).get('name');
            customerNameField.setValue(customerName);
        }
    },

    /**
     * [Multitenancy:] Add language resource: preselect filtered customer
     */
    setFilteredCustomerForLanguageResourceAdd: function(addTmWindow) {
        var customerId = this.getCustomerSwitchValue(),
            resourcesCustomersField;
        if (customerId == '0') {
            return;
        }
        resourcesCustomersField = addTmWindow.down('#resourcesCustomers');
        resourcesCustomersField.setValue(customerId);
    },
    

    /**
     * [Multitenancy:] Add user: preselect filtered customer
     */
    setFilteredCustomerForUserAdd: function(adminUserAddWindow){
        var customerId = this.getCustomerSwitchValue(),
            customersField;
        if (customerId == '0') {
            return;
        }
        customersField = adminUserAddWindow.down('#customers');
        customersField.setValue(customerId);
        // TODO: The value is set, but the visible content of the field does not show it.
        //   console.log(customersField.getValue());
        // Using the console to set the value works DOES immediately show it:
        //   Ext.ComponentQuery.query('#customers')[0].setValue(1)
        // Hence, maybe we are still too early here?
    },
    
    /**
     * 
     */
    setCustomerSwitchValue: function(val) {
        this.getCustomerSwitch().setValue(val);
    },
    
    /**
     * 
     */
    getCustomerSwitchValue: function() {
        return this.getCustomerSwitch().getValue();
    }
});