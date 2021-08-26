
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

Ext.define('Editor.view.admin.task.LogWindow', {
    extend: 'Editor.view.admin.log.Window',
    alias: 'widget.adminTaskLogWindow',
    requires: [
        'Editor.view.admin.task.LogGrid',
        'Editor.view.admin.log.Window'
    ],
    task: null,
    title: '#UT#Ereignisse zu Aufgabe "{0}"',
    initConfig: function(instanceConfig) {
        var me = this,
            config;
        me.task = me.initialConfig.actualTask;
        config = {
            title: Ext.String.format(me.title, me.task.get('taskName')),
            items : [{
                xtype: 'editorAdminTaskLogGrid'
            }] 
        };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    load: function(options) {
        this.down('editorAdminTaskLogGrid').load(this.task.getId());
    }
});
