
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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.user.Assoc', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.adminUserAssoc',
    layout:'border',
    requires: [
        'Editor.view.admin.user.AssocViewController',
        'Editor.view.admin.user.AssocViewModel',
        'Editor.view.admin.user.AssocGrid',
        'Editor.view.LanguageCombo'
    ],

    itemId: 'adminUserAssoc',
    controller: 'adminUserAssoc',
    viewModel: {
        type: 'adminUserAssoc'
    },

    glyph: 'xf0c0@FontAwesome5FreeSolid',
    title: '#UT#Standard-Benutzerzuweisungen',
    strings: {
        fieldWorkflowStepName: '#UT#Workflow-Schritt',
        fieldWorkflow: '#UT#Workflow',
        fieldState: '#UT#Status',
        fieldUser: '#UT#Benutzer',
        btnSave: '#UT#Speichern',
        btnCancel: '#UT#Abbrechen',
        formTitleAdd: '#UT#Benutzerzuweisung hinzufügen:',
        formTitleEdit: '#UT#Bearbeite Benutzer "{0}"',
        fieldDeadline:'#UT#Deadline',
        fieldSegmentrange: '#UT#Editierbare Segmente',
        fieldSegmentrangeInfo: '#UT#Bsp: 1-3,5,8-9 (Wenn die Rolle dieses Users das Editieren erlaubt und zu irgendeinem User dieser Rolle editierbare Segmente zugewiesen werden, dürfen auch alle anderen User dieser Rolle nur die Segmente editieren, die ihnen zugewiesen sind.)',
        deadlineDateInfoTooltip:'#UT#translate5 sendet standardmäßig 2 Tage vor und 2 Tage nach dem festgelegten Datum und der festgelegten Uhrzeit (+/- 10 Minuten) eine Fristerinnerung. Dies kann von Ihrem Administrator geändert werden.',
        wizardTitle:'#UT#Standard-Benutzerzuweisungen'
    },

    /***
     */
    customer:null,

    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                items: [{
                        xtype: 'adminUserAssocGrid',
                        itemId: 'adminUserAssocGrid',
                        bind:{
                            store:'{userAssoc}',
                            selection:'{selectedAssocRecord}'
                        },
                        region: 'center'
                    },{
                        xtype: 'container',
                        region: 'south',
                        height: 'auto',
                        itemId: 'editInfoOverlay',
                        cls: 'edit-info-overlay',
                        padding: 10,
                        bind: {
                            html: '{editInfoHtml}'
                        }
                    },{
                        xtype: 'container',
                        region: 'east',
                        autoScroll: true,
                        height: 'auto',
                        width: 300,
                        items: [{
                            xtype:'form',
                            title : me.strings.formTitleAdd,
                            bodyPadding: 10,
                            region: 'east',
                            defaults: {
                                labelAlign: 'top',
                                duplicateRecord: false,
                                validator: function(){
                                    if(this.duplicateRecord){
                                        return 'This record entry already exist.';
                                    }
                                    return true;
                                }
                            },
                            disabled:true,
                            bind: {
                                disabled: '{!selectedAssocRecord}'
                            },
                            items:[{
                                anchor: '100%',
                                xtype: 'combo',
                                editable: false,
                                forceSelection: true,
                                queryMode: 'local',
                                dataIndex: 'workflow',
                                itemId: 'workflow',
                                name: 'workflow',
                                fieldLabel: me.strings.fieldWorkflow,
                                valueField: 'id',
                                displayField: 'label',
                                allowBlank: false,
                                bind: {
                                    store:'{workflow}',
                                    value:'{selectedAssocRecord.workflow}'
                                }
                            },{
                                xtype: 'languagecombo',
                                viewModel:'adminUserAssoc',
                                dataIndex: 'sourceLang',
                                name: 'sourceLang',
                                itemId: 'sourceLang',
                                allowBlank: false,
                                bind:{
                                    value:'{selectedAssocRecord.sourceLang}'
                                }
                            },{
                                xtype: 'languagecombo',
                                dataIndex: 'targetLang',
                                name: 'targetLang',
                                itemId: 'targetLang',
                                viewModel:'adminUserAssoc',
                                allowBlank: false,
                                bind:{
                                    value:'{selectedAssocRecord.targetLang}'
                                }
                            },{
                                anchor: '100%',
                                xtype: 'combo',
                                editable: false,
                                forceSelection: true,
                                queryMode: 'local',
                                dataIndex: 'workflowStepName',
                                itemId: 'workflowStepName',
                                name: 'workflowStepName',
                                fieldLabel: me.strings.fieldWorkflowStepName,
                                valueField: 'id',
                                displayField: 'text',
                                allowBlank: false,
                                bind: {
                                    store:'{workflowSteps}',
                                    value:'{selectedAssocRecord.workflowStepName}'
                                }
                            },{
                                anchor: '100%',
                                xtype: 'combo',
                                allowBlank: false,
                                listConfig: {
                                    loadMask: false
                                },
                                bind: {
                                    store: '{users}',
                                    value:'{selectedAssocRecord.userGuid}'
                                },
                                forceSelection: true,
                                anyMatch: true,
                                queryMode: 'local',
                                dataIndex: 'userGuid',
                                name: 'userGuid',
                                itemId: 'userGuid',
                                displayField: 'longUserName',
                                valueField: 'userGuid',
                                fieldLabel: me.strings.fieldUser
                            },{
                                xtype:'numberfield',
                                dataIndex: 'deadlineDate',
                                itemId: 'deadlineDate',
                                decimalPrecision:4,
                                fieldLabel: me.strings.fieldDeadline,
                                labelCls: 'labelInfoIcon',
                                cls:'userAssocLabelIconField',
                                bind: {
                                    value:'{selectedAssocRecord.deadlineDate}'
                                },
                                autoEl: {
                                    tag: 'span',
                                    'data-qtip': me.strings.deadlineDateInfoTooltip
                                },
                                anchor: '100%'
                            },{
                                xtype: 'textfield',
                                itemId: 'segmentrange',
                                dataIndex: 'segmentrange',
                                name: 'segmentrange',
                                bind: {
                                    value:'{selectedAssocRecord.segmentrange}'
                                },
                                fieldLabel: me.strings.fieldSegmentrange,
                                labelCls: 'labelInfoIcon',
                                cls:'userAssocLabelIconField',
                                autoEl: {
                                    tag: 'span',
                                    'data-qtip': me.strings.fieldSegmentrangeInfo
                                },
                                anchor: '100%'
                            }],
                            dockedItems: [{
                                xtype: 'toolbar',
                                dock: 'bottom',
                                ui: 'footer',
                                items: [{
                                    xtype: 'tbfill'
                                },{
                                    xtype: 'button',
                                    itemId: 'saveAssocBtn',
                                    glyph: 'f00c@FontAwesome5FreeSolid',
                                    formBind:true,
                                    text: me.strings.btnSave
                                },{
                                    xtype: 'button',
                                    glyph: 'f00d@FontAwesome5FreeSolid',
                                    itemId: 'cancelAssocBtn',
                                    text: me.strings.btnCancel
                                }]
                            }]
                        }]
                    }]
            };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    setCustomer:function (newCustomer){
        this.getViewModel().set('selectedCustomer',newCustomer);
    },

    /**
     * loads all or all available users into the dropdown, the store is reused to get the username to userguids
     * @param {Boolean} edit true if edit an assoc, false if add a new one
     */
    loadUsers: function() {
        var me = this,
            user = me.down('combo[name="userGuid"]'),
            store = user.store;
        store.clearFilter(true);
        store.load();
    },
    /**
     * loads the given record into the userAssoc form
     * @param {Editor.data.model.admin.TaskUserAssoc} rec
     */
    loadRecord: function(rec) {
        var me = this,
            edit = !rec.phantom,
            form = me.down('form'),
            user = me.down('combo[name="userGuid"]');
        form.loadRecord(rec);
        if(edit) {
            form.setTitle(Ext.String.format(me.strings.formTitleEdit, rec.get('longUserName')));
        }
        else {
            me.loadUsers(edit);
            form.setTitle(me.strings.formTitleAdd);
        }
        user.setVisible(!edit);
        user.setDisabled(edit);
    }
});