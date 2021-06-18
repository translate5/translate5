
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

Ext.define('Editor.view.admin.task.UserAssocWizardViewController', {
    extend: 'Editor.view.admin.user.AssocViewController',
    alias: 'controller.adminTaskUserAssocWizard',

    listen:{
        component:{
            '#targetLang':{
                select:'onTargetlangSelect'
            }
        }
    },

    /***
     */
    onUserAssocWizardActivate:function(){
        var me=this,
            view = me.getView(),
            workflowCombo = view.down('#workflowCombo'),
            store=view.down('grid').getStore();

        store.setExtraParams({
            projectId:view.task.get('projectId')
        });
        store.load();

        workflowCombo.setValue(view.task.get('workflow'));
    },

    onAddAssocBtnClick : function(){
        var me=this,
            newRecord,
            task = me.getView().task,
            formPanel = me.lookup('assocForm'),
            form = formPanel.getForm(),
            workflowCombo = me.getView().down('#workflowCombo');

        form.reset(true);

        newRecord = Ext.create('Editor.model.admin.TaskUserAssoc',{
            sourceLang : task.get('sourceLang'), // source language is always the same for projects or single tasks
            workflow: workflowCombo.getValue()
        });

        form.loadRecord(newRecord);

        form.findField('sourceLang').setVisible(false);

        if(!task.hasProjectTasks()){
            newRecord.set('targetLang',task.get('targetLang'));
            newRecord.set('taskGuid',task.get('taskGuid'));
            form.findField('targetLang').setVisible(false);
        }else{
            var targetLangs = [];
            for (var i=0;i<task.get('projectTasks').length;i++){
                targetLangs.push(task.get('projectTasks')[i].targetLang);
            }
            form.findField('targetLang').getStore().filter([{
                property: 'id',
                operator:"in",
                value:targetLangs
            }]);
        }

        formPanel.setDisabled(false);
    },

    onTargetlangSelect: function (combo, record){
        var me = this,
            task = me.getView().task,
            projectTasks = task.get('projectTasks'),
            formPanel = me.lookup('assocForm'),
            rec = formPanel.getRecord();

        for (var i=0;i<projectTasks.length;i++){
            if(projectTasks[i].targetLang === record.get('id')){
                rec.set('taskGuid',projectTasks[i].taskGuid);
                break;
            }
        }
    }
});