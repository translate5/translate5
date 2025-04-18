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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.model.LanguageResources..TaskAssoc
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.LanguageResources.TaskAssoc', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int',
            convert: function (val, record) {
                // One term collection can be listed and assigned for multiple projectTasks
                // To display unique row for each field in the import wizard, attach the taskGuid to the id field
                if(record.get('taskGuid') !== undefined){
                  return record.get('id')+record.get('taskGuid');
                }
                return record.get('id');
            }
        },
        {name: 'languageResourceId', type: 'int'},
        {name: 'name', type: 'string'},
        {name: 'taskGuid', type: 'string'},
        {name: 'sourceLang', type: 'string'},
        {name: 'targetLang', type: 'string'},
        {name: 'color', type: 'string'},
        {name: 'resourceId', type: 'string'},
        {name: 'serviceName', type: 'string'},
        {name: 'serviceType', type: 'string'},
        {name: 'checked', type: 'boolean'},
        {name: 'writable', type: 'boolean'}, //this is the flag if the associated LanguageResource is technically able to write data back
        {name: 'segmentsUpdateable', type: 'boolean'}, // this is the user choice if write back should be enabled for this assoc
        {name: 'penaltyGeneral', type: 'int'},
        {
            name: 'penaltySublang',
            type: 'int',
            convert: val => val === null ? Editor.data.segments.matchratemaxvalue : parseInt(val)
        },
        {name: 'isTaskTm', type: 'boolean'}
    ],
    idProperty: 'id',
    proxy: {
        type: 'rest',//POST for create, GET to get a entity, DELETE to delete an entity, PUT call to edit an entity
        url: Editor.data.restpath + 'languageresourcetaskassoc', //same as PHP controller name
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