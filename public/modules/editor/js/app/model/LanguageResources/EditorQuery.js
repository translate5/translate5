
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
 * @class Editor.model.LanguageResources.EditorQuery
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.LanguageResources.EditorQuery', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'string'},
    {name: 'source', type: 'string'},
    {name: 'target', type: 'string'},
    {name: 'matchrate', type: 'integer'},
    {name: 'segmentId', type: 'string'},
    {name: 'state', type: 'string'},
    {name: 'metadata'},
    {name: 'tmConversionState', type: 'string'}
  ],
  SERVER_STATUS_LOADED:'loaded',
  SERVER_STATUS_LOADING: 'loading',
  SERVER_STATUS_NORESULT: 'noresult',
  SERVER_STATUS_SERVERERROR:'servererror',
  SERVER_STATUS_CLIENTTIMEOUT:'clienttimeout',
});