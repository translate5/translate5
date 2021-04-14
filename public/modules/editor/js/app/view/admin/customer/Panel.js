
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

Ext.define('Editor.view.admin.customer.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.customerPanel',
    requires: [
        'Editor.view.admin.customer.ViewModel',
        'Editor.view.admin.customer.ViewController',
        'Editor.view.admin.config.Grid'
    ],

    stores:['Editor.stores.admin.Customers'],
    controller: 'customerPanel',
    viewModel: {
        type: 'customerPanel'
    },
    listeners: {
        activate: {
            fn: 'reloadCustomerStore',//when customers panel is displayed,this function is executed in the ViewController
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
        editCustomerTitle:'#UT#Kunde bearbeiten',
        addCustomerTitle:'#UT#Kunde hinzufügen',
        saveCustomerMsg:'#UT#Kunde wird gespeichert...',
        customerSavedMsg:'#UT#Kunde gespeichert!',
        customerDeleteMsg:'#UT#Diesen Kunden löschen?',
        customerDeleteTitle:'#UT#Kunden löschen',
        customerDeletedMsg:'#UT#Kunde gelöscht',
        export:'#UT#Ressourcen-Nutzung Exportieren',
        domain:'#UT#translate5 Domain',
        openIdServer:'#UT#OpenId server',
        openIdIssuer:'#UT#OpenId Issuer',
        openIdClientId:'#UT#OpenId Benutzername',
        openIdClientSecret:'#UT#OpenId Passwort',
        openIdAuth2Url:'#UT#OpenId OAuth URL',
        defaultRolesGroupLabel: '#UT#Standardrollen',
        serverRolesGroupLabel: '#UT#Erlaubte Rollen',
        openIdRedirectLabel:'#UT#Verlinkter Text Loginseite',
        openIdRedirectCheckbox:'#UT#Anmeldeseite nicht anzeigen: Automatisch zum OpenID Connect-Server umleiten, wenn keine Benutzersitzung in translate5 vorhanden ist. Wenn diese Checkbox nicht aktiviert ist, wird der im untenstehenden Textfeld definierte Text auf der Loginseite von translate5 mit dem OpenID Connect Server verlinkt.',
        defaultRolesGroupLabelTooltip: '#UT#Standardsystemrollen werden verwendet, wenn der OpenId-Server keine Systemrollen für den Benutzer übergibt, der sich anmeldet.',
        serverRolesGroupLabelTooltip: '#UT#Systemrollen, die der OpenID-Server in translate5 festlegen darf.',
        propertiesTabPanelTitle: '#UT#Eigenschaften',
        configTabTitle:'#UT#Standardkonfiguration des Systems',
        actionColumn:'#UT#Aktionen',
        customerEditActionIcon:'#UT#Kunden bearbeiten'
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
            roles=[];
        
        Ext.Object.each(Editor.data.app.roles, function(key, value) {
            //if the role is not setable for the user, do not create an check box for it
            if(!value.setable){
                return;
            }
            roles.push({
                boxLabel: value.label, 
                name: 'roles_helper', 
                value: key,
                handler: me.roleCheckChange
            });
        });
        var config = {
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
                        columns: [{
                            xtype: 'gridcolumn',
                            dataIndex: 'id',
                            text: 'Id',
                            width: 20,
                            filter: {
                                type: 'number'
                            }
                        },
                            {
                                xtype: 'gridcolumn',
                                dataIndex: 'name',
                                text: me.strings.customerName,
                                filter: {
                                    type: 'string'
                                }
                            },
                            {
                                xtype: 'gridcolumn',
                                dataIndex: 'number',
                                text:  me.strings.customerNumber,
                                filter: {
                                    type: 'string'
                                }
                            },{
                                xtype: 'actioncolumn',
                                text:  me.strings.actionColumn,
                                menuDisabled: true,//must be disabled, because of disappearing filter menu entry on missing filter
                                sortable: false,
                                items:[{
                                    glyph: 'f044@FontAwesome5FreeSolid',
                                    tooltip: me.strings.customerEditActionIcon,
                                    handler:me.onCustomerEditClick
                                },{
                                    glyph: 'f1c3@FontAwesome5FreeSolid',
                                    tooltip: me.strings.export,
                                    handler:me.onTmExportClick
                                }]
                            }
                        ],
                        listeners: {
                            itemdblclick: {
                                fn: 'dblclick',
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
                        viewConfig: {
                            listeners: {
                                beforerefresh: 'onViewBeforeRefresh'
                            }
                        }
                    },
                    {
                        xtype: 'tabpanel',
                        flex: 0.7,
                        region: 'east',
                        split: true,
                        reference: 'display',
                        width: 150,
                        bodyBorder: true,
                        scrollable:true,
                        items: [
                            {
                                xtype: 'form',
                                reference: 'form',
                                bodyPadding: 10,
                                tools:[{
                                    itemId: 'home',
                                    type: 'home',
                                    cls:'tools-help-icon',
                                    qtip: 'OpenID connect',
                                    handler: function(panel, tool, event) {
                                        window.open('https://confluence.translate5.net/display/BUS/OpenID+connect+in+translate5', '_blank');
                                    }
                                }],
                                fieldDefaults: {
                                    anchor: '1'
                                },
                                title:me.strings.propertiesTabPanelTitle,
                                bind: {
                                    disabled: '{!record}'
                                },
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
                                        itemId:'openIdDomain'
                                    },{
                                        xtype:'fieldset',
                                        itemId:'openIdFieldset',
                                        collapsible: true,
                                        collapsed: true,
                                        title:'OpenId Connect',
                                        items:[
                                            {
                                                xtype:'textfield',
                                                fieldLabel:me.strings.openIdServer,
                                                vtype: 'url',
                                                name:'openIdServer',
                                                setAllowBlank:me.setFieldAllowBlank,
                                                listeners: {
                                                    change: {
                                                        fn: 'onOpenIdFieldChange',
                                                        scope: 'controller'
                                                    }
                                                },
                                                bind:{
                                                    allowBlank:'{!isOpenIdRequired}'
                                                }
                                            },
                                            {
                                                xtype:'textfield',
                                                fieldLabel:me.strings.openIdIssuer,
                                                vtype: 'url',
                                                name:'openIdIssuer',
                                                setAllowBlank:me.setFieldAllowBlank,
                                                listeners: {
                                                    change: {
                                                        fn: 'onOpenIdFieldChange',
                                                        scope: 'controller'
                                                    }
                                                },
                                                bind:{
                                                    allowBlank:'{!isOpenIdRequired}'
                                                }
                                            },
                                            {
                                                xtype:'textfield',
                                                fieldLabel:me.strings.openIdClientId,
                                                name:'openIdClientId',
                                                setAllowBlank:me.setFieldAllowBlank,
                                                listeners: {
                                                    change: {
                                                        fn: 'onOpenIdFieldChange',
                                                        scope: 'controller'
                                                    }
                                                },
                                                bind:{
                                                    allowBlank:'{!isOpenIdRequired}'
                                                }
                                            },
                                            {
                                                xtype:'textfield',
                                                fieldLabel:me.strings.openIdClientSecret,
                                                name:'openIdClientSecret',
                                                setAllowBlank:me.setFieldAllowBlank,
                                                listeners: {
                                                    change: {
                                                        fn: 'onOpenIdFieldChange',
                                                        scope: 'controller'
                                                    }
                                                },
                                                bind:{
                                                    allowBlank:'{!isOpenIdRequired}'
                                                }
                                            },
                                            {
                                                xtype:'textfield',
                                                fieldLabel:me.strings.openIdAuth2Url,
                                                vtype: 'url',
                                                name:'openIdAuth2Url',
                                                setAllowBlank:me.setFieldAllowBlank,
                                                listeners: {
                                                    change: {
                                                        fn: 'onOpenIdFieldChange',
                                                        scope: 'controller'
                                                    }
                                                },
                                                bind:{
                                                    allowBlank:'{!isOpenIdRequired}'
                                                }
                                            },{
                                                xtype: 'hidden',
                                                name: 'openIdDefaultServerRoles'
                                            },{
                                                xtype: 'checkboxgroup',
                                                itemId: 'defaultRolesGroup',
                                                cls: 'x-check-group-alt',
                                                labelClsExtra: 'checkBoxLableInfoIconDefault',
                                                fieldLabel: me.strings.defaultRolesGroupLabel,
                                                autoEl: {
                                                    tag: 'span',
                                                    'data-qtip': me.strings.defaultRolesGroupLabelTooltip
                                                },
                                                items: roles,
                                                columns: 3
                                            },{
                                                xtype: 'hidden',
                                                name: 'openIdServerRoles'
                                            },{
                                                xtype: 'checkboxgroup',
                                                name: 'serverRolesGroup',
                                                itemId: 'serverRolesGroup',
                                                cls: 'x-check-group-alt',
                                                labelClsExtra: 'checkBoxLableInfoIconDefault',
                                                fieldLabel: me.strings.serverRolesGroupLabel,
                                                autoEl: {
                                                    tag: 'span',
                                                    'data-qtip': me.strings.serverRolesGroupLabelTooltip
                                                },
                                                items: roles,
                                                columns: 3
                                            },{
                                                xtype:'textfield',
                                                fieldLabel:me.strings.openIdRedirectLabel,
                                                name:'openIdRedirectLabel',
                                                setAllowBlank:me.setFieldAllowBlank
                                            },{
                                                xtype:'checkbox',
                                                boxLabel:me.strings.openIdRedirectCheckbox,
                                                name:'openIdRedirectCheckbox',
                                                inputValue:1,
                                                uncheckedValue:0,
                                                checked:1,
                                                listeners: {
                                                    change: {
                                                        fn: 'onOpenIdRedirectCheckboxChange',
                                                        scope: 'controller'
                                                    }
                                                }
                                            }
                                        ]
                                    },{
                                        xtype: 'container',
                                        padding: 10,
                                        layout: {
                                            type: 'hbox',
                                            align: 'middle',
                                            pack: 'center'
                                        },
                                        items: [
                                            {
                                                xtype: 'button',
                                                flex: 1,
                                                formBind: true,
                                                itemId: 'saveButton',
                                                reference: 'saveButton',
                                                margin: 5,
                                                text: me.strings.save,
                                                listeners: {
                                                    click: {
                                                        fn: 'save',
                                                        scope: 'controller'
                                                    }
                                                }
                                            },
                                            {
                                                xtype: 'button',
                                                flex: 1,
                                                itemId: 'cancelButton',
                                                margin: 5,
                                                text: me.strings.cancel,
                                                listeners: {
                                                    click: {
                                                        fn: 'cancelEdit',
                                                        scope: 'controller'
                                                    }
                                                }
                                            },
                                            {
                                                xtype: 'button',
                                                flex: 1,
                                                itemId: 'removeButton',
                                                margin: 5,
                                                text: me.strings.remove,
                                                disabled:true,
                                                reference: 'removeButton',
                                                listeners: {
                                                    click: {
                                                        fn: 'remove',
                                                        scope: 'controller'
                                                    }
                                                }
                                                
                                            }
                                        ]
                                    }
                                ]
                            },{
                                xtype: 'adminConfigGrid',
                                store:'admin.CustomerConfig',
                                title:me.strings.configTabTitle,
                                bind: {
                                    disabled: '{!record}',
                                    extraParams:{
                                        customerId : '{record.id}'
                                    }
                                }
                            }
                        ]
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
                                    click: me.onTmExportClick
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
    
    initComponent: function() {
        var me = this;
        me.callParent(arguments);
    },
    
    setFieldAllowBlank:function(value){
        this.allowBlank=value || this.isDisabled();
        this.up('form').isValid();
    },
    
    onViewBeforeRefresh: function(dataview, eOpts) {
        //workaround / fix for TMUE-11
        dataview.getSelectionModel().deselectAll();
    },
    
    /**
     * merge and save the checked roles into the hidden roles field
     * @param {Ext.form.field.Checkbox} box
     * @param {Boolean} checked
     */
    roleCheckChange: function(box, checked) {
        var roles = [],
            holder=box.up('checkboxgroup'),
            boxes = holder.query('checkbox[checked=true]'),
            holderMap={
                serverRolesGroup:'openIdServerRoles',
                defaultRolesGroup:'openIdDefaultServerRoles'
            };
        Ext.Array.forEach(boxes, function(box){
            roles.push(box.initialConfig.value);
        });
        box.up('form').down('hidden[name="'+holderMap[holder.getItemId()]+'"]').setValue(roles.join(','));
    },

    /***
     * On export action column click handler
     */
    onTmExportClick:function(view, cell, row, col, ev, record) {
        var me=this.up('customerPanel');
        me.exportCustomerResourceUsage(record && record.get('id'));
    },
    
    /***
     * Actio icon "edit customer" event handler
     */
    onCustomerEditClick:function(view, cell, row, col, ev, record) {
        var me=this.up('customerPanel'),
            controller = me.getController();
        controller.editCustomer(record);
    },
    
    /***
     * Generate excel for resource usage for the given customer. If the customer is not defined,
     * summ excel for all customers will be generated.
     */
    exportCustomerResourceUsage:function(id){
        var url = Editor.data.restpath+'customer/exportresource?format=resourceLogExport',
            extraParams = [];
        
        if(id){
            extraParams.push(Ext.urlEncode({customerId: id}));
        }

        // Fire before resources export event. 
        this.fireEvent('beforeExportCustomerResourceUsage',extraParams);

        Ext.each(extraParams, function(ob){
            url += '&'+ob;
        });

        window.open(url); 
    }
});