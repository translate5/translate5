
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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.TaskAddWindow', {
    extend: 'Ext.window.Window',
    requires:[
        'Editor.view.admin.TaskUpload',
        'Editor.view.admin.TaskAddWindowViewModel',
        'Editor.view.LanguageCombo'
    ],
    mixins:[
        'Editor.controller.admin.IWizardCard'
    ],
    alias: 'widget.adminTaskAddWindow',
    itemId: 'adminTaskAddWindow',
    cls: 'adminTaskAddWindow',
    viewModel: {
        type: 'adminTaskAddWindow'
    },
    title: '#UT#Aufgabe erstellen',
    strings: {
        importUploadTip: '#UT#Wählen Sie die zu importierenden Daten (ZIP, CSV, SDLXLIFF, XLIFF; Angabe notwendig)',
        importUploadLabel: '#UT#Import Datei¹',
        importUploadType: '#UT#Bitte verwenden Sie eine ZIP, CSV, XLIFF oder SDLXLIFF Datei!',
        importTbxTip: '#UT#Wählen Sie die zu importierenden TBX Daten für das TermTagging',
        importTbxTipDis: '#UT#Wählen Sie die zu importierenden TBX Daten für das TermTagging',
        importTbxLabel: '#UT#TBX Datei²',
        importTbxType: '#UT#Bitte verwenden Sie eine TBX Datei!',
        importNews: '#UT#Sie können direkt SDLXLIFF, XLIFF oder CSV Dateien benutzen! <a target="_blank" href="{0}/index/usage">Mehr Info</a>.',
        
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
        addBtn: '#UT#Aufgabe Importieren',
        addBtnWizard: '#UT#Importieren (weitere überspringen)',
        btnNextWizard:'#UT#Weiter',
        cancelBtn: '#UT#Abbrechen',
        btnSkip:'#UT#Importieren (weitere überspringen)',
    },
    height : 500,
    width : 1000,
    modal : true,
    layout: 'anchor',
    autoScroll: true,
    /***
     * Group of cards before they are added to the window wizard
     * The groups are:preimport, import and postimport
     */
    groupCards:[],
    
    listeners:{
        beforerender:function(win){
            //insert the taskUpload card in before render
            win.insertCard({
                xtype:'taskUpload', 
                itemId:'taskUploadCard',
                groupIndex:4,
            },'postimport');
        },
        render:function(win){
        	
        	//sort the group by group index
        	win.groupCards['preimport'].sort(function (a, b) {
        		return a.groupIndex - b.groupIndex;
    		});
        	
            //add all of the cards in the window by order: preimport, import, postimport
            for(var i=0;i<win.groupCards['preimport'].length;i++){
                win.add(win.groupCards['preimport'][i]);
            }
            
            //sort the group by group index
        	win.groupCards['import'].sort(function (a, b) {
        		return a.groupIndex - b.groupIndex;
    		});
        	
            for(var i=0;i<win.groupCards['import'].length;i++){
                win.add(win.groupCards['import'][i]);
            }
            //sort the group by group index
        	win.groupCards['postimport'].sort(function (a, b) {
        		return a.groupIndex - b.groupIndex;
    		});
        	
            for(var i=0;i<win.groupCards['postimport'].length;i++){
                win.add(win.groupCards['postimport'][i]);
            }
        },
        afterrender:function(win){
            var winLayout=win.getLayout(),
                vm=win.getViewModel();
          vm.set('activeItem',winLayout.getActiveItem());
      }
    },
    
    importTaskMessage:"#UT#Hochladen beendet. Import und Vorbereitung laufen.",
    
    initConfig: function(instanceConfig) {
        var me = this,
            langCombo = {
                xtype: 'combo',
                typeAhead: false,
                displayField: 'label',
                forceSelection: true,
                anyMatch: true,
                queryMode: 'local',
                valueField: 'id'
            },
            now = new Date(),
            config;
        
        //init the card group arrays
        me.groupCards['preimport']=[];
        me.groupCards['import']=[];
        me.groupCards['postimport']=[];

        now.setHours(0,0,0,0);
        config = {
                title: me.title, //see EXT6UPD-9
                layout: 'card',
                items:[
                    {
                        xtype:'panel',
                        itemId: 'taskMainCard',
                        importType:'import',
                        scrollable:'y',
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
                                itemId: 'taskMainCardContainer',
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
                                    xtype: 'languagecombo',
                                    name: 'sourceLang',
                                    toolTip: me.strings.sourceLangTip,
                                    fieldLabel: me.strings.sourceLangLabel
                                },{
                                    xtype: 'languagecombo',
                                    name: 'targetLang',
                                    toolTip: me.strings.targetLangTip,
                                    fieldLabel: me.strings.targetLangLabel
                                },{
                                    xtype: 'languagecombo',
                                    name: 'relaisLang',
                                    getSubmitValue: function() {
                                        return this.getValue();
                                    },
                                    allowBlank: true,
                                    toolTip: me.strings.relaisLangTip,
                                    fieldLabel: me.strings.relaisLangLabel
                                },{
                                    xtype: 'filefield',
                                    name: 'importUpload',
                                    regex: new RegExp('\.('+Editor.data.import.validExtensions.join('|')+')$', 'i'),
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
                                    xtype: 'textfield',
                                    maxLength: 120,
                                    name: 'taskNr',
                                    fieldLabel: me.strings.taskNrLabel
                                },{
                                    xtype: 'datefield',
                                    name: 'orderdate',
                                    submitFormat: Editor.DATE_ISO_FORMAT,
                                    value: now,
                                    fieldLabel: me.strings.orderdate
                                },{
                                    xtype: 'datefield',
                                    name: 'targetDeliveryDate',
                                    submitFormat: Editor.DATE_ISO_FORMAT,
                                    value: now,
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
                        },
                        disableSkipButton:function(get){
                            return true;
                        },
                        
                        disableContinueButton:function(get){
                            var me=this,
                                win=me.up('window');
                            return win.isTaskUploadNext();
                        },
                        
                        disableAddButton:function(get){
                            var me=this,
                                win=me.up('window');
                            
                            if(!win.isTaskUploadNext()){
                                win.down('#add-task-btn').setText(win.strings.addBtnWizard);
                            }
                            
                            return false;
                        },

                        disableCancelButton:function(get){
                            return false;
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
                    iconCls : 'ico-next-wizard',
                    itemId : 'continue-wizard-btn',
                    bind:{
                        disabled:'{disableContinueButton}',
                        visible:'{!disableContinueButton}'
                    },
                    text : me.strings.btnNextWizard
                },{
                    xtype : 'button',
                    iconCls : 'ico-skip-wizard',
                    itemId : 'skip-wizard-btn',
                    bind:{
                        disabled:'{disableSkipButton}',
                        visible:'{!disableSkipButton}'
                    },
                    text : me.strings.btnSkip
                },{
                    xtype : 'button',
                    iconCls : 'ico-task-add',
                    itemId : 'add-task-btn',
                    bind:{
                      disabled:'{disableAddButton}',
                      visible:'{!disableAddButton}'
                    },
                    text : me.strings.addBtn
                }, {
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'cancel-task-btn',
                    bind:{
                        disabled:'{disableCancelButton}',
                        visible:'{!disableCancelButton}'
                    },
                    text : me.strings.cancelBtn
                }]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    /***
     * Insert card in window at given group
     * The groups are: preimport,import,postimport
     */
    insertCard:function(card,group){
        var me=this;
        if(!me.groupCards[group]){
            me.groupCards[group]=[];
        }
        
        me.groupCards[group].push(card);
    },
    
    isTaskUploadNext:function(){
        var winLayout=this.getLayout(),
            nextItem=winLayout.getNext();
        
        return nextItem.getXType()=="taskUpload";
    }
});