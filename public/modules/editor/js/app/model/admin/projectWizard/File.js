
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
 * @class Editor.model.admin.projectWizard.File
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.projectWizard.File', {
    extend: 'Ext.data.Model',

    statics: {
        TYPE_ERROR: 'error',
        TYPE_PIVOT: 'pivot',
        TYPE_WORKFILES: 'workfiles',
        TYPE_REFERENCE: 'reference',
    },

    fields: [{
        name: 'file'
    },{
        name: 'name'
    },{
        name: 'size'
    },{
        name: 'type'
    },{
        name: 'sourceLang',
        defaultValue:''
    },{
        name: 'targetLang',
        defaultValue:''
    },{
        name: 'error'
    },{
        name: 'bilingual',
        type: 'bool'
    }],

    /***
     * Get the file xtension from the file name
     * @returns {any|string}
     */
    getExtension:function (){
        return Editor.util.Util.getFileExtension(this.get('name'));
    }
});