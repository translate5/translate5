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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.MatchAnalysis.view.AnalysisPanel
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.plugins.MatchAnalysis.view.AnalysisPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.matchAnalysisPanel',

    requires: [
        'Editor.util.LanguageResources',
        'Editor.plugins.MatchAnalysis.view.AnalysisPanelViewModel',
        'Editor.plugins.MatchAnalysis.view.AnalysisPanelViewController'
    ],
    controller: 'matchAnalysisPanel',
    viewModel: {
        type: 'matchAnalysisPanel'
    },

    itemId: 'matchAnalysisPanel',

    strings: {
        noMatch: '#UT#Keine Treffer',
        matchCount: '#UT#Gesamtzahl der Wörter',
        tabTitle: "#UT#Analyse",
        exportAnalysis: '#UT#Export als Excel',
        exportAnalysisXML: '#UT#Export Trados-like XML',
        noAnalysis: '#UT#Start der Analyse im Tab “Sprachressourcen zuweisen“',
        languageResources: '#UT#Sprachressourcen',
        analysisDate: '#UT#Datum',
        languageResourceName: '#UT#Name',
        repetitions: '#UT#Wiederholungen:',
        totalSum: '#UT#Summe',
        internalFuzzy: "#UT#Interne Fuzzy verwendet",
        matchRate: "#UT#Match-Rate",
        edit100PercentMatchEnabledMessage: '#UT#Gesperrte Segmente sind in der Analyse enthalten',
        edit100PercentMatchDisabledMessage: '#UT#Gesperrte Segmente sind in der Analyse nicht enthalten'
    },

    listeners: {
        activate: 'onMatchAnalysisPanelActivate'
    },

    bind: {
        loading: '{isAnalysisRunning}',
        disabled: '{!enablePanel}'
    },

    initConfig: function (instanceConfig) {
        var me = this,
            columnRenderer = function (val, meta, record) {
                if (val) {
                    return val;
                }
                return 0;
            },

            config = {
                title: me.strings.tabTitle,
                scrollable: true,
                items: [{
                    xtype: 'grid',
                    itemId: 'matchAnalysisGrid',
                    cls: 'matchAnalysisGrid',
                    emptyText: me.strings.noAnalysis,
                    bind: {
                        store: '{analysisStore}',
                        loading: '{isAnalysisRunning}',
                        disabled: '{!enablePanel}'
                    },
                    features: [{
                        ftype: 'summary'
                    }],
                    columns: [{
                        xtype: 'gridcolumn',
                        text: me.strings.languageResourceName,
                        renderer: function (value, metaData, record) {
                            if (!value) {
                                return me.strings.repetitions;
                            }
                            return '<div style="float: left; width: 15px; height: 15px;margin-right:5px; border: 1px solid rgba(0, 0, 0, .2);background: #' + record.get('resourceColor') + ';"></div>' + value;
                        },
                        summaryRenderer: function (value, summaryData, dataIndex) {
                            return me.strings.totalSum;
                        },
                        dataIndex: 'resourceName',
                        flex: 5,
                        sortable: true
                    }, {
                        xtype: 'gridcolumn',
                        cls: 'info-icon',
                        flex: 2,
                        dataIndex: '104',
                        cellWrap: true,
                        text: "104%",
                        tooltip: Editor.util.LanguageResources.getMatchrateTooltip(104),
                        summaryType: 'sum',
                        renderer: columnRenderer,
                    }, {
                        xtype: 'gridcolumn',
                        cls: 'info-icon',
                        flex: 2,
                        dataIndex: '103',
                        cellWrap: true,
                        text: "103%",
                        tooltip: Editor.util.LanguageResources.getMatchrateTooltip(103),
                        summaryType: 'sum',
                        renderer: columnRenderer,
                    }, {
                        xtype: 'gridcolumn',
                        cls: 'info-icon',
                        flex: 2,
                        dataIndex: '102',
                        cellWrap: true,
                        text: "102%",
                        tooltip: Editor.util.LanguageResources.getMatchrateTooltip(102),
                        summaryType: 'sum',
                        renderer: columnRenderer
                    }, {
                        xtype: 'gridcolumn',
                        cls: 'info-icon',
                        flex: 2,
                        dataIndex: '101',
                        cellWrap: true,
                        text: "101%",
                        tooltip: Editor.util.LanguageResources.getMatchrateTooltip(101),
                        summaryType: 'sum',
                        renderer: columnRenderer
                    }, {
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '100',
                        cellWrap: true,
                        text: "100%",
                        summaryType: 'sum',
                        renderer: columnRenderer
                    }, {
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '99',
                        cellWrap: true,
                        text: "99%-90%",
                        summaryType: 'sum',
                        renderer: columnRenderer
                    }, {
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '89',
                        cellWrap: true,
                        text: "89%-80%",
                        summaryType: 'sum',
                        renderer: columnRenderer
                    }, {
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '79',
                        cellWrap: true,
                        text: "79%-70%",
                        summaryType: 'sum',
                        renderer: columnRenderer
                    }, {
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '69',
                        cellWrap: true,
                        text: "69%-60%",
                        summaryType: 'sum',
                        renderer: columnRenderer
                    }, {
                        xtype: 'gridcolumn',
                        flex: 2,
                        dataIndex: '59',
                        cellWrap: true,
                        text: "59%-51%",
                        summaryType: 'sum',
                        renderer: columnRenderer
                    }, {
                        xtype: 'gridcolumn',
                        flex: 3,
                        dataIndex: 'noMatch',
                        cellWrap: true,
                        text: me.strings.noMatch,
                        summaryType: 'sum',
                        renderer: columnRenderer
                    }, {
                        xtype: 'gridcolumn',
                        flex: 4,
                        dataIndex: 'wordCountTotal',
                        cellWrap: true,
                        summaryType: 'sum',
                        summaryRenderer: function (value, summaryData, dataIndex) {
                            return value;
                        },
                        text: me.strings.matchCount
                    }],
                    dockedItems: [{
                        xtype: 'toolbar',
                        dock: 'bottom',

                        items: [{
                            xtype: 'container',
                            layout: 'hbox',
                            padding: '5',
                            html: "¹ " + me.strings.noAnalysis,
                            dock: 'bottom',

                            items: [{
                                xtype: 'button',
                                flex: 2,
                                glyph: 'f1c3@FontAwesome5FreeSolid',
                                itemId: 'exportExcel',
                                text: me.strings.exportAnalysis,
                                margin: '50 0 0 0',

                                listeners: {
                                    click: {fn: 'exportAction', extraArg: 'excel'}
                                },
                            },
                                {
                                    xtype: 'button',
                                    glyph: 'f1c9@FontAwesome5FreeSolid',
                                    itemId: 'exportXml',
                                    text: me.strings.exportAnalysisXML,
                                    margin: '50 0 0 10',

                                    listeners: {
                                        click: {fn: 'exportAction', extraArg: 'xml'}
                                    },

                                }
                            ]
                        }
                        ]
                    },
                        {
                            xtype: 'toolbar',
                            dock
    :
        'top',
            layout
    :
        {
            type: 'vbox',
                align
        :
            'left'
        }
    ,
        items: [{
            xtype: 'displayfield',
            fieldLabel: me.strings.analysisDate,
            itemId: 'analysisDatum'
        }, {
            xtype: 'displayfield',
            fieldLabel: me.strings.internalFuzzy,
            itemId: 'internalFuzzy'
        }, {
            xtype: 'displayfield',
            bind: {
                value: '{getEdit100PercentMatchLableText}',
            },
            itemId: 'edit100PercentMatchLableText'
        }]

    }
    ]

    }]
    }
        ;

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});