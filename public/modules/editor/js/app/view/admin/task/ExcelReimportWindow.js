
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

Ext.define('Editor.view.admin.task.ExcelReimportWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.adminTaskExcelReimportWindow',
    itemId: 'adminTaskExcelReimportWindow',
    title: '#UT#Excel reimportieren',
    strings: {
        info: '#UT# Spielen Sie hier die vorher heruntergeladene Excel-Datei wieder in die Aufgabe ein.',
        reimportUploadType: '#UT# Erlaubt ist hier nur das Hochladen von *.xlsx-Dateien .',
        uploadBtn: '#UT# Excel hochladen',
        cancelBtn: '#UT# Abbrechen',
        closeBtn: '#UT# Schließen',
        loadingWindowMessage: '#UT# Datei wird hochgeladen',
    },
    modal : true,
    layout: 'anchor',
    autoScroll: true,
    
    requires: ['Editor.view.admin.task.ExcelReimportWindowController'],
    controller: 'editortaskExcelReimportWindowController',

    closeAction: 'destroy',
    layout: 'fit',
    modal: true,
    
    task: null,
    setTask: function(task) {
        this.task = task;
    },
    
    initConfig: function(instanceConfig) {
        var me = this,
            config;
        config = {
            height: Math.min(400, parseInt(Ext.getBody().getViewSize().height * 0.8)),
            width: 500,
            title: me.title, //see EXT6UPD-9
            items:[
                {
                    xtype: 'form',
                    padding: 10,
                    ui: 'default-frame',
                    layout: 'anchor',
                    items: [
                        {
                            anchor: '100%',
                            xtype: 'filefield',
                            name: 'excelreimportUpload',
                            regex: new RegExp('\.xlsx$', 'i'),
                            regexText: me.strings.reimportUploadType,
                            allowBlank: false,
                            toolTip: me.strings.info,
                            fieldLabel: me.strings.uploadBtn
                        },
                        {
                            anchor: '100%',
                            xtype: 'container',
                            html: me.strings.info
                        },
                        {
                            xtype: 'container',
                            itemId: 'feedback',
                            height:'70%',
                            style: {
                                userSelect: 'auto'
                            },
                            layout: 'fit',
                            scrollable: 'y',
                            margin: '10 0 0 0',
                            tpl: ['<tpl for=".">',
                                    '<tpl if="type == \'error\'">',
                                        '<div class="x-message-box-error x-message-box-icon" style="float:left;"></div><h3>{msg}</h3>',
                                    '<tpl else>',
                                        '<div class="x-message-box-warning x-message-box-icon" style="float:left;"></div><h3>{msg}</h3>',
                                    '</tpl>',
                                    '<tpl if="data">',
                                        '{[Editor.MessageBox.dataTable(values.data)]}',
                                    '</tpl>',
                                  '</tpl>']
                        }
                    ],
                },
            ],
            
            dockedItems: [{
                xtype: 'toolbar',
                itemId: 'mainToolbar',
                dock: 'bottom',
                ui: 'footer',
                items: [
                    {
                        xtype: 'tbfill'
                    },
                    {
                        xtype: 'button',
                        itemId: 'uploadBtn',
                        glyph: 'f56f@FontAwesome5FreeSolid',
                        text: me.strings.uploadBtn
                    },
                    {
                        xtype: 'button',
                        itemId: 'cancelBtn',
                        glyph: 'f00d@FontAwesome5FreeSolid',
                        text: me.strings.cancelBtn
                    },
                ]
            }],
        };
        
        config.items = Ext.Array.merge(config.items, instanceConfig.items);
        delete instanceConfig.items;
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

});