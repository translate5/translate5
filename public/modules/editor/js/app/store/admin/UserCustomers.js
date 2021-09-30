
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
 * All available customers for the curend loged in user
 * 
 * @class Editor.store.admin.UserCustomers
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.admin.UserCustomers', {
    extend : 'Ext.data.Store',
    model: 'Editor.model.admin.Customer',
    autoLoad: false,
    storeId:'userCustomers',
    /**
     * Load the customers assigned to the user
     */
    loadCustom:function(){
        var me=this;
        //get fresh user data
        Editor.model.admin.User.load(Editor.data.app.user.id, {
            scope: this,
            failure: function(record, operation) {
                Editor.app.getController('ServerException').handleCallback(arguments);
            },
            success: function(record, operation) {

                var userCustomers=record.get('customers').split(',');//get the user customers as array
                    userCustomers=userCustomers.filter(function(v){
                        return v!='';
                    });//remove the empty array fields
    
                //if no customers to the user are assigned, set empty store
                if(userCustomers.length<1){
                    return;
                }

                //get only the customers assigned to the user
                me.load({
                    params:{
                        filter: Ext.encode([{property: 'id',operator:'in', value: userCustomers}])
                    }
                });
            }
        });
    }
});