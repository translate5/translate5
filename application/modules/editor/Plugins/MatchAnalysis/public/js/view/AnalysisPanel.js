
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
        matchCount: '#UT#Summe',
        tabTitle: "#UT#Datum der Analyse",
        exportAnalysis: '#UT#Export als Excel',
        exportAnalysisXML: '#UT#Export Trados-like XML',
        noAnalysis: '#UT#Es wurde bisher keine Analyse erstellt. Starten Sie eine Analyse im Tab “Sprachressourcen zuweisen“',
        languageResources: '#UT#Sprachressourcen',
        analysisDate: '#UT#Datum',
        languageResourceName: '#UT#Name',
        repetitions: '#UT#Wiederholungen:',
        totalSum: '#UT#Summe',
        internalFuzzy: "#UT#Interne Fuzzy verwendet",
        matchRate: "#UT#Match-Rate",
        edit100PercentMatchDisabledMessage: '#UT#Gesperrte 100%-Matches sind nicht Teil der Analyse.',
        wordBased:"#UT#Wortbasiert",
        characterBased:"#UT#Zeichenbasiert",
        basedOn:'#Basiert auf'
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
                    columns: me.getColumnConfig(),
                    dockedItems: [{
                        dock: 'top',
                        xtype: 'panel',
                        bodyPadding: 10,
                        itemId: 'infoPanel',
                        cls: 'matchAnalysisInfoBox',
                        tpl: [
                            '<tpl if="hasAnalysisData">',
                                '<span class="date"><span class="label">{strings.analysisDate}:</span> {created}</span>',
                                '<span class="fuzzy"><span class="label">{strings.internalFuzzy}:</span> {internalFuzzy}</span>',
                                '<tpl if="!editFullMatch">',
                                    '<span class="editFullMatch">{strings.edit100PercentMatchDisabledMessage}</span>',
                                '</tpl>',
                            '<tpl else>',
                                '{strings.noAnalysis}',
                            '</tpl>'
                        ],
                        items:[{
                            xtype: 'combo',
                            name:'unitType',
                            itemId:'unitType',
                            fieldLabel: me.strings.basedOn,
                            store: Ext.create('Ext.data.Store', {
                                fields: ['id', 'type'],
                                data : [
                                    {"id":"word", "type":me.strings.wordBased},
                                    {"id":"character", "type":me.strings.characterBased}
                                ]
                            }),
                            listeners: {
                                select: 'onUnitTypeSelect'
                            },
                            queryMode: 'local',
                            displayField: 'type',
                            value: Editor.data.plugins.MatchAnalysis.calculateBasedOn,
                            valueField: 'id'
                        }]
                    },{
                        xtype: 'container',
                        dock: 'bottom',
                        html: '<ul><li>'+[
                            Editor.util.LanguageResources.getMatchrateTooltip(101),
                            Editor.util.LanguageResources.getMatchrateTooltip(102),
                            Editor.util.LanguageResources.getMatchrateTooltip(103),
                            Editor.util.LanguageResources.getMatchrateTooltip(104)
                        ].join('</li><li>')+'</li></ul>'
                    },{
                        xtype: 'toolbar',
                        dock: 'bottom',
                        ui: 'footer',
                        items: [{
                            xtype: 'button',
                            glyph: 'f1c3@FontAwesome5FreeSolid',
                            itemId: 'exportExcel',
                            text: me.strings.exportAnalysis,
                            bind: {
                                disabled: '{!hasAnalysisData}'
                            },
                            listeners: {
                                click: {fn: 'exportAction', extraArg: 'excel'}
                            }
                        }, {
                            xtype: 'button',
                            glyph: 'f1c9@FontAwesome5FreeSolid',
                            itemId: 'exportXml',
                            text: me.strings.exportAnalysisXML,
                            bind: {
                                disabled: '{!hasAnalysisData}'
                            },
                            listeners: {
                                click: {fn: 'exportAction', extraArg: 'xml'}
                            }
                        }]
                    }]
                }]
            };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    getColumnConfig: function(customRanges) {
        var me = this,
            columnRenderer = function (val) {
                return val ? val : 0;
            },
            columns;

        customRanges = customRanges || [];

        columns = [{
            xtype: 'gridcolumn',
            text: me.strings.languageResourceName,
            renderer: function (value, metaData, record) {
                if (!value) {
                    return me.strings.repetitions;
                }
                return '<div style="float: left; width: 15px; height: 15px;margin-right:5px; border: 1px solid rgba(0, 0, 0, .2);background: #' + record.get('resourceColor') + ';"></div>' + value;
            },
            summaryRenderer: function () {
                return me.strings.totalSum;
            },
            dataIndex: 'resourceName',
            flex: 5,
            sortable: true
        }];

        Ext.Array.each(customRanges, function(item){
            var label,
                isNoMatch = (item.begin === 'noMatch');
            if(!item.begin && !item.end) {
                return;
            }
            if(item.end === item.begin) {
                label = item.end+'%';
            }
            else {
                label = item.end+'%-'+item.begin+'%';
            }
            columns.push({
                xtype: 'gridcolumn',
                flex: isNoMatch ? 3 : 2,
                dataIndex: item.begin,
                cellWrap: true,
                text: isNoMatch ? me.strings.noMatch : label,
                summaryType: 'sum',
                renderer: columnRenderer
            });
        });

        columns.push({
            xtype: 'gridcolumn',
            flex: 4,
            dataIndex: 'unitCountTotal',
            cellWrap: true,
            summaryType: 'sum',
            summaryRenderer: function (value) {
                return value;
            },
            text: me.strings.matchCount
        });

        return columns;
    }
});