
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
        'Editor.view.admin.customer.UserCustomersCombo',
        'Editor.view.LanguageCombo',
        'Editor.view.admin.config.ConfigWizard'
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
    title: '#UT#Projekt erstellen',
    strings: {
        importUploadTip: '#UT#Wählen Sie die zu importierenden Daten (Angabe notwendig)',
        importUploadLabel: '#UT#Import Datei¹',
        importUploadType: '#UT#Das Dateiformat der ausgewählten Datei wird nicht unterstützt!',
        importNews: '#UT#<a target="_blank" href="https://confluence.translate5.net/display/BUS/Supported+file+formats">Mehr Informationen</a> zu den importierbaren Dateiformaten.',
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
        fullMatchLabel: '#UT#unveränderte 100% TM Matches sind editierbar',
        lockLockedLabel: '#UT#Nur für SDLXLIFF Dateien: In importierter Datei explizit gesperrte Segmente sind in translate5 ebenfalls gesperrt',
        sourceEditLabel: '#UT#Ausgangstext ist editierbar',
        bottomInfo: '#UT# ¹ Diese Angaben / Daten werden für den Import zwingend benötigt.',
        feedbackText: "#UT# Fehler beim Import!",
        feedbackTip: '#UT#Fehler beim Import: Bitte wenden Sie sich an den Support!',
        addBtn: '#UT#Aufgabe Importieren',
        addBtnWizard: '#UT#Importieren (weitere überspringen)',
        btnNextWizard:'#UT#Weiter',
        cancelBtn: '#UT#Abbrechen',
        btnSkip:'#UT#Importieren (weitere überspringen)',
        importDefaultsButtonText:'#UT#Importieren (mit Standardwerten für die nächsten Schritte)'
    },
    height : 550,
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
            
            win.insertCard({
                xtype:'adminConfigWizard', 
                itemId:'adminConfigWizard',
                groupIndex:2,//index 1 is language resources assoc
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
            
            for(i=0;i<win.groupCards['import'].length;i++){
                win.add(win.groupCards['import'][i]);
            }
            //sort the group by group index
            win.groupCards['postimport'].sort(function (a, b) {
                return a.groupIndex - b.groupIndex;
            });
            
            for(i=0;i<win.groupCards['postimport'].length;i++){
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
                                    anchor: '100%',
                                    msgTarget: 'under'
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
                                    xtype:'tagfield',
                                    name:'targetLang[]',
                                    toolTip: me.strings.targetLangTip,
                                    fieldLabel: me.strings.targetLangLabel,
                                    //each combo needs its own store instance, see EXT6UPD-8
                                    store: Ext.create(Editor.store.admin.Languages),
                                    typeAhead: false,
                                    autoSelect:true,
                                    //autoSelectLast:false,
                                    displayField: 'label',
                                    forceSelection: true,
                                    //encodeSubmitValue: true, // → as JSON
                                    anyMatch: true,
                                    queryMode: 'local',
                                    valueField: 'id',
                                    allowBlank: false
                                },{
                                    xtype: 'languagecombo',
                                    name: 'relaisLang',
                                    visible:Editor.data.frontend.importTask.pivotDropdownVisible, // the default value is system default
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
                                }]
                                // + item for assigning customers to the task
                                // (added dynamically by Editor.controller.admin.TaskPreferences)
                            },{
                                xtype: 'container',
                                itemId: 'taskSecondCardContainer',
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
                                    xtype: 'checkbox',
                                    inputValue:1,
                                    uncheckedValue:0,
                                    checked:Editor.data.enableSourceEditing,
                                    name: 'enableSourceEditing',
                                    fieldLabel: me.strings.sourceEditLabel
                                },{
                                    xtype: 'checkbox',
                                    inputValue:1,
                                    uncheckedValue:0,
                                    checked:Editor.data.frontend.importTask.edit100PercentMatch,
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
                            html: me.strings.bottomInfo,
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
                        },

                        disableImportDefaults:function(get){
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
                    xtype: 'tbfill'
                },{
                    xtype : 'button',
                    glyph: 'f051@FontAwesome5FreeSolid',
                    itemId : 'continue-wizard-btn',
                    bind:{
                        disabled:'{disableContinueButton}',
                        visible:'{!disableContinueButton}'
                    },
                    text : me.strings.btnNextWizard
                },{
                    xtype : 'button',
                    glyph: 'f04e@FontAwesome5FreeSolid',
                    itemId : 'skip-wizard-btn',
                    bind:{
                        disabled:'{disableSkipButton}',
                        visible:'{!disableSkipButton}'
                    },
                    text : me.strings.btnSkip
                },{
                    xtype : 'button',
                    glyph: 'f560@FontAwesome5FreeSolid',
                    itemId : 'importdefaults-wizard-btn',
                    bind:{
                        disabled:'{disableImportDefaults}',
                        visible:'{!disableImportDefaults}'
                    },
                    text : me.strings.importDefaultsButtonText
                },{
                    xtype : 'button',
                    glyph: 'f00c@FontAwesome5FreeSolid',
                    itemId : 'add-task-btn',
                    bind:{
                      disabled:'{disableAddButton}',
                      visible:'{!disableAddButton}'
                    },
                    text : me.strings.addBtn
                }, {
                    xtype : 'button',
                    glyph: 'f00d@FontAwesome5FreeSolid',
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
        
        return !nextItem || nextItem.getXType()=="taskUpload";
    },
    
    /**
     * we may only close the window when it is not in loading state. 
     * Otherwise the task model instance is not properly set in the window.
     */
    close: function() {
        if(!this.loadMask || !this.loadMask.isVisible()) {
            this.callParent([]);
        }
    }
});