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
     */
    onCopyButtonClick: function (){
        var me = this,
            copyDefaultAssignmentsCustomer = me.getView().down('#copyDefaultAssignmentsCustomer'),
            copyConfigCustomer = me.getView().down('#copyConfigCustomer'),
            assignmentsValue = copyDefaultAssignmentsCustomer && copyDefaultAssignmentsCustomer.getValue(),
            configsValue = copyConfigCustomer && copyConfigCustomer.getValue();

        if(Ext.isEmpty(assignmentsValue) && Ext.isEmpty(configsValue)){
            Editor.MessageBox.addInfo(me.getView().strings.noCustomerSelected);
            return;
        }

        me.copyCustomer({
            copyDefaultAssignmentsCustomer:assignmentsValue,
            copyConfigCustomer:configsValue,
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

        me.getView().mask();
        Ext.Ajax.request({
            url: Editor.data.restpath+'customer/'+record.get('id')+'/copy/operation',
            params:params,
            method: 'post',
            scope: this,
            success: function () {
                me.reloadStores();
            },
            failure: function (response) {
                me.getView().unmask();
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
    },

    /***
     * Reload the user defaults store and the config store
     */
    reloadStores:function (){
        var me = this,
            view = me.getView(),
            panel = Ext.ComponentQuery.query('customerPanel')[0],
            assocPanel = panel && panel.down('adminUserAssoc'),
            adminUserAssocGrid = assocPanel && assocPanel.down('adminUserAssocGrid'),
            adminConfigGrid = panel && panel.down('adminConfigGrid'),
            unmaskAndClose = function (){
                if(view && view.isMasked()){
                    view.unmask();
                    view.destroy();
                }
            };

        if(adminUserAssocGrid && adminUserAssocGrid.getStore){
            adminUserAssocGrid.getStore().load({callback:unmaskAndClose});
        }

        if(adminConfigGrid && adminConfigGrid.getStore){
            adminConfigGrid.getStore().load({callback:unmaskAndClose});
        }
    }
});