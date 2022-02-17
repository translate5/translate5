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

Ext.define('Editor.view.admin.customer.CopyWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.copyCustomerWindow',

    /***
     *
     * @param combo
     * @param newValue
     */
    onAddCopyDefaultAssignmentsCustomerClick: function (){
        var me = this,
            combo = me.getView().down('#copyDefaultAssignmentsCustomer');
        me.copyCustomerMessageBox({
            copyDefaultAssignmentsCustomer:combo && combo.getValue()
        });
    },

    /***
     *
     * @param combo
     * @param newValue
     */
    onAddCopyConfigCustomerClick: function (){
        var me = this,
            combo = me.getView().down('#copyConfigCustomer');
        me.copyCustomerMessageBox({
            copyConfigCustomer:combo && combo.getValue()
        });
    },

    copyCustomerMessageBox:function (params){
        var me = this;
        Ext.Msg.show({
            title:me.getView().saveConfirmMessageTitle,
            message: me.getView().saveConfirmMessage,
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn:function (btn){
                if (btn === 'yes') {
                    me.copyCustomer(params);
                }
            }
        });
    },

    /***
     *
     * @param params
     */
    copyCustomer: function(params) {
        var me = this,
            record = me.getView().getRecord();

        if(!record){
            return;
        }

        Ext.Ajax.request({
            url: Editor.data.restpath+'customer/'+record.get('id')+'/copy/operation',
            params:params,
            method: 'post',
            scope: this,
            success: function (response) {
                Ext.StoreManager.get('customersStore').load();
                Editor.MessageBox.addSuccess(me.getView().strings.copySuccess);
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /***
     * Filter out the current selected customer(copy from customer) from the copy customers store
     * @param store
     */
    onCopyCustomersStoreLoad:function (store){
        var me = this,
            view = me.getView();
        if(!view || !view.getRecord()){
            return;
        }
        store.filterBy(function (r){
            return r.get('id') !== view.getRecord().get('id');
        });
    }
});