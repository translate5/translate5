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
        customers: '#UT#Für diese Kunden nutzen',
        useAsDefault: '#UT#Leserechte standardmäßig',
        writeAsDefault: '#UT#Schreibrechte standardmäßig',
        mergeTerms: '#UT#Termeinträge verschmelzen',
        collection: '#UT#TBX-Datei',
        importTbxType: '#UT#Bitte verwenden Sie eine TBX Datei!',
        useAsDefaultTooltip: '#UT#Standardmäßig wird bei neuen Aufgaben für die gewählten Kunden Leserecht für diese Sprachresource aktiviert',
        writeAsDefaultTooltip: '#UT#Standardmäßig wird bei neuen Aufgaben für die gewählten Kunden gesetzt, dass Segmente beim Segment speichern in die Sprachresource geschrieben werden',
        collectionUploadTooltip: '#UT#Erlaubte Dateiformate: TBX oder eine Zip-Datei, die eine oder mehrere TBX-Dateien enthält.',
        mergeTermsLabelTooltip: '#UT#Begriffe in der TBX werden immer zuerst nach ID mit bestehenden Einträgen in der TermCollection zusammengeführt. Wenn Terme zusammenführen angekreuzt ist und die ID in der TBX nicht in der TermCollection gefunden wird, wird gesucht, ob derselbe Begriff bereits in derselben Sprache existiert. Wenn ja, werden die gesamten Termeinträge zusammengeführt. Insbesondere bei einer TermCollection mit vielen Sprachen kann dies zu unerwünschten Ergebnissen führen.',
        pivotAsDefault:'#UT#Standardmäßig als Pivot verwenden',
        pivotAsDefaultTooltip:'#UT#Standardmäßig als Pivot verwenden'
    },
    height: 500,
    width: 500,
    modal: true,
    layout: 'fit',
    autoScroll: true,

    tmxRegex: /\.(tm|tmx)$/i,
    tbxRegex: /\.(tbx|zip)$/i,

    listeners: {
        render: 'onTmWindowRender'
    },

    initConfig: function (instanceConfig) {
        var me = this,
            defaults = {
                labelWidth: 160,
                anchor: '100%'
            },
            config = {
                title: me.strings.add,
                items: [{
                    xtype: 'form',
                    padding: 5,
                    ui: 'default-frame',
                    defaults: defaults,
                    scrollable: 'y',
                    items: [{
                        xtype: 'combo',
                        name: 'resourceId',
                        allowBlank: false,
                        typeAhead: true,
                        forceSelection: true,
                        queryMode: 'local',
                        valueField: 'id',
                        displayField: 'name',
                        store: 'Editor.store.LanguageResources.Resources',
                        listeners: {
                            change: 'onResourceChange'
                        },
                        fieldLabel: me.strings.resource
                    }, {
                        xtype: 'sdlenginecombo',
                        itemId: 'sdlEngine',
                        name: 'engines',
                        bind: {
                            hidden: '{!isSdlResource}',
                            disabled: '{!isSdlResource}'
                        },
                        allowBlank: false,
                        listeners: {
                            change: 'onEngineComboChange'
                        }
                    }, {
                        xtype: 'textfield',
                        name: 'name',
                        maxLength: 255,
                        allowBlank: false,
                        toolTip: 'Name',
                        fieldLabel: me.strings.name
                    }, {
                        xtype: 'languagecombo',
                        name: 'sourceLang',
                        bind: {
                            hidden: '{isTermCollectionResource}',
                            disabled: '{isTermCollectionResource}'
                        }
                    }, {
                        xtype: 'languagecombo',
                        name: 'targetLang',
                        bind: {
                            hidden: '{isTermCollectionResource}',
                            disabled: '{isTermCollectionResource}'
                        }
                    }, {
                        xtype: 'checkbox',
                        bind: {
                            hidden: '{!isTermCollectionResource}',
                            disabled: '{!isTermCollectionResource}'
                        },
                        fieldLabel: me.strings.mergeTerms,
                        itemId: 'mergeTerms',
                        name: 'mergeTerms',
                        value: false
                    }, {
                        xtype: 'customers',
                        name: 'customerIds[]',
                        itemId: 'resourcesCustomers',
                        dataIndex: 'customerIds',
                        reference: 'resourcesCustomers',
                        publishes: 'value',
                        bind: {
                            store: '{customers}'
                        },
                        listeners: {
                            change: 'onCustomersTagFieldChange'
                        },
                        fieldLabel: me.strings.customers,
                        allowBlank: false
                    }, {
                        xtype: 'tagfield',
                        name: 'customerUseAsDefaultIds[]',
                        itemId: 'useAsDefault',
                        dataIndex: 'customerUseAsDefaultIds',
                        reference: 'useAsDefault',
                        publishes: 'value',
                        bind: {
                            store: '{customersDefaultRead}'
                        },
                        listeners: {
                            change: 'onCustomersReadTagFieldChange'
                        },
                        queryMode: 'local',
                        displayField: 'name',
                        valueField: 'id',
                        fieldLabel: me.strings.useAsDefault,
                        labelClsExtra: 'lableInfoIcon',
                        autoEl: {
                            tag: 'div',
                            'data-qtip': me.strings.useAsDefaultTooltip
                        }
                    }, {
                        xtype: 'tagfield',
                        name: 'customerWriteAsDefaultIds[]',
                        itemId: 'writeAsDefault',
                        dataIndex: 'customerWriteAsDefaultIds',
                        bind: {
                            store: '{customersDefaultWrite}',
                            hidden: '{!isTmResourceType}',
                            disabled: '{!isTmResourceType}'
                        },
                        displayField: 'name',
                        valueField: 'id',
                        queryMode: 'local',
                        fieldLabel: me.strings.writeAsDefault,
                        labelClsExtra: 'lableInfoIcon',
                        autoEl: {
                            tag: 'div',
                            'data-qtip': me.strings.writeAsDefaultTooltip
                        }
                    },{
                        xtype: 'tagfield',
                        name: 'customerPivotAsDefaultIds[]',
                        itemId: 'pivotAsDefault',
                        dataIndex: 'customerPivotAsDefaultIds',
                        bind: {
                            store: '{customersDefaultPivot}'
                        },
                        displayField: 'name',
                        valueField: 'id',
                        queryMode: 'local',
                        fieldLabel: me.strings.pivotAsDefault,
                        labelClsExtra: 'lableInfoIcon',
                        autoEl: {
                            tag: 'div',
                            'data-qtip': me.strings.pivotAsDefaultTooltip
                        }
                    }, {
                        xtype: 'hiddenfield',
                        name: 'serviceType',
                        dataIndex: 'serviceType',
                        maxLength: 255,
                        allowBlank: false
                    }, {
                        xtype: 'hiddenfield',
                        name: 'serviceName',
                        dataIndex: 'serviceName',
                        maxLength: 255,
                        allowBlank: false
                    }, {
                        xtype: 'hiddenfield',
                        name: 'specificData'
                    }, {
                        xtype: 'colorfield',
                        fieldLabel: me.strings.color,
                        toolTip: me.strings.colorTooltip,
                        labelWidth: 160,
                        anchor: '100%',
                        name: 'color'
                    }, {
                        xtype: 'filefield',
                        name: 'tmUpload',
                        vtype: 'tmFileUploadSize',
                        allowBlank: true,
                        disabled: true,
                        toolTip: me.strings.file,
                        regex: me.tmxRegex,
                        regexText: me.strings.importTmxType,
                        fieldLabel: me.strings.file,
                        bind: {
                            fieldLabel: '{uploadLabel}'
                        }
                    }, {
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
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    layout: {
                        type: 'hbox',
                        pack: 'start'
                    },
                    items: [{
                        xtype: 'tbfill'
                    }, {
                        xtype: 'button',
                        glyph: 'f00c@FontAwesome5FreeSolid',
                        itemId: 'save-tm-btn',
                        text: me.strings.save
                    }, {
                        xtype: 'button',
                        glyph: 'f00d@FontAwesome5FreeSolid',
                        itemId: 'cancel-tm-btn',
                        text: me.strings.cancel
                    }]
                }]
            };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    onDestroy: function () {
        if (this.getController() && this.getController().labelTooltipInstance) {
            this.getController().labelTooltipInstance.destroy();
        }
        this.callParent(arguments);
    }
});