
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

/**
 * This is used for defining default user-task associations. This component exist in the customer overview (where the default assignments are defined)
 * and in the task import wizard, where the matching defaults are listed/assigned and can be edited.
 */
Ext.define('Editor.view.admin.user.AssocGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.adminUserAssocGrid',
    requires: [
    ],
    strings: {
        sourceLang: '#UT#Quellsprache',
        targetLang: '#UT#Zielsprache',
        userGuidCol: '#UT#Benutzer',
        roleCol: '#UT#Rolle',
        addUser: '#UT#Hinzufügen',
        addUserTip: '#UT#Einen Benutzer dieser Aufgabe zuordnen.',
        removeUser: '#UT#Entfernen',
        removeUserTip: '#UT#Den gewählten Benutzer aus dieser Aufgabe entfernen.',
        save: '#UT#Änderungen speichern',
        reload: '#UT#Aktualisieren',
        cancel: '#UT#Abbrechen',
        deadlineDateLable: '#UT#Deadline',
        notifyButtonText: '#UT#Benutzer nach Import benachrichtigen',
        notifyButtonTooltip: '#UT#Alle zugewiesenen Benutzer über ihre Zuweisung per E-Mail benachrichtigen',
        workflowStepNameCol:'#UT#Workflow-Schritt',
        fieldWorkflow: '#UT#Workflow'
    },

    viewModel: {
        type: 'adminUserAssoc'
    },

    store:'Editor.store.admin.UserAssocDefault',

    initConfig: function (instanceConfig) {
        var me = this,
            config = {
                features:[{
                    ftype: 'grouping',
                    startCollapsed: false,
                    groupHeaderTpl: '{columnName}: {name}'
                }],
                columns: [{
                    xtype: 'gridcolumn',
                    dataIndex: 'sourceLang',
                    flex:1,
                    renderer:Editor.util.Util.gridColumnLanguageRenderer,
                    filter: {
                        type: 'list',
                        options: Editor.data.languages,
                        phpMode: false
                    },
                    text: me.strings.sourceLang
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'targetLang',
                    flex:1,
                    renderer:Editor.util.Util.gridColumnLanguageRenderer,
                    filter: {
                        type: 'list',
                        options: Editor.data.languages,
                        phpMode: false
                    },
                    text: me.strings.targetLang
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'userGuid',
                    flex:1,
                    renderer:me.userRenderer,
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.userGuidCol
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'workflowStepName',
                    flex:1,
                    text: me.strings.workflowStepNameCol,
                    renderer:Editor.util.Util.getWorkflowStepNameTranslated,
                    filter: {
                        type: 'list',

                        store:'admin.WorkflowSteps'
                    }
                } ,{
                    xtype: 'gridcolumn',
                    dataIndex: 'role',
                    flex:1,
                    hidden:true,
                    text: me.strings.roleCol
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'deadlineDate',
                    flex:1,
                    text: me.strings.deadlineDateLable
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    itemId:'assocGridTopToolbar',
                    dock: 'top',
                    border: 0,
                    enableOverflow: true,
                    items: [{
                        xtype: 'combo',
                        forceSelection: true,
                        queryMode: 'local',
                        itemId: 'workflowCombo',
                        fieldLabel: me.strings.fieldWorkflow,
                        valueField: 'id',
                        displayField: 'label',
                        allowBlank: false,
                        bind: {
                            store:'{workflow}'
                        }
                    }, {
                        xtype: 'checkbox',
                        hidden:true,
                        itemId: 'notifyAssociatedUsersCheckBox',
                        glyph: 'f674@FontAwesome5FreeSolid',
                        fieldLabel: me.strings.notifyButtonText,
                        tooltip: me.strings.notifyButtonTooltip
                    }, '-', {
                        xtype: 'button',
                        glyph: 'f234@FontAwesome5FreeSolid',
                        itemId: 'addAssocBtn',
                        text: me.strings.addUser,
                        tooltip: me.strings.addUserTip
                    }, {
                        xtype: 'button',
                        glyph: 'f503@FontAwesome5FreeSolid',
                        disabled: true,
                        bind:{
                            disabled: '{!selectedAssocRecord}'
                        },
                        itemId: 'deleteAssocBtn',
                        text: me.strings.removeUser,
                        tooltip: me.strings.removeUserTip
                    }]
                }]
            };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /**
     * renders the value of the users columns
     * @param {String} val
     * @returns {String}
     */
    userRenderer:function (val){
        var store = Ext.StoreManager.get('admin.Users'),
            idx = store.find('userGuid', val);
        if(idx < 0) {
            return '';
        }
        var user = store.getAt(idx);
        if (user) {
            return Ext.String.htmlEncode(user.get('surName')+', '+user.get('firstName')+' ('+user.get('login')+')');
        }
        return '';
    }
});
