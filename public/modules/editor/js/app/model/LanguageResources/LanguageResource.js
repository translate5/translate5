
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
 * @class Editor.model.LanguageResources.LanguageResource
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.LanguageResources.LanguageResource', {
  extend: 'Ext.data.Model',
  STATUS_LOADING: 'loading',
  STATUS_NOTCHECKED: 'notchecked',
  STATUS_ERROR: 'error',
  STATUS_AVAILABLE: 'available',
  STATUS_UNKNOWN: 'unknown',
  STATUS_NOCONNECTION: 'noconnection',
  STATUS_IMPORT: 'import',
  STATUS_NOTLOADED: 'notloaded',
  STATUS_NOVALIDLICENSE: 'novalidlicense',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'entityVersion', type: 'integer', critical: true},
    {name: 'name', type: 'string'},
    {name: 'color', type: 'string'},
    {name: 'resourceId', type: 'string'},
    {name: 'customerUseAsDefaultIds'},
    {name: 'customerWriteAsDefaultIds'},
    {name: 'customerIds'},
    {name: 'status', type: 'string', persist: false},
    {name: 'statusInfo', type: 'string', persist: false},
    {name: 'serviceName', type: 'string'},
    {name: 'serviceType', type: 'string'},
    {name: 'searchable', type: 'boolean'}
  ],

  /***
   * Is the current record Tm
   * @returns {boolean}
   */
  isTm:function (){
    return this.get('resourceType') === Editor.util.LanguageResources.resourceType.TM;
  },

  /***
   * Is the current record Mt
   * @returns {boolean}
   */
  isMt:function (){
    return this.get('resourceType') === Editor.util.LanguageResources.resourceType.MT;
  },

  /***
   * Is the current record Term collection
   * @returns {boolean}
   */
  isTc:function (){
    return this.get('resourceType') === Editor.util.LanguageResources.resourceType.TERM_COLLECTION;
  },

  idProperty: 'id',
  proxy : {
    type : 'rest',//POST for create, GET to get a entity, DELETE to delete an entity, PUT call to edit an entity 
    url: Editor.data.restpath+'languageresourceinstance', //same as PHP controller name
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