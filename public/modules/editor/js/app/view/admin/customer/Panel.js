
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
        'Editor.view.admin.customer.CopyWindow',
        'Editor.view.LanguageResources.CustomerTmAssoc',
        'Editor.view.LanguageResources.CustomerTmAssocController',
    ],

    stores: ['admin.Customers'],
    controller: 'customerPanel',
    viewModel: {
        type: 'customerPanel'
    },
    listeners: {
        activate: {
            fn: 'onCustomerPanelActivate',
            scope: 'controller'
        },
        render: {
            fn: 'onCustomerPanelRender',
            scope: 'controller'
        }
    },
    strings: {
        reload: 'Aktualisieren',
        customerName: '#UT#Kundenname',
        customerNumber: '#UT#Kundennummer',
        save: '#UT#Speichern',
        cancel: '#UT#Abbrechen',
        remove: '#UT#Löschen',
        addCustomerTitle: '#UT#Kunde hinzufügen',
        saveCustomerMsg: '#UT#Kunde wird gespeichert...',
        customerSavedMsg: '#UT#Kunde gespeichert!',
        customerDeleteMsg: '#UT#Diesen Kunden löschen?',
        customerDeleteTitle: '#UT#Kunden löschen',
        customerDeletedMsg: '#UT#Kunde gelöscht',
        export: '#UT#Ressourcen-Nutzung Exportieren',
        domain: '#UT#Internetadresse (nur die Domain)',
        propertiesTabPanelTitle: '#UT#Allgemein',
        configTabTitle:'#UT#Überschreibung der Systemkonfiguration',
        actionColumn:'#UT#Aktionen',
        customerEditActionIcon:'#UT#Kundenprofil bearbeiten',
        openIdTabPanelDisabledTooltip:'#UT#Bitte konfigurieren Sie zunächst das Feld "Internetadresse (nur die Domain)" im Tab "Allgemein". Danach können Sie OpenID Connect für diesen Kunden einrichten.'
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

    domainLabelInfoTooltip: null,

    initConfig: function (instanceConfig) {
        const me = this,
            canAddCustomer = Editor.app.authenticatedUser.isAllowed('editorAddCustomer'),
            canDeleteCustomer = Editor.app.authenticatedUser.isAllowed('editorDeleteCustomer'),
            isOpenIdEditor = Editor.app.authenticatedUser.isAllowed('customerOpenIdAdministration'),
            resourceExportAllowed = Editor.app.authenticatedUser.isAllowed('customerResourceExport'),
            customerConfigAllowed = Editor.app.authenticatedUser.isAllowed('customerConfig')
        ;

        let allowedTabs = [];

        if (canAddCustomer) {
            allowedTabs.push({
                xtype: 'form',
                itemId: 'customersForm',
                reference: 'form',
                fieldDefaults: {
                    width: '100%'
                },
                title: me.strings.propertiesTabPanelTitle,
                bodyPadding: 10,
                scrollable: true,
                dockedItems: [{
                    xtype: 'toolbar',
                    flex: 1,
                    dock: 'bottom',
                    ui: 'footer',
                    layout: {
                        pack: 'start',
                        type: 'hbox'
                    },
                    bind: {
                        disabled: '{!record}'
                    },
                    enableOverflow: true,
                    items: [
                        {
                            xtype: 'button',
                            formBind: true,
                            itemId: 'saveButton',
                            reference: 'saveButton',
                            text: me.strings.save,
                            glyph: 'f00c@FontAwesome5FreeSolid',
                            bind: {
                                visible: '{isActiveTabIncludedInForm}'
                            },
                            listeners: {
                                click: {
                                    fn: 'save',
                                    scope: 'controller'
                                }
                            }
                        },
                        {
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
                        }
                    ]
                }],
                items: [
                    {
                        xtype: 'textfield',
                        fieldLabel: me.strings.customerName,
                        name: 'name',
                        allowBlank: false,
                        maxLength: 255,
                        bind: {
                            readOnly: '{record.isDefaultCustomer}'
                        },
                        minLength: 1
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: me.strings.customerNumber,
                        name: 'number',
                        allowBlank: false,
                        bind: {
                            readOnly: '{record.isDefaultCustomer}'
                        },
                        maxLength: 255
                    },
                    {
                        xtype: 'textfield',
                        listeners: {
                            afterrender: function (cmp) {
                                // Gets the fiel label and registers tooltip for it
                                var label = cmp && cmp.labelEl;
                                if (!label) {
                                    return;
                                }
                                me.domainLabelInfoTooltip = Ext.create('Ext.tip.ToolTip', {
                                    target: label,
                                    title: '',
                                    autoHide: false,
                                    closable: true,
                                    html: Editor.data.l10n.clients.domainInfoTooltip
                                });
                            }
                        },
                        fieldLabel: me.strings.domain,
                        name: 'domain',
                        reference: 'customerDomain',
                        publishes: 'value',
                        labelClsExtra: 'lableInfoIcon',
                        itemId: 'openIdDomain',
                        disabled: ! isOpenIdEditor
                    }
                ]
            });
        }

        if (Editor.app.authenticatedUser.isAllowed('customerTmAssoc')) {
            allowedTabs.push({
                xtype: 'customerTmAssoc'
            });
        }

        allowedTabs.push({
            xtype: 'adminUserAssoc',
            bind: {
                customer: '{record.id}',
                disabled: '{isNewRecord}'
            }
        });

        if (customerConfigAllowed) {
            allowedTabs.push({
                xtype: 'adminConfigGrid',
                store: 'admin.CustomerConfig',
                title: me.strings.configTabTitle,
                bind: {
                    extraParams: {
                        customerId: '{record.id}'
                    }
                }
            });
        }

        if (isOpenIdEditor) {
            allowedTabs.push({
                xtype: 'openIdPanel',
                tabConfig: {
                    style: {
                        // to enable tooltip when the tabpanel is disabled
                        pointerEvents: 'all'
                    },
                    tooltip: me.strings.openIdTabPanelDisabledTooltip
                },
                bind: {
                    disabled: '{!customerDomain.value}'
                }
            });
        }

        let config = {
            title: me.title, //see EXT6UPD-9
            tooltip: Editor.data.l10n.clients.tooltip,
            items: [
                {
                    xtype: 'gridpanel',
                    cls: 'customerPanelGrid',
                    border: 0,
                    itemId: 'customerPanelGrid',
                    stateful: true,
                    stateId: 'editor.customerPanelGrid',
                    flex: 0.3,
                    region: 'center',
                    split: true,
                    reference: 'list',
                    resizable: false,
                    title: '',
                    bind: {
                        store: 'customersStore'
                    },
                    dockedItems: [
                        {
                            xtype: 'activeFiltersToolbar',
                        }
                    ],
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
                    activateGridColumnFilter: Editor.view.admin.TaskGrid.prototype.activateGridColumnFilter,
                    getColumnFilter: Editor.view.admin.TaskGrid.prototype.getColumnFilter,
                    columns: [
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'id',
                            text: 'Id',
                            width: 20,
                            filter: {
                                type: 'number'
                            }
                        },
                        {
                            xtype: 'actioncolumn',
                            text: me.strings.actionColumn,
                            minWidth: 90,
                            sortable: false,
                            items: [
                                {
                                    glyph: 'f044@FontAwesome5FreeSolid',
                                    tooltip: me.strings.customerEditActionIcon,
                                    scope: 'controller',
                                    handler: 'onCustomerEditClick',
                                    hidden: ! canAddCustomer
                                },
                                {
                                    glyph: 'f1c3@FontAwesome5FreeSolid',
                                    tooltip: me.strings.export,
                                    scope: 'controller',
                                    handler: 'onTmExportClick',
                                    hidden: ! resourceExportAllowed
                                },
                                {
                                    glyph: 'f0c5@FontAwesome5FreeSolid',
                                    tooltip: Editor.data.l10n.clients.copy,
                                    scope: 'controller',
                                    handler: 'onCopyActionClick',
                                    hidden: ! canAddCustomer
                                },
                                {
                                    glyph: 'f2ed@FontAwesome5FreeSolid',
                                    tooltip: Editor.data.l10n.clients.delete,
                                    scope: 'controller',
                                    handler: 'remove',
                                    isDisabled: function(view, rowIndex, colIndex, item, record) {
                                        return !canDeleteCustomer || record.get('isDefaultCustomer');
                                    },
                                    hidden: ! canDeleteCustomer
                                }
                            ]
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'name',
                            text: me.strings.customerName,
                            filter: {
                                type: 'string'
                            },
                            renderer: v => Ext.String.htmlEncode(v)
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'number',
                            text: me.strings.customerNumber,
                            filter: {
                                type: 'string'
                            },
                            renderer: (v) => Ext.String.htmlEncode(v)
                        }
                    ]
                },
                {
                    xtype: 'tabpanel',
                    flex: 0.7,
                    region: 'east',
                    split: true,
                    layout: 'fit',
                    itemId: 'displayTabPanel',
                    bodyStyle: 'border: 0',
                    reference: 'display',
                    bind: {
                        disabled: '{!record}'
                    },
                    tabBar: {
                        layout: {
                            overflowHandler: 'menu'
                        }
                    },
                    items: allowedTabs
                }
            ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    enableOverflow: true,
                    dock: 'top',
                    items: [
                        {
                            xtype: 'button',
                            glyph: 'f2f1@FontAwesome5FreeSolid',
                            text: me.strings.reload,
                            tooltip: Editor.data.l10n.clients.refresh,
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
                            tooltip: Editor.data.l10n.clients.create,
                            hidden: ! canAddCustomer,
                            listeners: {
                                click: {
                                    fn: 'add',
                                    scope: 'controller'
                                }
                            }
                        },
                        {
                            xtype: 'button',
                            glyph: 'f1c3@FontAwesome5FreeSolid',
                            text: me.strings.export,
                            tooltip: Editor.data.l10n.clients.export,
                            hidden: !resourceExportAllowed,
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

    onDestroy: function () {
        if (this.domainLabelInfoTooltip && this.domainLabelInfoTooltip.destroy) {
            this.domainLabelInfoTooltip.destroy();
        }
        this.callParent(arguments);
    }

});