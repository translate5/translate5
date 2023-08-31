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
Ext.define('Editor.view.admin.languageNumberFormat.NumberFormatGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.admin.languageNumberFormat.NumberFormatGridController',
        'Editor.store.admin.LanguageNumberFormatStore',
        'Editor.view.admin.languageNumberFormat.NumberFormatGridViewModel',
        'Editor.view.admin.languageNumberFormat.CreateNumberFormatWindow'
    ],
    alias: 'widget.NumberFormatGrid',
    viewModel:{
        type: 'NumberFormatGrid'
    },
    plugins: ['cellediting', 'gridfilters'],
    itemId: 'numberFormatGrid',
    reference: 'NumberFormatGrid',
    controller: 'Editor.view.admin.languageNumberFormat.NumberFormatGridController',
    stateful: true,
    stateId: 'adminNumberFormatGrid',
    store: 'admin.LanguageNumberFormatStore',
    bind: {
        title: '{l10n.languageNumberFormat.title}'
    },
    glyph: 'f292@FontAwesome5FreeSolid',
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    listeners: {
        beforeedit: 'onBeforeEdit',
        edit: 'onNumberFormatEdit',
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
            langStore = Ext.getStore('admin.Languages'),
            langFilter = [],
            typeFilter = [],
            rfcs = [];

        config.title = me.title;
        config.dockedItems = [{
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
                    handler: 'createNumberFormat'
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
                    handler: () => window.open('https://confluence.translate5.net/display/TAD/Application+NumberFormat', '_blank')
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
                dataIndex: 'languageId',
                flex: 1,
                renderer: function(v, meta, rec) {
                    const rfc = langStore.getRfcById(v);
                    if (rfc && !rfcs.includes(rfc)) {
                        rfcs.push(rfc);
                        langFilter.push([v, rfc]);

                        return rfc;
                    }

                    if (!rfcs.includes('empty')) {
                        rfcs.push('empty');
                        langFilter.push([v, '-']);
                    }

                    return rfc ? rfc : '-';
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
                renderer: function(v, meta, rec) {
                    if (!typeFilter.includes(v)) {
                        typeFilter.push(v);
                    }

                    return v;
                },
                bind: {
                    text: '{l10n.general.type}'
                },
                filter: {
                    type: 'list',
                    options: typeFilter,
                    phpMode: false
                }
            },
            {
                xtype: 'gridcolumn',
                alias: 'name',
                dataIndex: 'name',
                stateId: 'name',
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
                    text: '{l10n.general.name}'
                },
                filter: {
                    type: 'string'
                }
            },
            {
                xtype: 'gridcolumn',
                alias: 'regex',
                dataIndex: 'regex',
                stateId: 'regex',
                bind: {
                    text: '{l10n.general.regex}'
                },
                editor: {
                    field: {
                        xtype: 'textfield',
                        allowBlank: false,
                        bind: {
                            emptyText: '/d+/u'
                        }
                    }
                },
                renderer: 'editableCellRenderer',
                flex: 3
            },
            {
                xtype: 'gridcolumn',
                alias: 'format',
                dataIndex: 'format',
                stateId: 'format',
                bind: {
                    text: '{l10n.general.format}'
                },
                editor: {
                    field: {
                        xtype: 'textfield',
                        allowBlank: false,
                        bind: {
                            emptyText: 'Y-m-d|#.##0.###'
                        }
                    }
                },
                renderer: 'editableCellRenderer',
                flex: 3
            },
            {
                xtype: 'booleancolumn',
                alias: 'keepAsIs',
                dataIndex: 'keepAsIs',
                stateId: 'keepAsIs',
                filter: {
                    type: 'boolean'
                },
                editor: {
                    field: {
                        xtype: 'checkbox',
                        allowBlank: true
                    }
                },
                renderer: 'editableCellRenderer',
                flex: 1,
                bind: {
                    text: '{l10n.general.keepAsIs}'
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
                        isDisabled: function(view, rowIndex, colIndex, item, record) {
                            // Returns true if 'editable' is false (, null, or undefined)
                            return 'default' === record.get('name');
                        },
                        handler: 'deleteNumberFormat'
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