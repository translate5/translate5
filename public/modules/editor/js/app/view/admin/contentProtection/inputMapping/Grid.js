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
Ext.define('Editor.view.admin.contentProtection.inputMapping.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.store.admin.contentProtection.InputMappingStore',
        'Editor.view.admin.contentProtection.inputMapping.CreateWindow',
        'Editor.view.admin.contentProtection.inputMapping.GridController',
        'Editor.view.admin.contentProtection.inputMapping.GridViewModel'
    ],
    alias: 'widget.InputMappingGrid',
    viewModel: {
        type: 'InputMappingGrid'
    },
    plugins: ['cellediting', 'gridfilters'],
    itemId: 'InputMappingGrid',
    reference: 'InputMappingGrid',
    controller: 'Editor.view.admin.contentProtection.inputMapping.GridController',
    stateful: true,
    stateId: 'adminInputMappingGrid',
    store: 'admin.contentProtection.InputMappingStore',
    bind: {
        title: '{l10n.contentProtection.mapping.input_title}'
    },
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    listeners: {
        beforeedit: 'onBeforeEdit',
        edit: 'onInputMappingEdit',
        activate: 'onGridActivate'
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
            langStore = Ext.getStore('admin.Languages'),
            langs = [],
            langFilter = [];

        var infoPanel = Ext.create('Ext.panel.Panel', {
            bind: {
                html: '{l10n.contentProtection.mapping.input_infobox}',
            },
            cls: 'infobox-panel'
        });

        config.title = me.title;
        config.dockedItems = [
            infoPanel,
            {
                xtype: 'toolbar',
                dock: 'top',
                enableOverflow: true,
                items: [
                    {
                        xtype: 'button',
                        glyph: 'f067@FontAwesome5FreeSolid',
                        bind: {
                            text: '{l10n.configuration.add}'
                        },
                        ui: 'default-toolbar-small',
                        width: 'auto',
                        handler: 'createInputMapping'
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
                        iconCls: 'x-fa fa-question-circle',
                        handler: () => window.open('https://confluence.translate5.net/display/TAD/Application+NumberFormat', '_blank')
                    },
                    {
                        xtype: 'tbspacer',
                        flex: 1.6
                    }
                ]
            }
        ];

        config.viewConfig = {
            getRowClass: function (record, rowIndex, rowParams, store) {
                // You can put your condition here
                if (!record.get('ruleEnabled')) {
                    return 'disabled-row';
                }

                // Return an empty string if no class should be applied
                return '';
            }
        };

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
                dataIndex: 'languageId',
                flex: 1,
                renderer: function (v) {
                    const lang = langStore.getById(v).get('label');
                    if (!langs.includes(lang)) {
                        langs.push(lang);
                        langFilter.push([v, lang]);
                    }

                    return lang;
                },
                bind: {
                    text: '{l10n.general.language}'
                },
                filter: {
                    type: 'list',
                    options: langFilter,
                    phpMode: false
                }
            },
            {
                xtype: 'gridcolumn',
                dataIndex: 'type',
                flex: 1,
                bind: {
                    text: '{l10n.general.type}'
                },
                filter: {
                    type: 'list',
                    phpMode: false
                }
            },
            {
                xtype: 'gridcolumn',
                dataIndex: 'name',
                flex: 3,
                bind: {
                    text: '{l10n.general.name}'
                },
                filter: {
                    type: 'string',
                    phpMode: false
                }
            },
            {
                xtype: 'gridcolumn',
                alias: 'priority',
                dataIndex: 'priority',
                stateId: 'priority',
                bind: {
                    text: '{l10n.general.priority}'
                },
                filter: {
                    type: 'number'
                },
                editor: {
                    field: {
                        xtype: 'numberfield',
                        allowBlank: false
                    }
                },
                renderer: 'editableCellRenderer',
                flex: 1
            },
            {
                xtype: 'actioncolumn',
                align: 'center',
                width: 100,
                menuDisabled: true,
                items: [
                    {
                        bind: {
                            tooltip: '{l10n.configuration.remove}'
                        },
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        handler: 'deleteInputMapping'
                    }
                ]
            }
        ];
        if (instanceConfig) {
            config = me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
})
;