/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
/**
 * Lists and manages the available pricing presets to choose from when creating a task
 */
Ext.define('Editor.view.admin.token.TokenGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.admin.token.TokenGridController',
        'Editor.store.admin.TokenStore',
        'Editor.view.admin.token.TokenGridViewModel',
        'Editor.view.admin.token.CreateTokenWindow'
    ],
    alias: 'widget.TokenGrid',
    viewModel:{
        type: 'TokenGrid'
    },
    plugins: ['cellediting', 'gridfilters'],
    itemId: 'tokenGrid',
    reference: 'TokenGrid',
    controller: 'Editor.view.admin.token.TokenGridController',
    stateful: true,
    stateId: 'adminTokenGrid',
    store: 'admin.TokenStore',
    bind: {
        title: '{l10n.token.title}'
    },
    glyph: 'f084@FontAwesome5FreeSolid',
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    listeners: {
        beforeedit: 'onBeforeEdit',
        edit: 'onTokenEdit',
        activate:'onGridActivate'
    },
    viewConfig: {
        enableTextSelection: true
    },
    layout: {
        type: 'fit'
    },

    initConfig: function (instanceConfig) {
        var me = this,
            config = {},
            userStore = Ext.StoreMgr.get('admin.Users'),
            userFilter = [];

        config.title = me.title;
        config.dockedItems = [{
            xtype: 'toolbar',
            dock: 'top',
            enableOverflow: true,
            items: [
                {
                    xtype: 'textfield',
                    width: 300,
                    minWidth: 100,
                    bind: {
                        emptyText: '{l10n.general.useForSearch}'
                    },
                    triggers: {
                        clear: {
                            cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                            handler: function(field){
                                field.setValue(null);
                                field.focus();
                            },
                            hidden: true
                        }
                    },
                    listeners: {
                        change: 'filterByKeyword',
                        buffer: 150
                    }
                },
                {
                    xtype: 'button',
                    glyph: 'f067@FontAwesome5FreeSolid',
                    bind: {
                        text: '{l10n.configuration.add}'
                    },
                    ui: 'default-toolbar-small',
                    width: 'auto',
                    handler: 'createToken'
                },
                {
                    xtype: 'button',
                    iconCls: 'x-fa fa-undo',
                    bind: {
                        text: '{l10n.configuration.reload}'
                    },
                    handler: 'onRefreshClick'
                },
                {
                    xtype: 'button',
                    iconCls: 'x-fa fa-info-circle',
                    handler: () => window.open('https://confluence.translate5.net/display/TAD/Application+Token', '_blank')
                },
                {
                    xtype: 'tbspacer',
                    flex: 1.6
                }
            ]
        }];
        config.columns = [
            {
                xtype: 'gridcolumn',
                dataIndex: 'id',
                text: 'Id',
                filter: {
                    type: 'number'
                }
            },
            {
                xtype: 'gridcolumn',
                dataIndex: 'userGuid',
                renderer: function(v, meta, rec) {
                    var idx = userStore.find('userGuid', v),
                        user = userStore.getAt(idx);
                    if (user) {
                        const username = Editor.model.admin.User.getLongUserName(user);
                        userFilter.push([user.getUserGuid(), username]);

                        return username;
                    }

                    userFilter.push([v, v]);

                    return v;
                },
                flex: 2,
                bind: {
                    text: '{l10n.user.user}'
                },
                filter: {
                    type: 'list',
                    options: userFilter,
                    phpMode: false
                }
            },
            {
                xtype: 'gridcolumn',
                alias: 'description',
                dataIndex: 'description',
                stateId: 'description',
                editor: {
                    field: {
                        xtype: 'textfield',
                        allowBlank: false,
                        bind: {
                            emptyText: '...'
                        }
                    }
                },
                renderer: 'editableCellRenderer',
                flex: 3,
                bind: {
                    text: '{l10n.general.description}'
                },
                filter: {
                    type: 'string'
                }
            },
            {
                xtype: 'datecolumn',
                dataIndex: 'created',
                bind: {
                    text: '{l10n.general.creation_date}'
                },
                flex: 1,
                format: Ext.grid.column.Date.prototype.format + ' H:i:s',
                filter: {
                    type: 'date',
                    dateFormat: Editor.DATE_ISO_FORMAT
                }
            },
            {
                xtype: 'datecolumn',
                dataIndex: 'expires',
                bind: {
                    text: '{l10n.token.expiration_date}'
                },
                flex: 1,
                format: Ext.grid.column.Date.prototype.format,
                filter: {
                    type: 'date',
                    dateFormat: Editor.DATE_ISO_FORMAT
                },
                editor: {
                    completeOnEnter: true,
                    field: {
                        xtype: 'datetimefield',
                        editable: true,
                        allowBlank: true,
                        minValue: new Date(),
                    }
                }

            },
            {
                xtype: 'actioncolumn',
                stateId: 'tokenActionColumn',
                align: 'center',
                width: 100,
                menuDisabled: true,
                items: [
                    {
                        bind: {
                            tooltip: '{l10n.configuration.remove}'
                        },
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        isDisabled: false,
                        handler: 'deleteToken'
                    }
                ]
            }
        ];
        if (instanceConfig) {
            config=me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});