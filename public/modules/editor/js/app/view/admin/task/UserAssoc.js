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
 * the task user assoc panel used when editing an imported task
 */
Ext.define('Editor.view.admin.task.UserAssoc', {
    extend: 'Ext.panel.Panel',
    requires: [
        'Editor.view.admin.task.UserAssocGrid',
        'Editor.view.admin.task.UserAssocViewModel',
        'Ext.ux.DateTimeField'
    ],
    alias: 'widget.adminTaskUserAssoc',
    itemId: 'adminTaskUserAssoc',
    strings: {
        type: '#UT#Typ',
        fieldStep: '#UT#Workflowschritt',
        fieldState: '#UT#Status',
        fieldUser: '#UT#Benutzer',
        btnSave: '#UT#Speichern',
        btnCancel: '#UT#Abbrechen',
        formTitleAdd: '#UT#Benutzerzuweisung hinzufügen:',
        formTitleEdit: '#UT#Bearbeite Benutzer "{0}"',
        fieldDeadline: '#UT#Deadline',
        fieldSegmentrange: '#UT#Editierbare Segmente',
        fieldSegmentrangeInfo: '#UT#Bsp: 1-3,5,8-9 (Wenn die Rolle dieses Users das Editieren erlaubt und zu irgendeinem User dieser Rolle editierbare Segmente zugewiesen werden, dürfen auch alle anderen User dieser Rolle nur die Segmente editieren, die ihnen zugewiesen sind.)',
        deadlineDateInfoTooltip: '#UT#translate5 sendet standardmäßig 2 Tage vor und 2 Tage nach dem festgelegten Datum und der festgelegten Uhrzeit (+/- 10 Minuten) eine Fristerinnerung. Dies kann von Ihrem Administrator geändert werden.'
    },
    viewModel: {
        type: 'taskuserassoc'
    },
    title: '#UT#Benutzer-Plural',
    layout: 'border',
    bind: {
        disabled: '{!enablePanel}'
    },
    border: 0,
    initConfig: function (instanceConfig) {
        var me = this, config;

        config = {
            title: me.title, //see EXT6UPD-9
            items: [
                {
                    xtype: 'adminTaskUserAssocGrid', bind: {
                        //INFO: this will load only the users of the task when projectTaskGrid selection is changed
                        //override the store binding in the place where the component is used/defined
                        //the default usage is in the task properties panel
                        store: '{userAssoc}'
                    }, region: 'center'
                },
                {
                    xtype: 'container',
                    region: 'east',
                    autoScroll: true,
                    height: 'auto',
                    width: 300,
                    items: [
                        {
                            xtype: 'container',
                            itemId: 'editInfoOverlay',
                            cls: 'edit-info-overlay',
                            padding: 10,
                            bind: {
                                html: '{editInfoHtml}'
                            }
                        },
                        {
                            xtype: 'form',
                            title: me.strings.formTitleAdd,
                            hidden: true,
                            bodyPadding: 10,
                            region: 'east',
                            reference: 'assocForm',
                            itemId: 'userAssocForm',
                            defaults: {
                                labelAlign: 'top'
                            },
                            items: [
                                {
                                    anchor: '100%',
                                    xtype: 'combo',
                                    allowBlank: false,
                                    editable: false,
                                    forceSelection: true,
                                    queryMode: 'local',
                                    name: 'type',
                                    fieldLabel: me.strings.type,
                                    displayField: 'name',
                                    valueField: 'value',
                                    store: {
                                        fields: ['name', 'value'],
                                        data: [
                                            { name: 'Editor', value: 1 },
                                            { name: 'LSP', value: 2 },
                                        ]
                                    },
                                    listeners: {
                                        change: (fld, newValue) => newValue === 1 ? me.loadUsers() : me.loadCoordinators()
                                    }
                                },
                                {
                                    anchor: '100%',
                                    xtype: 'Editor.combobox',
                                    allowBlank: false,
                                    editable: false,
                                    forceSelection: true,
                                    queryMode: 'local',
                                    name: 'workflowStepName',
                                    fieldLabel: me.strings.fieldStep,
                                    valueField: 'id',
                                    bind: {
                                        store: '{steps}'
                                    }
                                },
                                {
                                    anchor: '100%',
                                    xtype: 'Editor.combobox',
                                    allowBlank: false,
                                    listConfig: {
                                        loadMask: false
                                    },
                                    store: {
                                        fields: ['userGuid', 'longUserName'],
                                        data: [] // Initially empty, will be set dynamically
                                    },
                                    forceSelection: true,
                                    anyMatch: true,
                                    queryMode: 'local',
                                    name: 'userGuid',
                                    displayField: 'longUserName',
                                    valueField: 'userGuid',
                                    fieldLabel: me.strings.fieldUser
                                },
                                {
                                    anchor: '100%',
                                    xtype: 'combo',
                                    allowBlank: false,
                                    editable: false,
                                    forceSelection: true,
                                    name: 'state',
                                    queryMode: 'local',
                                    fieldLabel: me.strings.fieldState,
                                    valueField: 'id',
                                    displayField: 'text',
                                    bind: {
                                        store: '{states}'
                                    },
                                    listConfig: {
                                        getInnerTpl: function () {
                                            // add css class to the selection item if the state is disabled.
                                            // disabled state is for example the auto-finish state
                                            return '<div class="{[values.disabled ? "x-item-disabled" : ""]}">{text}</div>';
                                        }
                                    },
                                    listeners: {
                                        beforeselect: me.onUserStateBeforeSelect
                                    }
                                },
                                {
                                    xtype: 'datetimefield',
                                    name: 'deadlineDate',
                                    format: Editor.DATE_HOUR_MINUTE_ISO_FORMAT,
                                    fieldLabel: me.strings.fieldDeadline,
                                    labelCls: 'labelInfoIcon',
                                    cls: 'userAssocLabelIconField',
                                    autoEl: {
                                        tag: 'span',
                                        'data-qtip': me.strings.deadlineDateInfoTooltip
                                    },
                                    anchor: '100%'
                                },
                                {
                                    xtype: 'textfield',
                                    itemId: 'segmentrange',
                                    name: 'segmentrange',
                                    fieldLabel: me.strings.fieldSegmentrange,
                                    labelCls: 'labelInfoIcon',
                                    cls: 'userAssocLabelIconField',
                                    bind: {
                                        disabled: '{disableRanges}'
                                    },
                                    autoEl: {
                                        tag: 'span',
                                        'data-qtip': me.strings.fieldSegmentrangeInfo
                                    },
                                    anchor: '100%'
                                }
                            ],
                            dockedItems: [
                                {
                                    xtype: 'toolbar',
                                    dock: 'bottom',
                                    ui: 'footer',
                                    items: [
                                        {
                                            xtype: 'tbfill'
                                        },
                                        {
                                            xtype: 'button',
                                            itemId: 'save-assoc-btn',
                                            glyph: 'f00c@FontAwesome5FreeSolid',
                                            text: me.strings.btnSave
                                        },
                                        {
                                            xtype: 'button',
                                            glyph: 'f00d@FontAwesome5FreeSolid',
                                            itemId: 'cancel-assoc-btn',
                                            text: me.strings.btnCancel
                                        }
                                    ]
                                }
                            ]
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
    /**
     * loads all or all available users into the dropdown, the store is reused to get the username to userguids
     */
    loadUsers: function () {
        const combo = this.down('combo[name=userGuid]'),
            record = this.down('form').getRecord();

        Ext.Ajax.request({
            url: Editor.data.restpath + 'user/combo/all',
            method: 'GET',
            success: function (response) {
                const data = Ext.decode(response.responseText);

                combo.setStore({
                    fields: ['userGuid', 'longUserName'],
                    data: data.rows
                })

                if (record && record.get('userGuid')) {
                    const userGuid = record.get('userGuid');
                    combo.setValue(userGuid);
                }
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    loadCoordinators: function (taskId = null, jobId = null) {
        const combo = this.down('combo[name=userGuid]'),
            record = this.down('form').getRecord();

        Ext.Ajax.request({
            url: Editor.data.restpath + (taskId && jobId ? `task/${taskId}/job/${jobId}/coordinators` : 'user/combo/coordinators'),
            method: 'GET',
            success: function (response) {
                const data = Ext.decode(response.responseText);

                combo.setStore({
                    fields: ['userGuid', 'longUserName'],
                    data: data.rows
                })

                if (record && record.get('userGuid')) {
                    const userGuid = record.get('userGuid');
                    combo.setValue(userGuid);
                }
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },
    /**
     * loads the given record into the userAssoc form
     * @param {Editor.data.model.admin.TaskUserAssoc} rec
     * @param {Editor.data.model.admin.Task} task
     */
    loadRecord: function (rec, task) {
        const me = this,
            edit = !rec.phantom,
            form = me.down('form'),
            userCombo = me.down('combo[name="userGuid"]'),
            typeCombo = form.down('combo[name="type"]'),
            workflowStepCombo = form.down('combo[name="workflowStepName"]'),
            segmentrange = form.down('textfield[name="segmentrange"]')
        ;

        form.loadRecord(rec);

        if (edit) {
            form.setTitle(Ext.String.format(me.strings.formTitleEdit, rec.get('longUserName')));

            rec.get('isLspJob') ? me.loadCoordinators(task.get('id'), rec.get('id')) : me.loadUsers();
        } else {
            form.setTitle(me.strings.formTitleAdd);
            me.loadUsers();
        }

        segmentrange.setDisabled(edit && rec.get('isLspJob'));

        userCombo.setVisible(! edit || rec.get('isLspJob'));
        userCombo.setDisabled(edit && ! rec.get('isLspJob'));

        workflowStepCombo.setVisible(! edit || ! rec.get('isLspJob'));
        workflowStepCombo.setDisabled(edit && rec.get('isLspJob'));

        typeCombo.setDisabled(edit);
        typeCombo.setVisible(! edit);
    },

    onUserStateBeforeSelect: function (combo, record) {
        // if record is loaded in the form or when we change the user state to the same state, allow selection
        if (combo.getValue() === null || combo.getValue() === record.get('id')) {
            return true;
        }
        // prevent selection if the record is disabled
        return !record.get('disabled');
    }
});