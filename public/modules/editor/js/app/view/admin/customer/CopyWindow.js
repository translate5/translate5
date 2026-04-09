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
    layout: {
        type: 'vbox',
        align: 'stretch'
    },
    bodyPadding: 15,
    autoHeight: true,
    autoScroll: true,
    modal: true,
    shrinkWrapDock:true, // resize the window based on the title
    strings: {
        title: '#UT#Copy client settings to current client',
        copyUserAssocLabel:'#UT#Copy default user assignments from',
        copyUserAssocTooltip:'#UT#Copy all user assignment defaults from the selected client to the current client, including selected multi-user mode and selected workflow for this client',
        copyConfigLabel:'#UT#Copy configuration from',
        copyConfigTooltip:'#UT#Copy all overridden system settings from the selected client to the current client, except <i>Initial workflow on task creation</i> and <i>Multi user task editing mode</i>',
        copySuccess:'#UT#Copying successfully completed',
        copyFromText:'#UT#Copy from',
        copyBtnText:'#UT#Copy',
        noCustomerSelected:'#UT#At least one client must be selected'
    },
    record: null,

    initConfig: function (instanceConfig) {
        var me = this,
            config = {
                title:me.strings.title,
                items: [{
                    xtype: 'displayfield',
                    hideLabel:true,
                    fieldCls: 'lableInfoIcon',
                    autoEl: {
                        tag: 'span',
                        'data-qtip': me.strings.copyUserAssocTooltip
                    },
                    value:me.strings.copyUserAssocLabel
                },{
                    xtype: 'customersCombo',
                    name: 'copyDefaultAssignmentsCustomer',
                    itemId: 'copyDefaultAssignmentsCustomer',
                    store:null,
                    allowBlank: true,
                    bind:{
                        store:'{copyCustomers}',
                    },
                    hideLabel:true
                },{
                    xtype: 'displayfield',
                    hideLabel: true,
                    fieldCls: 'lableInfoIcon',
                    autoEl: {
                        tag: 'span',
                        'data-qtip': me.strings.copyConfigTooltip
                    },
                    value: me.strings.copyConfigLabel
                }, {
                    xtype: 'customersCombo',
                    name: 'copyConfigCustomer',
                    itemId: 'copyConfigCustomer',
                    store:null,
                    allowBlank: true,
                    bind:{
                        store:'{copyCustomers}',
                    },
                    hideLabel:true
                }],
                bbar:[{
                    xtype: 'button',
                    glyph: 'f00c@FontAwesome5FreeSolid',
                    listeners: {
                        click:'onCopyButtonClick'
                    },
                    text: me.strings.copyBtnText
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