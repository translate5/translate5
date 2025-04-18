/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.view.admin.task.batchSet.BatchSetWindow', {
    extend: 'Ext.window.Window',
    bodyPadding: 20,
    border: false,
    width: 800,
    height: 500,
    bodyStyle: {
        borderWidth: 0
    },
    title: Editor.data.l10n.batchSetWindow.title,

    initConfig: function (instanceConfig) {
        var me = this,
            l10n = Editor.data.l10n.batchSetWindow,
            config = {
                scrollable: true,
                defaults: {
                    xtype: 'container',
                    flex: 1,
                    margin: '0 5 0 0',
                    autoSize: true
                },
                items: this.getInnerItems(),
                dockedItems: [{
                    xtype: 'panel',
                    bind: {
                        html: l10n.infobox
                    },
                    cls: 'infobox-panel'
                }, {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    items: [{
                        xtype: 'button',
                        itemId: 'setForFiltered',
                        text: l10n.setForFiltered,
                        tooltip: l10n.setForFilteredTip
                    }, {
                        xtype: 'tbfill'
                    }, {
                        xtype: 'button',
                        itemId: 'setForSelected',
                        text: l10n.setForSelected,
                        tooltip: l10n.setForSelectedTip
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }

});