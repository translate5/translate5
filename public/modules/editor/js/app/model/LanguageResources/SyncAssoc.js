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
 * @class Editor.model.LanguageResources.SyncAssoc
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.LanguageResources.SyncAssoc', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id', type: 'int'},
        {name: 'sourceLanguageResourceId', type: 'int'},
        {name: 'sourceLanguageResourceName', type: 'string'},
        {name: 'targetLanguageResourceId', type: 'int'},
        {name: 'targetLanguageResourceName', type: 'string'},
    ],
    idProperty: 'id',
    proxy: {
        type: 'rest',
        url: Editor.data.restpath + 'languageresourcesyncassoc',
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