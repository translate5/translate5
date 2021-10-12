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
        reimport: '#UT#Aufgaben reimportieren',
        reimportTooltip: '#UT#Ausgewählte Aufgaben erneut ins aktuelle TM importieren',
        gotoTask: '#UT#zur Aufgabe springen'
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
        var me = this,
            config = {
                bind:{
                    title:me.strings.title + ': {record.name}'
                },
                items: [{
                    xtype: 'grid',
                    selModel: {
                        selType: 'checkboxmodel'
                    },
                    bind: {
                        store: '{tasklist}' //FIXME loadmask is not triggered properly when loading the store
                    },
                    columns: [{
                        xtype: 'gridcolumn',
                        flex: 5,
                        //hideable: false,
                        //sortable: false,
                        //cellWrap: true,
                        //tdCls: 'segment-tag-column source',
                        dataIndex: 'taskName',
                        text: me.strings.task
                    }, {
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
                    }, {
                        xtype: 'taskActionColumn',
                        items: [{
                            tooltip: me.strings.gotoTask,
                            iconCls: 'ico-task-project',
                            handler: 'gotoTask'
                        }]
                    }]
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    layout: {
                        type: 'hbox',
                        pack: 'start'
                    },
                    items: [{
                        xtype: 'tbfill'
                    }, {
                        xtype: 'button',
                        glyph: 'f067@FontAwesome5FreeSolid',
                        itemId: 'import-task-tm-btn',
                        tooltip: me.strings.reimportTooltip,
                        text: me.strings.reimport
                    }, {
                        xtype: 'button',
                        glyph: 'f00d@FontAwesome5FreeSolid',
                        itemId: 'cancel',
                        text: me.strings.close
                    }]
                }]
            };
        me.assocStore = instanceConfig.assocStore;
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    loadRecord: function (record) {
        var tasks = this.getViewModel().getStore('tasklist'),
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