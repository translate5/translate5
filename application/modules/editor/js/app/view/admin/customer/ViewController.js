
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.customer.ViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.customerPanel',
    
    /**
     * Set record for editing
     */
    dblclick: function(dataview, record, item, index, e, eOpts) {
        var formPanel = this.getReferences().form,
            removeButton = this.getReferences().removeButton,
            vm = this.getViewModel();

        vm.set('record', record);
        vm.set('title', this.getView().strings.editCustomerTitle);

        formPanel.loadRecord(record);
        removeButton.setDisabled(false);
    },

    /**
     * Save record
     */
    save: function(button, e, eOpts) {
        var me = this,
            formPanel = me.getReferences().form,
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
            success: function() {
                Editor.MessageBox.addSuccess(me.getView().strings.customerSavedMsg);
                store.load();
                me.getView().unmask();
                //me.getView().fireEvent('customerSaved');
                me.cancelEdit();
            },
            failure: function(rec, op) {
                var error,
                    errorRes = op.error && op.error.response,
                    errorHandler = Editor.app.getController('ServerException');
                
                me.getView().unmask();
                if(errorRes && errorRes.responseText) {
                    error = Ext.decode(errorRes.responseText);
                    if(error.errors && op.error && op.error.status == '400') {
                        form.markInvalid(error.errors);
                        return;
                    }
                }
                errorHandler.handleCallback.apply(errorHandler, arguments); 
            }
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
        var formPanel = this.getReferences().form,
            form = formPanel.getForm(),
            vm = this.getViewModel();

        // Clear form
        form.reset();
        vm.set('record', false);
    },

    /***
     * Load empty record in customer form
     */
    add: function(button, e, eOpts) {
        var formPanel = this.getReferences().form,
            removeButton = this.getReferences().removeButton,
            form = formPanel.getForm(),
            newRecord = Ext.create('Editor.model.admin.Customer'),
            vm = this.getViewModel();

        // Clear form
        form.reset();

        // Set record
        form.loadRecord(newRecord);
        vm.set('record', newRecord);

        // Set title
        vm.set('title',this.getView().strings.addCustomerTitle);

        removeButton.setDisabled(true);
    },

    /**
     * Refresh the customers store
     */
    refresh: function(button, e, eOpts) {
        this.getReferences().list.getSelectionModel().deselectAll();
        Ext.StoreManager.get('customersStore').load();
    },

    /**
     * Show confirmation message for remove customer
     */
    remove:function(){
        var me=this;

        Ext.create('Ext.window.MessageBox').show({
            title: me.getView().strings.customerDeleteTitle,
            msg: me.getView().strings.customerDeleteMsg,
            buttons: Ext.Msg.YESNO,
            fn:me.handleDeleteCustomerWindowButton,
            scope:me,
            defaultFocus:'no',
            icon: Ext.MessageBox.QUESTION
        });

    },

    /***
     * Remove the loaded customer
     */
    removeCustomer:function(){
        var me = this,
            formPanel = me.getReferences().form,
            form = formPanel.getForm(),
            record = form.getRecord(),
            store = Ext.StoreManager.get('customersStore'),
            deleting = me.getView().strings.customerDeleteTitle;

        // Update associated record with values
        form.updateRecord();

        me.getView().mask(deleting);

        //remove the record from the store
        record.erase({
            success:function(){
                Editor.MessageBox.addSuccess(me.getView().strings.customerDeletedMsg);
                me.getView().unmask();
                me.cancelEdit();
                //me.getView().fireEvent('customerRemoved');
            },
            failure: function(rec, op) {
                var error,
                    errorRes = op.error && op.error.response,
                    errorHandler = Editor.app.getController('ServerException');
                
                me.getView().unmask();
                if(errorRes && errorRes.responseText) {
                    error = Ext.decode(errorRes.responseText);
                    if(error.errors && op.error && op.error.status == '400') {
                        form.markInvalid(error.errors);
                        return;
                    }
                }
                errorHandler.handleCallback.apply(errorHandler, arguments); 
            }
        });
    },

    /***
     * Handler for the delete customer dialog window.
     * 
     */
    handleDeleteCustomerWindowButton:function(button){
        if(button=="yes"){
            this.removeCustomer();
            return true;
        }
        return false
    },

    //when customers panel is displayed,this function is executed
    reloadCustomerStore:function(){
        Ext.StoreManager.get('customersStore').load();
    }
});
