
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * This class is extension of user assoc component, and it is rendered in the import wizard as import-wizard step/card. The component layout is slightly changed
 * to meet the needs of loading the default user associations of the importing tasks.
 * It will enable the user to assign users to a task based on selected target language(this is only for multitask) alongside with
 * the default user assignments matched by task customer and task target language.
 *
 * @class Editor.view.admin.task.UserAssocWizard
 * @extends Editor.view.admin.user.Assoc
 */
Ext.define('Editor.view.admin.task.UserAssocWizard', {
    extend:'Editor.view.admin.user.Assoc',
    alias: 'widget.adminTaskUserAssocWizard',
    itemId:'adminTaskUserAssocWizard',
    requires: [
        'Editor.view.admin.task.UserAssoc',
        'Editor.view.admin.task.UserAssocWizardViewController'
    ],
    viewModel:{
        type:'adminTaskUserAssocWizard'
    },
    controller:'adminTaskUserAssocWizard',
    mixins:['Editor.controller.admin.IWizardCard'],

    //card type, used for card display order
    importType:'postimport',
    task:null,// this will be project if the current import is project import
    header:false,
    title:null,

    referenceHolder: true,
    
    strings:{
        wizardTitle:'#UT#Standard-Benutzerzuweisungen',
        sourceLang: '#UT#Quellsprache',
        targetLang: '#UT#Zielsprache',
        fieldWorkflowStepName: '#UT#Workflow-Schritt',
        fieldWorkflow: '#UT#Workflow',
        fieldState: '#UT#Status',
        fieldUser: '#UT#Benutzer',
        btnSave: '#UT#Speichern',
        btnCancel: '#UT#Abbrechen',
        formTitleAdd: '#UT#Benutzerzuweisung hinzufügen:',
        formTitleEdit: '#UT#Bearbeite Benutzer "{0}"',
        fieldDeadline:'#UT#Deadline',
        deadlineDateInfoTooltip:'#UT#translate5 sendet standardmäßig 2 Tage vor und 2 Tage nach dem festgelegten Datum und der festgelegten Uhrzeit (+/- 10 Minuten) eine Fristerinnerung. Dies kann von Ihrem Administrator geändert werden.',
        usageModeCoop: "#UT#Sequentielles Arbeiten",
        usageModeCompetitive: "#UT#Konkurrierende Zuweisung",
        usageModeSimultaneous: "#UT#Gleichzeitiges Arbeiten"
    },

    listeners:{
        activate:'onUserAssocWizardActivate'
    },

    initComponent:function(){
        var me=this;
        me.callParent();
        me.setCustomConfig();
    },


    setCustomConfig:function (){
        var me = this,
            grid = me.down('grid'),
            formPanel = me.lookup('assocForm'),
            form = formPanel.getForm();

        // bind the assoc grid to taskUserAssoc store
        me.down('adminUserAssocGrid').setBind({
            store:'{userAssocImport}',
            selection:'{selectedAssocRecord}'
        });

        grid.down('#notifyAssociatedUsersCheckBox').setVisible(true);

        form.findField('sourceLang').setVisible(false);
        form.findField('targetLang').setVisible(false);

        // remove the number field deadlineDate (in the default assoc numberfield is used to define deadline date offset)
        formPanel.remove(form.findField('deadlineDate'));
        // insert deadlineDate as datepicker
        formPanel.insert(formPanel.items.length,{
            xtype: 'datetimefield',
            name: 'deadlineDate',
            dataIndex: 'deadlineDate',
            format: Editor.DATE_HOUR_MINUTE_ISO_FORMAT,
            fieldLabel: me.strings.fieldDeadline,
            labelCls: 'labelInfoIcon',
            cls: 'userAssocLabelIconField',
            autoEl: {
                tag: 'span',
                'data-qtip': me.strings.deadlineDateInfoTooltip
            },
            anchor: '100%'
        });

        // define the form fields bindings
        form.findField('userGuid').setBind({
            store: '{users}' // the store binding must be redefined because this will overwrite the main bind definition
        });

        form.findField('workflowStepName').setBind({
            store:'{workflowSteps}'// the store binding must be redefined because this will overwrite the main bind definition
        });

        grid.down('#assocGridTopToolbar').insert(4,{
            xtype:'combo',
            fieldLabel: me.strings.usageModeTitle,
            name:'usageMode',
            itemId:'usageMode',
            forceSelection: true,
            store:  Ext.create('Ext.data.Store', {
                fields: ['id', 'label'],
                data : [
                    {"id":"simultaneous", "name":me.strings.usageModeSimultaneous},
                    {"id":"competitive", "name":me.strings.usageModeCompetitive},
                    {"id":"cooperative", "name":me.strings.usageModeCoop}
                ]
            }),
            queryMode: 'local',
            displayField: 'name',
            valueField: 'id'
        });
    },

    /***
     * Get default assoc form record
     * @returns {Editor.model.admin.TaskUserAssoc}
     */
    getDefaultFormRecord:function (){
        var me=this,
            task = me.getController().getFormTask(),
            record = Ext.create('Editor.model.admin.TaskUserAssoc',{
                sourceLang : task && task.get('sourceLang'), // source language is always the same for projects or single tasks
                workflow: me.down('#workflowCombo').getValue(),
                taskGuid: task && task.get('taskGuid')
            });
        return record;
    },

    /***
     * Load the assoc data based on the current projectId and workflow
     */
    loadAssocData : function (){
        this.getController().loadAssocData();
    },

    //called when next button is clicked
    triggerNextCard:function(activeItem){
        this.getController().nextCardClick();
    },

    //called when skip button is clicked
    triggerSkipCard:function(activeItem){
        this.getController().skipCardClick();
    },

    disableSkipButton:function(){
        return false;
    },

    disableContinueButton:function(){
        return false;
    },

    disableAddButton:function(){
        return true;
    },

    disableCancelButton:function(){
        return false;
    }

});