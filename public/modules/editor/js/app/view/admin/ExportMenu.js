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

/**
 * contains the Admin Task Export Menu
 * @class Editor.view.admin.ExportMenu
 */
Ext.define('Editor.view.admin.ExportMenu', {
    extend: 'Ext.menu.Menu',
    alias: 'widget.adminExportMenu',
    itemId: 'exportMenu',
    messages: {
        exportDef: '#UT#Originalformat, {0}',
        transfer: '#UT#Transfer translated terms to TermCollection(s)',
        exportTranslation: '#UT#übersetzt',
        exportReview: '#UT#lektoriert',
        exportDiff: '#UT#SDLXLIFF mit Änderungsmarkierungen',
        export2Def: '#UT#XLIFF 2.1',
        exportQmField: '#UT#Export QS-Statistik (XML) für Feld: {0}',
        exportExcel: '#UT#Externe Bearbeitung als Excel',
        downloadImportArchive: '#UT#Importarchiv herunterladen',
        faultyQualityAlertTitle: '#UT#Warnung',
        faultyQualityAlertText: '#UT#Es gibt in dieser Aufgabe interne Tag Fehler, die für einen fehlerfreien Export automatisch korrigiert wurden',
    },

    makePath: function (path, field) {
        var task = this.initialConfig.task;
        return Editor.data.restpath + Ext.String.format(path, task.get('id'), task.get('taskGuid'), field);
    },
    initComponent: function () {
        var me = this,
            task = me.initialConfig.task;
        me.items = [];

        if (task && !task.isErroneous()) {
            me.items = me.initExportOptions();
            me.addExcelExport(task);
        }

        //add download archive link if allowed
        if (Editor.app.authenticatedUser.isAllowed('downloadImportArchive', this.initialConfig.task)) {
            me.items.length && me.items.push("-");
            me.items.push({
                itemId: 'exportItemImportArchive',
                hrefTarget: '_blank',
                href: me.makePath('task/export/id/{0}?format=importArchive'),
                text: me.messages.downloadImportArchive
            });
        }

        // fallback if nothing available
        if (!me.items.length) {
            me.items.push({
                itemId: 'exportItemImportArchive',
                text: me.messages.notAvailable,
                iconCls: 'x-fa fa-times',
                disabled: true
            });
        }

        me.callParent(arguments);
    },
    /**
     * Adds the Excel Export option if allowed
     */
    addExcelExport: function (task) {
        var me = this;
        if (!Editor.app.authenticatedUser.isAllowed('editorExcelreexportTask', task)) {
            return;
        }
        me.items.push({
            itemId: 'exportExcel',
            hrefTarget: '_blank',
            href: me.makePath('task/{0}/excelexport/'),
            text: me.messages.exportExcel,
            handler: function () {
                task.set('state', 'ExcelExported');
                if (task.get('qualityHasFaults')) {
                    me.createFaultyExportAlert();
                }
            }
        });

    },
    /**
     * Add export Links to item list
     */
    initExportOptions: function () {
        var me = this,
            fields = me.initialConfig !== undefined && me.initialConfig.fields,
            exportAllowed = Editor.app.authenticatedUser.isAllowed('editorExportTask', me.task),
            exportDiffString = me.task.get('emptyTargets') ? me.messages.exportTranslation : me.messages.exportReview,
            alertHandler = (me.task.get('qualityHasFaults')) ? function () {
                me.createFaultyExportAlert();
            } : null,
            result = [{
                itemId: 'exportItem',
                hidden: !exportAllowed,
                hrefTarget: '_blank',
                href: me.makePath('task/export/id/{0}'),
                text: Ext.String.format(me.messages.exportDef, exportDiffString),
                handler: alertHandler
            }, {
                itemId: 'transferItem',
                hidden: !exportAllowed || !me.task.get('taskType').match('termtranslation'),
                text: me.messages.transfer,
                path: me.makePath('task/export/id/{0}?format=transfer'),
                handler: me.transferHandler
            }, {
                itemId: 'exportDiffItem',
                hrefTarget: '_blank',
                href: me.makePath('task/export/id/{0}/diff/1'),
                hidden: !exportAllowed || !me.task.get('diffExportUsable'),
                text: me.messages.exportDiff,
                handler: alertHandler
            }, {
                itemId: 'exportItemXliff2',
                hrefTarget: '_blank',
                href: me.makePath('task/export/id/{0}?format=xliff2'),
                text: me.messages.export2Def,
                handler: alertHandler
            }];

        if (fields !== false) {
            fields.each(function (field) {
                field.get('editable') && result.push({
                    hrefTarget: '_blank',
                    href: me.makePath('quality/downloadstatistics/taskGuid/{1}/?type={2}', field.get('name')),
                    text: Ext.String.format(me.messages.exportQmField, field.get('label'))
                });
            });
        }
        return result;
    },
    createFaultyExportAlert: function () {
        Ext.Msg.alert(this.messages.faultyQualityAlertTitle + '!', this.messages.faultyQualityAlertText);
    },
    transferHandler: function (c) {
        Ext.Msg.alert('Status', 'Starting re-import of translated terms...');
        Ext.Ajax.request({url: c.path});
    }
});
