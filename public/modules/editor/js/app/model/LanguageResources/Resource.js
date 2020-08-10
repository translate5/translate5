
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.model.LanguageResources.Resource
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.LanguageResources.Resource', {
  extend: 'Ext.data.Model',

  statics: {
    //name of the sdl language cloud service
    SDL_SERVICE_NAME: 'SDLLanguageCloud',
    TERMCOLLECTION_SERVICE_NAME:'TermCollection',
    OPENTM2_SERVICE_NAME:'OpenTM2'
  },

  fields: [
    {name: 'id', type: 'string'},
    {name: 'name', type: 'string'},
    {name: 'filebased', type: 'boolean'},
    {name: 'serviceType', type: 'string'},
    {name: 'serviceName', type: 'string'},
    {name: 'defaultColor', type: 'string'}
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest',//POST for create, GET to get a entity, DELETE to delete an entity, PUT call to edit an entity 
    url: Editor.data.restpath+'languageresourceresource', //same as PHP controller name
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