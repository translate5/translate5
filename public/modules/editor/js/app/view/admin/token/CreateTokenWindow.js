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

Ext.define('Editor.view.admin.token.CreateTokenWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.adminCreateTokenWindow',
    itemId: 'adminCreateTokenWindow',
    cls: 'adminCreateTokenWindow',
    requires: [
        'Editor.view.admin.token.CreateTokenWindowViewController'
    ],
    controller: 'adminCreateTokenWindowViewController',
    modal: true,
    layout: 'fit',
    initComponent: function () {
        this.callParent(arguments);
    },
    initConfig: function (instanceConfig) {
        var me = this,
            config = {};

        config = {
            title: Editor.data.l10n.general.create_title,
            layout: {
                type: 'fit'
            },
            items: [
                {
                    xtype: 'form',
                    padding: 5,
                    ui: 'default-frame',
                    scrollable: 'vertical',
                    defaults: {
                        labelWidth: 160,
                        width: 480,
                        anchor: '100%'
                    },
                    items: [
                        {
                            xtype: 'textareafield',
                            name: 'description',
                            grow: true,
                            allowBlank: false,
                            bind: {
                                fieldLabel: '{l10n.general.description}'
                            }
                        },
                        {
                            xtype: 'combo',
                            itemId: 'userId',
                            name: 'userId',
                            allowBlank: true,
                            typeAhead: true,
                            anyMatch: true,
                            forceSelection: true,
                            displayField: 'longUserName',
                            valueField: 'id',
                            store: {
                                type: 'store',
                                model: 'Editor.model.admin.User',
                                autoLoad: true,
                                remoteFilter: false,
                                pageSize: 0
                            },
                            queryMode: 'local',
                            bind: {
                                fieldLabel: '{l10n.user.user}'
                            }
                        },
                        {
                            xtype: 'datefield',
                            name: 'expires',
                            allowBlank: true,
                            minValue: new Date(),
                            bind: {
                                fieldLabel: '{l10n.token.expiration_date}'
                            }
                        },
                    ]
                }
            ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    layout: {
                        type: 'hbox',
                        pack: 'start'
                    },
                    items: [
                        {
                            xtype: 'tbfill'
                        },
                        {
                            xtype: 'button',
                            glyph: 'f234@FontAwesome5FreeSolid',
                            itemId: 'create-btn',
                            bind: {
                                text: '{l10n.configuration.add}'
                            }
                        },
                        {
                            xtype: 'button',
                            glyph: 'f00d@FontAwesome5FreeSolid',
                            itemId: 'cancel-btn',
                            bind: {
                                text: '{l10n.configuration.cancel}'
                            }
                        }
                    ]
                }
            ]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});