
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

/**
 * All available customers for the currently loged in user
 * 
 * @class Editor.store.admin.UserCustomers
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.admin.UserCustomers', {
    extend : 'Ext.data.Store',
    model: 'Editor.model.admin.Customer',
    autoLoad: false,
    storeId:'userCustomers',
    pageSize: 0,
    /**
     * Load the customers assigned to the user
     */
    loadCustom:function(){
        var me = this;
        //get fresh user data
        Editor.model.admin.User.load(Editor.data.app.user.id, {
            scope: this,
            failure: function(record, operation) {
                Editor.app.getController('ServerException').handleCallback(record, operation, false);
            },
            success: function(record) {
                /** @var record {Editor.model.admin.User} */
                var userCustomers = record.getCustomerIds(); // get the user customers as int array
                //if no customers to the user are assigned, set empty store
                if(userCustomers.length < 1){
                    return;
                }
                //get only the customers assigned to the user
                me.load({
                    params:{
                        filter: Ext.encode([{property: 'id', operator: 'in', value: userCustomers}])
                    }
                });
            }
        });
    },

    /***
     * Return the default customer id. If there is no default customer in the current store, the first record's id in the store is returned.
     * @returns {*|null}
     */
    getDefaultCustomerId:function (){
       var rec = this.findRecord( 'name', Editor.data.customers.defaultCustomerName, 0, false, true, true);
       if(!rec){
           rec = this.getAt(0);
       }
       return rec ? rec.get('id') : null;
    }
});