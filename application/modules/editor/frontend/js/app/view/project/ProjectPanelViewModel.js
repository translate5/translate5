
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
Ext.define('Editor.view.project.ProjectPanelViewModel', {
    extend : 'Ext.app.ViewModel',
    alias : 'viewmodel.projectPanel',

    data : {
        projectSelection : null,
        projectTaskSelection : null
    },
    
    stores : {
        // this store is defined here because the reference filter binding is
        // required
        projectTasks : {
            model : 'Editor.model.admin.Task',
            storeId : 'projectTasks',
            remoteSort : true,
            remoteFilter : true,
            pageSize : false,
            listeners : {
                beforeload : 'onProjectTaskBeforeLoad',
                load : 'onProjectTaskLoad'
            },
            setFilters : function (filters) {
                // the binding is also triggered when the value is empty. Ignore
                // the filtering with empty value
                if (filters && !filters.value) {
                    this.loadData([], false);
                    return;
                }
                this.superclass.superclass.setFilters.apply(this, [ filters ]);
            },
            filters : {
                property : 'projectId',
                operator : "eq",
                value : '{projectSelection.projectId}'
            }
        }
    }
});