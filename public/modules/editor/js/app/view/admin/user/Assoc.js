
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
        deadlineDateInfoTooltip:'#UT#Wert setzt selbe Konfiguration, die auch die "Überschreibung der Systemkonfiguration" mit Namen "Default deadline date" setzt.',
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
                        store:Ext.StoreManager.get('admin.UserAssocDefault'),
                        bind:{
                            selection:'{selectedAssocRecord}'
                        },
                        region: 'center'
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
                            reference: 'assocForm',
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
                            items:[{
                                xtype: 'languagecombo',
                                name: 'sourceLang',
                                itemId: 'sourceLang',
                                allowBlank: false
                            },{
                                xtype: 'languagecombo',
                                name: 'targetLang',
                                itemId: 'targetLang',
                                reference: 'targetLangUserAssoc',
                                publishes: 'value',
                                allowBlank: false
                            },{
                                anchor: '100%',
                                xtype: 'combo',
                                editable: false,
                                forceSelection: true,
                                queryMode: 'local',
                                itemId: 'workflowStepName',
                                name: 'workflowStepName',
                                fieldLabel: me.strings.fieldWorkflowStepName,
                                valueField: 'id',
                                displayField: 'text',
                                allowBlank: false,
                                bind: {
                                    store:'{workflowSteps}'
                                }
                            },{
                                anchor: '100%',
                                xtype: 'combo',
                                allowBlank: false,
                                listConfig: {
                                    loadMask: false
                                },
                                bind: {
                                    store: '{users}'
                                },
                                forceSelection: true,
                                anyMatch: true,
                                queryMode: 'local',
                                name: 'userGuid',
                                itemId: 'userGuid',
                                displayField: 'longUserName',
                                valueField: 'userGuid',
                                fieldLabel: me.strings.fieldUser
                            },{
                                xtype:'numberfield',
                                itemId: 'deadlineDate',
                                name:'deadlineDate',
                                decimalPrecision:4,
                                minValue:0.1,
                                fieldLabel: me.strings.fieldDeadline,
                                labelCls: 'labelInfoIcon',
                                cls:'userAssocLabelIconField',
                                autoEl: {
                                    tag: 'span',
                                    'data-qtip': me.strings.deadlineDateInfoTooltip
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

    /***
     * set the current customer. This is used for binding
     * @param newCustomer
     */
    setCustomer:function (newCustomer){
        var me = this;
        me.customer = newCustomer;
        me.setDefaultWorkflow();
        me.loadAssocData();
    },

    getCustomer:function (){
        return this.customer;
    },

    /***
     * Update the assoc grid filters with current selected customer and workflow
     */
    loadAssocData : function (){
        var me=this,
            workflowCombo = me.down('#workflowCombo'),
            customerId = me.getCustomer() ? me.getCustomer().get('id') : false,
            currentWorkflow = !Ext.isEmpty(workflowCombo.getValue()) ? workflowCombo.getValue() : false;

        if(customerId && currentWorkflow){
            Ext.StoreManager.get('admin.UserAssocDefault').addFilter([{
                property: 'customerId',
                operator:"eq",
                value:customerId
            },{
                property: 'workflow',
                operator:"eq",
                value:currentWorkflow
            }]);
        }
    },

    /***
     * Set the default workflow in the workflow combo. If there is not no value defined
     * in the config, the "default" workflow will be set as default
     */
    setDefaultWorkflow:function (){
        var me=this,
            workflowCombo = me.down('#workflowCombo'),
            newValue = Ext.getStore('admin.CustomerConfig').getConfig('workflow.initialWorkflow');

        if(!newValue){
            newValue = Editor.data.app.workflow.CONST.DEFAULT_WORKFLOW;
        }
        workflowCombo.setValue(newValue);
    }
});