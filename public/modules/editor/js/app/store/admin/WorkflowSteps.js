
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

Ext.define('Editor.store.admin.WorkflowSteps', {
    extend: 'Ext.data.Store',
    alias: 'store.workflowsteps',

    useAssignableSteps:false, // if set to true only the assignable steps will be listed

    initConfig: function (instanceConfig) {
        var me = this,
            config = {};

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        var returnConfig = me.callParent([config]);

        Ext.Object.each(Editor.data.app.workflows, function (key, workflow) {
            me.addWorkflow(workflow);
        });

        return returnConfig;
    },

    /***
     * Add workflow steps
     * @param w
     */
    addWorkflow:function (w){
        var me=this,
            steps = me.useAssignableSteps ? w.assignableSteps : w.steps,
            added = [];

        Ext.Array.each(w.stepChain, function (key) {
            if(steps[key]){
                me.add({id: key, text: steps[key]});
                added.push(key);
            }
        });

        Ext.Object.each(steps,function(key,value){
            if(!Ext.Array.contains(added,key)){
                me.add({id: key, text: value});                
            }
        });
    },

    /***
     * Load all steps of given workflow
     * @param workflow
     */
    loadForWorkflow: function (workflow){
        var me=this;
        me.removeAll();
        if(!Editor.data.app.workflows.hasOwnProperty(workflow)){
            return;
        }
        me.addWorkflow(Editor.data.app.workflows[workflow]);
    }
});
