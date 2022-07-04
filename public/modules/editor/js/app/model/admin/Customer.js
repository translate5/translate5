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

Ext.define('Editor.model.admin.Customer', {
    extend: 'Ext.data.Model',
    alias: 'model.customer',
    fields: [
        {type: 'int', name: 'id', persist: false},
        {type: 'string', name: 'name', validations: [{type: 'presence'}, {type: 'length', max: 255, min: 3}]},
        {type: 'string', name: 'number', validations: [{type: 'presence'}, {type: 'length', max: 255}]},
        {type: 'string', name: 'domain', validations: [{type: 'presence'}, {type: 'length', max: 255}]},
        {type: 'string', name: 'openIdServer', validations: [{type: 'presence'}, {type: 'length', max: 255}]},
        {type: 'string', name: 'openIdIssuer', validations: [{type: 'presence'}, {type: 'length', max: 255}]},
        {type: 'string', name: 'openIdServerRoles', validations: [{type: 'presence'}, {type: 'length', max: 255}]},
        {type: 'string', name: 'openIdDefaultServerRoles', validations: [{type: 'presence'}, {type: 'length', max: 255}]},
        {type: 'string', name: 'openIdAuth2Url', validations: [{type: 'presence'}, {type: 'length', max: 255}]},
        {type: 'string', name: 'openIdClientId', validations: [{type: 'presence'}, {type: 'length', max: 1024}]},
        {type: 'string', name: 'openIdClientSecret', validations: [{type: 'presence'}, {type: 'length', max: 1024}]},
        {type: 'string', name: 'openIdRedirectLabel', validations: [{type: 'presence'}, {type: 'length', max: 1024}]},
        {type: 'auto', name: 'openIdRedirectCheckbox', serialize: function(value){return value ? '1' : '0';}},
        {
            name: 'isDefaultCustomer',
            calculate: function (data) {
                return data.name === Editor.data.customers.defaultCustomerName;
            }
        }

    ],

    idProperty: 'id',
    proxy: {
        type: 'rest',
        url: Editor.data.restpath + 'customer',
        reader: {
            rootProperty: 'rows',
            type: 'json',
        },
        writer: {
            encode: true,
            rootProperty: 'data',
            writeAllFields: false,
        },
    }
});