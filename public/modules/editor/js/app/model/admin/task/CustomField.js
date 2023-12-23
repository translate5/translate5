/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Represents a task custom field entry of the database. Model like
 * {
 *  "id": "20",
 *  //"customerId": null,                                          // Disabled so far
 *  "label": "Some custom field",
 *  "tooltip": "Some tooltip",
 *  "type": "picklist",                                            // Other possible values here are 'text', 'boolean' and 'textarea'
 *  "picklistData": "{option1: 'Option 1', option2: 'Option 2'}",  // Applicable for 'picklist' type only
 *  "regex": "^[a-zA-Z0-9]+$",                                     // Applicable for 'text' and 'textarea' types only
 *  "mode": "regular",                                             // Other possible values are 'required' and 'readonly'
 *  "placesToShow": "projectWizard,projectGrid,taskGrid",
 *  "position": "1"
 *  }
 */
Ext.define('Editor.model.admin.task.CustomField', {
    extend: 'Ext.data.Model',
    alias: 'model.taskCustomFieldModel',
    idProperty: 'id',
    proxy: {
        type: 'rest',
        url: Editor.data.restpath + 'taskcustomfield',
        reader: {
            rootProperty: 'rows',
            type : 'json'
        },
        writer: {
            encode: true,
            writeRecordId: false, // This line prevents sending the id
            rootProperty: 'data'
        }
    },
    fields: [{
        name: 'id',
        type: 'int',
    },/* {
        name: 'customerId',
        type: 'int',
        allowNull: true,
        reference: 'Editor.model.admin.Customer'
    },*/ {
        name: 'label',
        type: 'string',
    }, {
        name: 'tooltip',
        type: 'string',
    }, {
        name: 'type',
        type: 'string'
    }, {
        name: 'picklistData',
        type: 'string'
    }, {
        name: 'regex',
        type: 'string'
    }, {
        name: 'mode',
        type: 'string'
    }, {
        name: 'placesToShow',
        type: 'auto'
    }, {
        name: 'position',
        type: 'number'
    }],
    toUrl: function(){
        return Ext.util.History.getToken().replace(/taskCustomFields\/?\d*.*$/, 'taskCustomFields/' + this.id)
    }
});
