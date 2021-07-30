
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

Ext.define('Editor.view.admin.task.WorkflowStepFilter', {
  extend: 'Ext.grid.filters.filter.List',
  alias: 'grid.filter.workflowStep',
  constructor: function(config) {
      var me = this,
          defaults = {
              dataIndex: 'workflowStep',
              options: [],
              labelField: 'label',
              phpMode: false
          },
          col = Ext.ComponentQuery.query('#segmentgrid #workflowStepColumn');
      
      if(col.length > 0) {
          prefix = col[0].text;
      }
      Ext.Object.each(Editor.data.task.getWorkflowMetaData().steps, function(key, value){
          defaults.options.push({id:key, label:value});
      });
      
      config = Ext.apply({},config,defaults);
      me.callParent([config]);
    }
});