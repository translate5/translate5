
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
 * @event filteredCustomerForTaskAdd : It is fired when in the task add window, the customer is set from the global customers dropdown
 */
Ext.define('Editor.controller.admin.Customer', {
    extend : 'Ext.app.Controller',
    requires: ['Editor.view.admin.customer.CustomerFilter'],
    mixins: ['Editor.util.DevelopmentTools'],

    views: [
        'Editor.view.admin.customer.Panel',
        'Editor.view.admin.customer.TagField',
    ],
    models:['Editor.model.admin.Customer'],
    stores:['Editor.store.admin.Customers','Editor.store.admin.UserCustomers'],

    refs:[{
        ref: 'customerSwitch',
        selector: '#customerSwitch'
    }],

    listen: {
        component: {
            'headPanel toolbar#top-menu': {
                afterrender: 'onHeadPanelAfterRender'
            },
            '#adminUserAddWindow':{
                afterrender:'onAdminUserAddWindowAfterRender'
            },
            '#adminUserGrid': {
                beforerender:'onAdminUserGridBeforeRender',
                afterrender: 'onGridAfterRender'                            // Multitenancy
            },
            '#addTmWindow':{
                afterrender: 'setFilteredCustomerForLanguageResourceAdd',   // Multitenancy
            },
            '#customerSwitch': {
                select: 'onCustomerSwitchSelect'                            // Multitenancy
            },
            '#taskMainCard':{
                afterrender:'setFilteredCustomerForTaskAdd'                 // Multitenancy
            },
            '#tmOverviewPanel':{
                afterrender: 'onGridAfterRender'                           // Multitenancy
            }
        },
        global: {
            resetCustomerSwitch: 'resetCustomerSwitch'                      // Multitenancy
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
    customerSwitchLastClientsValue: 0,  // Last value of the custom clients switch. It is used when the user leave the task
    // Multitenancy: filtering
    handleFiltering: true,
    
    helpSection: 'customeroverview',
    
    /**
     * On head panel after render handler
     */
    onHeadPanelAfterRender: function(toolbar) {
        //if we are in edit task mode, do not add the multitenancy button
        if(Ext.ComponentQuery.query('#segmentgrid')[0] || !this.isCustomerOverviewAllowed()){
            return;
        }
        // multitenancy: add the drop-down "Switch client"
        this.addCustomerSwitch(toolbar);
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
            
            me.setCustomerSwitchValue(me.customerSwitchLastClientsValue);
        });
    },

    /**
     * [Multitenancy:] Reset customerSwitch to "All clients".
     */
    resetCustomerSwitch: function() {
        var me = this;
        if (!me.handleFiltering) {
            return;
        }
        me.consoleLog('---- resetCustomerSwitch ----');
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
        me.consoleLog("setCustomerSwitchValue for customerId: " + val);
        me.getCustomerSwitch().setValue(val);
        me.customerSwitchLastClientsValue = val;
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
        
        // fire event that the customer in the task add window is set by the global customers filter
        me.fireEvent('filteredCustomerForTaskAdd',customerId);
    },

    /**
     * [Multitenancy:] Add language resource: preselect filtered customer
     */
    setFilteredCustomerForLanguageResourceAdd: function(addTmWindow) {
        var me = this,
            customerId = this.getCustomerSwitchValue();
        if (customerId == me.customerSwitchAllClientsValue) {
            return;
        }
        addTmWindow.down('#resourcesCustomers').setValue(customerId);
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
                    me.consoleLog('- ' + gridId + ' val : ' + val);
                }
            });
        }
        me.consoleLog(grid.getId() + ': onGridAfterRender (val: ' + val + ')');
        if (val != '') {
            me.setGridFilter(grid,val);
        }
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
        var customerId = me.getCustomerSwitchValue();
        val = me.getCustomerName(customerId);
        me.customerSwitchLastClientsValue = customerId;
        me.consoleLog('onCustomerSwitchSelect: ' + customerId + '/' + val);
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
            projectGrid = Ext.ComponentQuery.query('#projectGrid')[0];
        me.consoleLog('=> OK, setCustomerFilterForAllGrids mit val: ' + val + ' / from: ' + from);
        me.beforeStoreFiltering();
        me.setGridFilter(tasksGrid,val);
        me.setGridFilter(usersGrid,val);
        me.setGridFilter(languageResourcesGrid,val);
        me.setGridFilter(projectGrid,val);
        me.afterStoreFiltering();
    },

    /**
     * [Multitenancy:] Internal settings before the stores are being filtered.
     */
    beforeStoreFiltering: function() {
        var me = this;
        me.consoleLog('****** beforeStoreFiltering *******');
        me.handleFiltering = false;
    },

    /**
     * [Multitenancy:] Internal "resets" after the stores have been filtered.
     */
    afterStoreFiltering: function() {
        var me = this;
        me.consoleLog('****** afterStoreFiltering *******');
        me.isFromGridFilter = false;
        me.handleFiltering = true;
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
            sorters = store.sorters ? store.sorters : null,
            customerColumnName = me.getCustomerColumnNameInStore(store),
            customerColumn;
        if(sorters && sorters.length > 0){
            sorters.clear();
        }
        if (val == '') {
            me.consoleLog('GRID ' + grid.getId() + ' remove customer-filter');
            customerColumn = grid.columnManager.getHeaderByDataIndex(customerColumnName);
            if(!customerColumn){
                //the column should not be rendered, remove the filter only from the store
                //TODO: the users grid is not filtered
                gridFilters.clearFilters();
            }else{
                customerColumn.filter.setActive(false);
            }
        } else {
            me.consoleLog('GRID ' + grid.getId() + ' add customer-filter');
            gridFilters.addFilters([{
                type: 'customer',
                dataIndex: customerColumnName,
                property: customerColumnName,
                value: val
            }]);
        }
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
            case 'project.Project':
                return 'customerId';
                break;
            case 'admin.Users':
                return 'customers';
                break;
            case 'Editor.store.LanguageResources.LanguageResource':
                return 'customerIds';
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
        if (customerColumn && customerColumn.filter && customerColumn.filter.filter && customerColumn.filter.filter.getValue() != undefined) {
            val = customerColumn.filter.filter.getValue();
        }
        return val;
    },
});