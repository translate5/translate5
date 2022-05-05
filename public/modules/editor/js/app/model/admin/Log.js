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

Ext.define('Editor.model.admin.Log', {
    extend: 'Ext.data.Model',
    alias: 'model.log',
    fields: [
        {name: 'id', type: 'int', persist: false},
        {name: 'created', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
        {name: 'last', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
        {name: 'duplicates', type: 'int'},
        {name: 'level', type: 'int'},
        {name: 'domain', type: 'string'},
        {name: 'worker', type: 'string'},
        {name: 'eventCode', type: 'string'},
        {name: 'message', type: 'string'},
        {name: 'appVersion', type: 'string'},
        {name: 'file', type: 'string'},
        {name: 'line', type: 'string'},
        {name: 'trace', type: 'string'},
        {name: 'httpHost', type: 'string'},
        {name: 'url', type: 'string'},
        {name: 'method', type: 'string'},
        {name: 'userLogin', type: 'string'},
        {
            name: 'extra',
            type: 'string',
            convert: function(val) {
                if(!val){
                    return '';
                }
                if(Ext.isString(val)){
                    try {
                        val = Ext.JSON.decode(val);
                    }
                    catch(e) {
                        console.log(e);
                        return 'jsonError: JSON could not be decoded, probably extra data is to long!';
                    }
                }
                return JSON.stringify(val, undefined, 2);
            }
        }
    ],
    idProperty: 'id',
    proxy : {
      type : 'rest',
      url: Editor.data.restpath+'log',
      reader : {
        rootProperty: 'rows',
        type : 'json'
      }
    }
});