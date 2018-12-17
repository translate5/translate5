
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
    requires: ['Editor.view.admin.customer.CustomerFilter'],

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
                afterrender:'onAdminUserAddWindowAfterRender'
            },
            '#adminUserGrid': {
                beforerender:'onAdminUserGridBeforeRender',
                filterchange: 'onGridFilterChange'                          // Multitenancy
            },
            '#addTmWindow':{
                afterrender: 'setFilteredCustomerForLanguageResourceAdd',   // Multitenancy
            },
            '#adminTaskGrid': {
                filterchange: 'onGridFilterChange'                          // Multitenancy
            },
            '#customerSwitch': {
                change: 'onCustomerSwitchChange'                            // Multitenancy
            },
            '#taskMainCard':{
                afterrender:'setFilteredCustomerForTaskAdd'                 // Multitenancy
            },
            '#tmOverviewPanel':{
                filterchange: 'onGridFilterChange'                          // Multitenancy
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
    
    // Multitenancy
    isUserCustomersStoreLoaded: false,
    // Multitenancy: Customer Switch
    customerSwitchId: 'customerSwitch', // CustomerSwitch-dropdown: component-Id
    customerSwitchAllClientsValue: 0,   // CustomerSwitch-dropdown: value for "All clients"
    // Multitenancy: filtering
    customerColumnNames: ['customerId','customers','resourcesCustomers'], // the columns that refer to the customer in the (stores of the affected) grids
    customerSwitchStoresAlreadyFiltered: [],
    handleFiltering: true,
    
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
                type: 'customer', // [Multitenancy]
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
        if (!me.isUserCustomersStoreLoaded) { // avoid ongoing reloads eg. after multitenancy-filtering via "Switch Client"
            //after the users store is loaded, load the usersCustomers store
            Ext.StoreManager.get('userCustomers').loadCustom();
            me.isUserCustomersStoreLoaded = true;
        }
    },

    // --------------------------- Multitenancy ----------------------------
    
    /**
     * [Multitenancy:] Add the drop-down "Switch client"
     */
    addCustomerSwitch: function(toolbar) {
        var me = this,
            auth = Editor.app.authenticatedUser,
            pos,
            storeForSwitch,
            allCustomers;
        if (!auth.isAllowed('editorCustomerSwitch')) {
            return;
        }
        pos = toolbar.items.length - 1;
        storeForSwitch = Ext.create(Editor.store.admin.UserCustomers, {storeId:'userCustomersSwitch',autoLoad:true});
        allCustomers = this.strings.allCustomers;
       // storeForSwitch refers to Editor.model.admin.Customer and does not wait for loadCustom() as the userCustomers does
       // hence it loads ALL customers, not only those assigned to the login-user. That's ok for now.
       toolbar.insert(pos, {
            xtype: 'usercustomerscombo',
            allowBlank: true,
            itemId: me.customerSwitchId,
            fieldLabel: '',
            store: storeForSwitch
        });
       // add the "All clients"-option
        storeForSwitch.on("load", function(store, items){
            store.insert(0, [{
                name: allCustomers,
                id: me.customerSwitchAllClientsValue
            }]);
            me.setCustomerSwitchValue(me.customerSwitchAllClientsValue);
        });
    },

    /**
     * [Multitenancy:] If the CustomerSwitch does not exist, we pretend that
     * it is set to "All clients".
     */
    getCustomerSwitchValue: function() {
        var me = this;
        if (!me.getCustomerSwitch()) {
            return me.customerSwitchAllClientsValue;
        }
        return me.getCustomerSwitch().getValue();
    },

    /**
     * [Multitenancy:] Add task: preselect filtered customer
     */
    setFilteredCustomerForTaskAdd: function(taskMainCard) {
        var me = this,
            customerId = this.getCustomerSwitchValue(),
            customerIdField,
            customerNameField,
            customersStore,
            customerName;
        if (customerId == me.customerSwitchAllClientsValue) {
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
        var me = this,
            customerId = this.getCustomerSwitchValue(),
            resourcesCustomersField;
        if (customerId == me.customerSwitchAllClientsValue) {
            return;
        }
        resourcesCustomersField = addTmWindow.down('#resourcesCustomers');
        resourcesCustomersField.setValue(customerId);
    },
    
    /**
     * [Multitenancy:] Add user: does not get a preselected customer 
     * (for users the customer is not mandatory)
     */

    // ---------------------- Multitenancy: filtering ----------------------
    
    // Concept: All filtering is done via the CustomerSwitch. Filtering via the grids changes
    // the CustomerSwitch-value which then invokes the filtering of the grids (= stores). The 
    // filter-changes that this causes in the stores must not start another filterchange-process.

    /**
     * [Multitenancy:] Grid filterchange handler.
     */
    onGridFilterChange: function (store, filters, eOpts) {
        console.dir(filters);
        var me = this,
            storeId = store.getStoreId(),
            customersStore,
            customerInStore,
            customerId = me.customerSwitchAllClientsValue,
            isXGridFilter = false;
        console.log("COMING FROM onGridFilterChange");
        filters.forEach(function(filter){
            if (Ext.String.startsWith(filter.getId(), 'x-gridfilter')) {
                isXGridFilter = true;
            }
            if (Ext.String.startsWith(filter.getId(), 'x-gridfilter') && Ext.Array.indexOf(me.customerColumnNames,filter.getProperty()) != -1  && filter.getValue() != undefined) {
                console.log(storeId + ": filter.getValue():" + filter.getValue());
                customersStore = Ext.StoreManager.get('customersStore');
                customerInStore = customersStore.findRecord('name',filter.getValue(),0,true,false,false);
                if(customerInStore) {
                    customerId = customerInStore.get('id');
                }
                console.log('stop iteration');
                return false; // stop iteration
            }
        });
        // Why check on 'x-gridfilter'? 
        // - Because filtering via the CustomerSwitchChange also fires the
        //   filterchange-event, but must not be handled here.
        // - This also prevents the very first reset of the filters when a grid 
        //   is opened for the first time (= fires the filterchange-event, too).
        if (!isXGridFilter) {
            console.log(storeId + ': no x-gridfilter = not our job.');
            return;
        }
        if (!me.handleFiltering) {
            console.log("no handleFiltering => RETURN");
            return;
        }
        console.log(storeId + ': START setCustomerSwitchValue with ' + customerId);
        me.setCustomerSwitchValue(customerId); // = this will set the CustomerSwitch
    },

    /**
     * [Multitenancy:] Check if CustomerSwitch exists before setting the value.
     */
    setCustomerSwitchValue: function(val) {
        var me = this;
        if (!me.getCustomerSwitch()) {
            return; // no CustomerSwitch, no Multitanancy-filtering!
        }
        me.getCustomerSwitch().setValue(val); // = fires the event for the onCustomerSwitchChange
    },

    /**
     * [Multitenancy:] "Switch client" drop-down change handler.
     */
    onCustomerSwitchChange: function(combo, customerId) {
        var me = this;
        console.log("STARTED BY onCustomerSwitchChange");
        console.log("customerSwitch value: " + me. getCustomerSwitchValue());
        me.clearStoresAlreadyFiltered();
        me.customerSwitchStoresAlreadyFiltered.push(me.customerSwitchId);
        me.setCustomerFilteringForStores(customerId,me.customerSwitchId);
    },

    /**
     * [Multitenancy:] Check if all stores and the CustomerSwitch are filtered for the current filter-change-event.
     * If we don't check this (and don't stop then), every filtering causes a new filterchange-event that we would handle.
     */
    clearStoresAlreadyFiltered: function() {
        var me = this;
        console.log('****** clearStoresAlreadyFiltered *******');
        me.customerSwitchStoresAlreadyFiltered = [];
    },

    /**
     * [Multitenancy:] Filter stores by customers according to the user's selection.
     */
    setCustomerFilteringForStores: function(customerId) {
        var me = this,
            customerName,
            customersStore,
            tasks = Ext.StoreMgr.get('admin.Tasks'),
            users = Ext.StoreMgr.get('admin.Users'),
            languageResources = Ext.StoreManager.get('Editor.store.LanguageResources.LanguageResource');
        console.log('setCustomerFilteringForStores: ' + customerId);
        me.filterStore(tasks, customerId);
        me.filterStore(users, customerId);
        me.filterStore(languageResources, customerId);
    },

    /**
     * [Multitenancy:] Apply the filtering for the given store.
     */
    filterStore: function(store, customerId){
        var me = this,
            storeId = store.getStoreId(),
            customersStore,
            customerName,
            storeCustomerFilters = {};
        // filter is already set for this store
        if (Ext.Array.indexOf(me.customerSwitchStoresAlreadyFiltered,storeId) != -1) {
            console.log(storeId + ': already filtered');
            return;
        }
        // now the filtering of the store will be handled => list it as such!
        me.customerSwitchStoresAlreadyFiltered.push(storeId);
        // clear filters (but don't remove other filters than the customer's filter!)
        if(customerId == me.customerSwitchAllClientsValue) {
            if (!me.isStoreFilteredByCustomerFilter(store)) {
                console.log(storeId + ': clear filter not necessary; was not filtered on customers.');
                return;
            }
            me.handleFiltering = false;
            console.log(storeId + ': clear filter');
                storeCustomerFilters = me.getStoreFiltersExceptCustomerFilter(store);
                store.clearFilter();
                storeCustomerFilters.forEach(function(filter){
                    console.log(storeId + ': reapply filter for ' + filter.property);
                    store.filter([{property: filter.property, operator:filter.operator, value: filter.value}]);
                });
                me.handleFiltering = true;
            return;
        } 
        // set customer-filter (either by name or by id, depends on the store)
        me.handleFiltering = false;
        console.log(storeId + ': set Filter');
        customersStore = Ext.StoreManager.get('customersStore');
        customerName = customersStore.findRecord('id',customerId,0,false,false,true).get('name');
        switch(storeId) {
            case 'admin.Tasks':
                store.filter([{property: 'customerId', operator:'eq', value: customerId}]);
                console.log(storeId + ': set Filter done');
                break;
            case 'admin.Users':
                store.filter([{property: 'customers', operator:'like', value: customerName}]);
                console.log(storeId + ': set Filter done');
                break;
            case 'Editor.store.LanguageResources.LanguageResource':
                store.filter([{property: 'resourcesCustomers', operator:'like', value: customerName}]);
                console.log(storeId + ': set Filter done');
                break;
            }
        me.handleFiltering = true;
    },
    

    /**
     * [Multitenancy:] Is the store filtered by customers?
     * @returns booelan
     */
    isStoreFilteredByCustomerFilter: function(store) {
        var me = this,
            isFilteredByCustomers = false;
        store.getFilters().items.forEach(function(filter){
            if (Ext.Array.indexOf(me.customerColumnNames,filter.getProperty()) != -1) {
                isFilteredByCustomers = true;
                return false; // stop iteration
            }
        });
        return isFilteredByCustomers;
    },

    /**
     * [Multitenancy:] Returns all the filters for a store other than the customer filter.
     * @returns array
     */
    getStoreFiltersExceptCustomerFilter: function(store) {
        var me = this,
            otherFilter,
            allOtherFilters = [];
        store.getFilters().items.forEach(function(filter){
            if (Ext.Array.indexOf(me.customerColumnNames,filter.getProperty()) == -1) {
                otherFilter = {property: filter.getProperty(), operator:filter.getOperator(), value: filter.getValue()};
                allOtherFilters.push(otherFilter);
            }
        });
        return allOtherFilters; 
    },
});