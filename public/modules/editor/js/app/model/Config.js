
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

Ext.define('Editor.model.Config', {
  extend: 'Ext.data.Model',
  CONFIG_LEVEL_SYSTEM : 1,
  CONFIG_LEVEL_INSTANCE : 2,
  CONFIG_LEVEL_CLIENT : 4,
  CONFIG_LEVEL_USER : 32,
  fields: [
    {name: 'id', type: 'int'},
    {name: 'isReadOnly', type: 'bool',defaultValue: false},//TODO: readonly validator
    {name: 'name', type: 'string'},
    {name: 'guiName', type: 'string'},
    {name: 'typeClassGui', type: 'string', persist: false},
    {
      name: 'value',
      critical: true,
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
          case 'float':
            if(Ext.isNumber(value)) {
              return value;
            }
            return Ext.Number.from(value,0);
          case 'map':
          case 'list':
          case 'regexlist':
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
    },{
        name:'defaults',
        type:'string',
        convert:function(value,record){
            if(value == undefined ){
                return "";
            }
            if(value === "" || value == null) {
                //this is only default for list and string (it is used for store bind value -> it must be array)
                return [];
            }
            //the values are comma separated
            return value.split(',');
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