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

/**
 * @class Editor.model.admin.TaskUserAssoc
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.TaskUserAssoc', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id', type: 'int'},
        {name: 'entityVersion', type: 'int'}, //does not exist in DB, for versioning only
        {name: 'taskGuid', type: 'string'},
        {name: 'sourceLang',persist: false}, // associated task source language
        {name: 'targetLang',persist: false}, // associated task target language
        {name: 'userGuid', type: 'string'},
        {name: 'login', type: 'string', persist: false},
        {name: 'surName', type: 'string', persist: false},
        {name: 'firstName', type: 'string', persist: false},
        {
            name: 'longUserName', type: 'string', persist: false, convert: function (v, rec) {
                return Editor.model.admin.User.getLongUserName(rec);
            }
        },
        {name: 'state', type: 'string'},
        {name: 'role', type: 'string'},
        {name: 'workflow', type: 'string',critical: true},
        {name: 'workflowStepName', type: 'string'},
        {name: 'segmentrange', type: 'string'},
        {name: 'deletable', type: 'boolean'},
        {name: 'editable', type: 'boolean'},
        {name: 'assignmentDate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
        {name: 'finishedDate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT},
        {name: 'deadlineDate', type: 'date', dateFormat: Editor.DATE_ISO_FORMAT}
    ],
    validators: {
        taskGuid: 'presence',
        userGuid: 'presence'//,
        //FIXME make me dynamic? state: {type: 'inclusion', list: Ext.Object.getKeys(Editor.data.app.utStates)},
        //FIXME make me dynamic? role: {type: 'inclusion', list: Ext.Object.getKeys(Editor.data.app.utRoles)}
    },

    /***
     * Return unique string value from the record
     */
    getUnique: function () {
        return [
            this.get('workflow'),
            this.get('workflowStepName'),
            this.get('taskGuid'),
            this.get('userGuid')
        ].join('-');
    },

    idProperty: 'id',
    proxy: {
        type: 'rest',//POST for create, GET to get a entity, DELETE to delete an entity, PUT call to edit an entity
        url: Editor.data.restpath + 'taskuserassoc', //same as PHP controller name
        reader: {
            rootProperty: 'rows',
            type: 'json'
        },
        writer: {
            encode: true,
            rootProperty: 'data',
            writeAllFields: false
        }
    }
});