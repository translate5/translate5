
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
                tbar:[{
                    xtype: 'displayfield',
                    hideLabel:true,
                    fieldCls: 'lableInfoIcon',
                    value:Ext.String.format(Editor.data.l10n.projectOverview.taskManagement.taskReimport.topBarHeaderInfo,Editor.data.editor.task.reimport.supportedExtensions.join(','))
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
                        text: '{l10n.projectOverview.taskManagement.taskReimport.actionColumnText}',
                    },
                    align: 'center',
                    items:[{
                        glyph: 'f093@FontAwesome5FreeSolid',
                        isDisabled:function (view, rowIdx, colIdx, item, record){
                            return me.isUploadDisabled(record);
                        },
                        getClass: function (view, meta, record){
                            if(me.isUploadDisabled(record)){
                                return 'disabledButtonTooltip';
                            }
                            return '';
                        },
                        getTip: function (view, meta, record){
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