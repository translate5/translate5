
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

Ext.define('Editor.model.admin.task.Log', {
    extend: 'Ext.data.Model',
    alias: 'model.taskLog',
    errorLevel: {
        "1": 'fatal',
        "2": 'error',
        "4": 'warn',
        "8": 'info',
        "16": 'debug',
        "32": 'trace',
    },
    fields: [
        {name: 'id', type: 'int', persist: false},
        {name: 'level', type: 'int'},
        {name: 'state', type: 'string'},
        {name: 'eventCode', type: 'string'},
        {name: 'domain', type: 'string'},
        {name: 'message', type: 'string'},
        {name: 'authUser', type: 'string'},
        {name: 'extra', convert: function(val) {
            if(Ext.isObject(val)){
                return val;
            }
            if(!val || val===""){
                return null;
            }
            return Ext.JSON.decode(val);
        }},
        {name: 'created', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT}
    ],

    idProperty: 'id',
    proxy : {
      type : 'rest',
      url: Editor.data.restpath+'event',
      reader : {
        rootProperty: 'rows',
        type : 'json'
      }
    },
    getLevelName: function(level) {
        if(!level) {
            level = this.get('level');
        }
        if(this.errorLevel[level]) {
            return this.errorLevel[level];
        }
        return '';
    }
});