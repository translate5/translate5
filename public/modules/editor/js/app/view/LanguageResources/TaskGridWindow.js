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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.LanguageResources.MatchGrid
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.view.LanguageResources.TaskGridWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.languageResourceTaskGridWindow',
    requires: [
        'Editor.view.LanguageResources.TaskGridWindowViewController',
        'Editor.view.LanguageResources.TaskGridWindowViewModel',
        'Ext.grid.Panel',
        'Ext.grid.filters.filter.String',
        'Ext.grid.filters.filter.List',
        'Ext.grid.column.Number',
        'Ext.grid.filters.filter.Number',
        'Ext.selection.RowModel',
        'Ext.grid.filters.Filters',
        'Ext.view.Table',
        'Ext.form.Panel',
        'Ext.form.field.ComboBox',
        'Ext.button.Button',
        'Ext.toolbar.Toolbar'
    ],
    strings: {
        title: '#UT#Zugewiesene Aufgaben',
        task: '#UT#Aufgabe',
        state: '#UT#Status',
        inUse: '#UT#Status "{0}" durch einen anderen Benutzer',
        close: '#UT#Schließen',
        reimportAll: '#UT#Alle Segmente im TM speichern',
        reimportUserSaved: '#UT#Benutzer-gespeicherte Segmente im TM speichern',
        reimportAllTooltip: '#UT#Speichert alle Segmente aller Aufgaben, die oben angehakt sind und dem TM mit Schreibrechten zugewiesen sind',
        reimportUserSavedTooltip: '#UT#Speichert nur die Abschnitte, die zuvor von einem Benutzer manuell gespeichert wurden. Dies gilt für alle Aufgaben, die oben angehakt sind und dem TM mit Schreibrechten zugewiesen sind',
        gotoTask: '#UT#zur Aufgabe springen',
        timeOption: '#UT#Zeitoption',
        currentTime: '#UT#Aktueller Zeitpunkt',
        segmentSaveTime: '#UT#Zeitpunkt der Segmentspeicherung',
        timeOptionTooltip: '#UT#Als Datum und Uhrzeit des erzeugten TM-Eintrags wird bei "Zeitpunkt der Segmentspeicherung" das Datum verwendet, an dem er letzte Benutzer das Segment in der Aufgabe gespeichert hat. Andernfalls wird der Zeitpunkt verwendet, an dem die Aufgabe neu ins TM durchgespeichert wird.',
    },
    controller: 'languageResourceTaskGridWindow',
    viewModel: {
        type: 'languageResourceTaskGridWindow'
    },
    height: 500,
    width: 800,
    modal: true,
    layout: 'fit',
    initConfig: function (instanceConfig) {
        let me = this,
            config = {
                bind: {
                    title: me.strings.title + ': {record.name}'
                },
                items: [
                    {
                        xtype: 'grid',
                        selModel: {
                            selType: 'checkboxmodel'
                        },
                        bind: {
                            store: '{tasklist}' //FIXME loadmask is not triggered properly when loading the store
                        },
                        columns: [
                            {
                                xtype: 'gridcolumn',
                                flex: 5,
                                //hideable: false,
                                //sortable: false,
                                //cellWrap: true,
                                //tdCls: 'segment-tag-column source',
                                dataIndex: 'taskName',
                                text: me.strings.task,
                                renderer: v => Ext.String.htmlEncode(v)
                            },
                            {
                                xtype: 'gridcolumn',
                                flex: 5,
                                dataIndex: 'state',
                                renderer: function (val, meta, rec) {
                                    if (!rec.get('lockingUser')) {
                                        return val;
                                    }
                                    return Ext.String.format(me.strings.inUse, rec.get('state'));
                                },
                                text: me.strings.state
                            },
                            {
                                xtype: 'taskActionColumn',
                                items: [{
                                    tooltip: me.strings.gotoTask,
                                    iconCls: 'ico-task-project',
                                    handler: 'gotoTask'
                                }]
                            }
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
                                xtype: 'combo',
                                itemId: 'import-time-option',
                                allowBlank: false,
                                editable: false,
                                forceSelection: true,
                                store: Ext.create('Ext.data.ArrayStore', {
                                    fields: ['name', 'value'],
                                    data: [
                                        [this.strings.currentTime, 'current'],
                                        [this.strings.segmentSaveTime, 'segment'],
                                    ]
                                }),
                                queryMode: 'local',
                                displayField: 'name',
                                valueField: 'value',
                                fieldLabel: me.strings.timeOption,
                                listeners: {
                                    afterrender: function(combo) {
                                        combo.select(combo.getStore().getAt(0));
                                        new Ext.tip.ToolTip({
                                            target: combo.inputEl,
                                            html: me.strings.timeOptionTooltip
                                        });
                                    },
                                }
                            },
                            {
                                xtype: 'button',
                                glyph: 'f067@FontAwesome5FreeSolid',
                                itemId: 'import-all-btn',
                                text: me.strings.reimportAll,
                                tooltip: me.strings.reimportAllTooltip
                            },
                            {
                                xtype: 'button',
                                glyph: 'f067@FontAwesome5FreeSolid',
                                itemId: 'import-user-saved-btn',
                                text: me.strings.reimportUserSaved,
                                tooltip: me.strings.reimportUserSavedTooltip
                            },
                            {
                                xtype: 'button',
                                glyph: 'f00d@FontAwesome5FreeSolid',
                                itemId: 'cancel',
                                text: me.strings.close
                            }
                        ]
                    }
                ]
            };
        me.assocStore = instanceConfig.assocStore;

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }

        return me.callParent([config]);
    },
    loadRecord: function (record) {
        let tasks = this.getViewModel().getStore('tasklist'),
            proxy = Editor.model.LanguageResources.LanguageResource.proxy,
            url = proxy.url;

        this.getViewModel().set('record', record);

        if (!url.match(proxy.slashRe)) {
            url += '/';
        }

        url += record.get('id') + '/tasks';
        tasks.load({
            url: url
        });
    }
});