
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
 * @class Editor.view.admin.task.TaskAttributesViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.admin.task.TaskAttributesViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.taskattributesviewcontroller',

    listen: {
        component: {
            "taskattributes": {
                show: 'loadTask'
            }
        }
    },
    
    loadTask: function() {
        var vm = this.getView().lookupViewModel();
        vm.set('disableUsageMode', Ext.getStore('admin.TaskUserAssocs').getCount() > 0);
        this.getView().loadRecord(vm.get('currentTask'));
    },
    
    onSaveTaskAttributesClick:function(){
        var me=this,
            currentTask=me.getView().lookupViewModel().get('currentTask');
 
        me.getView().updateRecord();
        //check if the model record is changed
        if(!currentTask.dirty){
            return;
        }
        me.getView().getEl().mask("Loading...");
        
        currentTask.save({
            failure: function(record, operation) {
                me.getView().getEl().unmask();
                Editor.app.getController('ServerException').handleException(operation.error.response);
            },
            success: function(record, operation) {
                me.getView().getEl().unmask();
                Editor.MessageBox.addSuccess(me.getView().strings.successUpdate);
            },
        });
    },
    
    onReloadTaskAttributesClick:function(){
        //Reload the task
        var me = this,
            task = me.getView().lookupViewModel().get('currentTask');
        task.load({
            success: function(rec) {
                Editor.app.getController('Editor.controller.admin.TaskPreferences').loadAllPreferences(rec);
                me.loadTask();
            }
        });
    },
    
    onCancelTaskAttributesClick:function(){
        this.getView().up('window').destroy();
    }

});