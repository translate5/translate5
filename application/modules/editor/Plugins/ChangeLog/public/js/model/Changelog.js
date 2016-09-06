/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * @class Editor.plugins.MatchResource.model.EditorQuery
 * @extends Ext.data.Model
 */
Ext.define('Editor.plugins.ChangeLog.model.Changelog', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'string'},
    {name: 'dateOfChange', type: "date", dateFormat: 'd/m/Y' },
    {name: 'jiraNumber', type: 'string'},
    {name: 'title', type: 'string'},
    {name: 'description', type: 'string'},
    {name: 'userGroup', type: 'integer'}
  ],
	idProperty: 'id',
	proxy : {
	  type : 'rest',//POST for create, GET to get a entity, DELETE to delete an entity, PUT call to edit an entity 
	  url: Editor.data.restpath+'plugins_changelog_changelog', //same as PHP controller name
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