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
Ext.define('Editor.view.admin.contentProtection.contentRecognition.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.admin.contentProtection.contentRecognition.GridController',
        'Editor.store.admin.contentProtection.ContentRecognitionStore',
        'Editor.view.admin.contentProtection.contentRecognition.GridViewModel',
        'Editor.view.admin.contentProtection.contentRecognition.CreateWindow',
    ],
    alias: 'widget.ContentRecognitionGrid',
    viewModel:{
        type: 'ContentRecognitionGrid'
    },
    plugins: ['cellediting', 'gridfilters'],
    itemId: 'ContentRecognitionGrid',
    reference: 'ContentRecognitionGrid',
    controller: 'Editor.view.admin.contentProtection.contentRecognition.GridController',
    stateful: true,
    stateId: 'adminContentRecognitionGrid',
    store: 'admin.contentProtection.ContentRecognitionStore',
    bind: {
        title: '{l10n.contentProtection.contentRecognition.title}'
    },
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    listeners: {
        beforeedit: 'onBeforeEdit',
        edit: 'onContentRecognitionEdit',
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
            config = {};
        var infoPanel = Ext.create('Ext.panel.Panel', {
            bind: {
                html: '{l10n.contentProtection.contentRecognition.infobox}',
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
                        handler: 'createContentRecognition'
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
                    },
                    {
                        xtype: 'button',
                        iconCls: 'x-fa fa-filter',
                        bind: {
                            text: '{l10n.general.hideDefault}'
                        },
                        enableToggle: true,
                        toggleHandler: 'onToggleDefaultFilter'
                    }
                ]
            }
        ];
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
                flex: 2,
                bind: {
                    text: '{l10n.general.name}'
                },
                filter: {
                    type: 'string'
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
                        allowBlank: true,
                        bind: {
                            emptyText: '...'
                        }
                    }
                },
                renderer: 'htmlEncRendererAllowBr',
                flex: 2,
                bind: {
                    text: '{l10n.general.description}'
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
                alias: 'matchId',
                dataIndex: 'matchId',
                stateId: 'matchId',
                bind: {
                    text: '{l10n.contentProtection.contentRecognition.matchId}'
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
                flex: 1
            },
            {
                xtype: 'checkcolumn',
                dataIndex: 'keepAsIs',
                stateId: 'keepAsIs',
                filter: {
                    type: 'boolean'
                },
                listeners: {
                    beforecheckchange: 'onBeforeCheckChange'
                },
                flex: 1,
                bind: {
                    text: '{l10n.general.keepAsIs}'
                }
            },
            {
                xtype: 'checkcolumn',
                dataIndex: 'rowEnabled',
                stateId: 'rowEnabled',
                filter: {
                    type: 'boolean'
                },
                listeners: {
                    beforecheckchange: 'onBeforeCheckChange'
                },
                flex: 1,
                bind: {
                    text: '{l10n.general.enabled}'
                }
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

                            return record.get('isDefault');
                        },
                        handler: 'deleteContentRecognition'
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