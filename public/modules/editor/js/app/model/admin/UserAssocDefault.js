
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
 * @class Editor.model.admin.UserAssocDefault
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.UserAssocDefault', {
    extend: 'Ext.data.Model',

    fields: [
      {name: 'id', type: 'int',persist: false},
      {name: 'customerId', type: 'int'},
      {name: 'type', type: 'int'},
      {name: 'sourceLang', type: 'int'},
      {name: 'targetLang', type: 'int'},
      {name: 'userGuid', type: 'string'},
      {name: 'login', type: 'string', persist: false},
      {name: 'workflowStepName', type: 'string'},
      {name: 'workflow', type: 'string'},
      {name: 'segmentrange', type: 'string'},
      {name: 'deadlineDate'},
      {name: 'trackchangesShow', type: 'bool'},
      {name: 'trackchangesShowAll', type: 'bool'},
      {name: 'trackchangesAcceptReject', type: 'bool'},
      {name: 'coordinatorGroupId', type: 'int', persist: false},
      {name: 'isCoordinatorGroupJob', type: 'bool', persist: false},
    ],

    /***
     * Return unique string value from the record
     */
    getUnique: function (){
        return [
            this.get('customerId'),
            this.get('workflow'),
            this.get('workflowStepName'),
            this.get('sourceLang'),
            this.get('targetLang'),
            this.get('userGuid')
        ].join('-');
    },

    idProperty: 'id',
    proxy : {
      type : 'rest',//POST for create, GET to get a entity, DELETE to delete an entity, PUT call to edit an entity
      url: Editor.data.restpath+'userassocdefault', //same as PHP controller name
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