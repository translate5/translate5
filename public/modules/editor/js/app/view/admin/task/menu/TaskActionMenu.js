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

Ext.define('Editor.view.admin.task.menu.TaskActionMenu', {
    extend: 'Ext.menu.Menu',
    itemId: 'taskActionMenu',
    alias: 'widget.taskActionMenu',
    viewModel: {
        type: 'taskActionMenu'
    },
    requires: ['Editor.view.admin.task.menu.TaskActionMenuViewModel'],
    messages: {
        actionOpen: '#UT#Aufgabe öffnen (schreibgeschützt)',
        actionEdit: '#UT#Aufgabe bearbeiten',
        actionClone: '#UT#Aufgabe klonen',
        actionFinish: '#UT#Aufgabe abschließen',
        actionUnFinish: '#UT#Aufgabe wieder öffnen',
        actionFinishAll: '#UT#Aufgabe für alle Benutzer abschließen',
        actionUnFinishAll: '#UT#Aufgabe für alle Benutzer wieder öffnen',
        actionEnd: '#UT#Aufgabe komplett Beenden',
        actionReOpen: '#UT#beendete Aufgabe wieder öffnen',
        actionDelete: '#UT#Aufgabe komplett löschen',
        actionCancel: '#UT#Import abbrechen',
        actionLog: '#UT#Ereignis-Protokoll',
        taskPrefs: '#UT#Aufgabenspezifische Einstellungen',
        exp: '#UT#Export',
        actionExcelReimport: '#UT#Excel Re-Importieren',
        projectOverview: '#UT#zum Projekt springen',
        taskOverview: '#UT#zur Aufgabe springen',
        actionDeleteProject: '#UT#Projekt komplett löschen'
    },

    strings:{
        cancelImportText: '#UT#Import abbrechen'
    },

    constructor: function (instanceConfig) {
        var me = this,
            config = {
                //Info: all items should be hidden by default, with this we reduce the "blinking" component behaviour
                items: [{
                    text: me.strings.cancelImportText,
                    action: 'editorCancelImport',
                    hidden: true,
                    bind: {
                        hidden: '{!isCancelable}'
                    },
                    glyph: 'f00d@FontAwesome5FreeSolid',
                    sortIndex: 0
                }, {
                    // - read only öffnen
                    text: me.messages.actionEdit,
                    action: 'editorEditTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorEditTask}'
                    },
                    glyph: 'f044@FontAwesome5FreeSolid',
                    sortIndex: 1
                }, {
                    // - öffnen
                    text: me.messages.actionOpen,
                    action: 'editorOpenTask',
                    hidden: true,
                    glyph: 'f06e@FontAwesome5FreeSolid',
                    bind: {
                        hidden: '{!isEditorOpenTask}'
                    },
                    sortIndex: 2//define the sort index (this is no extjs property, it is internaly used for sorting)
                }, {
                    xtype: 'menuseparator',
                    hidden: true,
                    bind: {
                        hidden: '{!isMenuGroupVisible}'
                    },
                    sortIndex: 3
                }, {
                    // - abschließen (Recht editorFinishTask benötigt, setzt den TaskUser Status des aktuellen Users auf finish)
                    text: me.messages.actionFinish,
                    action: 'editorFinishTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorFinishTask}'
                    },
                    glyph: 'f00c@FontAwesome5FreeSolid',
                    sortIndex: 4
                }, {
                    // - wieder öffnen (Recht editorUnFinishTask benötigt, setzt den TaskUser Status des aktuellen Users auf open, aktuell nicht gefordert)
                    text: me.messages.actionUnFinish,
                    action: 'editorUnfinishTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorUnfinishTask}'
                    },
                    glyph: 'f28d@FontAwesome5FreeSolid',
                    sortIndex: 5
                }, {
                    // - beenden (Recht editorEndTask benötigt, setzt den Task auf Status ""end"")
                    text: me.messages.actionEnd,
                    action: 'editorEndTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorEndTask}'
                    },
                    glyph: 'f28d@FontAwesome5FreeSolid',
                    sortIndex: 6
                }, {
                    // - wieder öffnen (Recht editorReOpenTask benötigt, setzt den Task auf Status ""open"")
                    text: me.messages.actionReOpen,
                    action: 'editorReopenTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorReopenTask}'
                    },
                    glyph: 'f100@FontAwesome5FreeSolid',
                    sortIndex: 7
                }, {
                    xtype: 'menuseparator',
                    hidden: true,
                    bind: {
                        hidden: '{!isMenuGroupVisible}'
                    },
                    sortIndex: 8
                }, {
                    text: me.messages.taskPrefs,
                    action: 'editorPreferencesTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorPreferencesTask}'
                    },
                    glyph: 'f085@FontAwesome5FreeSolid',
                    sortIndex: 9
                }, {
                    xtype: 'menuseparator',
                    hidden: true,
                    bind: {
                        hidden: '{!isMenuGroupVisible}'
                    },
                    sortIndex: 10
                }, {
                    text: me.messages.actionClone,
                    action: 'editorCloneTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorCloneTask}'
                    },
                    glyph: 'f24d@FontAwesome5FreeSolid',
                    sortIndex: 11
                }, {
                    xtype: 'menuseparator',
                    hidden: true,
                    bind: {
                        hidden: '{!isMenuGroupVisible}'
                    },
                    sortIndex: 12
                }, {
                    // - Export Icon, bei Klick darauf öffnet sich ein Menü mit den verschiedenen Export Möglichkeiten.
                    // Die einzelnen Menüpunkte ebenfalls per isAllowed abfragen.
                    text: me.messages.exp,
                    action: 'editorShowexportmenuTask',
                    hidden: true,
                    exportMenu: null,//Custom bindable menu property.
                    bind: {
                        hidden: '{!isEditorShowexportmenuTask}',
                        exportMenu: '{exportMenuConfig}'
                    },
                    glyph: 'f56e@FontAwesome5FreeSolid',
                    sortIndex: 13,
                    publishes: {
                        exportMenu: true
                    },
                    //INFO: initialize the menu, it is hidden and configured via view model
                    menu: {},
                    setExportMenu: function (newMenu) {
                        var me = this;
                        me.setMenu(newMenu, true);
                    }
                }, {
                    // - Excel Reimport Icon, bei Klick darauf öffnet sich der Datei-Upload-Dialog zum Reimport der Excel-Datei
                    text: me.messages.actionExcelReimport,
                    action: 'editorExcelreimportTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorExcelreimportTask}'
                    },
                    glyph: 'f1c3@FontAwesome5FreeSolid',
                    sortIndex: 14
                }, {
                    xtype: 'menuseparator',
                    hidden: true,
                    bind: {
                        hidden: '{!isMenuGroupVisible}'

                    },
                    sortIndex: 15
                }, {
                    text: me.messages.actionDelete,
                    action: 'editorDeleteTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorDeleteTask}'
                    },
                    glyph: 'f2ed@FontAwesome5FreeSolid',
                    sortIndex: 16
                }, {
                    xtype: 'menuseparator',
                    hidden: true,
                    bind: {
                        hidden: '{!isMenuGroupVisible}'
                    },
                    sortIndex: 17
                }, {
                    text: me.messages.actionLog,
                    action: 'editorLogTask',
                    hidden: true,
                    bind: {
                        hidden: '{!isEditorLogTask}'
                    },
                    glyph: 'f1da@FontAwesome5FreeSolid',
                    sortIndex: 18
                }]
            };

        //workaround for fire-event (the component is not created yet so fake the event)
        me.hasListeners = {};
        me.hasListeners['itemsinitialized'] = true;

        //fire the event, so another action columns can be added from outside
        me.fireEvent('itemsinitialized', config.items);

        config.items = Ext.Array.sort(config.items, function (a, b) {
            return a.sortIndex - b.sortIndex;
        });

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }

        me.callParent([Ext.apply({
            items: config.items
        }, config)]);
    }
});