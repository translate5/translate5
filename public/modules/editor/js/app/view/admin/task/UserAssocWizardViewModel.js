
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

Ext.define('Editor.view.admin.task.UserAssocWizardViewModel', {
    extend: 'Editor.view.admin.user.AssocViewModel',
    alias: 'viewmodel.adminTaskUserAssocWizard',

    data:{
        sendPreImportOperation:false,
        formTask:null

    },

    stores: {
        users: {
            source: 'admin.Users'
        },
        workflowSteps: Ext.create('Editor.store.admin.WorkflowSteps',{ useAssignableSteps:true }),
        workflow: Ext.create('Editor.store.admin.Workflow'),
        userAssocImport:{
            model:'Editor.model.admin.TaskUserAssoc',
            remoteFilter: true,
            pageSize: false,
            autoLoad:false,
            groupField: 'targetLang',
            /***
             * Add additional params to the store proxy. The newExtra params will be merged into
             * the existing proxy extra params
             */
            setExtraParams:function(newExtra){
                var me=this,
                    existing = me.getProxy().getExtraParams(),
                    merged = Ext.Object.merge(existing, newExtra);
                me.getProxy().setExtraParams(merged);
            },
            proxy : {
                type : 'rest',
                url: Editor.data.restpath+'taskuserassoc/project',
                reader : {
                    rootProperty: 'rows',
                    type : 'json'
                },
                writer: {
                    encode: true,
                    rootProperty: 'data',
                    writeAllFields: false
                }
            }
        }
    }
});