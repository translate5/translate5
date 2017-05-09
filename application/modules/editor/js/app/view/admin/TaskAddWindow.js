
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.TaskAddWindow', {
    extend: 'Ext.window.Window',
    mixins:['Editor.controller.admin.IWizardCard'],
    alias: 'widget.adminTaskAddWindow',
    itemId: 'adminTaskAddWindow',
    cls: 'adminTaskAddWindow',
    title: '#UT#Aufgabe erstellen',
    strings: {
        importUploadTip: '#UT#Wählen Sie die zu importierenden Daten (ZIP, CSV, SDLXLIFF; Angabe notwendig)',
        importUploadLabel: '#UT#Import Datei¹',
        importUploadType: '#UT#Bitte verwenden Sie eine ZIP, CSV oder SDLXLIFF Datei!',
        importTbxTip: '#UT#Wählen Sie die zu importierenden TBX Daten für das TermTagging',
        importTbxTipDis: '#UT#Wählen Sie die zu importierenden TBX Daten für das TermTagging',
        importTbxLabel: '#UT#TBX Datei²',
        importTbxType: '#UT#Bitte verwenden Sie eine TBX Datei!',
        importNews: '#UT#<b style="color:#ff0000;">Neu:</b> Sie können nun direkt SDLXLIFF, openTM2-XLIFF oder CSV Dateien benutzen! <a target="_blank" href="{0}/index/usage">Mehr Info</a>.',
        
        taskNrLabel: '#UT#Auftragsnummer',
        taskNameTip: '#UT#Projektname (frei wählbar, Angabe notwendig)',
        taskNameLabel: '#UT#Projektname¹',
        sourceLangTip: '#UT#Quellsprache des Projektes (Angabe notwendig)',
        sourceLangLabel: '#UT#Quellsprache¹',
        targetLangTip: '#UT#Zielsprache des Projektes (Angabe notwendig)',
        targetLangLabel: '#UT#Zielsprache¹',
        relaisLangTip: '#UT#Relaissprache (Angabe notwendig sofern Relaisdaten vorhanden)',
        relaisLangLabel: '#UT#Relaissprache',
        numberFieldLabel: '#UT#Anzahl Wörter',
        orderdate: '#UT#Bestelldatum',
        targetDeliveryLabel: '#UT#Lieferdatum',
        fullMatchLabel: '#UT#100% Matches sind editierbar',
        lockLockedLabel: '#UT#In importierter Datei gesperrte Segmente sind in translate5 gesperrt',
        sourceEditLabel: '#UT#Ausgangstext ist editierbar',
        bottomInfo: '#UT# ¹ Diese Angaben / Daten werden für den Import zwingend benötigt.',
        bottomInfo2: '#UT# ² Eine TBX Datei ist optional. Eine TBX Datei im TBX-Core Format wird benötigt um Terminology auszuzeichnen.',
        feedbackText: "#UT# Fehler beim Import!",
        feedbackTip: '#UT#Fehler beim Import: Bitte wenden Sie sich an den Support!',
        addBtn: '#UT#Task hinzufügen',
        cancelBtn: '#UT#Abbrechen'
    },
    height : 500,
    width : 1000,
    modal : true,
    layout: 'anchor',
    initConfig: function(instanceConfig) {
        var me = this,
            langCombo = {
                xtype: 'combo',
                typeAhead: true,
                displayField: 'label',
                forceSelection: true,
                queryMode: 'local',
                valueField: 'id'
            },
            config = {
                title: me.title, //see EXT6UPD-9
                layout: 'card',
                items:[
                    {
                        xtype:'panel',
                        itemId: 'step-1',
                        items:[{
                            xtype: 'form',
                            padding: 5,
                            ui: 'default-frame',
                            layout: 'hbox',
                            layoutConfig : {
                                align : 'stretch',
                            },
                            anchor: '100%',
                            items: [{
                                xtype: 'container',
                                flex: 1,
                                layout: 'anchor',
                                padding: '0 10 0 0',
                                defaults: {
                                    labelWidth: 200,
                                    anchor: '100%'
                                },
                                items: [{
                                    xtype: 'textfield',
                                    name: 'taskName',
                                    maxLength: 255,
                                    allowBlank: false,
                                    toolTip: me.strings.taskNameTip,
                                    fieldLabel: me.strings.taskNameLabel
                                },{
                                    xtype: 'textfield',
                                    maxLength: 120,
                                    name: 'taskNr',
                                    fieldLabel: me.strings.taskNrLabel
                                },Ext.applyIf({
                                    name: 'sourceLang',
                                    allowBlank: false,
                                    toolTip: me.strings.sourceLangTip,
                                    //each combo needs its own store instance, see EXT6UPD-8
                                    store: Ext.create(Editor.store.admin.Languages),
                                    fieldLabel: me.strings.sourceLangLabel
                                }, langCombo),Ext.applyIf({
                                    name: 'targetLang',
                                    allowBlank: false,
                                    toolTip: me.strings.targetLangTip,
                                    //each combo needs its own store instance, see EXT6UPD-8
                                    store: Ext.create(Editor.store.admin.Languages),
                                    fieldLabel: me.strings.targetLangLabel
                                }, langCombo),Ext.applyIf({
                                    name: 'relaisLang',
                                    getSubmitValue: function() {
                                    return this.getValue();
                                    },
                                    //each combo needs its own store instance, see EXT6UPD-8
                                    store: Ext.create(Editor.store.admin.Languages),
                                    toolTip: me.strings.relaisLangTip,
                                    fieldLabel: me.strings.relaisLangLabel
                                }, langCombo),{
                                    xtype: 'filefield',
                                    name: 'importUpload',
                                    regex: /\.(zip|sdlxliff|xlf|csv|testcase)$/i,
                                    regexText: me.strings.importUploadType,
                                    allowBlank: false,
                                    toolTip: me.strings.importUploadTip,
                                    fieldLabel: me.strings.importUploadLabel
                                },{
                                    xtype: 'container',
                                    layout: 'auto',
                                    padding: '0 0 10 0',
                                    html: Ext.String.format(me.strings.importNews, Editor.data.pathToRunDir)
                                },{
                                    xtype: 'filefield',
                                    name: 'importTbx',
                                    regex: /\.tbx$/i,
                                    regexText: me.strings.importTbxType,
                                    allowBlank: true,
                                    toolTip: me.strings.importTbxTip,
                                    fieldLabel: me.strings.importTbxLabel
                                }]
                            },{
                                xtype: 'container',
                                flex: 1,
                                layout: 'anchor',
                                defaults: {
                                    labelWidth: 200,
                                    anchor: '100%'
                                },
                                items: [{
                                    xtype: 'datefield',
                                    name: 'orderdate',
                                    submitFormat: Editor.DATE_ISO_FORMAT,
                                    value: new Date(),
                                    fieldLabel: me.strings.orderdate
                                },{
                                    xtype: 'datefield',
                                    name: 'targetDeliveryDate',
                                    submitFormat: Editor.DATE_ISO_FORMAT,
                                    value: new Date(),
                                    fieldLabel: me.strings.targetDeliveryLabel
                                },{
                                    xtype: 'numberfield',
                                    name: 'wordCount',
                                    fieldLabel: me.strings.numberFieldLabel
                                },(Editor.data.enableSourceEditing  ? {
                                    xtype: 'checkbox',
                                    inputValue: 1,
                                    name: 'enableSourceEditing',
                                    fieldLabel: me.strings.sourceEditLabel
                                } : {
                                    xtype: 'hidden',
                                    value: 0,
                                    name: 'enableSourceEditing'
                                }),{
                                    xtype: 'checkbox',
                                    inputValue: 1,
                                    name: 'edit100PercentMatch',
                                    fieldLabel: me.strings.fullMatchLabel
                                },{
                                    xtype: 'checkbox',
                                    inputValue: 1,
                                    name: 'lockLocked',
                                    checked: true,
                                    fieldLabel: me.strings.lockLockedLabel
                                }]
                            }]
                        
                        },{
                            xtype: 'container',
                            padding: '10',
                            html: me.strings.bottomInfo+'<br />'+me.strings.bottomInfo2,
                            dock : 'bottom'
                        }],
                        triggerNextCard:function(activeItem){
                            var form = activeItem.down('form');
                            if(form.isValid()){
                                activeItem.fireEvent('wizardCardFinished');
                            }
                        }
                    }
                ],
            dockedItems : [{
                xtype : 'toolbar',
                dock : 'bottom',
                ui: 'footer',
                layout: {
                    type: 'hbox',
                    pack: 'start'
                },
                items : [{
                    xtype: 'button',
                    hidden: true,
                    itemId: 'feedbackBtn',
                    text: me.strings.feedbackText,
                    tooltip: me.strings.feedbackTip,
                    iconCls: 'ico-error',
                    ui: 'default-toolbar'
                },{
                    xtype: 'tbfill'
                },{
                    xtype : 'button',
                    iconCls : 'ico-task-add',
                    itemId : 'add-task-btn',
                    text : 'Continue (fixme in the locales)'
                }, {
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'cancel-task-btn',
                    text : me.strings.cancelBtn
                }]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});