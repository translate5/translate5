/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
Ext.define('Editor.view.admin.UserChooseWindow', {
    extend : 'Ext.window.Window',
    alias : 'widget.adminUserChooseWindow',
    requires: ['Editor.view.admin.user.ChooserGridFilter'],
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
    initComponent : function() {
        var me = this,
            store = Ext.create('Editor.store.admin.Users', {
                storeId: 'adminUsersForTask',
                pageSize: 15,
                proxy : {
                    type : 'rest',
                    url: Editor.data.restpath+'user/',
                    reader : {
                        root: 'rows',
                        type : 'json'
                    }
                }
            }),
            wf = me.task.getWorkflowMetaData(),
            states = [],
            roles = [];
        Ext.Object.each(wf.states, function(key, state) {
            states.push([key, state]);
        });
        Ext.Object.each(wf.roles, function(key, role) {
            roles.push([key, role]);
        });
        if(me.excludeLogins && me.excludeLogins.length > 0) {
            store.getProxy().extraParams = {
                defaultFilter: '[{"field":"login","type":"notInList","value":["'+me.excludeLogins.join('","')+'"]}]'
            };
        }
        Ext.applyIf(me, {
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
                    dataIndex: 'login'
                },{
                    xtype: 'gridcolumn',
                    text: me.strings.surNameCol,
                    width: 100,
                    dataIndex: 'surName'
                },{
                    xtype: 'gridcolumn',
                    text: me.strings.firstNameCol,
                    width: 100,
                    dataIndex: 'firstName'
                },{
                    text: me.strings.emailCol,
                    xtype: 'gridcolumn',
                    width: 160,
                    dataIndex: 'email'
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
        });

        me.callParent(arguments);
    }
});