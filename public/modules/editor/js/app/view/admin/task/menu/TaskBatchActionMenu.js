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

Ext.define('Editor.view.admin.task.menu.TaskBatchActionMenu', {
    extend: 'Ext.menu.Menu',
    itemId: 'taskBatchActionMenu',
    alias: 'widget.taskBatchActionMenu',
    constructor: function (instanceConfig) {
        var me = this,
            config = {
                autoDestroy: true,
                //Info: all items should be hidden by default, with this we reduce the "blinking" component behaviour
                items: [{
                    itemId: 'batch-set-btn',
                    bind: {
                        text: '{l10n.taskBatchActionMenu.batchSetProperties}',
                        tooltip: '{l10n.taskBatchActionMenu.batchSetPropertiesTip}'
                    }
                },{
                    itemId: 'batch-export-btn',
                    bind: {
                        text: '{l10n.taskBatchActionMenu.batchExport}',
                        tooltip: '{l10n.taskBatchActionMenu.batchExportTip}'
                    }
                }]
            };

        //workaround for fire-event (the component is not created yet so fake the event)
        me.hasListeners = {};
        me.hasListeners.itemsinitialized = true;

        //fire the event, so another action columns can be added from outside
        me.fireEvent('itemsinitialized', config.items);

        config.items = Ext.Array.sort(config.items, function (a, b) {
            return a.sortIndex - b.sortIndex;
        });

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }

        me.callParent([Ext.apply({
            items: config.items
        }, config)]);
    }
});