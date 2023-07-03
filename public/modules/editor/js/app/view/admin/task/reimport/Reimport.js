
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

/***
 *
 */
Ext.define('Editor.view.admin.task.reimport.Reimport', {
    extend: 'Ext.tree.Panel',
    requires: [
        'Editor.view.admin.task.reimport.ReimportViewController',
        'Editor.view.admin.task.reimport.ReimportWindowViewModel',
        'Editor.model.admin.task.Filetree'
    ],

    alias: 'widget.adminTaskReimportReimport',
    itemId: 'adminTaskReimportReimport',
    controller:'adminTaskReimportReimport',
    viewModel:{
        type:'adminTaskReimportReimport'
    },
    reserveScrollbar: true,
    useArrows: true,
    rootVisible: false,

    initComponent: function() {
        var me = this;
        me.callParent(arguments);
        Ext.on({
            projectTaskSelectionChange:'onProjectTaskSelectionChange',
            scope:me
        })
    },

    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'top',
                    layout: {
                        type: 'column',
                        columnCount: 2
                    },
                    items: [
                        {
                            xtype: 'button',
                            itemId: 'exportTranslatorPackage',
                            bind: {
                                text:'{l10n.projectOverview.taskManagement.taskReimport.exportTranslatorPackage}',
                                disabled: '{!isTranslatorPackageAvailable}'
                            }
                        },{
                            xtype: 'button',
                            itemId: 'importTranslatorPackage',
                            bind: {
                                text: '{l10n.projectOverview.taskManagement.taskReimport.importTranslatorPackage}',
                                disabled: '{!isTranslatorPackageAvailable}'
                            }
                        },{
                            xtype: 'displayfield',
                            width:'80%',
                            hideLabel:true,
                            bind:{
                                hidden: '{!isTranslatorPackageAvailable}'
                            },
                            value:'<i>' + Ext.String.format(Editor.data.l10n.projectOverview.taskManagement.taskReimport.topBarHeaderInfo,Editor.data.editor.task.reimport.supportedExtensions.join(','))+ '</i>'
                        },{
                            xtype: 'displayfield',
                            width:'80%',
                            hideLabel:true,
                            bind:{
                                value:'{l10n.projectOverview.taskManagement.taskReimport.translatorPackageDisabledTooltip}',
                                hidden: '{isTranslatorPackageAvailable}'
                            }
                        }
                    ]
                }],
                columns: [{
                    xtype: 'treecolumn',
                    flex: 0.5,
                    bind:{
                        text: '{l10n.projectOverview.taskManagement.taskReimport.fileNameColumnText}'
                    },
                    dataIndex: 'filename'
                },{
                    xtype: 'actioncolumn',
                    flex: 0.5,
                    menuDisabled: true,
                    bind:{
                        text: '{l10n.projectOverview.taskManagement.taskReimport.actionColumnText}'
                    },
                    align: 'center',
                    items:[{
                        glyph: 'f093@FontAwesome5FreeSolid',
                        isDisabled:function (view, rowIdx, colIdx, item, record){
                            return me.isUploadDisabled(record) || !me.isPackageExportImportAllowed();
                        },
                        getClass: function (view, meta, record){
                            if(me.isUploadDisabled(record) || !me.isPackageExportImportAllowed()){
                                return 'disabledButtonTooltip';
                            }
                            return '';
                        },
                        getTip: function (view, meta, record){
                            if(!me.isPackageExportImportAllowed()){
                                // Do not show any tooltip in case the export package is not allowed.
                                // We already show message in the dockedItems
                                return '';
                            }
                            if(me.isUploadDisabled(record) === false){
                                return Editor.data.l10n.projectOverview.taskManagement.taskReimport.actionColumnTooltip;
                            }
                            return Editor.data.l10n.projectOverview.taskManagement.taskReimport.actionColumnDisabledTooltip;
                        },
                        handler: 'onUploadAction'
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Check if the upload is disabled for the giver record.
     * @param record
     * @returns {boolean}
     */
    isUploadDisabled: function (record){
        var isFolder = !record.get('leaf'),
            disabledByExtension = !Ext.Array.contains(Editor.data.editor.task.reimport.supportedExtensions,record.get('extension'));
        return isFolder || disabledByExtension;
    },

    /***
     * Is the translator package feature available.
     * It is not available whe the task is closed or not importable
     * @returns {boolean}
     */
    isPackageExportImportAllowed:function (){
        return this.lookupViewModel().get('isTranslatorPackageAvailable');
    },

    /***
     * Event handler for project task change event.
     * On each task change, the files store will be reloaded for the
     * new task
     * @param newTask
     */
    onProjectTaskSelectionChange: function (newTask){
        if(!newTask){
            return;
        }
        var me=this;
        me.task = newTask;
        me.getController().loadStoreData(me.task.get('taskGuid'));
    }

});