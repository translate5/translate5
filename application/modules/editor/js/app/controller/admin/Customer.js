
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
                afterrender: 'onGridAfterRender',                           // Multitenancy
                filterchange: 'onGridFilterChange'                          // Multitenancy
            },
            '#addTmWindow':{
                afterrender: 'setFilteredCustomerForLanguageResourceAdd',   // Multitenancy
            },
            '#adminTaskGrid': {
                filterchange: 'onGridFilterChange'                          // Multitenancy
            },
            '#customerSwitch': {
                select: 'onCustomerSwitchSelect'                            // Multitenancy
            },
            '#taskMainCard':{
                afterrender:'setFilteredCustomerForTaskAdd'                 // Multitenancy
            },
            '#tmOverviewPanel':{
                filterchange: 'onGridFilterChange',                         // Multitenancy
                afterrender: 'onGridAfterRender',                           // Multitenancy
            }
        },
        global: {
            resetCustomerSwitch: 'resetCustomerSwitch'                      // Multitenancy
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
       // add and show the "All clients"-option
        storeForSwitch.on("load", function(store, items){
            store.insert(0, [{
                name: allCustomers,
                id: me.customerSwitchAllClientsValue
            }]);
            me.setCustomerSwitchValue(me.customerSwitchAllClientsValue);
        });
    },

    /**
     * [Multitenancy:] Reset customerSwitch to "All clients".
     */
    resetCustomerSwitch: function() {
        if (!this.handleFiltering) {
            return;
        }
        console.log('---- resetCustomerSwitch ----');
        if(Ext.ComponentQuery.query('#customerSwitch')[0]){
             Ext.ComponentQuery.query('#customerSwitch')[0].setValue('0');
        }
    },

    /**
     * [Multitenancy:] Check if CustomerSwitch exists before setting the value.
     */
    setCustomerSwitchValue: function(val) {
        var me = this;
        if (!me.getCustomerSwitch()) {
            return;
        }
        console.log("setCustomerSwitchValue for customerId: " + val);
        me.getCustomerSwitch().setValue(val);
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
            customerName = me.getCustomerName(customerId);
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
    
    /**
     * [Multitenancy:] On grid after render handler.
     */
    onGridAfterRender: function (grid) {
        var me = this,
            val = me.getCustomerName(me.getCustomerSwitchValue()),
            allGridsToCheck,
            gridToCheck;
        // Filter the grid according to the CustomerSwitch.
        // If the CustomerSwitch is set to "All Clients",
        // check if there is any filtering in the customer-columns
        // of the other grids (= they shall run synchronously).
        if (val == '') {
            allGridsToCheck = ['#adminTaskGrid','#adminUserGrid','#tmOverviewPanel'];
            Ext.Array.each(allGridsToCheck, function(gridId){
                if (val == '' && gridId != grid.getId()) {
                    gridToCheck = Ext.ComponentQuery.query(gridId)[0];
                    val = me.getCustomerFilterValInGrid(gridToCheck);
                    console.log('- ' + gridId + ' val : ' + val);
                }
            });
        }
        console.log(grid.getId() + ': onGridAfterRender (val: ' + val + ')');
        if (val != '') {
            me.setGridFilter(grid,val);
        }
    },
    
    /**
     * [Multitenancy:] Grid filterchange handler.
     */
    onGridFilterChange: function (store, filters, eOpts) {
        var me = this,
            storeId = store.getStoreId(),
            customerColumnName = me.getCustomerColumnNameInStore(store),
            isCustomerFilter = false,
            isXGridFilter = false,
            val = '';
        if (!me.handleFiltering) {
            return;
        }
        if (filters.length == 0) {
            return;
        }
        Ext.Array.each(filters, function(filter){
            if (Ext.String.startsWith(filter.getId(), 'x-gridfilter')) {
                isXGridFilter = true;
                if (filter.getProperty() == customerColumnName
                        && filter.getValue() != undefined) {
                    isCustomerFilter = true;
                    val = filter.getValue();
                    return false; // stop iteration
                }
            }
        });
        if (!isCustomerFilter) {
            console.log(storeId + ': no isCustomerFilter = not our job.');
            return;
        }
        // Why check on 'x-gridfilter'? 
        // - Because filtering via the CustomerSwitchChange also fires the
        //   filterchange-event, but must not be handled here.
        // - This also prevents the very first reset of the filters when a grid 
        //   is opened for the first time (= fires the filterchange-event, too).
        if (!isXGridFilter) {
            console.log(storeId + ': no x-gridfilter = not our job.');
            return;
        }
        console.log(storeId + " onGridFilterChange: " + val);
        me.setCustomerFilterForAllGrids(val,storeId);
    },

    /**
     * [Multitenancy:] "Switch client" drop-down change handler.
     */
    onCustomerSwitchSelect: function(combo) {
        var me = this,
            val;
        if (!me.handleFiltering) {
            return;
        }
        val = me.getCustomerName(me.getCustomerSwitchValue());
        console.log('onCustomerSwitchSelect: ' + me.getCustomerSwitchValue() + '/' + val);
        me.setCustomerFilterForAllGrids(val,me.customerSwitchId);
    },

    /**
     * [Multitenancy:] Filter grids (+ their stores) by customers according to the user's selection.
     * @param {string} val
     * @param {string} from (from: currently not used, but might be helpful again)
     */
    setCustomerFilterForAllGrids: function(val,from) {
        var me = this,
            // grids
            tasksGrid = Ext.ComponentQuery.query('#adminTaskGrid')[0],
            usersGrid = Ext.ComponentQuery.query('#adminUserGrid')[0],
            languageResourcesGrid = Ext.ComponentQuery.query('#tmOverviewPanel')[0],
            // stores
            tasksStore = Ext.StoreMgr.get('admin.Tasks'), 
            usersStore = Ext.StoreMgr.get('admin.Users'),
            languageResourcesStore = Ext.StoreManager.get('Editor.store.LanguageResources.LanguageResource');
        console.log('=> OK, setCustomerFilterForAllGrids mit val: ' + val + ' / from: ' + from);
        me.beforeStoreFiltering();
        // for the customer-column in the (active) grid:
        me.setGridFilter(tasksGrid,val);
        me.setGridFilter(usersGrid,val);
        me.setGridFilter(languageResourcesGrid,val);
        // for the stores of the grids:
        me.setStoreFilter(tasksStore,val);
        me.setStoreFilter(usersStore,val);
        me.setStoreFilter(languageResourcesStore,val);
        me.afterStoreFiltering();
    },

    /**
     * [Multitenancy:] Internal settings before the stores are being filtered.
     */
    beforeStoreFiltering: function() {
        console.log('****** beforeStoreFiltering *******');
        this.handleFiltering = false;
    },

    /**
     * [Multitenancy:] Internal "resets" after the stores have been filtered.
     */
    afterStoreFiltering: function() {
        console.log('****** afterStoreFiltering *******');
        this.isFromGridFilter = false;
        this.handleFiltering = true;
    },
    
    /**
     * [Multitenancy:] Return the customer-filter in the given grid (or false if there is none).
     * @param {object} store
     * @returns false|{object} filter
     */
    getCustomerFilterInGrid: function(grid) {
        if(!grid) {
            return false;
        }
        var me = this,
            customerColumnName = me.getCustomerColumnNameInStore(store),
            customerFilter = false;
        Ext.Array.each(grid.filters.items,function(filter){
            if (filter.getProperty() == customerColumnName) {
                customerFilter = filter;
                return false; // stop iteration
            }
        });
        return customerFilter;
    },
    
    /**
     * [Multitenancy:] Show in the grid how the customer-column is (not) filtered.
     * @param {object} grid
     * @param {string} val
     */
    setGridFilter: function(grid,val) {
        if(!grid) {
            return;
        }
        var me = this,
            gridFilters = grid.filters,
            store = gridFilters.store,
            storeId = store.getStoreId(),
            sorters = store.sorters,
            customerColumnName = me.getCustomerColumnNameInStore(store),
            customerColumn,
            isActive = (val != '');
        grid.selModel.deselectAll();
        if(sorters.length > 0){
            sorters.clear();
        }
        me.clearCustomerFilter(store,val); // otherwise we'll have the customer-filter twice
        customerColumn = grid.columnManager.getHeaderByDataIndex(customerColumnName);
        customerColumn.filter.setActive(isActive);
        console.log('GRID ' + grid.getId() + ' customerColumn.filter.setActive: ' + isActive + ' (val: ' + val + ')');
        customerColumn.filter.setValue(val);
        console.log('GRID ' + grid.getId() + ' customerColumn.filter.setValue: ' + val);
        if (val == '') {
            console.log('GRID ' + grid.getId() + ' gridFilters.clearFilters');
            gridFilters.clearFilters(); // TODO: only for customer-filter...
        }
    },
    
    /**
     * [Multitenancy:] Apply the filtering for the given grid's store.
     */
    setStoreFilter: function(store,val){
        var me = this,
            storeId = store.getStoreId();
        // clear filters (but don't remove other filters than the customer's filter!)
        if(val == '') {
            me.clearCustomerFilter(store,val);
            return;
        } 
        // set customer-filter
        me.setCustomerFilter(store,val);
    },
    
    /**
     * [Multitenancy:] Clear the customer-filter in the given grid's store (keep all others!).
     */
    clearCustomerFilter: function(store,val) {
        var me = this,
            customerFilter = me.getCustomerFilterInStore(store),
            otherFilters;
        if(customerFilter !== false) {
            otherFilters = me.getAllFilterInStoreExceptCustomerFilter(store);
            console.log('STORE ' + store.getStoreId() + ': customerFilter found (' + customerFilter.getProperty() + ') => removeFilter customerFilter');
            store.removeFilter(customerFilter.getProperty());
            console.log('STORE ' + store.getStoreId() + ': clear (all) filter');
            store.clearFilter();
            Ext.Array.each(otherFilters,function(filter){
                console.log('STORE ' + store.getStoreId() + ': reapply filter for ' + filter.property);
                store.filter([{property: filter.property, operator:filter.operator, value: filter.value}]);
            });
            store.reload();
        } else {
            console.log('STORE ' + store.getStoreId() + ': no customer filter set');
        }
    },
    
    /**
     * [Multitenancy:] Set the customer-filter in the given grid's store.
     */
    setCustomerFilter: function(store,val) {
        var me = this,
            storeId = store.getStoreId(),
            customerFilter = me.getCustomerFilterInStore(store),
            customerColumnName;
        if(customerFilter !== false) {
            // If there is a customer-filter already, we change the Value.
            customerFilter.setValue(val);
            console.log('STORE ' + storeId + ': set Value For (existing) Filter done: ' + val);
        } else {
            // If no customer-filter exists, we create one.
            customerColumnName = me.getCustomerColumnNameInStore(store);
            store.filter([{property: customerColumnName, operator:'like', value: val}]);
            console.log('STORE ' + storeId + ': set (new) Filter done: ' + val);
        }
        store.reload();
    },
    
    /**
     * [Multitenancy:] Return the customer-filter in the given store (or false if there is none).
     * @param {object} store
     * @returns false|{object} filter
     */
    getCustomerFilterInStore: function(store) {
        var me = this,
            customerFilter = false,
            customerColumnName = me.getCustomerColumnNameInStore(store);
        Ext.Array.each(store.getFilters().items,function(filter){
            if (filter.getProperty() == customerColumnName) {
                customerFilter = filter;
                return false; // stop iteration
            }
        });
        return customerFilter;
    },

    /**
     * [Multitenancy:] Returns all the filters for a store other than the customer filter.
     * @returns array
     */
    getAllFilterInStoreExceptCustomerFilter: function(store) {
        var me = this,
            otherFilter,
            allOtherFilters = [],
            customerColumnName = me.getCustomerColumnNameInStore(store);
        Ext.Array.each(store.getFilters().items,function(filter){
            if (filter.getProperty() != customerColumnName) {
                otherFilter = {property: filter.getProperty(), operator:filter.getOperator(), value: filter.getValue()};
                allOtherFilters.push(otherFilter);
            }
        });
        return allOtherFilters; 
    },
    
    /**
     * [Multitenancy:] Helpers.
     */
    getCustomerName: function (id) {
        var customersStore = Ext.StoreManager.get('customersStore'),
            customer = customersStore.getById(id);
        return customer ? customer.get('name') : '';
    },
    getCustomerColumnNameInStore: function (store) {
        var me = this,
            storeId = store.getStoreId();
        switch(storeId) {
            case 'admin.Tasks':
                return 'customerId';
                break;
            case 'admin.Users':
                return 'customers';
                break;
            case 'Editor.store.LanguageResources.LanguageResource':
                return 'resourcesCustomers';
                break;
        }
    },
    getCustomerFilterValInGrid: function (grid) {
        if(!grid) {
            return '';
        }
        var me = this,
            store = grid.filters.store,
            customerColumnName = me.getCustomerColumnNameInStore(store),
            customerColumn = grid.columnManager.getHeaderByDataIndex(customerColumnName),
            val = '';
        if (customerColumn.filter && customerColumn.filter.filter && customerColumn.filter.filter.getValue() != undefined) {
            val = customerColumn.filter.filter.getValue();
        }
        return val;
    },
});