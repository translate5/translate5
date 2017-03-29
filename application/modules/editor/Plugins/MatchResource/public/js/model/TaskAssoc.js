
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.MatchResource.model.TaskAssoc
 * @extends Ext.data.Model
 */
Ext.define('Editor.plugins.MatchResource.model.TaskAssoc', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'name', type: 'string'},
    {name: 'sourceLang', type: 'string'},
    {name: 'targetLang', type: 'string'},
    {name: 'color', type: 'string'},
    {name: 'resourced', type: 'string'},
    {name: 'serviceName', type: 'string'},
    {name: 'serviceType', type: 'string'},
    {name: 'checked', type: 'boolean'},
    {name: 'writable', type: 'boolean'}, //this is the flag if the associated TMMT is technically able to write data back
    {name: 'segmentsUpdateable', type: 'boolean'} // this is the user choice if write back should be enabled for this assoc
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest',//POST for create, GET to get a entity, DELETE to delete an entity, PUT call to edit an entity 
    url: Editor.data.restpath+'plugins_matchresource_taskassoc', //same as PHP controller name
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