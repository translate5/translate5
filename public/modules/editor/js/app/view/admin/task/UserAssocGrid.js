
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
 * the task user assoc grid used when editing an imported task
 */
Ext.define('Editor.view.admin.task.UserAssocGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.adminTaskUserAssocGrid',
    cls: 'task-user-assoc-grid',
    itemId: 'adminTaskUserAssocGrid',
    controller: 'adminTaskUserAssocGrid',
    requires: [
        'Editor.view.admin.task.UserAssocGridWindowController'
    ],
    strings: {
        confirmDeleteTitle: '#UT#Eintrag löschen?',
        confirmDelete: '#UT#Soll dieser Eintrag wirklich gelöscht werden?',
        userGuidCol: '#UT#Benutzer',
        roleCol: '#UT#Rolle',
        stepCol: '#UT#Workflowschritt',
        segmentrangeCol: '#UT#Segmente',
        stateCol: '#UT#Status',
        addUser: '#UT#Hinzufügen',
        addUserTip: '#UT#Einen Benutzer dieser Aufgabe zuordnen.',
        removeUser: '#UT#Entfernen',
        removeUserTip: '#UT#Den gewählten Benutzer aus dieser Aufgabe entfernen.',
        save: '#UT#Änderungen speichern',
        reload: '#UT#Aktualisieren',
        cancel: '#UT#Abbrechen',
        assignmentDateLable: '#UT#Zuweisung',
        finishedDateLabel: '#UT#Abgeschlossen',
        deadlineDateLable: '#UT#Deadline',
        userSpecialProperties: '#UT#Spezial',
        notifyUsersTitle: '#UT#Zugewiesene Benutzer benachrichtigen?',
        notifyUsersMsg: '#UT#Sollen die zugewiesenen Benutzer über die Zuweisung der Aufgabe benachrichtigt werden?',
        userNotifySuccess: '#UT#Benutzer wurden erfolgreich per E-Mail benachrichtigt',
        notifyButtonText: '#UT#Benutzer benachrichtigen',
        notifyButtonTooltip: '#UT#Alle zugewiesenen Benutzer über ihre Zuweisung per E-Mail benachrichtigen',
    },
    states: {
        edit: '#UT#in Arbeit'
    },

    plugins: ['gridfilters'],
    initConfig: function (instanceConfig) {
        var me = this,
            config = {
                columns: [{
                    xtype: 'gridcolumn',
                    width: 230,
                    dataIndex: 'login',
                    renderer: function (v, meta, rec) {
                        if (Editor.data.debug) {
                            v = Ext.String.format('<a href="{0}session/?authhash={1}">{2}</a>', Editor.data.restpath, rec.get('staticAuthHash'), v);
                        }
                        return rec.get('surName') + ', ' + rec.get('firstName') + ' (' + v + ')';
                    },
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.userGuidCol
                }, {
                    xtype: 'gridcolumn',
                    width: 100,
                    hidden: true,
                    dataIndex: 'role',
                    renderer: function (v, meta, rec) {
                        var task = me.lookupViewModel().get('currentTask'),
                            vfm = task && task.getWorkflowMetaData(),
                            role = (vfm && vfm.roles && vfm.roles[v]) || v;
                        return role;
                    },
                    text: me.strings.roleCol
                }, {
                    xtype: 'gridcolumn',
                    width: 120,
                    dataIndex: 'workflowStepName',
                    renderer: function (v, meta, rec) {
                        var task = me.lookupViewModel().get('currentTask'),
                            vfm = task && task.getWorkflowMetaData(),
                            step = (vfm && vfm.steps && vfm.steps[v]) || v;
                        return step;
                    },
                    text: me.strings.stepCol
                }, {
                    xtype: 'gridcolumn',
                    width: 80,
                    dataIndex: 'segmentrange',
                    text: me.strings.segmentrangeCol
                }, {
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'state',
                    renderer: function (v, meta, rec) {
                        //is custom state translation needed
                        if (me.states[v]) {
                            return me.states[v];
                        }
                        var task = me.lookupViewModel().get('currentTask'),
                            vfm = task && task.getWorkflowMetaData(),
                            state = (vfm && vfm.mergedStates && vfm.mergedStates[v]) || v;
                        return state;
                    },
                    text: me.strings.stateCol
                }, {
                    xtype: 'datecolumn',
                    width: 90,
                    dataIndex: 'assignmentDate',
                    format: Editor.DATE_TIME_LOCALIZED_FORMAT,
                    text: me.strings.assignmentDateLable
                }, {
                    xtype: 'datecolumn',
                    width: 90,
                    dataIndex: 'finishedDate',
                    format: Editor.DATE_TIME_LOCALIZED_FORMAT,
                    text: me.strings.finishedDateLabel
                }, {
                    xtype: 'datecolumn',
                    width: 90,
                    dataIndex: 'deadlineDate',
                    format: Editor.DATE_TIME_LOCALIZED_FORMAT,
                    text: me.strings.deadlineDateLable
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [{
                        xtype: 'button',
                        glyph: 'f234@FontAwesome5FreeSolid',
                        itemId: 'add-user-btn',
                        text: me.strings.addUser,
                        tooltip: me.strings.addUserTip
                    }, {
                        xtype: 'button',
                        glyph: 'f503@FontAwesome5FreeSolid',
                        disabled: true,
                        itemId: 'remove-user-btn',
                        handler: function () {
                            Ext.Msg.confirm(me.strings.confirmDeleteTitle, me.strings.confirmDelete, function (btn) {
                                var toDelete = me.getSelectionModel().getSelection();
                                if (btn == 'yes') {
                                    me.fireEvent('confirmDelete', me, toDelete, this);
                                }
                            });
                        },
                        text: me.strings.removeUser,
                        tooltip: me.strings.removeUserTip
                    }, {
                        xtype: 'button',
                        itemId: 'reload-btn',
                        glyph: 'f2f1@FontAwesome5FreeSolid',
                        text: me.strings.reload
                    }, '-', {
                        xtype: 'button',
                        itemId: 'notifyAssociatedUsersBtn',
                        glyph: 'f674@FontAwesome5FreeSolid',
                        text: me.strings.notifyButtonText,
                        tooltip: me.strings.notifyButtonTooltip,
                    }, '->', {
                        xtype: 'button',
                        hidden: !Editor.app.authenticatedUser.isAllowed('editorWorkflowPrefsTask'),
                        itemId: 'userSpecialPropertiesBtn',
                        glyph: 'f509@FontAwesome5FreeSolid',
                        text: me.strings.userSpecialProperties
                    }]
                }]
            };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
