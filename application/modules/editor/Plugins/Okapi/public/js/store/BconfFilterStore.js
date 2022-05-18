
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
 * Store for the Filters inside a bconf
 * @class Editor.plugins.Okapi.store.BconfFilterStore
 * @extends Ext.data.Store
 */
Ext.define('Editor.plugins.Okapi.store.BconfFilterStore', {
  extend: 'Ext.data.Store',
  requires: ['Editor.plugins.Okapi.store.OkapiBconfFilterStore'], // for Okapi and Translate5 filters
  storeId: 'bconfFilterStore',
  alias: 'store.bconfFilterStore',
  model: 'Editor.plugins.Okapi.model.BconfFilterModel',
  autoLoad: false,
  pageSize: 0,
  idProperty: 'id',
  proxy: {
    type: 'rest',
    url: Editor.data.restpath + 'plugins_okapi_bconffilter',
    reader: {
      rootProperty: 'rows',
      type: 'json'
    },
    writer: {
      encode: true,
      rootProperty: 'data',
      writeAllFields: false
    }
  },
  data:[{
    name:'okapi',
    extensions:'doc',
    description:'new filter'
  }]
});