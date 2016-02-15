
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.UserChooseWindow', {
    extend : 'Ext.window.Window',
    alias : 'widget.adminUserChooseWindow',
    plugins: ['gridfilters'],
    itemId : 'adminUserChooseWindow',
    title : '#UT#Einen oder mehrere Benutzer auswählen',
    strings: {
        loginCol: '#UT#Login',
        firstNameCol: '#UT#Vorname',
        surNameCol: '#UT#Nachname',
        emailCol: '#UT#E-Mail',
        addBtn: '#UT#Benutzer hinzufügen',
        cancelBtn: '#UT#Abbrechen'
    },
    height : 490,
    width : 520,
    layout: 'fit',
    modal : true,
    initConfig : function(instanceConfig) {
        var me = this,
            store = Ext.create('Editor.store.admin.Users', {
                storeId: 'adminUsersForTask',
                pageSize: 15,
                proxy : {
                    type : 'rest',
                    url: Editor.data.restpath+'user/',
                    reader : {
                        rootProperty: 'rows',
                        type : 'json'
                    }
                }
            }),
            wf = me.task.getWorkflowMetaData(),
            states = [],
            config,
            roles = [];
        Ext.Object.each(wf.states, function(key, state) {
            states.push([key, state]);
        });
        Ext.Object.each(wf.roles, function(key, role) {
            roles.push([key, role]);
        });
        if(me.excludeLogins && me.excludeLogins.length > 0) {
            store.getProxy().extraParams = {
                defaultFilter: '[{"property":"login","type":"notInList","value":["'+me.excludeLogins.join('","')+'"]}]'
            };
        }
        config = {
            items : [{
                xtype: 'grid',
                features: [{
                    ftype: 'adminUserChooserGridFilter'
                }],
                store: store,
                selModel: Ext.create('Ext.selection.CheckboxModel', {}),
                columns: [{
                    xtype: 'gridcolumn',
                    width: 100,
                    text: me.strings.loginCol,
                    dataIndex: 'login',
                    filter: {
                        type: 'string'
                    }
                },{
                    xtype: 'gridcolumn',
                    text: me.strings.surNameCol,
                    width: 100,
                    dataIndex: 'surName',
                    filter: {
                        type: 'string'
                    }
                },{
                    xtype: 'gridcolumn',
                    text: me.strings.firstNameCol,
                    width: 100,
                    dataIndex: 'firstName',
                    filter: {
                        type: 'string'
                    }
                },{
                    text: me.strings.emailCol,
                    xtype: 'gridcolumn',
                    width: 160,
                    dataIndex: 'email',
                    filter: {
                        type: 'string'
                    }
                }]
            }],
            dockedItems : [{
                xtype: 'container',
                dock : 'top',
                items:[{
                    xtype: 'fieldset',
                    titel: '#UT# Vorbelegung',
                    items:[{
                        xtype: 'combo',
                        fieldLabel: '#UT#Status',
                        editable: false,
                        //displayField: 'label',
                        //valueField: 'id',
                        forceSelection: true,
                        queryMode: 'local',
                        store: states
                    },{
                        xtype: 'combo',
                        fieldLabel: '#UT#Rolle',
                        editable: false,
                        //displayField: 'label',
                        //valueField: 'id',
                        forceSelection: true,
                        queryMode: 'local',
                        store: roles
                    }]
                }]
            },{
                xtype : 'toolbar',
                dock : 'bottom',
                ui: 'footer',
                layout: {
                    type: 'hbox',
                    pack: 'end'
                },
                items : [{
                    xtype : 'button',
                    iconCls : 'ico-user-add',
                    itemId : 'add-user-btn',
                    text : me.strings.addBtn
                }, {
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'cancel-btn',
                    text : me.strings.cancelBtn
                }]
            },{
                xtype: 'pagingtoolbar',
                store: store,
                dock: 'bottom',
                displayInfo: true
            }]
        };

        if (instanceConfig) {
            me.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});