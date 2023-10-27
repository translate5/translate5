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
Ext.define('Editor.view.admin.numberProtection.outputMapping.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.store.admin.numberProtection.OutputMappingStore',
        'Editor.view.admin.numberProtection.outputMapping.CreateWindow',
        'Editor.view.admin.numberProtection.outputMapping.GridController',
        'Editor.view.admin.numberProtection.outputMapping.GridViewModel'
    ],
    alias: 'widget.OutputMappingGrid',
    viewModel: {
        type: 'OutputMappingGrid'
    },
    plugins: ['cellediting', 'gridfilters'],
    itemId: 'OutputMappingGrid',
    reference: 'OutputMappingGrid',
    controller: 'Editor.view.admin.numberProtection.outputMapping.GridController',
    stateful: true,
    stateId: 'adminOutputMappingGrid',
    store: 'admin.numberProtection.OutputMappingStore',
    bind: {
        title: '{l10n.numberProtection.mapping.output_title}'
    },
    /** @property {string} routePrefix Used to setup routes on different view instances */
    routePrefix: '',
    listeners: {
        edit: 'onOutputMappingEdit',
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
            html: '<h2>List of active output rules</h2>' +
                'Add rules here per target language to override default output formats for your active input rules.' +
                '<br/>You can provide your own formats for desired <b>type-ruleName-targetLang</b> combination here.' +
                '<br/>To find format string rules you can look at:' +
                '<br/><a href="https://icu4c-demos.unicode.org/icu-bin/locexp?d_=ru#region" target="_blank" rel="noopener noreferrer">1. Locale related format presets</a>' +
                '<br/><a href="https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax" target="_blank" rel="noopener noreferrer">2. Dates syntax</a>' +
                '<br/><a href="https://unicode-org.github.io/icu-docs/apidoc/released/icu4j/com/ibm/icu/text/DecimalFormat.html" target="_blank" rel="noopener noreferrer">3. Number syntax</a>' +
                '<br/><a href="icu4c-demos.unicode.org" target="_blank" rel="noopener noreferrer">4. ICU Demonstration</a>',
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
                    handler: 'createOutputMapping'
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
                flex: 2,
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
                flex: 2
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
                        handler: 'deleteOutputMapping'
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