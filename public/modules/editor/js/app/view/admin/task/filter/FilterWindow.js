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
    bodyPadding: 20,
    layout: 'hbox',
    border:false,
    width:800,
    height: Editor.data.statistics.enabled ? 600 : 500,
    bodyStyle: {
        borderWidth: 0
    },

    initConfig: function (instanceConfig) {
        var me = this,
            config = {
                title: me.strings.title,
                scrollable: true,
                defaults: {
                    xtype: 'container',
                    flex: 1,
                    margin: '0 5 0 0',
                    autoSize: true
                },
                items: [{
                    items: [{
                        xtype: 'tagfield',
                        name: 'userName',
                        itemId: 'userName',
                        typeAhead: true,
                        queryMode: 'local',
                        displayField: 'longUserName',
                        valueField: 'userGuid',
                        store: 'admin.UsersList',
                        fieldLabel: me.strings.userNameLabel + '¹',
                        labelAlign: 'top',
                        labelWidth: '100%',
                        filter: {
                            operator: 'in',
                            property: 'userName',
                            type: 'list',
                            textLabel: me.strings.userNameLabel
                        }
                    }, {
                        xtype: 'tagfield',
                        name: 'workflowState',
                        itemId: 'workflowState',
                        typeAhead: true,
                        queryMode: 'local',
                        valueField: 'id',
                        displayField: 'label',
                        store: 'admin.WorkflowState',
                        fieldLabel: me.strings.workflowStateFilterLabel,
                        labelAlign: 'top',
                        labelWidth: '100%',
                        filter: {
                            operator: 'in',
                            property: 'workflowState',
                            type: 'list',
                            textLabel: me.strings.workflowStateFilterLabel
                        }
                    }, {
                        xtype: 'editorAdminTaskFilterDateFilter',
                        filterLabel: me.strings.assignmentDateText,
                        filterProperty: 'assignmentDate',
                        itemId: 'assignmentDate',
                        title: me.strings.assignmentDateText
                    }, {
                        xtype: 'fieldcontainer',
                        fieldLabel: me.strings.matchRate,
                        labelAlign: 'top',
                        labelWidth:'100%',
                        layout: 'vbox',
                        combineErrors: true,
                        hidden: !Editor.data.statistics.enabled,
                        defaults: {
                            hideLabel: true,
                            xtype: 'numberfield',
                            flex: 1,
                            minValue: 0,
                            allowBlank: true
                        },
                        items: [
                            {
                                name: 'matchRateMin',
                                itemId:'matchRateMin',
                                fieldLabel: me.strings.min,
                                margin: '0 5 0 0',
                                emptyText: me.strings.min,
                                filter:{
                                    operator: 'gteq',
                                    property: 'matchRateMin',
                                    type: 'number'
                                }
                            },
                            {
                                name: 'matchRateMax',
                                itemId:'matchRateMax',
                                fieldLabel: me.strings.max,
                                emptyText: me.strings.max,
                                filter:{
                                    operator: 'lteq',
                                    property: 'matchRateMax',
                                    type: 'number'
                                }
                            }
                        ]
                    }]
                }, {
                    items: [{
                        xtype: 'tagfield',
                        name: 'workflow',
                        itemId: 'workflow',
                        typeAhead: true,
                        queryMode: 'local',
                        valueField: 'id',
                        displayField: 'label',
                        store: 'admin.Workflow',
                        fieldLabel: me.strings.workflowLabel,
                        labelAlign: 'top',
                        labelWidth: '100%',
                        filter: {
                            operator: 'in',
                            property: 'workflow',
                            type: 'list',
                            textLabel: me.strings.workflowLabel
                        },
                        listeners: {
                            afterrender: (cmp) => cmp.fireEvent('change', cmp),
                            change: 'workflowFieldChange'
                        }
                    }, {
                        xtype: 'taggroup',
                        name: 'workflowStep',
                        itemId: 'workflowStep',
                        typeAhead: true,
                        queryMode: 'local',
                        valueField: 'id',
                        displayField: 'text',
                        fieldLabel: me.strings.workflowStepLabel,
                        afterLabelTextTpl: ' <img src="/modules/editor/images/information.png" class="info_image" data-qtip="' +
                            me.strings.workflowStepTip + '"></img>',
                        labelAlign: 'top',
                        labelWidth: ' 100%',
                        filter: {
                            operator: 'in',
                            property: 'workflowStep',
                            type: 'list',
                            textLabel: me.strings.workflowStepLabel
                        }
                    }, {
                        xtype: 'editorAdminTaskFilterDateFilter',
                        filterLabel: me.strings.finishedDateText,
                        filterProperty: 'finishedDate',
                        itemId: 'finishedDate',
                        title: me.strings.finishedDateText
                    }, {
                        xtype: 'tagfield',
                        name:'langResourceType',
                        itemId:'langResourceType',
                        typeAhead: true,
                        queryMode: 'local',
                        displayField: 'id',
                        valueField: 'id',
                        fieldLabel: me.strings.resourceType,
                        labelAlign:'top',
                        labelWidth:'100%',
                        hidden: !Editor.data.statistics.enabled,
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
                            textLabel:me.strings.resourceType
                        }
                    }]
                }, {
                    items: [{
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
                        fieldLabel: me.strings.workflowUserRoleLabel,
                        afterLabelTextTpl: ' <img src="/modules/editor/images/information.png" class="info_image" data-qtip="' +
                            me.strings.workflowUserRoleTip + '"></img>',
                        labelWidth: '100%',
                        labelAlign: 'top',
                        filter: {
                            operator: 'in',
                            property: 'workflowUserRole',
                            type: 'list',
                            textLabel: me.strings.workflowUserRoleLabel
                        },
                        listeners: {
                            beforerender: function (cmp) {
                                var store = cmp.getStore();
                                ['translator', 'reviewer', 'translatorCheck', 'visitor'].forEach(function (key) {
                                    var rec = store.getById(key);
                                    if (rec) {
                                        rec.set('label', me.strings.type + ': ' + me.strings[key + 'Step']);
                                    }
                                });
                            }
                        }
                    }, {
                        xtype: 'editorAdminTaskFilterDateFilter',
                        filterLabel: me.strings.deadlineDateText,
                        filterProperty: 'deadlineDate',
                        itemId: 'deadlineDate',
                        title: me.strings.deadlineDateText,
                        style: 'margin-top:80px'
                    }].concat(Editor.app.authenticatedUser.isAllowed('languageResourcesOverview') ? [{
                        xtype: 'tagfield',
                        name:'langResource',
                        itemId:'langResource',
                        typeAhead: true,
                        queryMode: 'local',
                        displayField: 'name',
                        valueField: 'id',
                        fieldLabel: me.strings.langResources,
                        labelAlign:'top',
                        labelWidth:'100%',
                        store:'languageResourceStore',
                        hidden: !Editor.data.statistics.enabled,
                        filter:{
                            operator: 'in',
                            property: 'langResource',
                            type:'list',
                            textLabel:me.strings.langResources,
                        }
                    }] : [])
                }],
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
                        html: me.strings.gridFiltersInfo
                    }, {
                        xtype: 'container',
                        padding: '10',
                        html: "¹ " + me.strings.anonymizedUsersInfo
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
