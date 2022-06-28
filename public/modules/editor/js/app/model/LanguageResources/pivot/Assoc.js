
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

Ext.define('Editor.model.LanguageResources.pivot.Assoc', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int',
            persist: false,
            convert: function (val, record) {
                // One term collection can be listed and assigned for multiple projectTasks
                // To display unique row for each field in the import wizard, attach the taskGuid to the id field
                if(record.get('taskGuid') !== undefined){
                    return record.get('id')+record.get('taskGuid');
                }
                return record.get('id');
            }
        },
        {name: 'checked',type: 'boolean'},
        {name: 'taskGuid', type: 'string'},
        {name: 'languageResourceId', type: 'int'}
    ],

    idProperty: 'id',
    proxy : {
      type : 'rest',
      url: Editor.data.restpath+'languageresourcetaskpivotassoc',
      reader : {
        rootProperty: 'rows',
        type : 'json'
      }
    }
});