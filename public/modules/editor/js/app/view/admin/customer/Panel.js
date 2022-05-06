
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

Ext.define('Editor.view.admin.customer.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.customerPanel',
    requires: [
        'Editor.view.admin.customer.ViewModel',
        'Editor.view.admin.customer.ViewController',
        'Editor.view.admin.config.Grid',
        'Editor.view.admin.user.Assoc',
        'Editor.view.admin.customer.OpenIdPanel',
        'Editor.view.admin.customer.CopyWindow'
    ],

    stores:['admin.Customers'],
    controller: 'customerPanel',
    viewModel: {
        type: 'customerPanel'
    },
    listeners: {
        activate: {
            fn: 'onCustomerPanelActivate',
            scope: 'controller'
        },
        render:{
            fn:'onCustomerPanelRender',
            scope:'controller'
        }
    },
    strings:{
        reload:'Aktualisieren',
        customerName:'#UT#Kundenname',
        customerNumber:'#UT#Kundennummer',
        save:'#UT#Speichern',
        cancel:'#UT#Abbrechen',
        remove:'#UT#Löschen',
        addCustomerTitle:'#UT#Kunde hinzufügen',
        saveCustomerMsg:'#UT#Kunde wird gespeichert...',
        customerSavedMsg:'#UT#Kunde gespeichert!',
        customerDeleteMsg:'#UT#Diesen Kunden löschen?',
        customerDeleteTitle:'#UT#Kunden löschen',
        customerDeletedMsg:'#UT#Kunde gelöscht',
        export:'#UT#Ressourcen-Nutzung Exportieren',
        domain:'#UT#translate5 Domain',
        propertiesTabPanelTitle: '#UT#Allgemein',
        configTabTitle:'#UT#Überschreibung der Systemkonfiguration',
        actionColumn:'#UT#Aktionen',
        customerEditActionIcon:'#UT#Kunden bearbeiten',
        openIdTabPanelDisabledTooltip:'#UT#Bitte konfigurieren Sie zunächst das Feld "translate5 Domain" im Tab "Allgemein". Danach können Sie OpenID Connect für diesen Kunden einrichten.'
    },
    shrinkWrap: 0,
    layout: 'border',
    collapsed: false,
    title: '#UT#Kunden',
    glyph: 'xf1ad@FontAwesome5FreeSolid',
    helpSection: 'customeroverview',
    defaultListenerScope: true,
    defaultButton: 'saveButton',
    referenceHolder: true,

    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                items: [
                    {
                        xtype: 'gridpanel',
                        cls: 'customerPanelGrid',
                        flex: 0.3,
                        region: 'center',
                        split: true,
                        reference: 'list',
                        resizable: false,
                        title: '',
                        forceFit: true,
                        bind: {
                            store: 'customersStore'
                        },
                        listeners: {
                            itemdblclick: {
                                fn: 'dblclick',
                                scope: 'controller'
                            },
                            select: {
                                fn: 'customerGridSelect',
                                scope: 'controller'
                            }
                        },
                        selModel: {
                            selType: 'rowmodel'
                        },
                        plugins: [
                            {
                                ptype: 'gridfilters'
                            }
                        ],
                        columns: [{
                                xtype: 'gridcolumn',
                                dataIndex: 'id',
                                text: 'Id',
                                width: 20,
                                filter: {
                                    type: 'number'
                                }
                            },{
                                xtype: 'actioncolumn',
                                text:  me.strings.actionColumn,
                                sortable: false,
                                fixed: true,
                                items:[{
                                    glyph: 'f044@FontAwesome5FreeSolid',
                                    tooltip: me.strings.customerEditActionIcon,
                                    scope:'controller',
                                    handler:'onCustomerEditClick'
                                },{
                                    glyph: 'f1c3@FontAwesome5FreeSolid',
                                    tooltip: me.strings.export,
                                    scope:'controller',
                                    handler:'onTmExportClick'
                                },{
                                    glyph: 'f0c5@FontAwesome5FreeSolid',
                                    tooltip: 'Copy',
                                    scope:'controller',
                                    handler:'onCopyActionClick'
                                },{
                                    glyph: 'f2ed@FontAwesome5FreeSolid',
                                    tooltip:me.strings.remove,
                                    scope:'controller',
                                    handler:'remove'
                                }]
                            },{
                                xtype: 'gridcolumn',
                                dataIndex: 'name',
                                text: me.strings.customerName,
                                filter: {
                                    type: 'string'
                                }
                            },{
                                xtype: 'gridcolumn',
                                dataIndex: 'number',
                                text:  me.strings.customerNumber,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    },{
                        xtype: 'tabpanel',
                        flex: 0.7,
                        region: 'east',
                        split: true,
                        layout:'fit',
                        itemId:'displayTabPanel',
                        reference: 'display',
                        bind:{
                            disabled : '{!record}'
                        },
                        items:[{
                            xtype: 'form',
                            itemId:'customersForm',
                            reference: 'form',
                            fieldDefaults: {
                                width: '100%'
                            },
                            title:me.strings.propertiesTabPanelTitle,
                            bodyPadding: 10,
                            scrollable:true,
                            dockedItems:[{
                                xtype: 'toolbar',
                                flex: 1,
                                dock: 'bottom',
                                ui: 'footer',
                                layout: {
                                    pack: 'start',
                                    type: 'hbox'
                                },
                                bind:{
                                    disabled:'{!record}'
                                },
                                items:[{
                                    xtype: 'button',
                                    formBind:true,
                                    itemId: 'saveButton',
                                    reference: 'saveButton',
                                    text: me.strings.save,
                                    glyph: 'f00c@FontAwesome5FreeSolid',
                                    bind:{
                                        visible:'{isActiveTabIncludedInForm}'
                                    },
                                    listeners: {
                                        click: {
                                            fn: 'save',
                                            scope: 'controller'
                                        }
                                    }
                                },{
                                    xtype: 'button',
                                    itemId: 'cancelButton',
                                    text: me.strings.cancel,
                                    glyph: 'f00d@FontAwesome5FreeSolid',
                                    listeners: {
                                        click: {
                                            fn: 'cancelEdit',
                                            scope: 'controller'
                                        }
                                    }
                                }]
                            }],
                            items: [
                                {
                                    xtype: 'textfield',
                                    fieldLabel: me.strings.customerName,
                                    name: 'name',
                                    allowBlank: false,
                                    maxLength: 255,
                                    minLength: 1
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: me.strings.customerNumber,
                                    name: 'number',
                                    allowBlank: false,
                                    maxLength: 255
                                },{
                                    xtype:'textfield',
                                    fieldLabel:me.strings.domain,
                                    name:'domain',
                                    reference:'customerDomain',
                                    publishes:'value',
                                    bind:{
                                        visible:'{!isOpenIdHidden}'
                                    },
                                    itemId:'openIdDomain'
                                }]
                        },{
                            xtype: 'adminUserAssoc',
                            bind:{
                                customer:'{record}',
                                disabled:'{isNewRecord}'
                            }
                        },{
                            xtype: 'adminConfigGrid',
                            store:'admin.CustomerConfig',
                            title:me.strings.configTabTitle,
                            bind: {
                                extraParams:{
                                    customerId : '{record.id}'
                                }
                            }
                        },{
                            xtype:'openIdPanel',
                            tabConfig: {
                                style: {
                                    // to enable tooltip when the tabpanel is disabled
                                    pointerEvents: 'all'
                                },
                                tooltip: me.strings.openIdTabPanelDisabledTooltip
                            },
                            bind:{
                                disabled:'{!customerDomain.value}'
                            }
                        }]
                    }
                ],
                dockedItems: [
                    {
                        xtype: 'toolbar',
                        dock: 'top',
                        items: [
                            {
                                xtype: 'button',
                                glyph: 'f2f1@FontAwesome5FreeSolid',
                                text: me.strings.reload,
                                listeners: {
                                    click: {
                                        fn: 'refresh',
                                        scope: 'controller'
                                    }
                                }
                            },
                            {
                                xtype: 'button',
                                glyph: 'f067@FontAwesome5FreeSolid',
                                text: me.strings.addCustomerTitle,
                                listeners: {
                                    click: {
                                        fn: 'add',
                                        scope: 'controller'
                                    }
                                }
                            },{
                                xtype: 'button',
                                glyph: 'f1c3@FontAwesome5FreeSolid',
                                text: me.strings.export,
                                listeners: {
                                    click: {
                                        fn: 'onTmExportClick',
                                        scope: 'controller'
                                    }
                                }
                            }
                        ]
                    }
                ]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

});