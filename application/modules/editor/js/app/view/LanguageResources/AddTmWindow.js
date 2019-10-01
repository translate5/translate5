
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

Ext.define('Editor.view.LanguageResources.AddTmWindow', {
    extend: 'Ext.window.Window',
    requires: [
        'Ext.ux.colorpick.Button',
        'Ext.ux.colorpick.Field',
        'Editor.view.admin.customer.TagField',
        'Editor.view.admin.customer.UserCustomersCombo',
        'Editor.view.LanguageResources.EngineCombo',
        'Editor.view.LanguageResources.TmWindowViewController',
        'Editor.view.LanguageResources.TmWindowViewModel',
        'Editor.view.LanguageCombo',
        'Editor.store.Categories'
    ],
    controller: 'tmwindowviewcontroller',
    viewModel: {
        type: 'tmwindow'
    },
    alias: 'widget.addTmWindow',

    itemId: 'addTmWindow',
    strings: {
        add: '#UT#Sprachressource hinzufügen',
        resource: '#UT#Ressource',
        name: '#UT#Name',
        file: '#UT#TM/TMX-Datei (optional)',
        importTmxType: '#UT#Bitte verwenden Sie eine TM oder TMX Datei!',
        categories: '#UT#Kategorien',
        color: '#UT#Farbe',
        colorTooltip: '#UT#Farbe dieser Sprachressource',
        save: '#UT#Speichern',
        cancel: '#UT#Abbrechen',
        customers:'#UT#Für diese Kunden nutzen',
        useAsDefault:'#UT#Language Ressource standardmässig aktiv für',
        mergeTerms:'#UT#Termeinträge verschmelzen',
        deleteTermEntriesDate:'#UT#Termeinträge löschen älter als',
        deleteTermEitriesImport:'#UT#Termeinträge löschen älter als aktueller Import',
        collection:'#UT#TBX-Datei',
        importTbxType: '#UT#Bitte verwenden Sie eine TBX Datei!'
    },
    height : 500,
    width : 500,
    modal : true,
    layout:'fit',

    tmxRegex: /\.(tm|tmx)$/i,
    tbxRegex: /\.(tbx)$/i,

    listeners:{
        render:'onTmWindowRender'
    },

    initConfig : function(instanceConfig) {
        var me = this,
        langCombo = {
                xtype: 'combo',
                typeAhead: true,
                displayField: 'label',
                queryMode: 'local',
                valueField: 'id'
            },
            config = {},
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
        config = {
            title: me.strings.add,
            items : [{
                xtype: 'form',
                padding: 5,
                ui: 'default-frame',
                defaults: defaults,
                items: [{
                    xtype: 'combo',
                    name:'resourceId',
                    allowBlank: false,
                    typeAhead: true,
                    forceSelection: true,
                    queryMode: 'local',
                    valueField: 'id',
                    displayField: 'name',
                    store:'Editor.store.LanguageResources.Resources',
                    listeners:{
                        change:'onResourceChange'
                    },
                    fieldLabel: me.strings.resource
                },{
                    xtype:'sdlenginecombo',
                    itemId:'sdlEngine',
                    bind:{
                        hidden:'{!isSdlResource}',
                        disabled:'{!isSdlResource}'
                    },
                    allowBlank: false,
                    listeners:{
                        change:'onSdlEngineComboChange'
                    }
                },{
                    xtype: 'textfield',
                    name: 'name',
                    maxLength: 255,
                    allowBlank: false,
                    toolTip:'Name',
                    fieldLabel: me.strings.name
                },{
                    xtype: 'languagecombo',
                    name: 'sourceLang',
                    bind:{
                        hidden:'{isTermCollectionResource}',
                        disabled:'{isTermCollectionResource}'
                    }
                },{
                    xtype: 'languagecombo',
                    name: 'targetLang',
                    bind:{
                        hidden:'{isTermCollectionResource}',
                        disabled:'{isTermCollectionResource}'
                    }
                },{
                    xtype:'checkbox',
                    bind:{
                        hidden:'{!isTermCollectionResource}',
                        disabled:'{!isTermCollectionResource}'
                    },
                    fieldLabel: me.strings.mergeTerms,
                    itemId:'mergeTerms',
                    name:'mergeTerms',
                    value:true
                },{
                    xtype:'datefield',
                    fieldLabel: me.strings.deleteTermEntriesDate,
                    itemId:'deleteEntriesModifiedOlderThan',
                    name:'deleteEntriesModifiedOlderThan',
                    bind:{
                        hidden:'{!isTermCollectionResource}',
                        disabled:'{!isTermCollectionResource}'
                    }
                },{
                    xtype:'checkbox',
                    fieldLabel: me.strings.deleteTermEitriesImport,
                    itemId:'deleteEntriesOlderThanCurrentImport',
                    name:'deleteEntriesOlderThanCurrentImport',
                    value:false,
                    bind:{
                        hidden:'{!isTermCollectionResource}',
                        disabled:'{!isTermCollectionResource}'
                    }
                },{
                    xtype:'customers',
                    name:'resourcesCustomers',
                    listeners:{
                        change:'onCustomersTagFieldChange'
                    },
                    fieldLabel:me.strings.customers,
                    itemId:'resourcesCustomers',
                    dataIndex:'resourcesCustomers',
                    allowBlank: false,
                    store:Ext.create('Ext.data.Store', {
                        model:'Editor.model.admin.Customer',
                        autoLoad:true
                    })
                },{
                    xtype:'hiddenfield',
                    name:'resourcesCustomersHidden'
                },{
                    xtype:'tagfield',
                    //name:'useAsDefault',
                    itemId:'useAsDefault',
                    fieldLabel:me.strings.useAsDefault,
                    store:Ext.create('Ext.data.Store', {
                        model:'Editor.model.admin.Customer',
                    }),
                    displayField: 'name', 
                    valueField: 'id', 
                    queryMode: 'local', 
                    dataIndex:'useAsDefault'
                },{
                    xtype: 'hiddenfield',
                    name: 'serviceType',
                    dataIndex: 'serviceType',
                    maxLength: 255,
                    allowBlank: false
                },{
                    xtype: 'hiddenfield',
                    name: 'serviceName',
                    dataIndex: 'serviceName',
                    maxLength: 255,
                    allowBlank: false
                },{
                    xtype: 'hiddenfield',
                    name: 'specificData'
                },{
                    xtype: 'colorfield',
                    fieldLabel: me.strings.color,
                    toolTip: me.strings.colorTooltip, 
                    labelWidth: 160,
                    anchor: '100%',
                    name: 'color'
                },{
                    xtype: 'filefield',
                    name: 'tmUpload',
                    allowBlank: true,
                    disabled:true,
                    toolTip: me.strings.file,
                    regex:me.tmxRegex,
                    regexText: me.strings.importTmxType,
                    fieldLabel: me.strings.file,
                    bind:{
                        fieldLabel:'{uploadLabel}' //me.strings.file
                    }
                },{
                    xtype: 'tagfield',
                    name: 'categories',
                    id: 'categories',
                    store: Ext.create('Editor.store.Categories').load(),
                    fieldLabel: me.strings.categories,
                    disabled: true,
                    typeAhead: true,
                    valueField: 'id',
                    displayField: 'customLabel',
                    multiSelect: true,
                    queryMode: 'local',
                    encodeSubmitValue: true
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
                    iconCls:'ico-save',
                    itemId: 'save-tm-btn',
                    text: me.strings.save
                }, {
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'cancel-tm-btn',
                    text : me.strings.cancel
                }]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});