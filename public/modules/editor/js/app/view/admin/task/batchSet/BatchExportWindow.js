/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.task.batchSet.BatchExportWindow', {
    extend: 'Ext.window.Window',
    requires: [
        'Editor.view.admin.task.batchSet.BatchExportViewController',
        'Editor.store.admin.task.BatchTasks'
    ],
    controller: 'batchExport',
    itemId: 'batchExportWindow',
    bodyPadding: 20,
    border: false,
    width: 900,
    bodyStyle: {
        borderWidth: 0
    },
    strings: {
        title: '#UT#Exportieren',
        btnExport: '#UT#Exportieren',
        info: '#UT#Originalformat, übersetzt/lektoriert',
        tooManyTasks: '#UT#Es können nur maximal {0} Aufgaben als Batch verarbeitet werden',
        lockedTasksUnselectable: '#UT#Gesperrte Augfgaben können nicht ausgewählt werden',
        colId: '#UT#Id',
        colName: '#UT#Name',
        exportSelected: '#UT#Alle selektierten exportieren',
        exportFiltered: '#UT#Alle in Filterung exportieren',
        reloadTooltip: '#UT#Aktualisieren Sie die Liste, falls gesperrte Aufgaben (rot) enthalten sind, um zu prüfen, ob sie jetzt wieder verfügbar sind'
    },
    viewModel: {
        data: {
            showingTasks: false
        },
        formulas: {
            isShowingTasks: function(get) {
                return get('showingTasks');
            }
        }
    },
    initConfig: function (instanceConfig) {
        var me = this,
            config = {
                title: me.strings.title,
                bind: {
                    height: '{isShowingTasks ? 525 : 225}'
                },
                scrollable: true,
                defaults: {
                    xtype: 'container',
                    flex: 1,
                    margin: '0 5 0 0',
                    autoSize: true
                },
                items: [{
                    xtype: 'panel',
                    html: '<span style="color:#0e69b9;">' + me.strings.info + '</span>',
                    border: 0,
                    padding: '0 0 5 0',
                    bind: {
                        hidden: '{!isShowingTasks}'
                    }
                }, {
                    xtype: 'grid',
                    itemId: 'batchTasksGrid',
                    bind: {
                        hidden: '{!isShowingTasks}'
                    },
                    height: 370,
                    width: 850,
                    // data layout: taskId, taskGuid, taskName
                    columns: [{
                        xtype: 'checkcolumn',
                        text: '',
                        dataIndex: 'checked',
                        width: 35,
                        renderer: function (value, metaData, record) {
                            // we mimic a disabled checkbox when the record is "busy". This is more or less a hack,
                            // since we use a static string ...
                            if (record.get('busy')) {
                                return '<span class="x-grid-checkcolumn" role="button" tabIndex="0"' +
                                    ' style="opacity:0.3" data-qtip="' +
                                    me.strings.lockedTasksUnselectable + '"></span>';
                            }
                            return this.defaultRenderer.apply(this, arguments);
                        }
                    }, {
                        xtype: 'gridcolumn',
                        text: me.strings.colId,
                        dataIndex: 'taskId',
                        width: 50,
                        renderer: function (value, metaData, record) {
                            if (record.get('busy')) {
                                metaData.tdAttr = 'data-qtip="' + me.strings.lockedTasksUnselectable + '"';
                            }
                            return value;
                        }
                    }, {
                        xtype: 'gridcolumn',
                        text: me.strings.colName,
                        dataIndex: 'taskName',
                        width: 760,
                        renderer: function (value, metaData, record) {
                            if (record.get('busy')) {
                                metaData.tdAttr = 'data-qtip="' + me.strings.lockedTasksUnselectable + '"';
                            }
                            return Ext.String.htmlEncode(value);
                        }
                    }],
                    viewConfig: {
                        getRowClass: function(record){
                            return record.get('busy') ? 'redRow' : '';
                        }
                    },
                    store: Ext.create('Editor.store.admin.task.BatchTasks')
                }],
                dockedItems: [{
                    xtype: 'panel',
                    bind: {
                        html: Editor.data.l10n.batchSetWindow.infobox.split(/\.\s+[A-Z][^.]/)[0] + '.',
                        hidden: '{isShowingTasks}'
                    },
                    cls: 'infobox-panel'
                }, {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    items: [{
                        xtype: 'button',
                        itemId: 'runForFiltered',
                        text: me.strings.exportFiltered,
                        bind: {
                            hidden: '{isShowingTasks}'
                        }
                    }, {
                        xtype: 'button',
                        itemId: 'refreshBatchPreview',
                        text: Editor.data.l10n.general.reload,
                        tooltip: me.strings.reloadTooltip,
                        bind: {
                            hidden: '{!isShowingTasks}'
                        }
                    }, {
                        xtype: 'tbfill'
                    }, {
                        xtype: 'button',
                        itemId: 'runForSelected',
                        text: me.strings.exportSelected,
                        bind: {
                            hidden: '{isShowingTasks}'
                        }
                    }, {
                        xtype: 'button',
                        itemId: 'startBatchProcess',
                        glyph: 'f04e@FontAwesome5FreeSolid',
                        text: me.strings.btnExport,
                        bind: {
                            hidden: '{!isShowingTasks}'
                        }
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
