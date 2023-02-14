
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

Ext.define('Editor.view.admin.task.ManageFiles.Main', {
    extend: 'Ext.tab.Panel',
    requires: [
        'Editor.view.admin.task.reimport.Reimport'
    ],
    alias: 'widget.taskManageFilesMain',
    itemId: 'taskManageFilesMain',
    activeTab:0,
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                bind:{
                    title:'{l10n.projectOverview.taskManagement.taskManageFiles.title}',
                    disabled:'{disabledDuringTaskImport}'
                },
                items: [{
                    xtype: 'adminTaskReimportReimport',
                    bind: {
                        title: '{l10n.projectOverview.taskManagement.taskReimport.title}'
                    }
                },{
                    bind: {
                        title: '{l10n.projectOverview.taskManagement.sourceFiles.title}'
                    },
                    html: 'Will be implemented with a following release.'
                },{
                    bind: {
                        title: '{l10n.projectOverview.taskManagement.referenceFiles.title}'
                    },
                    html: 'Will be implemented with a following release.'
                }]
            };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});