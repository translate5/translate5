
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.view.admin.customer.ViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.customerPanel',
    requires: ['Editor.view.mixin.UserFilterPresetable'],
    routes: {
        'client': 'onClientRoute'
    },
    mixins: {
        userFilterPresetable: 'Editor.view.mixin.UserFilterPresetable'
    },
    init: function(view) {
        this.mixins.userFilterPresetable.init(this, view);
    },
    listen:{
        controller: {
            '#Editor.$application': {
                adminSectionChanged: 'onApplicationSectionChanged'
            }
        },
        component:{
            '#saveOpenIdButton':{
                click:'save'
            },
            '#cancelOpenIdButton': {
                click:'cancelEdit'
            },
            '#adminUserAssoc': {
                beforerender: function (panel, opts) {
                    panel.setTaskGuid(null);
                },
            },
            // This selector will select all menuitems inside toolbar's overflow-menu
            // so that click event is triggered on toolbar's corresponding item
            'button[iconCls="x-toolbar-more-icon"] > menu > menuitem': {
                click: menuitem => menuitem.masterComponent.fireEvent('click')
            },
            '#displayTabPanel': {
                tabchange: 'onDisplayTabPanelTabChanged'
            }
        },
        store:{
            'customersStore': {
                filterchange: 'onCustomerStoreFilterChange'
            }
        }
    },

    /**
     * Will hide the domain tooltip when the application section is changed.
     * The domain tooltip is only visible in the customer section, and it has the closable
     * flag set because of the inner tooltip link.
     * @param openedView
     */
    onApplicationSectionChanged: function (openedView){
        var me = this,
            view = me.getView();

        if(!view || !openedView){
            return;
        }
        if(view.getXType() === openedView.getXType()){
            return;
        }

        if(view.domainLabelInfoTooltip && view.domainLabelInfoTooltip.isVisible()){
            view.domainLabelInfoTooltip.hide();
        }
    },

    onClientRoute: function() {
        Editor.app.openAdministrationSection(this.getView());
    },

    /**
     * Set record for editing
     */
    dblclick: function(dataview, record, item, index, e, eOpts) {
        this.editCustomer(record);
    },

    /***
     * Customer grid row select handler
     */
    customerGridSelect: function(selection, record){
        var view = this.getView(),
            grid = view && view.down('#customerPanelGrid');

        if (grid && grid.isVisible(true)) {
            // Set the record for editing only if the component is visible. The grid selection can be triggered
            // from multiple places (ex: reloading the customers store) which can lead to trying to edit the record,
            // when the component is not visible
            this.editCustomer(record);
        }
    },

    /***
     * Action icon "edit customer" event handler
     */
    onCustomerEditClick:function(view, cell, row, col, ev, record) {
        this.editCustomer(record);
    },

    /***
     * On export action column click handler
     */
    onTmExportClick:function(view, cell, row, col, ev, record) {
        this.exportCustomerResourceUsage(record && record.get('id'));
    },

    /***
     *
     * @param view
     * @param cell
     * @param row
     * @param col
     * @param ev
     * @param record
     */
    onCopyActionClick:function(view, cell, row, col, ev, record) {
        var win = Ext.create('Editor.view.admin.customer.CopyWindow');
        win.setRecord(record);
        win.show();
    },

    /**
     * Save record
     */
    save: function(button, e, eOpts) {
        var me = this,
            formPanel = me.getView().down('#customersForm'),
            form = formPanel.getForm(),
            record = form.getRecord(),
            store = Ext.StoreManager.get('customersStore'),
            saving = me.getView().strings.saveCustomerMsg;

        // Valid
        if (!form.isValid()) {
            return;
        }

        // Update associated record with values
        form.updateRecord();

        me.getView().mask(saving);

        record.save({
            preventDefaultHandler: true,
            success: function() {
                Editor.MessageBox.addSuccess(me.getView().strings.customerSavedMsg);
                store.load();
                me.getView().unmask();
                me.cancelEdit();
            },
            failure: function(rec, op) {
                me.getView().unmask();
                Editor.app.getController('ServerException').handleFormFailure(form, rec, op);
            }
        });
    },
    
    /***
     * Loads the current customer record into the customer edit form
     */
    editCustomer:function(record){
        var me=this,
            grid = me.getView().down('grid'),
            formPanel = me.getView().down('#customersForm'),
            vm = me.getViewModel();

        if(grid && Ext.isEmpty(grid.getSelection())){
            grid.setSelection(record);
        }

        vm.set('record', record);

        if (! formPanel) {
            return;
        }

        formPanel.loadRecord(record);

        if (! Editor.app.authenticatedUser.isAllowed('customerOpenIdAdministration')) {
            return;
        }
        
        var roles = record.get('openIdServerRoles').split(','),
            rolesBoxes=me.getView().down('#serverRolesGroup').items.items;
        Ext.Array.forEach(rolesBoxes, function(item) {
            item.setValue(Ext.Array.indexOf(roles, item.initialConfig.value) >= 0);
        });
        
        roles = record.get('openIdDefaultServerRoles').split(',');
        rolesBoxes=me.getView().down('#defaultRolesGroup').items.items;
        
        Ext.Array.forEach(rolesBoxes, function(item) {
            item.setValue(Ext.Array.indexOf(roles, item.initialConfig.value) >= 0);
        });
    },
    
    /***
     * Reset the form on escape key press
     */
    onCustomerPanelRender:function(cmp){
        var me=this,
            map = new Ext.util.KeyMap({
                target: cmp.getEl(),
                key: Ext.event.Event.ESC,
                fn: function(){
                    me.cancelEdit();
                }
            });
    },
    
    /**
     * Reset the customer form
     */
    cancelEdit: function(button, e, eOpts) {
        var me=this,
            formPanel = me.getView().down('#customersForm'),
            form = formPanel.getForm(),
            vm = me.getViewModel();

        // Clear form
        form.reset();
        vm.set('record', false);
    },

    /***
     * Load empty record in customer form
     */
    add: function(button, e, eOpts) {
        var me = this,
            formPanel = me.getView().down('#customersForm'),
            form = formPanel.getForm(),
            newRecord = Ext.create('Editor.model.admin.Customer'),
            vm = me.getViewModel();


        // set the first tab always active after new record add
        me.getView().down('#displayTabPanel').setActiveTab(0);

        // Clear form
        form.reset();

        // Set record
        form.loadRecord(newRecord);
        vm.set('record', newRecord);

        // Set title
        vm.set('title',this.getView().strings.addCustomerTitle);
    },

    /**
     * Refresh the customers store
     */
    refresh: function(button, e, eOpts) {
        Ext.StoreManager.get('customersStore').reload();
    },

    /**
     * Show confirmation message for remove customer
     *
     * @param {Ext.grid.View} view
     * @param {DOMElement} cell
     * @param {Integer} row
     * @param {Integer} col
     * @param {Ext.Event} ev
     * @param {Object} record
     */
    remove:function(view, cell, row, col, ev, record) {
        var me=this;

        Ext.create('Ext.window.MessageBox').show({
            title: me.getView().strings.customerDeleteTitle,
            msg: me.getView().strings.customerDeleteMsg,
            buttons: Ext.Msg.YESNO,
            fn:function(button){
                if(button === "yes"){
                    me.removeCustomer(record);
                    return true;
                }
                return false;
            },
            scope:me,
            defaultFocus:'no',
            icon: Ext.MessageBox.QUESTION
        });

    },

    /***
     * Remove the loaded customer
     */
    removeCustomer:function(record){
        var me = this,
            store = Ext.StoreManager.get('customersStore'),
            deleting = me.getView().strings.customerDeleteTitle;

        me.getView().mask(deleting);

        //remove the record from the store
        record.erase({
            success:function(){
                Editor.MessageBox.addSuccess(me.getView().strings.customerDeletedMsg);
                me.getView().unmask();
                me.cancelEdit();
            },
            failure: function(rec, op) {
                store.load();
                me.getView().unmask();
                Editor.app.getController('ServerException').handleCallback(rec, op, false);
            }
        });
    },

    /***
     * On customer panel activate event handler
     */
    onCustomerPanelActivate:function(){
        Ext.StoreManager.get('customersStore').load();
    },
    
    /***
     * Generate excel for resource usage for the given customer. If the customer is not defined,
     * summ excel for all customers will be generated.
     */
    exportCustomerResourceUsage:function(id){
        var url = Editor.data.restpath+'customer/exportresource?format=resourceLogExport',
            extraParams = [];

        if(id){
            extraParams.push(Ext.urlEncode({customerId: id}));
        }

        // Fire before resources export event.
        this.getView().fireEvent('beforeExportCustomerResourceUsage',extraParams);

        Ext.each(extraParams, function(ob){
            url += '&'+ob;
        });

        window.open(url);
    },

    /**
     * Auto-select first record if current selection do not match the filters
     *
     * @param store
     */
    onCustomerStoreFilterChange: function(store) {

        // Get selection model
        var sm = this.getView().down('#customerPanelGrid').getSelectionModel();

        // If we have some record selected
        if (sm.getSelection().length) {

            // Get that record
            var record = sm.getSelection()[0];

            // If it's still in the store despite the filters - do nothing
            if (~store.findExact('id', record.getId())) {

            // Else if not, but store is not empty
            } else if (store.getCount()){

                // Select first record we have
                sm.select(store.first());

            // Else if store is empty
            } else {

                // Clear selection
                sm.deselect([record]);

                // Reset right panel
                this.cancelEdit();
            }

        // Ele select first record
        } else {
            sm.select(store.first());
        }
    },

    /**
     * Check and hide the domain tooltip when the tab is changed and the active tab is not
     * the customer form.
     * @param tabPanel
     */
    onDisplayTabPanelTabChanged: function(tabPanel) {
        if(tabPanel.getActiveTab().getItemId() === 'customersForm'){
            return;
        }
        if(this.getView().domainLabelInfoTooltip && this.getView().domainLabelInfoTooltip.isVisible()){
            this.getView().domainLabelInfoTooltip.hide();
        }
    }
});
