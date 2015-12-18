
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
 * TreeStore für Editor.model.File
 * @class Editor.store.Files
 * @extends Ext.data.TreeStore
 */
Ext.define('Editor.store.ReferenceFiles', {
  requires: ['Editor.model.File'],
  extend : 'Ext.data.TreeStore',
  model: 'Editor.model.File',
  listeners: {
      //prevents the automatic loading of the treestore while rendering the treepanel
      beforeload: function(store, op) {
          return !!op.manualLoaded;
      }
  },
  root: {
    text: '#UT#Referenz-Dateien',
    id: 0,
    expanded: false
  },
  folderSort: false,
  autoSync: false,
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'referencefile'
  },
  constructor: function() {
      this.callParent(arguments);
      //enabling loading indexAction for id === 0
      Ext.override(this.getProxy(), {
          isValidId: function(id) {
              return id || id > 0;
          }
      });
  }
  /*,
  sorters: [{
      property: 'text',
      direction: 'ASC'
  }]*/
});