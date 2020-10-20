
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

Ext.define('Editor.model.UserConfig', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'userGuid', type: 'string'},
    {name: 'name', type: 'string'},
    {
      name: 'value', 
      convert: function(value, record) {
        if(value === "") {
            return null;
        }
        switch(record.data.type) {
          case 'boolean':
            if(Ext.isBoolean(value)) {
              return value;
            }
            return !/^(?:f(?:alse)?|no?|0+)$/i.test(value) && !!value;
          case 'integer':
            if(Ext.isNumber(value)) {
              return value;
            }
            return parseInt(value);
          case 'map':
          case 'list':
            if(Ext.isArray(value) || Ext.isObject(value)) {
              return value;
            }
            return Ext.JSON.decode(value);
          case 'string':
          case 'absolutepath':
          default: 
            return value;
        }
      },
      serialize: function(value, record) {
          if(value === null) {
              return "";
          }
          if(Ext.isBoolean(value)) {
              return value ? '1' : '0';
          }
          if(Ext.isNumber(value)) {
              return String(value);
          }
          if(Ext.isArray(value) || Ext.isObject(value)) {
              return Ext.JSON.encode(value);
          }
          return value;
      }
    }
  ],
  idProperty: 'name',
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'config',
    reader : {
      rootProperty: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      rootProperty: 'data',
      writeAllFields: false
    }
  }
});