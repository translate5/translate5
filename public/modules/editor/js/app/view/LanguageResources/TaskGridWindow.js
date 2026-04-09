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
        title: '#UT#Associated tasks',
        task: '#UT#Task',
        state: '#UT#Status',
        inUse: '#UT#status “{0}” by another user',
        qualityErrorCount: '#UT#QA errors',
        segmentsInDraft: '#UT#Segments in draft state',
        yes: '#UT#Yes',
        no: '#UT#No',
        close: '#UT#Close',
        reimportAll: '#UT#Save all segments to TM',
        reimportUserSaved: '#UT#Save user-saved segments to TM',
        reimportAllTooltip: '#UT#Saves all segments of all tasks, that are checked above and are assigned to the TM with write permissions',
        reimportUserSavedTooltip: '#UT#Saves only those segments that have previously been manually saved by a user. Does this for all tasks that are checked above and are assigned to the TM with write permissions',
        gotoTask: '#UT#jump to task',
        timeOption: '#UT#Time option',
        currentTime: '#UT#Current timestamp',
        segmentSaveTime: '#UT#Time of segment saving',
        warning: '#UT#Warning',
        saveWarning: '#UT#Do not save any tasks to the TM that have QA errors and/or contain segments in draft status. This leads to incorrect and/or unfinished segment versions in the pre-translation!',
        timeOptionTooltip: '#UT#The date and time of the TM entry created is the timestamp on which the last user saved the segment in the task. Otherwise, the timestamp on which the task is saved to TM is used.',
    },
    controller: 'languageResourceTaskGridWindow',
    viewModel: {
        type: 'languageResourceTaskGridWindow'
    },
    height: 500,
    width: 1000,
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
                                flex: 4,
                                //hideable: false,
                                //sortable: false,
                                //cellWrap: true,
                                //tdCls: 'segment-tag-column source',
                                dataIndex: 'taskName',
                                text: me.strings.task,
                                renderer: v => Ext.String.htmlEncode(v)
                            },{
                                xtype: 'gridcolumn',
                                flex: 3,
                                dataIndex: 'state',
                                renderer: function (val, meta, rec) {
                                    if (!rec.get('lockingUser')) {
                                        return val;
                                    }
                                    return Ext.String.format(me.strings.inUse, rec.get('state'));
                                },
                                text: me.strings.state
                            },{
                                xtype: 'gridcolumn',
                                flex: 1,
                                dataIndex: 'qualityErrorCount',
                                renderer: function (val, meta, rec) {
                                    if (rec.get('qualityHasFaults')) {
                                        val += ' <span class="x-grid-symbol t5-quality-faulty">' + Ext.String.fromCodePoint(parseInt('0xf057', 16)) + '</span>';
                                    }
                                    // We use the loader-feature of the qtips
                                    return '<span data-qtipurl="' + Editor.data.restpath + 'quality/tasktooltip?taskGuid=' + rec.get('taskGuid') + '">' + val + '</span>';
                                },
                                text: me.strings.qualityErrorCount
                            },{
                                xtype: 'gridcolumn',
                                flex: 2,
                                dataIndex: 'segmentsInDraft',
                                text: me.strings.segmentsInDraft,
                                renderer: function (value) {
                                    return '<span style="color:' + (value ? '#FF1C00;font-weight:bold' : 'darkgreen') + '">' + me.strings[value?'yes':'no'] + '</span>';
                                }
                            },{
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
                dockedItems: [{
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
                    },{
                        xtype: 'container',
                        html: '<p style="margin-left:10px;font-weight: bold"><span style="color:#FF1C00">' +
                            me.strings.warning + '</span>: ' + me.strings.saveWarning + '</p>',
                        dock: 'bottom'
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