
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

Ext.define('Editor.view.admin.task.WorkflowStepFilter', {
  extend: 'Ext.grid.filters.filter.List',
  alias: 'grid.filter.workflowStep',
  constructor: function(config) {
      var me = this,
          defaults = {
              dataIndex: 'qmId',
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