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

Ext.define('Editor.view.admin.task.filter.FilterWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.editorAdminTaskFilterFilterWindow',
    requires: [
        'Editor.view.admin.task.filter.FilterWindowViewController',
        'Editor.view.admin.task.filter.DateFilter',
        'Editor.view.form.field.TagGroup'
    ],
    controller: 'editorAdminTaskFilterFilterWindow',
    itemId: 'editorAdminTaskFilterFilterWindow',
    strings: {
        workflowStateFilterLabel: '#UT#Workflow-Status',
        workflowUserRoleLabel: '#UT#Workflowschritt-Typ',
        workflowUserRoleTip: '#UT#Filtert alle Aufgaben, denen ein Job mit einem Workflow-Schritt des gewählten Typs zugewiesen ist',
        workflowLabel: '#UT#Workflow',
        workflowStepLabel: '#UT#Workflow-Schritt',
        workflowStepTip: '#UT#Filtert alle Aufgaben, denen ein Job mit dem gewählten Workflow-Schritt zugewiesen ist',
        userNameLabel: '#UT#Zugewiesene/r Benutzer',
        applyBtn: '#UT#Anwenden',
        cancelBtn: '#UT#Abbrechen',
        title: '#UT#Erweiterte Filter',
        anonymizedUsersInfo: '#UT#Anonymisierte Benutzer nicht auswählbar',
        gridFiltersInfo: '#UT#Weitere Filter im Kopf jeder Spalte',
        assignmentDateText: '#UT#Benutzer-Zuweisungsdatum',
        finishedDateText: '#UT#Benutzer-Abschlussdatum',
        deadlineDateText: '#UT#Benutzer-Deadline/s',
        type: '#UT#Typ',
        translatorStep: '#UT#Übersetzung',
        reviewerStep: '#UT#Lektorat',
        translatorCheckStep: '#UT#Finales Lektorat',
        visitorStep: '#UT#Nur lesen',
        min:'#UT#min',
        max:'#UT#max',
        matchRate:'#UT#Matchrate',
        resourceType:'#UT#Typ der Ressource',
        langResources:'#UT#Sprachressourcen'
    },
    listeners:{
        render:'onFilterWindowRender'
    },
    bodyPadding: '10 20 12 20',
    layout: {
        type: 'anchor',
    },
    border:false,
    width: 900,
    height: Editor.data.statistics.enabled ? 710 : 600,

    initConfig: function (instanceConfig) {
        var me = this,
            config = {
                bind: {
                    title: '{l10n.tasksGrid.filterWindow.title}'
                },
                scrollable: true,
                defaults: {
                    xtype: 'fieldset',
                    width: '100%',
                    margin: '0 0 10 0',
                    padding: '0 15 15 15'
                },
                items: [
                    {
                        title: Editor.data.l10n.tasksGrid.filterWindow.fieldset1,
                        items: {
                            xtype: 'tagfield',
                            margin: 0,
                            width: '100%',
                            name: 'workflow',
                            itemId: 'workflow',
                            typeAhead: true,
                            queryMode: 'local',
                            valueField: 'id',
                            displayField: 'label',
                            store: 'admin.Workflow',
                            bind: {
                                fieldLabel: '{l10n.tasksGrid.filterWindow.workflowLabel}'
                            },
                            labelAlign: 'top',
                            labelWidth: '100%',
                            filter: {
                                operator: 'in',
                                property: 'workflow',
                                type: 'list',
                                textLabel: Editor.data.l10n.tasksGrid.filterWindow.workflowLabel
                            },
                            listeners: {
                                afterrender: cmp => cmp.fireEvent('change', cmp),
                                change: 'workflowFieldChange'
                            }
                        }
                    },
                    {
                        title: Editor.data.l10n.tasksGrid.filterWindow.fieldset2,
                        padding: '0 15 6 15',
                        items: [
                            {
                                xtype: 'container',
                                layout: {
                                    type: 'hbox',
                                    columns: 2
                                },
                                defaults: {
                                    flex: 1
                                },
                                width: '100%',
                                items: [
                                    {
                                        xtype: 'tagfield',
                                        name: 'userName',
                                        itemId: 'userName',
                                        typeAhead: true,
                                        queryMode: 'local',
                                        displayField: 'longUserName',
                                        valueField: 'userGuid',
                                        store: 'admin.UsersList',
                                        bind: {
                                            fieldLabel: '{l10n.tasksGrid.filterWindow.userNameLabel}¹'
                                        },
                                        labelAlign: 'top',
                                        labelWidth: '100%',
                                        margin: '0 15 1 0',
                                        filter: {
                                            operator: 'in',
                                            property: 'userName',
                                            type: 'list',
                                            textLabel: Editor.data.l10n.tasksGrid.filterWindow.userNameLabel
                                        }
                                    },
                                    {
                                        xtype: 'tagfield',
                                        name: 'workflowUserRole',
                                        itemId: 'workflowUserRole',
                                        typeAhead: true,
                                        queryMode: 'local',
                                        valueField: 'id',
                                        displayField: 'label',
                                        store: Ext.create('Ext.data.ChainedStore', {
                                            source: 'admin.WorkflowUserRoles',
                                            filters: [ (item) => item.id !== 'visualApprover' ]
                                        }),
                                        bind: {
                                            fieldLabel: '{l10n.tasksGrid.filterWindow.workflowUserRoleLabel}'
                                        },
                                        afterLabelTextTpl: ' <img src="/modules/editor/images/information.png" class="info_image" data-qtip="' +
                                            Editor.data.l10n.tasksGrid.filterWindow.workflowUserRoleTip + '"></img>',
                                        labelWidth: '100%',
                                        labelAlign: 'top',
                                        filter: {
                                            operator: 'in',
                                            property: 'workflowUserRole',
                                            type: 'list',
                                            textLabel: Editor.data.l10n.tasksGrid.filterWindow.workflowUserRoleLabel
                                        },
                                        listeners: {
                                            beforerender: function (cmp) {
                                                var store = cmp.getStore();
                                                ['translator', 'reviewer', 'translatorCheck', 'visitor'].forEach(function (key) {
                                                    var rec = store.getById(key);
                                                    if (rec) {
                                                        rec.set('label', Editor.data.l10n.tasksGrid.filterWindow.type + ': ' + Editor.data.l10n.tasksGrid.filterWindow[key + 'Step']);
                                                    }
                                                });
                                            }
                                        }
                                    },
                                ]
                            },
                            {
                                xtype: 'container',
                                layout: {
                                    type: 'hbox',
                                    columns: 2
                                },
                                defaults: {
                                    flex: 1,
                                    margin: '0 0 5 0'
                                },
                                width: '100%',
                                items: [
                                    {
                                        xtype: 'tagfield',
                                        name: 'workflowState',
                                        itemId: 'workflowState',
                                        typeAhead: true,
                                        queryMode: 'local',
                                        valueField: 'id',
                                        displayField: 'label',
                                        store: 'admin.WorkflowState',
                                        bind: {
                                            fieldLabel: '{l10n.tasksGrid.filterWindow.workflowStateFilterLabel}'
                                        },
                                        labelAlign: 'top',
                                        labelWidth: '100%',
                                        margin: '0 15 1 0',
                                        filter: {
                                            operator: 'in',
                                            property: 'workflowState',
                                            type: 'list',
                                            textLabel: Editor.data.l10n.tasksGrid.filterWindow.workflowStateFilterLabel
                                        }
                                    },
                                    {
                                        xtype: 'taggroup',
                                        name: 'workflowStep',
                                        itemId: 'workflowStep',
                                        typeAhead: true,
                                        queryMode: 'local',
                                        valueField: 'id',
                                        displayField: 'text',
                                        bind: {
                                            fieldLabel: '{l10n.tasksGrid.filterWindow.workflowStepLabel}'
                                        },
                                        afterLabelTextTpl: ' <img src="/modules/editor/images/information.png" class="info_image" data-qtip="' +
                                            Editor.data.l10n.tasksGrid.filterWindow.workflowStepTip + '"></img>',
                                        labelAlign: 'top',
                                        labelWidth: ' 100%',
                                        filter: {
                                            operator: 'in',
                                            property: 'workflowStep',
                                            type: 'list',
                                            textLabel: Editor.data.l10n.tasksGrid.filterWindow.workflowStepLabel
                                        }
                                    }
                                ]
                            },
                            {
                                xtype: 'container',
                                layout: {
                                    type: 'hbox',
                                    columns: 3
                                },
                                defaults: {
                                    flex: 1,
                                    margin: '0 16 0 0',
                                    padding: '5 15 5 15'
                                },
                                width: '100%',
                                items: [
                                    {
                                        xtype: 'editorAdminTaskFilterDateFilter',
                                        filterLabel: Editor.data.l10n.tasksGrid.filterWindow.assignmentDateText,
                                        filterProperty: 'assignmentDate',
                                        itemId: 'assignmentDate',
                                        title: Editor.data.l10n.tasksGrid.filterWindow.assignmentDateText
                                    },
                                    {
                                        xtype: 'editorAdminTaskFilterDateFilter',
                                        filterLabel: Editor.data.l10n.tasksGrid.filterWindow.finishedDateText,
                                        filterProperty: 'finishedDate',
                                        itemId: 'finishedDate',
                                        title: Editor.data.l10n.tasksGrid.filterWindow.finishedDateText
                                    },
                                    {
                                        xtype: 'editorAdminTaskFilterDateFilter',
                                        filterLabel: Editor.data.l10n.tasksGrid.filterWindow.deadlineDateText,
                                        filterProperty: 'deadlineDate',
                                        itemId: 'deadlineDate',
                                        margin: '0 2 10 0',
                                        title: Editor.data.l10n.tasksGrid.filterWindow.deadlineDateText
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        title: Editor.data.l10n.tasksGrid.filterWindow.fieldset3,
                        hidden: !Editor.data.statistics.enabled,
                        margin: '0 0 0 0',
                        padding: '0 15 15 15',
                        itemId: 'editorAdminTaskFilterSegmentBased',
                        items: [
                            {
                                xtype: 'container',
                                itemId: 'contentContainer',
                                width: '100%',
                                layout: {
                                    type: 'hbox',
                                    columns: 3
                                },
                                defaults: {
                                    flex: 1,
                                    margin: '0 15 0 0'
                                },
                                items: [
                                    {
                                        xtype: 'fieldcontainer',
                                        bind: {
                                            fieldLabel: '{l10n.tasksGrid.filterWindow.matchRate}'
                                        },
                                        labelAlign: 'top',
                                        labelWidth:'100%',
                                        combineErrors: true,
                                        layout: 'column',
                                        defaults: {
                                            hideLabel: true,
                                            xtype: 'numberfield',
                                            columnWidth: 0.5,
                                            minValue: 0,
                                            margin: '0 0 0 0',
                                            allowBlank: true
                                        },
                                        items: [
                                            {
                                                name: 'matchRateMin',
                                                itemId:'matchRateMin',
                                                fieldLabel: Editor.data.l10n.tasksGrid.filterWindow.min,
                                                margin: '0 5 0 0',
                                                emptyText: Editor.data.l10n.tasksGrid.filterWindow.min,
                                                filter:{
                                                    operator: 'gteq',
                                                    property: 'matchRateMin',
                                                    type: 'number'
                                                }
                                            },
                                            {
                                                name: 'matchRateMax',
                                                itemId:'matchRateMax',
                                                fieldLabel: Editor.data.l10n.tasksGrid.filterWindow.max,
                                                emptyText: Editor.data.l10n.tasksGrid.filterWindow.max,
                                                filter:{
                                                    operator: 'lteq',
                                                    property: 'matchRateMax',
                                                    type: 'number'
                                                }
                                            }
                                        ]
                                    },
                                    {
                                        xtype: 'tagfield',
                                        name:'langResourceType',
                                        itemId:'langResourceType',
                                        typeAhead: true,
                                        queryMode: 'local',
                                        displayField: 'id',
                                        valueField: 'id',
                                        bind: {
                                            fieldLabel: '{l10n.tasksGrid.filterWindow.resourceType}',
                                        },
                                        labelAlign:'top',
                                        labelWidth:'100%',
                                        listeners: {
                                            beforerender: function (cmp) {
                                                const resTypes = [], store = new Ext.data.Store();
                                                ['MT', 'TM', 'TermCollection'].forEach((key) => resTypes.push({id: key}));
                                                store.loadData(resTypes, false);
                                                me.down('#langResourceType').setStore(store);
                                            }
                                        },
                                        filter:{
                                            operator: 'in',
                                            property: 'langResourceType',
                                            type:'list',
                                            textLabel: Editor.data.l10n.tasksGrid.filterWindow.resourceType
                                        }
                                    }
                                ].concat(Editor.app.authenticatedUser.isAllowed('languageResourcesOverview') ? [{
                                    xtype: 'tagfield',
                                    name:'langResource',
                                    itemId:'langResource',
                                    typeAhead: true,
                                    queryMode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    margin: Editor.plugins.OpenAI ? '0 15 0 0' : 0,
                                    bind: {
                                        fieldLabel: '{l10n.tasksGrid.filterWindow.langResources}'
                                    },
                                    labelAlign:'top',
                                    labelWidth:'100%',
                                    store:'languageResourceStore',
                                    filter:{
                                        operator: 'in',
                                        property: 'langResource',
                                        type:'list',
                                        textLabel: Editor.data.l10n.tasksGrid.filterWindow.langResources,
                                    }
                                }] : [])
                            }
                        ]
                    }
                ],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    align: 'left',
                    layout: {
                        type: 'vbox',
                        align: 'left'
                    },
                    items: [{
                        xtype: 'tbfill'
                    }, {
                        xtype: 'container',
                        padding: '10',
                        bind: {
                            html: '{l10n.tasksGrid.filterWindow.gridFiltersInfo}'
                        }
                    }, {
                        xtype: 'container',
                        padding: '10',
                        bind: {
                            html: '¹ {l10n.tasksGrid.filterWindow.anonymizedUsersInfo}'
                        }
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Load the selected fields data
     */
    loadRecord: function (record) {
        var me = this,
            field = null;
        Ext.each(record, function (rec) {
            field = me.down('#' + rec.get('property'));
            field && field.setValue(rec.get('value'), rec);
        });
    }
});
