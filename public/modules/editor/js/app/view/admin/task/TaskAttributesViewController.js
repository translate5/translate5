
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
            'radio[name="usageMode"]': {
                change: 'onUsageModeChange'
            }
        }
    },
    
    onSaveTaskAttributesClick:function(){
        var me=this;
        me.mask();
        me.getCurrentTask().save({
            failure: function(record, operation) {
                me.unmask();
                Editor.app.getController('ServerException').handleException(operation.error.response);
            },
            success: function(record, operation) {
                me.unmask();
                Editor.MessageBox.addSuccess(me.getView().strings.successUpdate);
            },
        });
    },
    
    onReloadTaskAttributesClick:function(){
        //Reload the task
        this.getCurrentTask().load();
    },
    
    onCancelTaskAttributesClick:function(){
    	//reject the changes
    	this.getCurrentTask().reject();
    },
    
    onUsageModeChange: function(field, newVal) {
        if(field.config.inputValue == 'simultaneous' && newVal && !Editor.plugins.FrontEndMessageBus) {
            Editor.MessageBox.addError('In order to use that mode the FrontEndMessageBus plug-in must be active!');
        }
    },
    
    onEdit100PercentMatchChange:function(field, newValue, oldValue){
        Ext.MessageBox.show({
            title: 'Info',
            msg: 'Translate me',
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.WARNING
        });
    },
    
    /***
     * Add loading mask to the task attribute panel
     */
    mask:function(){
        this.getView().getEl().mask();
    },
    
    /***
     * Remove loading mask from task attribute panel
     */
    unmask:function(){
        this.getView().getEl().unmask();
    },
    
    /***
     * Get the current loaded task from the view model
     */
    getCurrentTask:function(){
    	return this.getView().lookupViewModel().get('currentTask');
    }

});