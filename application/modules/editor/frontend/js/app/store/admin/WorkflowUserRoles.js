
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

Ext.define('Editor.store.admin.WorkflowUserRoles', {
	extend : 'Ext.data.Store',
	alias:'store.workflowuserroles',
	initConfig: function(instanceConfig) {
		var me = this,
			config={},
			workflowUserRoles=[];
		if (instanceConfig) {
			me.self.getConfigurator().merge(me, config, instanceConfig);
		}
	    var returnConfig= me.callParent([config]);

	    //required order
	    me.add({id:'translator',label:''});
	    me.add({id:'reviewer',label:''});
	    me.add({id:'translatorCheck',label:''});
	    me.add({id:'visitor',label:''});
	    
		//Info:duplicated id values will be ignored by te store
		Ext.Object.each(Editor.data.app.workflows, function(key, workflow){
			Ext.Object.each(workflow.roles, function(key, value){
				var rec=me.getById(key);
				if(!rec){
					me.add({id:key,label:value})
				}else{
					rec.set('label',value);
				}
			});
		});
		return returnConfig;
	},
});