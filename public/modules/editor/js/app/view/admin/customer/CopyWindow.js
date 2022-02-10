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

Ext.define('Editor.view.admin.customer.CopyWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.copyCustomerWindow',
    itemId: 'copyCustomerWindow',
    controller: 'copyCustomerWindow',
    viewModel: {
        type: 'copyCustomerWindow'
    },
    requires: [
        'Editor.view.admin.customer.CopyWindowViewController',
        'Editor.view.admin.customer.CopyWindowViewModel'
    ],
    listen: {
        store:{
            '#copyCustomers':{
                load:'onCopyCustomersStoreLoad'
            }
        }
    },
    autoHeight: true,
    autoScroll: true,
    modal: true,
    bodyPadding: 10,
    strings: {
        title: '#UT#Kundeneinstellungen kopieren',
        copyUserAssocLabel:'#UT#Standard-Benutzerzuweisungen kopieren von',
        copyUserAssocTooltip:'#UT#Alle Standard-Benutzerzuweisungen des ausgewählten Kunden, einschließlich des ausgewählten Mehrbenutzermodus und Workflows, in den aktuellen Kunden kopieren',
        copyConfigLabel:'#UT#Konfigurationen kopieren von',
        copyConfigTooltip:'#UT#Alle überschriebenen Systemkonfigurationen des ausgewählten Kunden in den aktuellen Kunden kopieren',
        copySuccess:'#UT#Kopieren erfolgreich',
        copyFromText:'#UT#Kopieren von',
        copyBtnText:'#UT#Kopieren'
    },
    record: null,

    initConfig: function (instanceConfig) {
        var me = this,
            config = {
                title:me.strings.title,
                items: [{
                    xtype: 'fieldcontainer',
                    layout: {
                        type: 'hbox',
                        align: 'center'
                    },
                    items:[{
                        xtype: 'customersCombo',
                        name: 'copyDefaultAssignmentsCustomer',
                        itemId: 'copyDefaultAssignmentsCustomer',
                        store:null,
                        bind:{
                            store:'{copyCustomers}',
                        },
                        margin: '0 10 0 0',
                        allowBlank:true,
                        labelCls: 'labelInfoIcon',
                        autoEl: {
                            tag: 'span',
                            'data-qtip': me.strings.copyUserAssocTooltip
                        },
                        fieldLabel:me.strings.copyUserAssocLabel
                    },{
                        xtype: 'button',
                        glyph: 'f00c@FontAwesome5FreeSolid',
                        margin: '0 0 0 10',
                        listeners: {
                            click:'onAddCopyDefaultAssignmentsCustomerClick'
                        },
                        text: me.strings.copyBtnText
                    }]
                }, {
                    xtype: 'fieldcontainer',
                    layout: {
                        type: 'hbox',
                        align: 'center'
                    },
                    items: [{
                        xtype: 'customersCombo',
                        name: 'copyConfigCustomer',
                        itemId: 'copyConfigCustomer',
                        store:null,
                        bind:{
                            store:'{copyCustomers}',
                        },
                        margin: '0 10 0 0',
                        allowBlank: true,
                        labelCls: 'labelInfoIcon',
                        autoEl: {
                            tag: 'span',
                            'data-qtip': me.strings.copyConfigTooltip
                        },
                        fieldLabel: me.strings.copyConfigLabel
                    },{
                        xtype: 'button',
                        glyph: 'f00c@FontAwesome5FreeSolid',
                        margin: '0 0 0 10',
                        listeners: {
                            click:'onAddCopyConfigCustomerClick'
                        },
                        text: me.strings.copyBtnText
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    getRecord:function (){
        return this.record;
    },

    setRecord:function (newRec){
        this.record = newRec;
    }
});