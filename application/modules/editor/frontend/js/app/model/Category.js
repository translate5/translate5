
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.model.Tag
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.Category', {
  extend: 'Ext.data.Model',
  alias: 'model.category',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'origin', type: 'string'},
    {name: 'label', type: 'string'},
    {name: 'originalCategoryId', type: 'string'},
    {name: 'specificData', type: 'string'},
    {name: 'customLabel', type: 'string', persist: false, convert: function(v, rec) {
        return rec.get('label') + ' (' + rec.get('originalCategoryId') + ')';
    }},
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'category',
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