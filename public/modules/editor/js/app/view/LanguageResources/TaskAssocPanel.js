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

Ext.define('Editor.view.LanguageResources.TaskAssocPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.languageResourceTaskAssocPanel',
    itemId: 'languageResourceTaskAssocPanel',
    viewModel: {
        type: 'languageResourceTaskAssocPanel',
    },
    requires: ['Editor.view.admin.TaskActionColumn', 'Editor.view.LanguageResources.TaskAssocPanelViewModel'],
    cls: 'adminTaskGrid',
    title: '#UT#Sprachressourcen',
    strings: {
        reload: '#UT#Aktualisieren',
        save: '#UT#Speichern',
        empty: '#UT#Keine Sprachressource in der Sprachkombination des geöffneten Tasks verfügbar.',
        checked: '#UT#Ressource in Aufgabe verwenden',
        name: '#UT#Name',
        segmentsUpdateable: '#UT#Segmente zurückspeichern',
        source: '#UT#Quellsprache',
        target: '#UT#Zielsprache',
        serviceName: '#UT#Ressource',
        taskGuid: '#UT#Aufgabename',
    },
    padding: 0,
    layout: 'fit',
    border: 0,
    bind: {
        loading: '{isLoadingActive}',
    },
    initConfig: function (instanceConfig) {
        var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                dockedItems: [],
                items: [
                    {
                        xtype: 'grid',
                        border: 0,
                        itemId: 'languageResourcesTaskAssocGrid',
                        bind: {
                            store: '{taskAssoc}',
                            disabled: '{!enablePanel}',
                        },
                        plugins: ['gridfilters', 'cellediting'],
                        emptyText: me.strings.empty,
                        features: [
                            {
                                id: 'group',
                                ftype: 'grouping',
                                groupHeaderTpl: Ext.create(
                                    'Ext.XTemplate',
                                    '{columnName}: {[this.formatValue(values)]}',
                                    {
                                        formatValue: function (values) {
                                            var ret = values.name;

                                            if (values.groupField === 'taskGuid') {
                                                //when taskGuid is active as grouping, render the task name as group value
                                                var data = values.rows && values.rows[0];

                                                return data ? Ext.String.htmlEncode(data.get('taskName')) : ret;
                                            }

                                            return Ext.String.htmlEncode(ret);
                                        },
                                    },
                                ),
                                hideGroupedHeader: false,
                                enableGroupingMenu: true,
                                groupers: [{ property: 'serviceName' }, { property: 'targetLang' }],
                            },
                        ],
                        columns: [
                            {
                                xtype: 'checkcolumn',
                                tooltip: me.strings.checked,
                                text: me.strings.checked,
                                dataIndex: 'checked',
                                sortable: true,
                                cls: 'taskAssocChecked',
                                width: 60,
                            },
                            {
                                xtype: 'gridcolumn',
                                tooltip: me.strings.taskGuid,
                                text: me.strings.taskGuid,
                                dataIndex: 'taskGuid',
                                sortable: true,
                                hidden: true,
                                width: 60,
                            },
                            {
                                xtype: 'checkcolumn',
                                tooltip: me.strings.segmentsUpdateable,
                                text: me.strings.segmentsUpdateable,
                                cls: 'segmentsUpdateable',
                                dataIndex: 'segmentsUpdateable',
                                sortable: true,
                                renderer: function (value, meta, record) {
                                    return record.get('writable') ? this.defaultRenderer(value, meta, record) : '';
                                },
                                width: 60,
                            },
                            {
                                xtype: 'gridcolumn',
                                text: me.strings.name,
                                renderer: function (value, metaData, record) {
                                    const style = 'float: left; width: 15px; height: 15px;margin-right:5px;';
                                    let className = '';
                                    let tooltip = '';

                                    if ('not-converted' === record.get('tmConversionState')) {
                                        className = 'ico-tm-converseTm';
                                        tooltip = Editor.data.l10n.contentProtection.tm_not_converted;
                                    }

                                    if ('conversion-scheduled' === record.get('tmConversionState')) {
                                        className = 'ico-tm-converseTm-scheduled';
                                        tooltip = Editor.data.l10n.contentProtection.tm_conversion_scheduled;
                                    }

                                    if ('conversion-started' === record.get('tmConversionState')) {
                                        className = 'ico-tm-converseTm-inProgress';
                                        tooltip = Editor.data.l10n.contentProtection.tm_conversion_in_progress;
                                    }

                                    return (
                                        '<div style="' +
                                        style +
                                        ' border: 1px solid rgba(0, 0, 0, .2);background: #' +
                                        record.get('color') +
                                        ';"></div>' +
                                        (
                                            record.get('tmConversionState') && 'converted' !== record.get('tmConversionState')
                                                ? `<div style="${style}" class="${className}" data-qtip="${tooltip}"></div>`
                                                : ''
                                        ) +
                                        Ext.String.htmlEncode(value)
                                    );
                                },
                                dataIndex: 'name',
                                sortable: true,
                                flex: 50 / 100,
                            },
                            {
                                xtype: 'gridcolumn',
                                text: me.strings.serviceName,
                                dataIndex: 'serviceName',
                                sortable: true,
                                flex: 25 / 100,
                            },
                            {
                                xtype: 'gridcolumn',
                                tooltip: me.strings.source,
                                text: me.strings.source,
                                cls: 'source-lang',
                                dataIndex: 'sourceLang',
                                renderer: me.langRenderer,
                                sortable: true,
                                flex: 25 / 100,
                            },
                            {
                                xtype: 'gridcolumn',
                                tooltip: me.strings.target,
                                text: me.strings.target,
                                cls: 'target-lang',
                                dataIndex: 'targetLang',
                                renderer: me.langRenderer,
                                flex: 25 / 100,
                                sortable: true,
                            },
                            {
                                xtype: 'owncheckcolumn',
                                text: Editor.data.l10n.projectOverview.taskManagement.languageResourceAssoc.taskTm,
                                dataIndex: 'isTaskTm',
                                flex: 25 / 100,
                                filter:{
                                    local: true,
                                    type: 'boolean',
                                    yesText: Editor.data.l10n.projectOverview.taskManagement.languageResourceAssoc
                                        .showForeignTaskTms,
                                    noText: Editor.data.l10n.projectOverview.taskManagement.languageResourceAssoc
                                        .hideForeignTaskTms
                                }
                            },
                            {
                                xtype: 'gridcolumn',
                                width: 74,
                                dataIndex: 'penaltyGeneral',
                                menuDisabled: true,
                                text:
                                    '<span style="color: #df0000;"><span class="fa fa-chevron-down"></span><span> 1 </span></span>' +
                                    '<span class="fa fa-edit" style="position: relative; top: 1px;"></span>',
                                bind: {
                                    tooltip: '{l10n.languageResourceTaskAssocPanel.penaltyGeneral}'
                                },
                                editor: {
                                    xtype: 'combobox',
                                    viewModel: 'languageResourceTaskAssocPanel',
                                    forceSelection: true,
                                    bind: {
                                        store: '{penaltyGeneral}'
                                    }
                                }
                            },
                            {
                                xtype: 'gridcolumn',
                                width: 74,
                                dataIndex: 'penaltySublang',
                                menuDisabled: true,
                                text:
                                    '<span style="color: #df0000;"><span class="fa fa-chevron-down"></span><span> 2 </span></span>' +
                                    '<span class="fa fa-edit" style="position: relative; top: 1px;"></span>',
                                bind: {
                                    tooltip: '{l10n.languageResourceTaskAssocPanel.penaltySublang}'
                                },
                                editor: {
                                    xtype: 'combobox',
                                    viewModel: 'languageResourceTaskAssocPanel',
                                    forceSelection: true,
                                    bind: {
                                        store: '{penaltySublang}'
                                    }
                                }
                            }
                        ],
                    },
                ],
            };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }

        return me.callParent([config]);
    },
    langRenderer: function (val, md) {
        var lang = Ext.StoreMgr.get('admin.Languages').getById(val),
            label;

        if (lang) {
            label = lang.get('label');
            md.tdAttr = 'data-qtip="' + label + '"';

            return label;
        }

        return '';
    },
});
