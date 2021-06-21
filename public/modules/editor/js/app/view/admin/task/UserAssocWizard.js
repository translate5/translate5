
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.admin.task.UserAssocWizard
 * @extends Ext.form.Panel
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
    task:null,
    header:false,
    title:null,

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
        fieldSegmentrange: '#UT#Editierbare Segmente',
        fieldSegmentrangeInfo: '#UT#Bsp: 1-3,5,8-9 (Wenn die Rolle dieses Users das Editieren erlaubt und zu irgendeinem User dieser Rolle editierbare Segmente zugewiesen werden, dürfen auch alle anderen User dieser Rolle nur die Segmente editieren, die ihnen zugewiesen sind.)',
        deadlineDateInfoTooltip:'#UT#translate5 sendet standardmäßig 2 Tage vor und 2 Tage nach dem festgelegten Datum und der festgelegten Uhrzeit (+/- 10 Minuten) eine Fristerinnerung. Dies kann von Ihrem Administrator geändert werden.'
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
            formPanel = me.lookup('assocForm'),
            form = formPanel.getForm();
        // bind the assoc grid to taskUserAssoc store
        me.down('adminUserAssocGrid').setBind({
            store:'{userAssocImport}'
        });

        // remove the numberfield deadline date and create it as datetime field
        formPanel.remove(form.findField('deadlineDate'));
        formPanel.insert(formPanel.items.length-1,{
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
        });

        // define the form fields bindings
        form.findField('userGuid').setBind({
            store: '{users}', // the store binding must be redefined because this will overwrite the main bind definition
            disabled:'{!targetLangUserAssoc.value}'
        });

        form.findField('workflowStepName').setBind({
            store:'{workflowSteps}',// the store binding must be redefined because this will overwrite the main bind definition
            disabled:'{!targetLangUserAssoc.value}'
        });

        form.findField('deadlineDate').setBind({
            disabled:'{!targetLangUserAssoc.value}'
        });

        form.findField('segmentrange').setBind({
            disabled:'{!targetLangUserAssoc.value}'
        });

    },

    //called when next button is clicked
    triggerNextCard:function(activeItem){
        this.fireEvent('wizardCardFinished', null);
    },
    //called when skip button is clicked
    triggerSkipCard:function(activeItem){
        this.fireEvent('wizardCardFinished', 2);
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