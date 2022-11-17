
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

Ext.define('Editor.view.admin.task.reimport.ReimportWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.adminTaskReimportReimportWindow',
    itemId: 'adminTaskReimportReimportWindow',
    controller:'adminTaskReimportReimportWindow',
    requires:['Editor.view.admin.task.reimport.ReimportWindowViewController'],
    height : 300,
    width : 500,
    modal : true,
    layout:'fit',
    record: null,
    task: null,
    initConfig : function(instanceConfig) {
        var me = this,
            locales = Editor.data.l10n.projectOverview.taskManagement.taskReimportWindow,
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
            config = {
                bind:{
                    title:'{l10n.projectOverview.taskManagement.taskReimportWindow.title}'
                },
                items : [{
                    xtype: 'form',
                    padding: 5,
                    ui: 'default-frame',
                    defaults: defaults,
                    items: [{
                        xtype: 'filefield',
                        bind:{
                            fieldLabel:'{l10n.projectOverview.taskManagement.taskReimportWindow.fileFieldLabel}',
                        },
                        regexText: Ext.String.format(locales.fileUploadRegexText, Editor.data.editor.task.reimport.supportedExtensions.join(',')),
                        regex: me.getSupportedFilesRegex(),
                        labelWidth: 160,
                        anchor: '100%',
                        vtype:'tmFileUploadSize',
                        name: 'fileReimport'
                    },{
                        xtype:'checkbox',
                        bind:{
                            fieldLabel:'{l10n.projectOverview.taskManagement.taskReimportWindow.saveToMemoryLable}',
                        },
                        tooltip: locales.saveToMemoryTooltip,
                        itemId: 'saveToMemory',
                        name: 'saveToMemory',
                        inputValue: 1,
                        uncheckedValue: 0,
                        value: me.getCustomerSaveToMemory()
                    }]
                }],
                dockedItems : [{
                    xtype : 'toolbar',
                    dock : 'bottom',
                    ui: 'footer',
                    layout: {
                        type: 'hbox',
                        pack: 'start'
                    },
                    items : [{
                        xtype: 'tbfill'
                    },{
                        xtype: 'button',
                        glyph: 'f00c@FontAwesome5FreeSolid',
                        itemId: 'saveBtn',
                        bind:{
                            text:'{l10n.projectOverview.taskManagement.taskReimportWindow.saveBtnText}'
                        }
                    }, {
                        xtype : 'button',
                        glyph: 'f00d@FontAwesome5FreeSolid',
                        itemId : 'cancelBtn',
                        handler:function (){
                            me.close();
                        },
                        bind:{
                            text:'{l10n.projectOverview.taskManagement.taskReimportWindow.cancelBtnText}'
                        }
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /**
     * @param record
     */
    loadRecord: function(record,task) {
        var me=this;
        me.record = record;
        me.task = task;
    },

    /***
     * Return regular expression for all supported upload file extensions
     * @returns {string}
     */
    getSupportedFilesRegex: function (){
        return new RegExp('('+Editor.data.editor.task.reimport.supportedExtensions.join('|')+')');
    },

    getCustomerSaveToMemory: function (){
        return Ext.getStore('admin.task.Config').getConfig('task.reimport.saveToMemory');
    }
});