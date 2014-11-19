/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
Ext.define('Editor.view.admin.TaskAddWindow', {
    extend : 'Ext.window.Window',
    alias : 'widget.adminTaskAddWindow',
    itemId : 'adminTaskAddWindow',
    cls : 'adminTaskAddWindow',
    title : '#UT#Aufgabe erstellen',
    strings: {
        importUploadTip: '#UT#Wählen Sie die zu importierenden Daten (ZIP, CSV, SDLXLIFF; Angabe notwendig)',
        importUploadLabel: '#UT#Import Datei¹',
        importUploadType: '#UT#Bitte verwenden Sie eine ZIP, CSV oder SDLXLIFF Datei!',
        importTbxTip: '#UT#Wählen Sie die zu importierenden TBX Daten für das TermTagging',
        importTbxTipDis: '#UT#Wählen Sie die zu importierenden TBX Daten für das TermTagging',
        importTbxLabel: '#UT#TBX Datei¹²',
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
        targetDeliveryLabel: '#UT#Lieferdatum',
        fullMatchLabel: '#UT#100% Matches sind editierbar',
        sourceEditLabel: '#UT#Ausgangstext ist editierbar',
        bottomInfo: '#UT# ¹ Diese Angaben / Daten werden für den Import zwingend benötigt.',
        bottomInfo2: '#UT# ² separate TBX nur beim Import einer SDLXLIFF Datei möglich.',
        feedbackText: "#UT# Fehler beim Import!",
        feedbackTip: '#UT#Fehler beim Import: Bitte wenden Sie sich an den Support!',
        addBtn: '#UT#Task hinzufügen',
        cancelBtn: '#UT#Abbrechen'
    },
    height : 500,
    width : 550,
    loadingMask: null,
    modal : true,
    initComponent : function() {
        var me = this,
            langCombo = {
                xtype: 'combo',
                typeAhead: true,
                displayField: 'label',
                forceSelection: true,
                queryMode: 'local',
                store: 'admin.Languages',
                valueField: 'id'
            };
        Ext.applyIf(me, {
            items : [{
                xtype: 'form',
                padding: 5,
                ui: 'default-frame',
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
                    fieldLabel: me.strings.sourceLangLabel
                }, langCombo),{
                },Ext.applyIf({
                    name: 'targetLang',
                    allowBlank: false,
                    toolTip: me.strings.targetLangTip,
                    fieldLabel: me.strings.targetLangLabel
                }, langCombo),{
                },Ext.applyIf({
                    name: 'relaisLang',
                    toolTip: me.strings.relaisLangTip,
                    fieldLabel: me.strings.relaisLangLabel
                }, langCombo),{
                    xtype: 'filefield',
                    name: 'importUpload',
                    regex: /\.(zip|sdlxliff|xlf|csv)$/i,
                    regexText: me.strings.importUploadType,
                    allowBlank: false,
                    toolTip: me.strings.importUploadTip,
                    fieldLabel: me.strings.importUploadLabel
                },{
                    xtype: 'container',
                    padding: '0 0 10 0',
                    html: Ext.String.format(me.strings.importNews, Editor.data.pathToRunDir)
                },{
                    xtype: 'filefield',
                    name: 'importTbx',
                    regex: /\.tbx$/i,
                    regexText: me.strings.importTbxType,
                    allowBlank: true,
                    disabled: true,
                    toolTip: me.strings.importTbxTip,
                    fieldLabel: me.strings.importTbxLabel
                },{
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
                    xtype: 'container',
                    html: me.strings.bottomInfo+'<br />'+me.strings.bottomInfo2,
                    dock : 'bottom'
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
                    text : me.strings.addBtn
                }, {
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'cancel-task-btn',
                    text : me.strings.cancelBtn
                }]
            }]
        });

        me.callParent(arguments);
    }
});