
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
 * @class Editor.plugins.MatchAnalysis.view.AnalysisGrid
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.plugins.MatchAnalysis.view.AnalysisGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.matchAnalysisGrid',

    requires: [
        'Editor.plugins.MatchAnalysis.view.AnalysisGridViewController',
        'Editor.plugins.MatchAnalysis.view.AnalysisGridViewModel'
    ],

    controller: 'matchAnalysisGrid',
    viewModel: {
        type: 'matchAnalysisGrid'
    },

    itemId: 'matchAnalysisGrid',
    cls: 'matchAnalysisGrid',

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
        internalFuzzy: "#UT#Interne Fuzzy verwendet",
        matchRate: "#UT#Match-Rate",
        edit100PercentMatchDisabledMessage: '#UT#Gesperrte 100%-Matches sind nicht Teil der Analyse.',
        wordBased:"#UT#Wortbasiert",
        characterBased:"#UT#Zeichenbasiert",
        basedOn:'#Basiert auf',
        fromImportedFile:'#UT#Aus importierter Datei',
        hasErrors: '#UT#Bei der Analyse sind {0} Fehler in der Kommunikation mit den Sprachressourcen aufgetreten. <br />Die Analyse kann daher unvollständig sein. Bitte prüfen Sie die Ereignisse der Aufgabe und das System Log und erstellen ggf. die Analyse erneut.'
    },

    bind: {
        loading: '{isAnalysisRunning}',
        disabled: '{!enablePanel}'
    },

    initConfig: function (instanceConfig) {
        var me = this,
            config = {
                emptyText: me.strings.noAnalysis,
                store: 'Editor.plugins.MatchAnalysis.store.MatchAnalysis',
                bind: {
                    loading: '{isAnalysisRunning}',
                    disabled: '{!enablePanel}'
                },
                columns: me.getColumnConfig(),
                dockedItems: [{
                    dock: 'top',
                    xtype: 'panel',
                    border: 0,
                    bodyPadding: 10,
                    itemId: 'infoPanel',
                    cls: 'matchAnalysisInfoBox',
                    height: 'auto',
                    tpl: [
                        '<tpl if="hasAnalysisData">',
                            '<span class="date"><span class="label">{strings.analysisDate}:</span> {created}</span>',
                            '<span class="fuzzy"><span class="label">{strings.internalFuzzy}:</span> {internalFuzzy}</span>',
                            '<tpl if="!editFullMatch">',
                                '<span class="editFullMatch">{strings.edit100PercentMatchDisabledMessage}</span>',
                            '</tpl>',
                            '<tpl if="this.hasErrors(errorCount)">',
                                '<span class="errors">{[this.getErrorMsg(values.strings.hasErrors, values.errorCount)]}</span>',
                            '</tpl>',
                        '<tpl else>',
                            '{strings.noAnalysis}',
                        '</tpl>',
                        {
                            hasErrors: function(errorCount) {
                                return errorCount > 0;
                            },
                            getErrorMsg: function(msg, errorCount) {
                                return Ext.String.format(msg, errorCount);
                            }
                        }
                    ],
                    bbar:[{
                        xtype: 'combo',
                        name:'unitType',
                        itemId:'unitType',
                        disabled: !Editor.app.authenticatedUser.isAllowed('pluginMatchAnalysisPricingPreset'),
                        editable : true,
                        typeAhead : true,
                        forceSelection : true,
                        disableKeyFilter : true,
                        fieldLabel: me.strings.basedOn,
                        store: Ext.create('Ext.data.ArrayStore', {
                            fields: ['id', 'type'],
                            data : [
                                ['word',me.strings.wordBased],
                                ['character',me.strings.characterBased]
                            ]
                        }),
                        listeners: {
                            select: 'onUnitTypeSelect'
                        },
                        queryMode: 'local',
                        displayField: 'type',
                        valueField: 'id'
                    }, {
                        xtype: 'combobox',
                        queryMode: 'local',
                        forceSelection: true,
                        displayField: 'name',
                        itemId: 'pricingPresetId',
                        name: 'pricingPresetId',
                        valueField: 'id',
                        disabled: true,
                        value: Editor.data.plugins.MatchAnalysis.pricing.systemDefaultPresetId,
                        bind: {
                            fieldLabel: '{l10n.MatchAnalysis.pricing.preset.combo}'
                        },
                        tpl: Ext.create('Ext.XTemplate',
                            '<ul class="x-list-plain t5leveledList"><tpl for=".">',
                            '<li role="option" class="{[values.cid == 0 ? "x-boundlist-item t5level1" : "x-boundlist-item"]}" title="{description}">{name}</li>',
                            '</tpl></ul>'
                        )
                    }, {
                        xtype: 'image',
                        src: '/modules/editor/images/information.png',
                        listeners: {
                            render: img => img.el.dom.setAttribute('data-qtip', me.getTooltip())
                        }
                    }]
                },{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    enableOverflow: true,
                    border: '1 0 0 0',
                    defaultButtonUI: false,
                    items: [{
                        xtype: 'textfield',
                        readOnly: true,
                        itemId: 'priceAdjustment',
                        bind: {
                            fieldLabel: '{l10n.MatchAnalysis.pricing.preset.priceAdjustment}',
                            value: '{priceAdjustment} {currency}'
                        },
                        labelWidth: 110,
                        width: 220,
                    }, {
                        xtype: 'textfield',
                        readOnly: true,
                        itemId: 'finalAmount',
                        bind: {
                            fieldLabel: '{l10n.MatchAnalysis.analysisWindow.finalAmount}',
                            value: '{finalAmount} {currency}'
                        },
                        labelWidth: 90,
                        width: 190,
                    }, {
                        xtype: 'tbtext',
                        bind: {
                            hidden: '{!noPricing}',
                            html: '{l10n.MatchAnalysis.analysisWindow.notDefined}'
                        }
                    }, '->', {
                        xtype: 'button',
                        glyph: 'f1c3@FontAwesome5FreeSolid',
                        itemId: 'exportExcel',
                        text: me.strings.exportAnalysis,
                        bind: {
                            disabled: '{!hasAnalysisData}'
                        },
                        listeners: {
                            click: 'exportAction'
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
                            click: 'exportAction'
                        }
                    }]
                }]
            };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Set internal task instance and reload the analysis grid for this task
     */
    setTask: function (task){
        var me = this,
            store = me.getStore(),
            proxy = store && store.getProxy();

        me.task = task;

        if( !proxy || !proxy.setExtraParams){
            return;
        }
        if( task){
            proxy.setExtraParams({
                taskGuid: task.get('taskGuid')
            });
            store.load();
        } else {
            store.removeAll();
        }
    },

    getTooltip: function() {
        return '<ul><li>' + [
            Editor.util.LanguageResources.getMatchrateTooltip(101),
            Editor.util.LanguageResources.getMatchrateTooltip(102),
            Editor.util.LanguageResources.getMatchrateTooltip(103),
            Editor.util.LanguageResources.getMatchrateTooltip(104)
        ].join('</li><li>') + '</li></ul>';
    },

    /***
     *
     * @param customRanges
     */
    getColumnConfig: function(customRanges) {
        var me = this,
            columnRenderer = function (val, m, r) {
                return r.get('resourceName') === 'Amount' ? Ext.util.Format.number(val, '0.00') : val;
            },
            columns,
            metrics = Ext.create('Ext.util.TextMetrics'),
            paddings = 25,
            tooltip = me.getTooltip(),
            vm = this.getViewModel();

        customRanges = customRanges || [];

        columns = [{
            xtype: 'gridcolumn',
            text: me.strings.languageResourceName,
            renderer: function (value, metaData, record) {
                if (!value) {
                    return me.strings.repetitions;
                } else if (value === 'summary' || value === 'amount') {
                    return vm.get('l10n.MatchAnalysis.analysisWindow.' + value) + ':';
                } else if (value === 'source') {
                    return me.strings.fromImportedFile+':';
                } else if (!record.get('resourceColor')) {
                    return value;
                }
                return '<div style="float: left; width: 15px; height: 15px;margin-right:5px; border: 1px solid rgba(0, 0, 0, .2);background: #' + record.get('resourceColor') + ';"></div>' + Ext.String.htmlEncode(value);
            },
            dataIndex: 'resourceName',
            minWidth: 200,
            flex: 1,
            sortable: true
        }, {
            xtype: 'gridcolumn',
            dataIndex: 'unitCountTotal',
            align: 'end',
            cellWrap: true,
            renderer: columnRenderer,
            menuDisabled: true,
            text: me.strings.matchCount,
            width: metrics.getWidth(me.strings.matchCount) + paddings
        }, {
            xtype: 'gridcolumn',
            dataIndex: 'penaltyTotal',
            align: 'end',
            cellWrap: true,
            menuDisabled: true,
            bind: {
                text: '{l10n.analysisGrid.penaltyTotal}'
            },
            width: metrics.getWidth(Editor.data.l10n.analysisGrid.penaltyTotal) + paddings
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
            var text = isNoMatch ? me.strings.noMatch : label;
            columns.push({
                xtype: 'gridcolumn',
                dataIndex: item.begin,
                tooltip: tooltip,
                align: 'end',
                cellWrap: true,
                menuDisabled: true,
                text: text,
                width: metrics.getWidth(text) + paddings,
                renderer: columnRenderer
            });
        });

        return columns;
    }
});