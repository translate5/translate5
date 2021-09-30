
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

Ext.define('Editor.view.admin.log.Window', {
    extend: 'Ext.window.Window',
    alias: 'widget.adminLogWindow',
    requires: ['Editor.view.admin.log.WindowViewController'],
    controller: 'editorlogWindowViewController',
    strings: {
        close: '#UT#Fenster schließen',
        details: '#UT#Detailansicht',
        btnBack: '#UT#Zurück zu den Ereignissen'
    },
    closeAction: 'destroy',
    layout: 'fit',
    modal: true,
    initConfig: function(instanceConfig) {
        var me = this,
            config;
        config = {
            height: Math.min(800, parseInt(Ext.getBody().getViewSize().height * 0.8)),
            width: 1000,
            dockedItems: [
                {
                    xtype: 'toolbar',
                    itemId: 'mainToolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    items: [
                        {
                            xtype: 'tbfill'
                        },
                        {
                            xtype: 'button',
                            itemId: 'closeBtn',
                            glyph: 'f00d@FontAwesome5FreeSolid',
                            text: me.strings.close
                        }
                    ]
                }
            ]
        };
        
        config.items = Ext.Array.merge(config.items, instanceConfig.items);
        delete instanceConfig.items;
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
