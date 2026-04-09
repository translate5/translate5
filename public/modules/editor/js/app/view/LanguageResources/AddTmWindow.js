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
        add: '#UT#Add language resource',
        resource: '#UT#Resource',
        name: '#UT#Name',
        file: '#UT#ZIP/TM/TMX file (optional)',
        importTmxType: '#UT#Please use a TM or TMX file!',
        categories: '#UT#Categories',
        color: '#UT#Colour',
        colorTooltip: '#UT#Colour of this language resource',
        save: '#UT#Save',
        cancel: '#UT#Cancel',
        customers: '#UT#Use for selected clients',
        useAsDefault: '#UT#Read access by default',
        writeAsDefault: '#UT#Write access by default',
        mergeTerms: '#UT#Merge term entries',
        collection: '#UT#TBX file',
        importTbxType: '#UT#Please use a TBX file!',
        useAsDefaultTooltip: '#UT#By default, read permission for this language resource is enabled for new tasks for the selected clients',
        writeAsDefaultTooltip: '#UT#By default, saving segments to the language resource is enabled for new tasks for the selected clients',
        collectionUploadTooltip: '#UT#Allowed file formats: TBX or a ZIP file containing one or multiple TBX files.',
        mergeTermsLabelTooltip: '#UT#Terms in the TBX will always be merged first by ID with existing entries in the TermCollection. If “Merge terms” is checked and the ID in the TBX is not found in the TermCollection, it will be checked whether the same term already exists in the same language. If yes, the entire term entries will be merged. Especially with a TermCollection with many languages, this may lead to unwanted results.',
        pivotAsDefault:'#UT#Use as pivot by default',
        pivotAsDefaultTooltip:'#UT#Use as pivot by default',
        stripFramingTags: '#UT#Strip framing tags at import',
        stripFramingTagsTooltip: '#UT#Works analogous to system configuration runtimeOptions.import.xlf.ignoreFramingTags, but for TMX import. It strips all (or only paired) tags from the start and end of an imported segment, if activated. The system configuration should therefore have the same setting for the same client for task imports. If you need to convert existing TMs, please ask the translate5 support for help.',
        resegmentTmxTooltip: '#UT#Segmentation rules for the respective language are applied to the source and target text of the segments. If the same number of segments is found, the segments are re-segmented (i.e. further segmented). The segmentation rules that are stored as standard for the selected client are used. If several clients are assigned to the language resource, the rules of the first assigned client are used.',
    },
    height: 730,
    width: 800,
    modal: true,
    layout: 'fit',
    autoScroll: true,

    tmxRegex: /\.(tm|tmx|zip)$/i,
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
                        xtype: 'hiddenfield',
                        name: 'CsrfToken',
                        value: Editor.data.csrfToken
                    }, {
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
                            change: 'onResourceChange',
                            beforeselect: 'onResourceBeforeSelect'
                        },
                        fieldLabel: me.strings.resource
                    }, {
                        xtype: 'enginecombo',
                        itemId: 'engine',
                        name: 'engines',
                        allowBlank: false,
                        bind: {
                            hidden: '{!useEnginesListCombo}',
                            disabled: '{!useEnginesListCombo}'
                        },
                        listeners: {
                            change: 'onEngineComboChange'
                        }
                    }, {
                        xtype: 'textfield',
                        itemId: 'enginefield',
                        name: 'engine',
                        maxLength: 255,
                        allowBlank: false,
                        fieldLabel: 'Engine/Model',
                        bind: {
                            hidden: '{!useEngineTextfield}',
                            disabled: '{!useEngineTextfield}'
                        }
                    }, {
                        xtype: 'textfield',
                        itemId: 'domaincodefield',
                        name: 'domaincode',
                        maxLength: 255,
                        allowBlank: false,
                        fieldLabel: 'Domain/Code',
                        bind: {
                            hidden: '{!hasDomaincodeTextfield}',
                            disabled: '{!hasDomaincodeTextfield}'
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
                        },
                        listeners: {
                            change: 'onSelectFile'
                        }
                    },
                        {
                            xtype: 'combo',
                            itemId: 'stripFramingTags',
                            name: 'stripFramingTags',
                            fieldLabel: me.strings.stripFramingTags,
                            store: new Ext.data.ArrayStore({
                                fields: ['id', 'value'],
                            }),
                            queryMode: 'local',
                            displayField: 'value',
                            valueField: 'id',
                            value: 'none',
                            bind: {
                                disabled: '{!isStrippingFramingTagsSupported}',
                                hidden: '{!isStrippingFramingTagsSupported}'
                            },
                            labelClsExtra: 'lableInfoIcon',
                            autoEl: {
                                tag: 'div',
                                'data-qtip': me.strings.stripFramingTagsTooltip
                            }
                        },
                        {
                            xtype: 'checkbox',
                            bind: {
                                hidden: '{!isResegmentingTmxSupported}',
                                disabled: '{!isResegmentingTmxSupported}',
                                fieldLabel: '{l10n.languageResources.resegmentTmx}'
                            },
                            itemId: 'resegmentTmx',
                            name: 'resegmentTmx',
                            value: false,
                            labelClsExtra: 'lableInfoIcon',
                            autoEl: {
                                tag: 'div',
                                'data-qtip': me.strings.resegmentTmxTooltip
                            }
                        },
                        {
                            xtype: 'tagfield',
                            name: 'categories',
                            store: Ext.create('Editor.store.Categories').load(),
                            fieldLabel: me.strings.categories,
                            disabled: true,
                            typeAhead: true,
                            valueField: 'id',
                            displayField: 'customLabel',
                            multiSelect: true,
                            queryMode: 'local',
                            encodeSubmitValue: true
                        },
                    ]
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
