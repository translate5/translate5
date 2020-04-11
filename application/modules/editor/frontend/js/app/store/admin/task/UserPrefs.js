
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

/**
 * Store for Editor.model.admin.task.UserPref
 * @class Editor.store.admin.task.UserPrefs
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.admin.task.UserPrefs', {
  extend : 'Ext.data.Store',
  model: 'Editor.model.admin.task.UserPref',
  alias:'store.taskUserPrefs',
  remoteSort: false,
  autoLoad: false,
  pageSize: 20,
  getDefaultFor: function(workflow) {
      var idx = this.findBy(function(rec){
          return (rec.get('workflow') == workflow && rec.isDefault());
      });
      if(idx >= 0) {
          return this.getAt(idx);
      }
      return null;
  }
});
