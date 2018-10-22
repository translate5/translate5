
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

Ext.define('Editor.view.admin.task.LogGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.editorAdminTaskLogGrid',
    store: 'admin.task.Logs',

    strings: {
        defaultEntry: '#UT# Standard Eintrag',
        reload: '#UT# Aktualisieren',
        level: '#UT# Typ',
        colUsername: '#UT# Benutzer',
        eventCode: '#UT# Fehlercode',
        domain: '#UT# Bereich',
        message: '#UT# Fehler',
        created: '#UT# Zeitpunkt'
    },
    viewConfig: {
        loadMask: false
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config;
        config = {
            columns: [
                {
                    xtype: 'datecolumn',
                    dataIndex: 'created',
                    text: me.strings.created,
                    width: 100,
                    filter: {
                        type: 'date',
                        dateFormat: Editor.DATE_ISO_FORMAT
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'level',
                    text: me.strings.level,
                    width: 80,
                    renderer: function(v, meta, rec) {
                        return v;
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'eventCode',
                    width: 80,
                    text: me.strings.eventCode
                },
                {
                    flex: 1,
                    xtype: 'gridcolumn',
                    dataIndex: 'message',
                    text: me.strings.message
                },
                {
                    width: 100,
                    xtype: 'gridcolumn',
                    dataIndex: 'authUser',
                    text: me.strings.colUsername
                },
                {
                    width: 100,
                    xtype: 'gridcolumn',
                    dataIndex: 'domain',
                    text: me.strings.domain
                }
            ],
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'top',
                items: [{
                    xtype: 'button',
                    itemId: 'userPrefReload',
                    iconCls: 'ico-refresh',
                    text: me.strings.reload
                }]
            }]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
